// Preferências do usuário (tema, animações e volume).
// É um script comum (não módulo) carregado no <head>: aplica as escolhas antes
// da página ser desenhada, para não piscar o tema errado. Salvas no localStorage.
(function () {
  var KEY = 'onda-settings';
  var OPTIONS = {
    theme: ['dark', 'light', 'system'],
    motion: ['auto', 'on', 'off'],
  };
  var DEFAULTS = { theme: 'dark', motion: 'auto', rememberVolume: true };
  var root = document.documentElement;
  var systemLight = window.matchMedia ? window.matchMedia('(prefers-color-scheme: light)') : null;

  // Lê e valida o que está salvo; valores desconhecidos voltam ao padrão.
  function read() {
    var saved = {};
    try {
      saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
      var legacy = localStorage.getItem('onda-theme'); // chave antiga do botão de tema
      if (legacy && !saved.theme) saved.theme = legacy;
    } catch (e) { /* armazenamento indisponível ou JSON inválido */ }
    var prefs = {};
    for (var name in DEFAULTS) {
      var value = saved[name];
      var valid = OPTIONS[name] ? OPTIONS[name].indexOf(value) !== -1 : typeof value === typeof DEFAULTS[name];
      prefs[name] = valid ? value : DEFAULTS[name];
    }
    return prefs;
  }

  function apply(prefs) {
    var theme = prefs.theme === 'system' ? (systemLight && systemLight.matches ? 'light' : 'dark') : prefs.theme;
    root.dataset.theme = theme;
    root.dataset.motion = prefs.motion;
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'light' ? '#f5f8fa' : '#080808');
  }

  function save(prefs) {
    try { localStorage.setItem(KEY, JSON.stringify(prefs)); } catch (e) { /* ignora */ }
    // Transição suave só durante a troca, para não deixar a página lenta no resto do tempo.
    root.classList.add('theme-transition');
    clearTimeout(save.timer);
    save.timer = setTimeout(function () { root.classList.remove('theme-transition'); }, 400);
    apply(prefs);
    document.dispatchEvent(new CustomEvent('prefs-change', { detail: prefs }));
    return prefs;
  }

  window.OndaPrefs = {
    OPTIONS: OPTIONS,
    DEFAULTS: DEFAULTS,
    get: read,
    set: function (changes) {
      var prefs = read();
      for (var name in changes) prefs[name] = changes[name];
      return save(prefs);
    },
    reset: function () {
      try { localStorage.removeItem('onda-theme'); localStorage.removeItem('onda-volume'); } catch (e) { /* ignora */ }
      return save(Object.assign({}, DEFAULTS));
    },
  };

  apply(read());
  // No modo "Sistema", acompanha a troca de tema do sistema operacional em tempo real.
  if (systemLight && systemLight.addEventListener)
    systemLight.addEventListener('change', function () { apply(read()); });
})();
