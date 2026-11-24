<?php
declare(strict_types=1);
require __DIR__ . "/../src/bootstrap.php";
require __DIR__ . "/../vendor/autoload.php";
use Onda\Database as DB;
use Onda\Repositories\CatalogImporter;

// CSV uses the same portable schema as seed.php. Without CSV, tags provide metadata.
$options = getopt("", ["dir:", "csv:", "license:"]);
$root = realpath($options["dir"] ?? dirname(__DIR__, 2) . "/storage/audio");
$destination = realpath(dirname(__DIR__, 2) . "/storage/audio");
if (!$root || !is_dir($root) || !$destination) {
    fwrite(STDERR, "Diretório inválido\n");
    exit(1);
}
$analyzer = new getID3();
$rows = [];
try {
    if (isset($options["csv"])) {
        foreach (CatalogImporter::rows($options["csv"]) as $row) {
            $rows[] = $row;
        }
    } else {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $root,
                FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($files as $file) {
            if (
                !$file->isFile() ||
                !in_array(
                    strtolower($file->getExtension()),
                    ["mp3", "ogg", "wav", "flac", "m4a"],
                    true,
                )
            ) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $rows[] = [
                "arquivo" => $relative,
                "titulo" => "",
                "artista" => "",
                "album" => "",
                "numero_faixa" => "",
                "ano" => "",
                "licenca" => $options["license"] ?? "",
                "origem_url" => "",
            ];
        }
    }
    $count = 0;
    foreach ($rows as $row) {
        $relative = $row["arquivo"];
        if (
            $relative === "" ||
            str_contains($relative, "\0") ||
            str_contains($relative, "\\") ||
            str_starts_with($relative, "/") ||
            in_array("..", explode("/", $relative), true)
        ) {
            throw new RuntimeException("Caminho inválido no CSV");
        }
        $source = realpath($root . "/" . $relative);
        if (
            !$source ||
            !str_starts_with($source, $root . DIRECTORY_SEPARATOR) ||
            !is_file($source)
        ) {
            throw new RuntimeException("Arquivo não encontrado: " . $relative);
        }
        if (
            !in_array(
                strtolower(pathinfo($source, PATHINFO_EXTENSION)),
                ["mp3", "ogg", "wav", "flac", "m4a"],
                true,
            )
        ) {
            throw new RuntimeException("Formato não suportado: " . $relative);
        }
        $info = $analyzer->analyze($source);
        if (!empty($info["error"]) || empty($info["playtime_seconds"])) {
            throw new RuntimeException("Áudio inválido: " . $relative);
        }
        getid3_lib::CopyTagsToComments($info);
        $tags = $info["comments"] ?? [];
        foreach (
            [
                "titulo" => "title",
                "artista" => "artist",
                "album" => "album",
                "numero_faixa" => "track_number",
                "ano" => "year",
            ]
            as $column => $tag
        ) {
            if ($row[$column] === "") {
                $row[$column] = (string) ($tags[$tag][0] ?? "");
            }
        }
        $row["titulo"] = $row["titulo"] ?: pathinfo($source, PATHINFO_FILENAME);
        $row["artista"] = $row["artista"] ?: "Artista desconhecido";
        $row["album"] = $row["album"] ?: "Álbum desconhecido";
        $row["numero_faixa"] = max(
            1,
            (int) explode("/", $row["numero_faixa"])[0],
        );
        if (
            $row["ano"] !== "" &&
            (!ctype_digit((string) $row["ano"]) ||
                (int) $row["ano"] < 1000 ||
                (int) $row["ano"] > 9999)
        ) {
            throw new RuntimeException("Ano inválido: " . $relative);
        }
        if (trim($row["licenca"]) === "") {
            throw new RuntimeException(
                "Informe uma licença no CSV ou em --license: " . $relative,
            );
        }
        // External imports use a content-addressed path. Reimporting the same file cannot duplicate it.
        if ($root !== $destination) {
            $row["arquivo"] =
                "import-" .
                hash_file("sha256", $source) .
                "." .
                strtolower(pathinfo($source, PATHINFO_EXTENSION));
            $target = $destination . "/" . $row["arquivo"];
            if (!is_file($target) && !copy($source, $target)) {
                throw new RuntimeException("Falha ao copiar áudio");
            }
            chmod($target, 0644);
        }
        DB::connection()->beginTransaction();
        try {
            CatalogImporter::save($row, (float) $info["playtime_seconds"]);
            DB::connection()->commit();
        } catch (Throwable $e) {
            DB::connection()->rollBack();
            throw $e;
        }
        $count++;
    }
    echo "Importação concluída: $count faixas processadas (upsert por audio_path).\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
