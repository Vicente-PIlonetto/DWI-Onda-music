<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Onda\{Admin,Database as DB};
Admin::guard();
$cursor=filter_var($_GET['cursor']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]) ?: 0;
$users=DB::query('SELECT id,name,email,role,created_at FROM users WHERE id>? ORDER BY id LIMIT 21',[$cursor])->fetchAll();
$more=count($users)>20;$users=array_slice($users,0,20);adminStart('Cadastros recebidos');
?>
<p>Registros enviados pelo formulário de cadastro. Use somente dados fictícios nesta demonstração.</p>
<div class="table-wrap"><table><caption>Usuários cadastrados</caption><thead><tr><th scope="col">Nome</th><th scope="col">Email</th><th scope="col">Perfil</th><th scope="col">Data</th></tr></thead><tbody>
<?php foreach($users as $user): ?><tr><td><?= h($user['name']) ?></td><td><?= h($user['email']) ?></td><td><?= h($user['role']) ?></td><td><?= h($user['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if($more): ?><a href="/admin/users.php?cursor=<?= h(end($users)['id']) ?>">Próxima página</a><?php endif; ?>
<?php adminEnd(); ?>
