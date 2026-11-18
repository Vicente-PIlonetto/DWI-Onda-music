// Comportamento compartilhado entre a SPA e o painel renderizado pelo PHP.

// Tema claro/escuro: a escolha fica salva no localStorage deste navegador.
const THEME_KEY = 'onda-theme';
function readTheme() {
  try { return localStorage.getItem(THEME_KEY); } catch { return null; }
}
function applyTheme(theme) {
  document.documentElement.dataset.theme = theme;
  const button = document.querySelector('#theme-toggle');
  if (button) {
    const light = theme === 'light';
    button.innerHTML = light ? '☾<span class="theme-label"> Escuro</span>' : '☀<span class="theme-label"> Claro</span>';
    button.setAttribute('aria-label', light ? 'Ativar tema escuro' : 'Ativar tema claro');
    button.setAttribute('aria-pressed', String(light));
  }
}
const themeButton = document.createElement('button');
themeButton.id = 'theme-toggle';
themeButton.type = 'button';
themeButton.className = 'theme-toggle';
document.querySelector('.topbar')?.append(themeButton);
applyTheme(readTheme() === 'light' ? 'light' : 'dark');
themeButton.addEventListener('click', () => {
  const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
  applyTheme(next);
  try { localStorage.setItem(THEME_KEY, next); } catch { /* armazenamento indisponível */ }
});

const toggle = document.querySelector('#menu-toggle');
const navigation = document.querySelector('#navigation');
function closeMenu() {
  navigation?.classList.remove('menu-open');
  toggle?.setAttribute('aria-expanded', 'false');
  toggle?.setAttribute('aria-label', 'Abrir menu');
}
toggle?.addEventListener('click', () => {
  const open = navigation.classList.toggle('menu-open');
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
});
navigation?.addEventListener('click', event => {
  if (event.target.closest('a')) closeMenu();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && navigation?.classList.contains('menu-open')) {
    closeMenu(); toggle?.focus();
  }
});

// Validação local: não substitui as verificações independentes no PHP.
document.addEventListener('submit', event => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement)) return;
  form.querySelectorAll('.field-error').forEach(node => node.remove());
  let firstInvalid = null;
  for (const field of form.querySelectorAll('input, select, textarea')) {
    field.removeAttribute('aria-invalid');
    field.removeAttribute('aria-describedby');
    if (!field.checkValidity()) {
      const message = document.createElement('span');
      message.className = 'field-error';
      message.id = 'error-' + (field.name || 'field');
      message.textContent = field.validity.valueMissing ? (field.type === 'checkbox' ? 'Marque esta opção.' : 'Preencha este campo.') :
        field.validity.typeMismatch ? 'Informe um email válido.' :
        field.validity.tooShort ? `Use pelo menos ${field.minLength} caracteres.` :
        field.validity.patternMismatch ? (field.dataset.patternMessage || 'Use o formato indicado.') :
        field.validity.rangeUnderflow ? `O valor mínimo é ${field.min}.` :
        field.validity.rangeOverflow ? `O valor máximo é ${field.max}.` : 'Confira o valor informado.';
      field.insertAdjacentElement('afterend', message);
      field.setAttribute('aria-invalid', 'true');
      field.setAttribute('aria-describedby', message.id);
      firstInvalid ||= field;
    }
  }
  if (firstInvalid) {
    event.preventDefault(); event.stopImmediatePropagation(); firstInvalid.focus();
  }
}, true);
if (document.documentElement.dataset.demo === 'true') {
  const notice = document.createElement('p');
  notice.className = 'demo-notice';
  notice.textContent = 'Parte 2 · demonstração com dados fictícios. Alterações ficam apenas neste navegador.';
  document.querySelector('#main')?.before(notice);
  document.querySelectorAll('.admin-link').forEach(link => { link.href = '#/admin'; });
}
