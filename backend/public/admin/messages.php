<?php
// Painel: lista as mensagens do formulário de contato, marca como lida e exclui.
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use Onda\Admin;
use Onda\Repositories\ContactRepository;

Admin::guard();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Admin::csrf();
        $id = Admin::id($_POST['id'] ?? null);
        match ($_POST['action'] ?? '') {
            'toggle' => ContactRepository::toggleRead($id),
            'delete' => ContactRepository::delete($id),
            default => throw new RuntimeException('Ação inválida.'),
        };
        $_SESSION['flash'] = ($_POST['action'] === 'delete') ? 'Mensagem excluída.' : 'Mensagem atualizada.';
        $back = '/admin/messages.php' . (($_POST['filter'] ?? '') === 'unread' ? '?filtro=nao-lidas' : '');
        header('Location: ' . $back, true, 303);
        exit;
    } catch (RuntimeException $e) {
        $error = $e instanceof PDOException ? 'Não foi possível atualizar a mensagem.' : $e->getMessage();
    }
}

$unreadOnly = ($_GET['filtro'] ?? '') === 'nao-lidas';
$before = filter_var($_GET['antes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$messages = ContactRepository::list($before, $unreadOnly);
$more = count($messages) > 20;
$messages = array_slice($messages, 0, 20);
$unread = ContactRepository::unreadCount();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
adminStart('Mensagens de contato');
?>
<p class="success" role="status"><?= h($flash) ?></p><p class="error" role="alert"><?= h($error) ?></p>
<nav class="admin-tabs" aria-label="Filtro de mensagens">
  <a href="/admin/messages.php"<?= $unreadOnly ? '' : ' aria-current="page"' ?>>Todas</a>
  <a href="/admin/messages.php?filtro=nao-lidas"<?= $unreadOnly ? ' aria-current="page"' : '' ?>>Não lidas <span class="badge"><?= $unread ?></span></a>
  <a href="/contato.php">Abrir formulário público</a>
</nav>
<?php if (!$messages): ?>
<p class="empty">Nenhuma mensagem <?= $unreadOnly ? 'não lida' : 'recebida' ?> ainda.</p>
<?php else: ?>
<div class="table-wrap"><table>
<caption>Mensagens recebidas pelo formulário de contato</caption>
<thead><tr><th scope="col">Remetente</th><th scope="col">Assunto</th><th scope="col">Mensagem</th><th scope="col">Data</th><th scope="col">Ações</th></tr></thead>
<tbody>
<?php foreach ($messages as $message): ?>
<tr class="<?= $message['read_at'] ? '' : 'unread' ?>">
  <td><?= h($message['name']) ?><br><small><?= h($message['email']) ?><?= $message['phone'] ? ' · ' . h($message['phone']) : '' ?></small></td>
  <td><?= h(ContactRepository::SUBJECTS[$message['subject']] ?? $message['subject']) ?></td>
  <td class="message-cell"><?= h($message['message']) ?></td>
  <td><?= h(date('d/m/Y H:i', strtotime($message['created_at']))) ?><br><span class="badge"><?= $message['read_at'] ? 'Lida' : 'Nova' ?></span></td>
  <td><div class="admin-actions">
    <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= h($message['id']) ?>"><input type="hidden" name="filter" value="<?= $unreadOnly ? 'unread' : '' ?>"><input type="hidden" name="action" value="toggle"><button class="pill"><?= $message['read_at'] ? 'Marcar como nova' : 'Marcar como lida' ?></button></form>
    <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= h($message['id']) ?>"><input type="hidden" name="filter" value="<?= $unreadOnly ? 'unread' : '' ?>"><input type="hidden" name="action" value="delete"><button class="pill">Excluir</button></form>
  </div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if ($more): ?><a class="pill" href="/admin/messages.php?<?= h(http_build_query(array_filter(['filtro' => $unreadOnly ? 'nao-lidas' : null, 'antes' => end($messages)['id']]))) ?>">Mensagens anteriores</a><?php endif; ?>
<?php endif; ?>
<?php adminEnd(); ?>
