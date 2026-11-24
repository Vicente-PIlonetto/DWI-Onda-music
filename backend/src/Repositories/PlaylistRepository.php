<?php
declare(strict_types=1);
namespace Onda\Repositories;
use Onda\{Database as DB, Http};
final class PlaylistRepository
{
    public function owned(int $id, int $user, bool $lock = false): array
    {
        $row = DB::query(
            "SELECT * FROM playlists WHERE id=? AND user_id=?" .
                ($lock ? " FOR UPDATE" : ""),
            [$id, $user],
        )->fetch();
        if (!$row) {
            Http::fail("Playlist não encontrada", 404);
        }
        return $row;
    }
    public function add(int $id, int $user, int $track): void
    {
        DB::connection()->beginTransaction();
        try {
            $this->owned($id, $user, true);
            if (
                !DB::query(
                    "SELECT id FROM tracks WHERE id=? AND status='ready'",
                    [$track],
                )->fetch()
            ) {
                Http::fail("Faixa não encontrada", 404);
            }
            // Serializing on the playlist row prevents duplicate positions under concurrent additions.
            DB::query(
                "INSERT INTO playlist_tracks(playlist_id,track_id,position) SELECT ?,?,COALESCE(MAX(position),0)+1 FROM playlist_tracks WHERE playlist_id=? ON DUPLICATE KEY UPDATE track_id=VALUES(track_id)",
                [$id, $track, $id],
            );
            DB::connection()->commit();
        } catch (\Throwable $e) {
            DB::connection()->rollBack();
            throw $e;
        }
    }
    // Reordena a playlist inteira; track_ids precisa conter exatamente as faixas atuais.
    public function reorder(int $id, int $user, array $tracks): void
    {
        DB::connection()->beginTransaction();
        try {
            $this->owned($id, $user, true);
            $current = DB::query("SELECT track_id FROM playlist_tracks WHERE playlist_id=?", [$id])->fetchAll(\PDO::FETCH_COLUMN);
            $a = array_map("intval", $current);
            $b = $tracks;
            sort($a);
            sort($b);
            if ($a !== $b) {
                Http::fail("Envie todas as faixas da playlist, sem repetir", 422);
            }
            // Desloca antes para não violar a chave única (playlist_id, position) no meio da troca.
            DB::query("UPDATE playlist_tracks SET position=position+1000000 WHERE playlist_id=?", [$id]);
            foreach ($tracks as $i => $track) {
                DB::query("UPDATE playlist_tracks SET position=? WHERE playlist_id=? AND track_id=?", [$i + 1, $id, $track]);
            }
            DB::connection()->commit();
        } catch (\Throwable $e) {
            DB::connection()->rollBack();
            throw $e;
        }
    }
}
