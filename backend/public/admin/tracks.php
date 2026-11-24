<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require __DIR__.'/../../vendor/autoload.php';
use Onda\{Admin,Database as DB};
use Onda\Repositories\CatalogImporter;

// Envio de faixas pelo painel: o arquivo é analisado (tags ID3 e duração), salvo com nome
// derivado do conteúdo e cadastrado com artista e álbum, como no importador de linha de comando.
const MAX_AUDIO_BYTES = 20 * 1024 * 1024;
const AUDIO_TYPES = ['mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4'];

$user=Admin::guard(); $error='';
$form=['titulo'=>'','artista'=>'','album'=>'','numero_faixa'=>'1','ano'=>'','licenca'=>''];
$storage=realpath(__DIR__.'/../../../storage/audio');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    foreach ($form as $key=>$value) if (is_string($_POST[$key]??null)) $form[$key]=trim($_POST[$key]);
    try {
        Admin::csrf();
        if (($_POST['action']??'')==='delete') {
            $id=Admin::id($_POST['id']??null);
            $track=DB::query('SELECT audio_path,album_id,artist_id FROM tracks WHERE id=?',[$id])->fetch();
            if (!$track) throw new RuntimeException('Faixa não encontrada.');
            $path=$track['audio_path'];
            DB::query('DELETE FROM tracks WHERE id=?',[$id]);
            // Álbum e artista criados só para essa faixa saem junto quando ficam vazios.
            DB::query('DELETE FROM albums WHERE id=? AND NOT EXISTS (SELECT 1 FROM tracks WHERE album_id=?)',[$track['album_id'],$track['album_id']]);
            DB::query('DELETE FROM artists WHERE id=? AND NOT EXISTS (SELECT 1 FROM albums WHERE artist_id=?) AND NOT EXISTS (SELECT 1 FROM tracks WHERE artist_id=?)',[$track['artist_id'],$track['artist_id'],$track['artist_id']]);
            // Só apaga arquivos enviados pelo painel; o resto da pasta de áudio não é do PHP.
            if (is_string($path) && str_starts_with($path,'uploads/') && $storage
                && !DB::query('SELECT 1 FROM tracks WHERE audio_path=?',[$path])->fetchColumn()) @unlink($storage.'/'.$path);
            $_SESSION['flash']='Faixa excluída.';
        } elseif (($_POST['action']??'')==='upload') {
            $upload=$_FILES['audio']??null;
            if (!is_array($upload) || ($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name']))
                throw new RuntimeException(($upload['error']??0)===UPLOAD_ERR_INI_SIZE ? 'Arquivo maior que 20 MB.' : 'Escolha um arquivo de áudio.');
            if ($upload['size']>MAX_AUDIO_BYTES) throw new RuntimeException('Arquivo maior que 20 MB.');
            $ext=strtolower(pathinfo((string)$upload['name'],PATHINFO_EXTENSION));
            if (!isset(AUDIO_TYPES[$ext])) throw new RuntimeException('Use MP3, OGG ou M4A.');
            if ($form['licenca']==='') throw new RuntimeException('Informe a licença da faixa.');
            if ($form['ano']!=='' && (!ctype_digit($form['ano']) || (int)$form['ano']<1000 || (int)$form['ano']>9999)) throw new RuntimeException('Ano inválido.');
            $info=(new getID3())->analyze($upload['tmp_name']);
            if (!empty($info['error']) || empty($info['playtime_seconds'])) throw new RuntimeException('O arquivo não parece ser um áudio válido.');
            getid3_lib::CopyTagsToComments($info);
            $tags=$info['comments']??[];
            $row=$form;
            foreach (['titulo'=>'title','artista'=>'artist','album'=>'album','ano'=>'year'] as $col=>$tag)
                if ($row[$col]==='') $row[$col]=trim((string)($tags[$tag][0]??''));
            $row['titulo']=mb_substr($row['titulo'] ?: pathinfo((string)$upload['name'],PATHINFO_FILENAME),0,255);
            $row['artista']=mb_substr($row['artista'] ?: 'Artista desconhecido',0,160);
            $row['album']=mb_substr($row['album'] ?: 'Singles',0,160);
            $row['numero_faixa']=max(1,(int)$row['numero_faixa']);
            if (!ctype_digit((string)$row['ano'])) $row['ano']='';
            $row['arquivo']='uploads/import-'.hash_file('sha256',$upload['tmp_name']).'.'.$ext;
            if (!$storage) throw new RuntimeException('Pasta de áudio indisponível.');
            $target=$storage.'/'.$row['arquivo'];
            if (!is_file($target) && !move_uploaded_file($upload['tmp_name'],$target)) throw new RuntimeException('Não foi possível salvar o arquivo.');
            chmod($target,0644);
            DB::connection()->beginTransaction();
            try { CatalogImporter::save($row,(float)$info['playtime_seconds']); DB::connection()->commit(); }
            catch (Throwable $e) { DB::connection()->rollBack(); throw $e; }
            $_SESSION['flash']='Faixa "'.$row['titulo'].'" cadastrada.';
        } else throw new RuntimeException('Ação inválida.');
        header('Location: /admin/tracks.php',true,303); exit;
    } catch (RuntimeException $e) { $error=$e instanceof PDOException ? 'Não foi possível salvar os dados.' : $e->getMessage(); }
}

$cursor=filter_var($_GET['cursor']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]) ?: 0;
$tracks=DB::query('SELECT t.id,t.title,t.duration_seconds,t.license,a.name AS artist,b.title AS album FROM tracks t
    JOIN artists a ON a.id=t.artist_id JOIN albums b ON b.id=t.album_id WHERE t.id>? ORDER BY t.id LIMIT 21',[$cursor])->fetchAll();
$more=count($tracks)>20; $tracks=array_slice($tracks,0,20);
$flash=$_SESSION['flash']??''; unset($_SESSION['flash']); adminStart('Faixas');
function duration(mixed $s): string { $s=(int)round((float)$s); return intdiv($s,60).':'.str_pad((string)($s%60),2,'0',STR_PAD_LEFT); }
?>
<p class="success" role="status"><?= h($flash) ?></p><p class="error" role="alert"><?= h($error) ?></p>
<h2>Enviar faixa</h2>
<p>MP3, OGG ou M4A de até 20 MB. Campos vazios são preenchidos pelas tags do arquivo. Envie só áudio com licença que permita o uso.</p>
<form method="post" enctype="multipart/form-data" class="admin-form">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="upload">
<input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_AUDIO_BYTES ?>">
<label>Arquivo<input type="file" name="audio" required accept=".mp3,.ogg,.m4a,audio/mpeg,audio/ogg,audio/mp4"></label>
<label>Título<input name="titulo" maxlength="255" value="<?= h($form['titulo']) ?>" placeholder="das tags"></label>
<label>Artista<input name="artista" maxlength="160" value="<?= h($form['artista']) ?>" placeholder="das tags"></label>
<label>Álbum<input name="album" maxlength="160" value="<?= h($form['album']) ?>" placeholder="Singles"></label>
<div class="admin-row">
<label>Faixa nº<input name="numero_faixa" type="number" min="1" max="999" value="<?= h($form['numero_faixa']) ?>"></label>
<label>Ano<input name="ano" type="number" min="1000" max="9999" value="<?= h($form['ano']) ?>"></label>
</div>
<label>Licença<input name="licenca" required maxlength="255" value="<?= h($form['licenca']) ?>" placeholder="ex.: CC BY 4.0, Biblioteca de Áudio do YouTube"></label>
<div class="admin-actions"><button class="primary">Enviar faixa</button></div>
</form>
<div class="table-wrap"><table><caption>Faixas cadastradas</caption><thead><tr><th scope="col">Título</th><th scope="col">Artista</th><th scope="col">Álbum</th><th scope="col">Duração</th><th scope="col">Licença</th><th scope="col">Ações</th></tr></thead><tbody>
<?php foreach($tracks as $t): ?><tr><td><?= h($t['title']) ?></td><td><?= h($t['artist']) ?></td><td><?= h($t['album']) ?></td><td><?= h(duration($t['duration_seconds'])) ?></td><td><?= h($t['license']) ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= h($t['id']) ?>"><input type="hidden" name="action" value="delete"><button class="pill">Excluir</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if($more): ?><a class="pill" href="/admin/tracks.php?cursor=<?= h(end($tracks)['id']) ?>">Próxima página</a><?php endif; ?>
<?php adminEnd(); ?>
