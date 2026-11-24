<?php
declare(strict_types=1);
$loadSpa=true; $adminMode=false;
require __DIR__.'/../views/header.php';
require __DIR__.'/../views/menu.php';
?>

    <div class="workspace">
      <header class="topbar">
        <button id="menu-toggle" class="menu-toggle" aria-label="Abrir menu" aria-controls="navigation" aria-expanded="false">☰ Menu</button>
        <div class="breadcrumb">SUA TRILHA COMEÇA AQUI</div>
        <div id="account"><a class="pill" href="#/login">Entrar ↗</a></div>
      </header>
      <main id="main" tabindex="-1"></main>
<?php require __DIR__.'/../views/footer.php'; ?>
    </div>
    <section class="player" aria-label="Player de música">
      <div class="now">
        <img id="now-cover" src="/assets/cover-default.svg" alt="Capa ilustrativa da faixa em reprodução" />
        <div>
          <strong id="now-title">Escolha sua próxima onda</strong
          ><small id="now-artist"></small>
        </div>
      </div>
      <div class="transport">
        <div class="controls">
          <button id="shuffle" aria-label="Ativar shuffle" aria-pressed="false">
            ⤨</button
          ><button id="previous" aria-label="Faixa anterior">❮❮</button
          ><button id="play" class="play-round" aria-label="Reproduzir">
            ▶</button
          ><button id="next" aria-label="Próxima faixa">❯❯</button
          ><button id="repeat" aria-label="Repetição: desligada">↻</button>
        </div>
        <div class="progress">
          <time id="current">0:00</time
          ><input
            id="seek"
            type="range"
            min="0"
            max="100"
            value="0"
            step="0.1"
            aria-label="Posição da faixa"
          /><time id="duration">0:00</time>
        </div>
      </div>
      <div class="player-extra">
        <button
          id="queue-toggle"
          aria-label="Mostrar fila"
          aria-expanded="false"
        >
          ☷</button
        ><label for="volume">Volume</label
        ><input
          id="volume"
          type="range"
          min="0"
          max="1"
          value="0.65"
          step="0.01"
          aria-label="Volume"
        />
      </div>
      <audio id="audio" preload="metadata"></audio>
    </section>
    <aside id="queue" class="queue" hidden>
      <h2>
        Na sequência
        <button id="queue-close" aria-label="Fechar fila">×</button>
      </h2>
      <label for="queue-volume">Volume</label>
      <input
        id="queue-volume"
        type="range"
        min="0"
        max="1"
        step="0.01"
        value="0.65"
        aria-label="Volume da reprodução"
      />
      <ol id="queue-items"></ol>
    </aside>
    <div id="toast" role="status" aria-live="polite"></div>
    <dialog id="playlist-dialog">
      <form novalidate id="playlist-form">
        <h2>Nova playlist</h2>
        <label
          >Nome<input
            name="name"
            required
            maxlength="160"
            placeholder="Minha playlist"
        /></label>
        <div class="dialog-actions">
          <button type="button" id="cancel-playlist">Cancelar</button
          ><button class="primary">Criar playlist</button>
        </div>
      </form>
    </dialog>
    <dialog id="add-dialog">
      <form novalidate id="add-form">
        <h2>Adicionar à playlist</h2>
        <label>Playlist<select name="playlist" required></select></label>
        <div class="dialog-actions">
          <button type="button" id="cancel-add">Cancelar</button
          ><button class="primary">Adicionar</button>
        </div>
      </form>
    </dialog>
  </body>
</html>
