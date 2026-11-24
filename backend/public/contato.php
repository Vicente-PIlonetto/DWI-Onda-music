<?php
// Página pública de contato: formulário enviado por POST, validado e gravado no banco.
// As mensagens aparecem no painel administrativo (/admin/messages.php).
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../views/layout.php';

use Onda\Session;
use Onda\Repositories\ContactRepository;

Session::start();
header('Cache-Control: no-store');

$form = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
$consent = false;
$errors = [];
$general = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403);
        $general = 'Sessão do formulário expirada. Envie novamente.';
    }
    [$clean, $errors] = ContactRepository::validate($_POST);
    // Mantém o que a pessoa digitou para ela corrigir sem perder o texto.
    $form = array_merge($form, $clean);
    $consent = ($_POST['consent'] ?? '') === '1';
    if (!$general && !$errors) {
        try {
            ContactRepository::create($clean);
            // Padrão Post/Redirect/Get: evita reenviar o formulário ao recarregar.
            $_SESSION['contact_flash'] = 'Mensagem enviada! Obrigado, ' . $clean['name'] . '. Responderemos pelo email informado.';
            header('Location: /contato.php', true, 303);
            exit;
        } catch (PDOException $e) {
            error_log((string) $e);
            $general = 'Não foi possível salvar sua mensagem agora. Tente novamente.';
        }
    } elseif ($errors && !$general) {
        http_response_code(422);
        $general = 'Confira os campos destacados abaixo.';
    }
}

$flash = $_SESSION['contact_flash'] ?? '';
unset($_SESSION['contact_flash']);

publicStart('Contato', 'contato');
?>
<section class="auth">
  <h1>Contato</h1>
  <p class="muted">Dúvidas, sugestões ou pedidos de artistas. Use apenas dados fictícios.</p>
  <?php if ($flash): ?><p class="success" role="status"><?= h($flash) ?></p><?php endif; ?>
  <?php if ($general): ?><p class="error" role="alert"><?= h($general) ?></p><?php endif; ?>

  <form method="post" action="/contato.php" class="admin-form contact-form" novalidate>
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <label>Nome
      <input name="name" autocomplete="name" required maxlength="160"
        pattern="[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,160}" data-pattern-message="Use apenas letras e espaços."
        value="<?= h($form['name']) ?>"<?= invalidAttrs($errors, 'name') ?>>
      <?= fieldError($errors, 'name') ?>
    </label>
    <label>Email
      <input type="email" name="email" autocomplete="email" required maxlength="254"
        value="<?= h($form['email']) ?>"<?= invalidAttrs($errors, 'email') ?>>
      <?= fieldError($errors, 'email') ?>
    </label>
    <label>Telefone (opcional)
      <input type="tel" name="phone" autocomplete="tel" maxlength="20" placeholder="(49) 99999-9999"
        pattern="\(?[0-9]{2}\)?\s?[0-9]{4,5}-?[0-9]{4}" data-pattern-message="Use o formato (49) 99999-9999."
        value="<?= h($form['phone']) ?>"<?= invalidAttrs($errors, 'phone') ?>>
      <?= fieldError($errors, 'phone') ?>
    </label>
    <label>Assunto
      <select name="subject" required<?= invalidAttrs($errors, 'subject') ?>>
        <option value="">Selecione</option>
        <?php foreach (ContactRepository::SUBJECTS as $value => $label): ?>
          <option value="<?= h($value) ?>"<?= $form['subject'] === $value ? ' selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <?= fieldError($errors, 'subject') ?>
    </label>
    <label>Mensagem
      <textarea name="message" required minlength="10" maxlength="2000" rows="6"<?= invalidAttrs($errors, 'message') ?>><?= h($form['message']) ?></textarea>
      <?= fieldError($errors, 'message') ?>
    </label>
    <label class="check">
      <input type="checkbox" name="consent" value="1" required<?= $consent ? ' checked' : '' ?><?= invalidAttrs($errors, 'consent') ?>>
      <span>Confirmo que os dados enviados são fictícios (projeto acadêmico).</span>
    </label>
    <?= fieldError($errors, 'consent') ?>
    <small class="muted">Limite: <?= ContactRepository::LIMIT_PER_DAY ?> mensagens por email a cada 24 horas.</small>
    <div class="admin-actions"><button class="primary">Enviar mensagem →</button></div>
  </form>
</section>
<?php publicEnd(); ?>
