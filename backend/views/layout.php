<?php
// Funções de apresentação das páginas públicas renderizadas em PHP (catálogo e contato).
// Cabeçalho, menu e rodapé vêm dos mesmos includes usados pela SPA e pelo painel.
declare(strict_types=1);

if (!function_exists('h')) {
    /** Escapa a saída para HTML (proteção contra XSS). */
    function h(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function publicStart(string $title, string $activePage): void
{
    $loadSpa = false;
    $adminMode = false;
    $bodyClass = 'public';
    require __DIR__ . '/header.php';
    require __DIR__ . '/menu.php';
    echo '<div class="workspace"><header class="topbar">'
        . '<button id="menu-toggle" class="menu-toggle" aria-controls="navigation" aria-expanded="false" aria-label="Abrir menu">☰ Menu</button>'
        . '<div class="breadcrumb">' . h(mb_strtoupper($title)) . '</div>'
        . '<a class="pill" href="/#/">Voltar ao player ↗</a>'
        . '</header><main id="main" tabindex="-1">';
}

function publicEnd(): void
{
    echo '</main>';
    require __DIR__ . '/footer.php';
    echo '</div></body></html>';
}

/** Mensagem de erro exibida logo abaixo do campo (mesmo visual da validação em JS). */
function fieldError(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<span class="field-error" id="error-' . h($field) . '">' . h($errors[$field]) . '</span>'
        : '';
}

/** Atributos de acessibilidade para campos com erro vindo do servidor. */
function invalidAttrs(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="error-' . h($field) . '"' : '';
}
