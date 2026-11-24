<?php
declare(strict_types=1);
namespace Onda\Controllers;
use Onda\{Database as DB, Http, Session};
use Onda\Repositories\{CatalogRepository as Catalog, PlaylistRepository};
final class LibraryController
{
    public function lists(): never
    {
        $user = Session::user();
        [$cursor, $limit] = Http::page();
        Http::json(
            Http::pageResult(
                DB::query(
                    "SELECT * FROM playlists WHERE user_id=? AND id>? ORDER BY id LIMIT ?",
                    [$user["id"], $cursor, $limit + 1],
                )->fetchAll(),
                $limit,
            ),
        );
    }
    public function create(): never
    {
        $user = Session::user();
        Session::csrf();
        $name = Http::text(Http::body(), "name");
        DB::query("INSERT INTO playlists(user_id,name) VALUES (?,?)", [$user["id"], $name]);
        Http::json($this->playlistRow((int) DB::connection()->lastInsertId(), (int) $user["id"]), 201);
    }
    public function detail(string $id): never
    {
        $user = Session::user();
        $id = Http::positive($id);
        $row = (new PlaylistRepository())->owned($id, $user["id"]);
        [$cursor, $limit] = Http::page(); // Position is the keyset cursor, so playlist order remains stable after removing a track.
        $rows = DB::query(
            "SELECT q.*,pt.position FROM (" .
                Catalog::TRACKS .
                ") q JOIN playlist_tracks pt ON pt.track_id=q.id WHERE pt.playlist_id=? AND pt.position>? ORDER BY pt.position LIMIT ?",
            [$id, $cursor, $limit + 1],
        )->fetchAll();
        $row["tracks"] = Http::pageResult($rows, $limit, "position");
        Http::json($row);
    }
    public function update(string $id): never
    {
        $user = Session::user();
        Session::csrf();
        $id = Http::positive($id);
        (new PlaylistRepository())->owned($id, $user["id"]);
        $name = Http::text(Http::body(), "name");
        DB::query("UPDATE playlists SET name=? WHERE id=? AND user_id=?", [$name, $id, $user["id"]]);
        Http::json($this->playlistRow($id, (int) $user["id"]));
    }
    private function playlistRow(int $id, int $user): array
    {
        return DB::query("SELECT * FROM playlists WHERE id=? AND user_id=?", [$id, $user])->fetch();
    }
    public function setCover(string $id): never
    {
        $user = Session::user();
        Session::csrf();
        $id = Http::positive($id);
        $playlist = (new PlaylistRepository())->owned($id, $user["id"]);
        $upload = $_FILES["cover"] ?? null;
        if (
            !is_array($upload) ||
            ($upload["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            ($upload["size"] ?? 0) < 1 ||
            ($upload["size"] ?? PHP_INT_MAX) > 3 * 1024 * 1024 ||
            !is_uploaded_file($upload["tmp_name"] ?? "")
        ) {
            Http::fail("Escolha uma imagem de até 3 MB", 422);
        }
        $image = @getimagesize($upload["tmp_name"]);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload["tmp_name"]);
        $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        if (
            !$image ||
            !isset($extensions[$mime]) ||
            $image["mime"] !== $mime ||
            $image[0] > 5000 ||
            $image[1] > 5000
        ) {
            Http::fail("Use uma imagem JPEG, PNG ou WebP de até 5000 × 5000 px", 422);
        }
        $name = "playlist-" . $id . "-" . bin2hex(random_bytes(12)) . "." . $extensions[$mime];
        $root = dirname(__DIR__, 3) . "/storage/covers";
        if (!is_dir($root) && !mkdir($root, 0775, true) && !is_dir($root)) {
            Http::fail("Não foi possível preparar o armazenamento de capas", 500);
        }
        $target = $root . "/" . $name;
        if (!move_uploaded_file($upload["tmp_name"], $target)) {
            Http::fail("Não foi possível salvar a capa", 500);
        }
        try {
            DB::query("UPDATE playlists SET cover_path=? WHERE id=? AND user_id=?", [$name, $id, $user["id"]]);
        } catch (\Throwable $error) {
            @unlink($target);
            throw $error;
        }
        $previous = $playlist["cover_path"] ?? "";
        if (preg_match('/^playlist-' . $id . '-[a-f0-9]{24}\\.(?:jpg|png|webp)$/D', $previous)) {
            @unlink($root . "/" . $previous);
        }
        Http::json(["cover_path" => $name]);
    }
    public function removeCover(string $id): never
    {
        $user = Session::user();
        Session::csrf();
        $id = Http::positive($id);
        $playlist = (new PlaylistRepository())->owned($id, $user["id"]);
        DB::query("UPDATE playlists SET cover_path=NULL WHERE id=? AND user_id=?", [$id, $user["id"]]);
        $name = $playlist["cover_path"] ?? "";
        if (preg_match('/^playlist-' . $id . '-[a-f0-9]{24}\\.(?:jpg|png|webp)$/D', $name)) {
            @unlink(dirname(__DIR__, 3) . "/storage/covers/" . $name);
        }
        http_response_code(204);
        exit();
    }
    public function delete(string $id): never
    {
        $user = Session::user();
        Session::csrf();
        $id = Http::positive($id);
        $playlist = (new PlaylistRepository())->owned($id, $user["id"]);
        DB::query("DELETE FROM playlists WHERE id=? AND user_id=?", [
            $id,
            $user["id"],
        ]);
        $name = $playlist["cover_path"] ?? "";
        if (preg_match('/^playlist-' . $id . '-[a-f0-9]{24}\\.(?:jpg|png|webp)$/D', $name)) {
            @unlink(dirname(__DIR__, 3) . "/storage/covers/" . $name);
        }
        http_response_code(204);
        exit();
    }
    public function tracks(string $id, bool $add): never
    {
        $user = Session::user();
        Session::csrf();
        $id = Http::positive($id);
        $track = Http::positive(Http::body()["track_id"] ?? null);
        $repo = new PlaylistRepository();
        if ($add) {
            $repo->add($id, $user["id"], $track);
        } else {
            $repo->owned($id, $user["id"]);
            DB::query(
                "DELETE FROM playlist_tracks WHERE playlist_id=? AND track_id=?",
                [$id, $track],
            );
        }
        http_response_code(204);
        exit();
    }
    public function reorder(string $id): never
    {
        $user = Session::user();
        Session::csrf();
        $ids = Http::body()["track_ids"] ?? null;
        if (!is_array($ids) || !array_is_list($ids) || count($ids) > 5000) {
            Http::fail("Envie track_ids como lista", 422);
        }
        (new PlaylistRepository())->reorder(Http::positive($id), (int) $user["id"], array_map([Http::class, "positive"], $ids));
        http_response_code(204);
        exit();
    }
    public function likes(): never
    {
        $user = Session::user();
        [$cursor, $limit] = Http::page();
        Http::json(
            Http::pageResult(
                DB::query(
                    Catalog::TRACKS .
                        " JOIN liked_tracks l ON l.track_id=t.id WHERE l.user_id=? AND t.id>? ORDER BY t.id LIMIT ?",
                    [$user["id"], $cursor, $limit + 1],
                )->fetchAll(),
                $limit,
            ),
        );
    }
    public function like(string $id, bool $add): never
    {
        $user = Session::user();
        Session::csrf();
        $id = Http::positive($id);
        (new Catalog())->detail("tracks", $id);
        if ($add) {
            DB::query(
                "INSERT INTO liked_tracks(user_id,track_id) VALUES (?,?) ON DUPLICATE KEY UPDATE track_id=VALUES(track_id)",
                [$user["id"], $id],
            );
        } else {
            DB::query(
                "DELETE FROM liked_tracks WHERE user_id=? AND track_id=?",
                [$user["id"], $id],
            );
        }
        http_response_code(204);
        exit();
    }
}
