<?php
declare(strict_types=1);
require __DIR__.'/../../src/bootstrap.php';
\Onda\Session::start();
header('Cache-Control: no-store');
$adminMode=true; $loadSpa=false;
function h(mixed $value): string { return \Onda\Admin::h($value); }
function adminStart(string $title): void {
    global $adminMode,$loadSpa;
    require __DIR__.'/../../views/header.php';
    require __DIR__.'/../../views/menu.php';
    echo '<div class="workspace"><header class="topbar"><button id="menu-toggle" class="menu-toggle" aria-controls="navigation" aria-expanded="false" aria-label="Abrir menu">☰ Menu</button><a href="/">Voltar ao site</a><a href="/admin/messages.php">Mensagens</a><a href="/admin/users.php">Cadastros</a></header><main id="main" tabindex="-1"><h1>'.h($title).'</h1>';
}
function adminEnd(): void {
    echo '</main>';
    require __DIR__.'/../../views/footer.php';
    echo '</div></body></html>';
}
