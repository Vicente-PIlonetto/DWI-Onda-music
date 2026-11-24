<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="theme-color" content="#080808" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <title>Onda</title>
    <link rel="stylesheet" href="/css/app.css" />
    <link rel="stylesheet" href="/css/layout.css" />
    <script src="/js/prefs.js"></script>
    <?php if ($loadSpa ?? false): ?><script type="module" src="/js/app.js"></script><?php endif; ?>
    <script type="module" src="/js/site.js"></script>
  </head>
  <body class="<?= ($adminMode ?? false) ? 'admin' : ($bodyClass ?? '') ?>">
    <a class="skip" href="#main">Pular para o conteúdo</a>
