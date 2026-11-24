<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Onda\{Admin,Database as DB};
use Onda\Repositories\AdminRepository;
$user=Admin::guard(); $error=''; $form=['id'=>'','title'=>'','artist_id'=>'','year'=>date('Y')];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    foreach ($form as $key=>$value) if (is_string($_POST[$key]??null)) $form[$key]=$_POST[$key];
    try {
        Admin::csrf(); $repository=new AdminRepository();
        if (($_POST['action']??'')==='delete') $repository->delete(Admin::id($_POST['id']??null));
        elseif (($_POST['action']??'')==='save') $repository->save();
        else throw new RuntimeException('Ação inválida.');
        $_SESSION['flash']='Catálogo atualizado.';header('Location: /admin/',true,303);exit;
    } catch (RuntimeException $e) { $error=$e instanceof PDOException ? 'Não foi possível salvar os dados.' : $e->getMessage(); }
} elseif (isset($_GET['edit'])) {
    try { $form=DB::query('SELECT id,title,artist_id,year FROM albums WHERE id=?',[Admin::id($_GET['edit'])])->fetch() ?: $form; }
    catch(RuntimeException $e) { $error=$e->getMessage(); }
}
$cursor=filter_var($_GET['cursor']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]) ?: 0;
$albums=DB::query('SELECT b.*,a.name AS artist_name FROM albums b JOIN artists a ON a.id=b.artist_id WHERE b.id>? ORDER BY b.id LIMIT 21',[$cursor])->fetchAll();
$more=count($albums)>20;$albums=array_slice($albums,0,20);
$artists=DB::query('SELECT id,name FROM artists ORDER BY id')->fetchAll();
// Resumo do painel: contagens agregadas direto no banco com COUNT, numa única consulta.
$stats=DB::query("SELECT (SELECT count(*) FROM albums) AS albums,(SELECT count(*) FROM tracks) AS tracks,
    (SELECT count(*) FROM users WHERE role='user') AS users,(SELECT count(*) FROM contact_messages WHERE read_at IS NULL) AS unread")->fetch();
$flash=$_SESSION['flash']??'';unset($_SESSION['flash']);adminStart('Álbuns');
?>
<div class="admin-actions"><p>Olá, <?= h($user['name']) ?>.</p><a href="/catalogo.php">Ver catálogo público</a><form method="post" action="/admin/logout.php"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><button class="pill">Sair</button></form></div>
<dl class="stats" aria-label="Resumo do catálogo">
  <div><dt>Álbuns</dt><dd><?= h($stats['albums']) ?></dd></div>
  <div><dt>Faixas</dt><dd><?= h($stats['tracks']) ?></dd></div>
  <div><dt>Usuários</dt><dd><a href="/admin/users.php"><?= h($stats['users']) ?></a></dd></div>
  <div><dt>Mensagens não lidas</dt><dd><a href="/admin/messages.php?filtro=nao-lidas"><?= h($stats['unread']) ?></a></dd></div>
</dl>
<p class="success" role="status"><?= h($flash) ?></p><p class="error" role="alert"><?= h($error) ?></p>
<h2><?= $form['id'] ? 'Editar álbum' : 'Novo álbum' ?></h2>
<form method="post" class="admin-form" novalidate>
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($form['id']) ?>">
<label>Título<input name="title" required maxlength="160" value="<?= h($form['title']) ?>"></label>
<label>Artista<select name="artist_id" required><option value="">Selecione</option><?php foreach($artists as $artist): ?><option value="<?= h($artist['id']) ?>" <?= (string)$form['artist_id']===(string)$artist['id']?'selected':'' ?>><?= h($artist['name']) ?></option><?php endforeach; ?></select></label>
<label>Ano<input name="year" type="number" required min="1000" max="9999" value="<?= h($form['year']) ?>"></label>
<div class="admin-actions"><button class="primary">Salvar álbum</button><a href="/admin/">Limpar formulário</a></div>
</form>
<div class="table-wrap"><table><caption>Álbuns cadastrados</caption><thead><tr><th scope="col">Título</th><th scope="col">Artista</th><th scope="col">Ano</th><th scope="col">Ações</th></tr></thead><tbody>
<?php foreach($albums as $album): ?><tr><td><?= h($album['title']) ?></td><td><?= h($album['artist_name']) ?></td><td><?= h($album['year']) ?></td><td><div class="admin-actions"><a href="/admin/?edit=<?= h($album['id']) ?>">Editar</a><form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= h($album['id']) ?>"><input type="hidden" name="action" value="delete"><button class="pill">Excluir álbum vazio</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if($more): ?><a class="pill" href="/admin/?cursor=<?= h(end($albums)['id']) ?>">Próxima página</a><?php endif; ?>
<?php adminEnd(); ?>
