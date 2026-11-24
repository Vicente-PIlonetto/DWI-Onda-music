<?php
declare(strict_types=1);
namespace Onda\Repositories;
use Onda\Database as DB;
use Onda\Http;
final class CatalogRepository
{
    public const TRACKS = "SELECT t.*, a.name AS artist_name, b.title AS album_title, b.cover_path FROM tracks t JOIN artists a ON a.id=t.artist_id JOIN albums b ON b.id=t.album_id";
    public function listing(string $type, int $cursor, int $limit): array
    {
        $sql = match ($type) {
            "tracks" => self::TRACKS .
                " WHERE t.status='ready' AND t.id>? ORDER BY t.id LIMIT ?",
            "albums"
                => "SELECT b.*, a.name AS artist_name FROM albums b JOIN artists a ON a.id=b.artist_id WHERE b.id>? ORDER BY b.id LIMIT ?",
            "artists"
                => "SELECT * FROM artists WHERE id>? ORDER BY id LIMIT ?",
        };
        return DB::query($sql, [$cursor, $limit + 1])->fetchAll();
    }
    public function detail(string $type, int $id): array
    {
        $sql = match ($type) {
            "tracks" => self::TRACKS . " WHERE t.id=? AND t.status='ready'",
            "albums"
                => "SELECT b.*, a.name AS artist_name FROM albums b JOIN artists a ON a.id=b.artist_id WHERE b.id=?",
            "artists" => "SELECT * FROM artists WHERE id=?",
        };
        $row = DB::query($sql, [$id])->fetch();
        if (!$row) {
            Http::fail("Não encontrado", 404);
        }
        return $row;
    }
}
