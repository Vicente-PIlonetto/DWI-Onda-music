<?php
declare(strict_types=1);
namespace Onda\Controllers;
use Onda\{Database as DB, Http, Session};
use Onda\Repositories\CatalogRepository as Catalog;
final class CatalogController
{
    public function listing(string $type): never
    {
        [$cursor, $limit] = Http::page();
        Http::json(
            Http::pageResult(
                (new Catalog())->listing($type, $cursor, $limit),
                $limit,
            ),
        );
    }
    public function detail(string $type, string $id): never
    {
        $row = (new Catalog())->detail($type, Http::positive($id));
        [$cursor, $limit] = Http::page();
        if ($type === "albums") {
            $row["tracks"] = Http::pageResult(
                DB::query(
                    Catalog::TRACKS .
                        " WHERE t.album_id=? AND t.id>? AND t.status='ready' ORDER BY t.id LIMIT ?",
                    [$id, $cursor, $limit + 1],
                )->fetchAll(),
                $limit,
            );
        }
        if ($type === "artists") {
            $row["albums"] = Http::pageResult(
                DB::query(
                    "SELECT * FROM albums WHERE artist_id=? AND id>? ORDER BY id LIMIT ?",
                    [$id, $cursor, $limit + 1],
                )->fetchAll(),
                $limit,
            );
        }
        Http::json($row);
    }
    public function stream(string $id): never
    {
        Session::user();
        $track = (new Catalog())->detail("tracks", Http::positive($id));
        $root = realpath(dirname(__DIR__, 3) . "/storage/audio");
        $file = $root ? realpath($root . "/" . $track["audio_path"]) : false;
        if (
            !$file ||
            !str_starts_with($file, $root . DIRECTORY_SEPARATOR) ||
            !is_file($file)
        ) {
            Http::fail("Áudio indisponível", 404);
        }
        session_write_close(); // Release the session lock before Nginx streams or seeks.
        header(
            "Content-Type: " .
                match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                    "mp3" => "audio/mpeg",
                    "ogg" => "audio/ogg",
                    "wav" => "audio/wav",
                    "flac" => "audio/flac",
                    "m4a" => "audio/mp4",
                    default => "application/octet-stream",
                },
        );
        header("Cache-Control: private, no-store");
        header(
            "X-Accel-Redirect: /_audio/" .
                implode(
                    "/",
                    array_map(
                        "rawurlencode",
                        explode("/", $track["audio_path"]),
                    ),
                ),
        );
        exit();
    }
}
