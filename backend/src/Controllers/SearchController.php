<?php
declare(strict_types=1);
namespace Onda\Controllers;
use Onda\{Database as DB, Http};
use Onda\Repositories\CatalogRepository as Catalog;
final class SearchController
{
    public function search(): never
    {
        $q = $_GET["q"] ?? "";
        if (!is_string($q) || strlen($q) > 160) {
            Http::fail("Busca inválida", 422);
        }
        $q = trim($q);
        [$cursor, $limit] = Http::page();
        if ($q === "") {
            Http::json(["items" => [], "next_cursor" => null]);
        }
        // Treat SQL wildcard characters as literal input; each parameter is bound separately.
        $term =
            "%" .
            str_replace(["!", "%", "_"], ["!!", "!%", "!_"], $q) .
            "%";
        $rows = DB::query(
            Catalog::TRACKS .
                " WHERE t.status='ready' AND t.id>? AND (t.id IN (SELECT id FROM tracks WHERE title LIKE ? ESCAPE '!') OR t.artist_id IN (SELECT id FROM artists WHERE name LIKE ? ESCAPE '!') OR t.album_id IN (SELECT id FROM albums WHERE title LIKE ? ESCAPE '!')) ORDER BY t.id LIMIT ?",
            [$cursor, $term, $term, $term, $limit + 1],
        )->fetchAll();
        $result = Http::pageResult($rows, $limit);
        // Na primeira página também vêm artistas e álbuns com o termo no nome.
        if ($cursor === 0) {
            $result["artists"] = DB::query("SELECT * FROM artists WHERE name LIKE ? ESCAPE '!' ORDER BY name LIMIT 6", [$term])->fetchAll();
            $result["albums"] = DB::query("SELECT b.*, a.name AS artist_name FROM albums b JOIN artists a ON a.id=b.artist_id WHERE b.title LIKE ? ESCAPE '!' OR a.name LIKE ? ESCAPE '!' ORDER BY b.title LIMIT 8", [$term, $term])->fetchAll();
        }
        Http::json($result);
    }
}
