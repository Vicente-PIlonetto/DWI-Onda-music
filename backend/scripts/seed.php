<?php
declare(strict_types=1);
require __DIR__ . "/../src/bootstrap.php";
use Onda\Database as DB;
use Onda\Repositories\CatalogImporter;
$storage = dirname(__DIR__, 2) . "/storage";
foreach (["audio", "covers"] as $dir) {
    if (!is_dir("$storage/$dir")) {
        mkdir("$storage/$dir", 0755, true);
    }
}
$svg =
    '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600"><defs><linearGradient id="g" x2="1" y2="1"><stop stop-color="#1DB954"/><stop offset="1" stop-color="#16332a"/></linearGradient></defs><rect width="600" height="600" fill="url(#g)"/><g fill="none" stroke="#d6ffb8" stroke-width="2" opacity=".6">';
for ($i = 0; $i < 14; $i++) {
    $svg .= '<circle cx="300" cy="300" r="' . (30 + $i * 18) . '"/>';
}
$svg .= '</g><circle cx="300" cy="300" r="15" fill="#d6ffb8"/></svg>';
if (!is_file("$storage/covers/placeholder.svg")) {
    file_put_contents("$storage/covers/placeholder.svg", $svg);
}
DB::query("SELECT GET_LOCK(?, 30)", ["onda_seed"]);
try {
    foreach (
        CatalogImporter::rows(dirname(__DIR__, 2) . "/data/catalog.csv")
        as $i => $row
    ) {
        if (!preg_match('/^tone-[0-9]{2}\.mp3$/', $row["arquivo"])) {
            throw new RuntimeException(
                "Seed aceita apenas tons de demonstração",
            );
        }
        $file = "$storage/audio/" . $row["arquivo"];
        if (!is_file($file)) {
            $cmd =
                "ffmpeg -nostdin -v error -f lavfi -i " .
                escapeshellarg(
                    "sine=frequency=" . (220 + $i * 15) . ":duration=12",
                ) .
                " -af volume=0.15 -q:a 7 " .
                escapeshellarg($file);
            exec($cmd, $output, $code);
            if ($code !== 0) {
                throw new RuntimeException("Falha ao gerar áudio");
            }
        }
        CatalogImporter::save($row, 12);
    }
    foreach (["ADMIN" => "admin", "USER" => "user"] as $key => $role) {
        $email = getenv("SEED_" . $key . "_EMAIL");
        $password = getenv("SEED_" . $key . "_PASSWORD");
        if (!$email || strlen($password ?: "") < 12) {
            throw new RuntimeException(
                "Defina emails e senhas de seed (mínimo 12 caracteres) no .env",
            );
        }
        DB::query(
            "INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE email=email",
            [
                $role === "admin" ? "Administrador" : "Estudante",
                strtolower($email),
                password_hash($password, PASSWORD_ARGON2ID),
                $role,
            ],
        );
    }
    echo "Seed pronto: catálogo e usuários preservados em execuções repetidas.\n";
} finally {
    DB::query("SELECT RELEASE_LOCK(?)", ["onda_seed"]);
}
