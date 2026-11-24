<?php
// Menu lateral reutilizado pela SPA (site.php), pelas páginas públicas em PHP e pelo painel.
// Nas páginas PHP fora da SPA, os links por hash recebem o prefixo "/" para voltar à SPA.
$base = ($loadSpa ?? false) ? '' : '/';
?>
<?php if ($adminMode ?? false): ?>
<aside id="navigation" class="sidebar"><a class="brand" href="/"><svg class="brand-wave" viewBox="0 0 40 24" aria-hidden="true"><path d="M-20 12.00 L-19 9.84 L-18 7.89 L-17 6.34 L-16 5.34 L-15 5.00 L-14 5.34 L-13 6.34 L-12 7.89 L-11 9.84 L-10 12.00 L-9 14.16 L-8 16.11 L-7 17.66 L-6 18.66 L-5 19.00 L-4 18.66 L-3 17.66 L-2 16.11 L-1 14.16 L0 12.00 L1 9.84 L2 7.89 L3 6.34 L4 5.34 L5 5.00 L6 5.34 L7 6.34 L8 7.89 L9 9.84 L10 12.00 L11 14.16 L12 16.11 L13 17.66 L14 18.66 L15 19.00 L16 18.66 L17 17.66 L18 16.11 L19 14.16 L20 12.00 L21 9.84 L22 7.89 L23 6.34 L24 5.34 L25 5.00 L26 5.34 L27 6.34 L28 7.89 L29 9.84 L30 12.00 L31 14.16 L32 16.11 L33 17.66 L34 18.66 L35 19.00 L36 18.66 L37 17.66 L38 16.11 L39 14.16 L40 12.00 L41 9.84 L42 7.89 L43 6.34 L44 5.34 L45 5.00 L46 5.34 L47 6.34 L48 7.89 L49 9.84 L50 12.00 L51 14.16 L52 16.11 L53 17.66 L54 18.66 L55 19.00 L56 18.66 L57 17.66 L58 16.11 L59 14.16 L60 12.00"/></svg><span>onda<span class="brand-dot">.</span></span></a><nav aria-label="Administração"><a href="/admin/">Álbuns</a><a href="/admin/messages.php">Mensagens</a><a href="/admin/users.php">Cadastros</a><a href="/catalogo.php">Catálogo público</a></nav></aside>
<?php else: ?>
    <aside id="navigation" class="sidebar">
      <a class="brand" href="<?= $base ?>#/"
        ><svg class="brand-wave" viewBox="0 0 40 24" aria-hidden="true"><path d="M-20 12.00 L-19 9.84 L-18 7.89 L-17 6.34 L-16 5.34 L-15 5.00 L-14 5.34 L-13 6.34 L-12 7.89 L-11 9.84 L-10 12.00 L-9 14.16 L-8 16.11 L-7 17.66 L-6 18.66 L-5 19.00 L-4 18.66 L-3 17.66 L-2 16.11 L-1 14.16 L0 12.00 L1 9.84 L2 7.89 L3 6.34 L4 5.34 L5 5.00 L6 5.34 L7 6.34 L8 7.89 L9 9.84 L10 12.00 L11 14.16 L12 16.11 L13 17.66 L14 18.66 L15 19.00 L16 18.66 L17 17.66 L18 16.11 L19 14.16 L20 12.00 L21 9.84 L22 7.89 L23 6.34 L24 5.34 L25 5.00 L26 5.34 L27 6.34 L28 7.89 L29 9.84 L30 12.00 L31 14.16 L32 16.11 L33 17.66 L34 18.66 L35 19.00 L36 18.66 L37 17.66 L38 16.11 L39 14.16 L40 12.00 L41 9.84 L42 7.89 L43 6.34 L44 5.34 L45 5.00 L46 5.34 L47 6.34 L48 7.89 L49 9.84 L50 12.00 L51 14.16 L52 16.11 L53 17.66 L54 18.66 L55 19.00 L56 18.66 L57 17.66 L58 16.11 L59 14.16 L60 12.00"/></svg><span>onda<span class="brand-dot">.</span></span></a
      >
      <nav aria-label="Principal">
        <a href="<?= $base ?>#/"><svg class="nav-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg><span>Início</span></a
        ><a href="<?= $base ?>#/search"><svg class="nav-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><span>Buscar</span></a
        ><a href="/catalogo.php"<?= ($activePage ?? '') === 'catalogo' ? ' class="active" aria-current="page"' : '' ?>><svg class="nav-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/></svg><span>Catálogo</span></a
        ><a href="<?= $base ?>#/library"><svg class="nav-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><path d="M5 3v18M10 3v18M15 4l5 16"/></svg><span>Sua Biblioteca</span></a
        ><a href="<?= $base ?>#/likes"><svg class="nav-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><path d="M12 20s-7.5-4.6-9.2-9.4A4.9 4.9 0 0 1 12 7a4.9 4.9 0 0 1 9.2 3.6C19.5 15.4 12 20 12 20z"/></svg><span>Músicas Curtidas</span></a
        ><a href="<?= $base ?>#/settings"><svg class="nav-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/></svg><span>Configurações</span></a>
      </nav>
<?php if ($loadSpa ?? false): ?>
      <div class="sidebar-heading">
        SUAS PLAYLISTS
        <button id="create-playlist" aria-label="Criar playlist">＋</button>
      </div>
      <div id="sidebar-playlists"></div>
<?php endif; ?>
      <div class="sidebar-note">
        <small>Projeto acadêmico · áudio sintético</small>
      </div>
    </aside>
<?php endif; ?>
