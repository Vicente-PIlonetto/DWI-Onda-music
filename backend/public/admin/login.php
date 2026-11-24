<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Onda\Admin;
$error=''; $email=is_string($_POST['email']??null)?$_POST['email']:'';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        Admin::csrf(); $email=strtolower(Admin::text('email',254));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Informe um email válido.');
        Admin::login($email,Admin::text('password',1024));
        header('Location: /admin/',true,303); exit;
    } catch (RuntimeException $e) { $error=$e->getMessage(); }
}
adminStart('Entrar no painel');
?>
<p class="muted">Use a conta fictícia de administrador configurada no ambiente.</p>
<form method="post" class="admin-form" novalidate>
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
<label>Email<input name="email" type="email" required maxlength="254" autocomplete="username" value="<?= h($email) ?>"></label>
<label>Senha<input name="password" type="password" required minlength="12" maxlength="1024" autocomplete="current-password"></label>
<p class="error" role="alert"><?= h($error) ?></p><button class="primary">Entrar</button>
</form>
<?php adminEnd(); ?>
