<?php
declare(strict_types=1);
namespace Onda\Repositories;
use Onda\Database as DB;
final class CatalogImporter
{
    public static function save(array $r, float $duration): int
    {
        $artist = DB::query(
            "INSERT INTO artists(name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",
            [$r["artista"]],
        );
        $artist = DB::connection()->lastInsertId();
        $album = DB::query(
            "INSERT INTO albums(artist_id,title,year,cover_path) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",
            [$artist, $r["album"], $r["ano"] ?: null, "placeholder.svg"],
        );
        $album = DB::connection()->lastInsertId();
        DB::query(
            "INSERT INTO tracks(album_id,artist_id,title,track_number,duration_seconds,audio_path,license,status) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),title=VALUES(title),album_id=VALUES(album_id),artist_id=VALUES(artist_id),track_number=VALUES(track_number),duration_seconds=VALUES(duration_seconds),license=VALUES(license),status=VALUES(status)",
            [
                $album,
                $artist,
                $r["titulo"],
                $r["numero_faixa"] ?: 1,
                $duration,
                $r["arquivo"],
                $r["licenca"],
                "ready",
            ],
        );
        return (int) DB::connection()->lastInsertId();
    }
    public static function rows(string $file): \Generator
    {
        $f = fopen($file, "r");
        if (!$f) {
            throw new \RuntimeException("CSV não encontrado");
        }
        try {
            $header = fgetcsv($f, 0, ",", '"', "");
            $required = [
                "arquivo",
                "titulo",
                "artista",
                "album",
                "numero_faixa",
                "ano",
                "licenca",
                "origem_url",
            ];
            if (!$header || array_diff($required, $header)) {
                throw new \RuntimeException("Cabeçalho CSV inválido");
            }
            while (($row = fgetcsv($f, 0, ",", '"', "")) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== count($header)) {
                    throw new \RuntimeException("Linha CSV inválida");
                }
                yield array_combine($header, $row);
            }
        } finally {
            fclose($f);
        }
    }
}
