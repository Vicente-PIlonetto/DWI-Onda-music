<?php
declare(strict_types=1);
namespace Onda\Controllers;
use Onda\{Database as DB, Http, Session};
use Onda\Repositories\CatalogRepository as Catalog;
// Histórico de reprodução por usuário ("Tocadas recentemente").
final class HistoryController
{
    private const KEEP = 500; // entradas mantidas por usuário
    public function record(): never
    {
        $user = Session::user();
        Session::csrf();
        $track = Http::positive(Http::body()["track_id"] ?? null);
        (new Catalog())->detail("tracks", $track);
        self::add((int) $user["id"], $track);
        http_response_code(204);
        exit();
    }
    /** Registra uma reprodução (também usado pelo scrobble da API Subsonic). */
    public static function add(int $user, int $track): void
    {
        // Repetir a mesma faixa em sequência (ex.: repetir uma) não gera várias linhas.
        $last = DB::query("SELECT track_id, played_at > NOW() - INTERVAL 30 SECOND AS recent FROM play_history WHERE user_id=? ORDER BY id DESC LIMIT 1", [$user])->fetch();
        if (!$last || (int) $last["track_id"] !== $track || !$last["recent"]) {
            DB::query("INSERT INTO play_history(user_id,track_id) VALUES (?,?)", [$user, $track]);
            DB::query(
                "DELETE FROM play_history WHERE user_id=? AND id < (SELECT id FROM (SELECT id FROM play_history WHERE user_id=? ORDER BY id DESC LIMIT 1 OFFSET " . (self::KEEP - 1) . ") k)",
                [$user, $user],
            );
        }
    }
    public function recent(): never
    {
        $user = Session::user();
        $limit = $_GET["limit"] ?? "20";
        if (!is_string($limit) || !ctype_digit($limit) || (int) $limit < 1 || (int) $limit > 50) {
            Http::fail("Limite deve ser de 1 a 50", 422);
        }
        // Uma linha por faixa, na ordem da reprodução mais recente.
        $rows = DB::query(
            "SELECT q.*, h.last_played FROM (SELECT track_id, MAX(played_at) AS last_played, MAX(id) AS last_id FROM play_history WHERE user_id=? GROUP BY track_id ORDER BY last_id DESC LIMIT ?) h JOIN (" .
                Catalog::TRACKS . " WHERE t.status='ready') q ON q.id=h.track_id ORDER BY h.last_id DESC",
            [$user["id"], (int) $limit],
        )->fetchAll();
        Http::json(["items" => $rows]);
    }
    public function clear(): never
    {
        $user = Session::user();
        Session::csrf();
        DB::query("DELETE FROM play_history WHERE user_id=?", [$user["id"]]);
        http_response_code(204);
        exit();
    }
}
