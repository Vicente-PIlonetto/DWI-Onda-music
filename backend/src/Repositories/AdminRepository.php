<?php
declare(strict_types=1);
namespace Onda\Repositories;
use Onda\{Database as DB,Admin};
final class AdminRepository {
    public function save(): void {
        $title=Admin::text('title'); $artist=Admin::id($_POST['artist_id']??null);
        $year=filter_var($_POST['year']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1000,'max_range'=>9999]]);
        if ($year===false) throw new \RuntimeException('Informe um ano entre 1000 e 9999.');
        if (!DB::query('SELECT id FROM artists WHERE id=?',[$artist])->fetch()) throw new \RuntimeException('Artista não encontrado.');
        $id=($_POST['id']??'')!=='' ? Admin::id($_POST['id']) : null;
        try {
            if ($id) {
                DB::query('UPDATE albums SET title=?,artist_id=?,year=? WHERE id=?',[$title,$artist,$year,$id]);
                $result=DB::query('SELECT id FROM albums WHERE id=?',[$id])->fetchColumn();
                if (!$result) throw new \RuntimeException('Álbum não encontrado.');
            } else DB::query('INSERT INTO albums(title,artist_id,year,cover_path) VALUES (?,?,?,?)',[$title,$artist,$year,'placeholder.svg']);
        } catch (\PDOException $e) {
            if ($e->getCode()==='23000') throw new \RuntimeException('Este artista já tem um álbum com esse título.');
            throw $e;
        }
    }
    public function delete(int $id): void {
        DB::connection()->beginTransaction();
        try {
            if (!DB::query('SELECT id FROM albums WHERE id=? FOR UPDATE',[$id])->fetch()) throw new \RuntimeException('Álbum não encontrado.');
            // Evita apagar por acidente as faixas ligadas a um álbum do catálogo.
            if (DB::query('SELECT count(*) FROM tracks WHERE album_id=?',[$id])->fetchColumn()>0) throw new \RuntimeException('Só é possível excluir álbuns sem faixas.');
            DB::query('DELETE FROM albums WHERE id=?',[$id]);
            DB::connection()->commit();
        } catch (\Throwable $e) { DB::connection()->rollBack(); throw $e; }
    }
}
