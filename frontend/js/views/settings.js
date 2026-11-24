import { api, isDemo, session, user } from "../api.js";
import { escape, toast } from "../ui.js";
import { stop } from "../player.js";

// Tela de configurações: cada grupo é um fieldset de rádios, salvo na hora em que muda.
const THEMES = [
  ["dark", "Escuro", "Padrão."],
  ["light", "Claro", ""],
  ["system", "Sistema", "Igual ao do sistema operacional."],
];
const MOTION = [
  ["auto", "Automático", "Respeita a opção de reduzir movimento do sistema."],
  ["on", "Ligadas", ""],
  ["off", "Desligadas", ""],
];

const SHORTCUTS = [
  ["Espaço ou K", "Tocar / pausar"],
  ["← / →", "Voltar / avançar 5 segundos"],
  ["Shift + ← / →, P / N", "Faixa anterior / próxima"],
  ["M", "Mudo"],
  ["/", "Buscar"],
  ["Alt + ↑ / ↓", "Mover faixa na playlist"],
];
const radios = (name, options, current, extra = () => "") =>
  options
    .map(
      ([value, label, hint]) => `<label class="choice choice-${name}" data-value="${value}">
        <input type="radio" name="${name}" value="${value}"${value === current ? " checked" : ""}>
        ${extra(value)}<span class="choice-text"><strong>${label}</strong>${hint ? `<small>${hint}</small>` : ""}</span>
      </label>`,
    )
    .join("");

export function settings(el) {
  const prefs = window.OndaPrefs.get();
  el.innerHTML = `<h1>Configurações</h1>
    ${user && !isDemo ? account() : ""}
    <h2 class="settings-title">Preferências</h2>
    <p class="muted">Salvas neste navegador.</p>
    <form class="settings" id="settings-form">
      <fieldset class="settings-group">
        <legend>Tema</legend>
        <div class="choices">${radios("theme", THEMES, prefs.theme, (v) => `<span class="theme-preview theme-preview-${v}" aria-hidden="true"><i></i><i></i><i></i></span>`)}</div>
      </fieldset>
      <fieldset class="settings-group">
        <legend>Animações</legend>
        <div class="choices">${radios("motion", MOTION, prefs.motion)}</div>
      </fieldset>
      <fieldset class="settings-group">
        <legend>Reprodução</legend>
        <label class="switch"><input type="checkbox" name="rememberVolume"${prefs.rememberVolume ? " checked" : ""}>
          <span class="choice-text"><strong>Lembrar o volume</strong><small>Usa o último volume ao abrir o site.</small></span></label>
      </fieldset>
      <fieldset class="settings-group">
        <legend>Atalhos de teclado</legend>
        <dl class="about shortcuts-list">${SHORTCUTS.map(([keys, what]) => `<div><dt><kbd>${keys}</kbd></dt><dd>${what}</dd></div>`).join("")}</dl>
      </fieldset>
      <fieldset class="settings-group">
        <legend>Sobre este servidor</legend>
        <dl class="about">
          <div><dt>Modo</dt><dd>${isDemo ? "Demonstração estática (dados fictícios, sem servidor)" : "Servidor Onda com PHP e MariaDB"}</dd></div>
          <div><dt>Armazenamento das preferências</dt><dd>localStorage deste navegador</dd></div>
        </dl>
        <button type="button" class="pill" id="settings-reset">Restaurar padrões</button>
      </fieldset>
    </form>`;

  if (user && !isDemo) bindAccount(el);
  const form = el.querySelector("#settings-form");
  form.addEventListener("change", (event) => {
    const input = event.target;
    window.OndaPrefs.set({ [input.name]: input.type === "checkbox" ? input.checked : input.value });
    toast("Preferência salva");
  });
  el.querySelector("#settings-reset").addEventListener("click", () => {
    window.OndaPrefs.reset();
    settings(el);
    toast("Configurações restauradas");
  });
}

// Conta no servidor (só fora da demonstração).
function account() {
  return `<div class="settings account">
      <form id="profile-form"><fieldset class="settings-group">
        <legend>Perfil</legend>
        <p class="muted">Email: <strong>${escape(user.email)}</strong></p>
        <label>Nome<input name="name" required minlength="2" maxlength="160" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,160}" data-pattern-message="Use apenas letras e espaços." value="${escape(user.name)}"></label>
        <button class="pill">Salvar nome</button>
      </fieldset></form>
      <form id="password-form"><fieldset class="settings-group">
        <legend>Senha</legend>
        <label>Senha atual<input type="password" name="current_password" autocomplete="current-password" required></label>
        <label>Nova senha<input type="password" name="new_password" autocomplete="new-password" required minlength="12" maxlength="1024"></label>
        <small class="muted">Use pelo menos 12 caracteres.</small>
        <button class="pill">Trocar senha</button>
      </fieldset></form>
      <fieldset class="settings-group">
        <legend>Histórico</legend>
        <p class="muted">Usado em “Tocadas recentemente” na tela inicial.</p>
        <button type="button" class="pill" id="clear-history">Limpar histórico</button>
      </fieldset>
      <form id="delete-form"><fieldset class="settings-group danger-zone">
        <legend>Excluir conta</legend>
        <p class="muted">Apaga sua conta, playlists, curtidas e histórico. Não dá para desfazer.</p>
        <label>Confirme com sua senha<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="pill danger">Excluir minha conta</button>
      </fieldset></form>
    </div>`;
}
function bindAccount(el) {
  const submit = (selector, handler) =>
    (el.querySelector(selector).onsubmit = async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = form.querySelector("button");
      button.disabled = true;
      try { await handler(Object.fromEntries(new FormData(form)), form); }
      catch (error) { toast(error.message); }
      finally { button.disabled = false; }
    });
  submit("#profile-form", async (body) => {
    await api("/me", { method: "PUT", body });
    await session();
    document.dispatchEvent(new Event("library-changed"));
    toast("Nome atualizado");
  });
  submit("#password-form", async (body, form) => {
    await api("/me/password", { method: "PUT", body });
    await session(); // a sessão foi renovada: pega o novo token CSRF
    form.reset();
    toast("Senha alterada");
  });
  el.querySelector("#clear-history").onclick = async () => {
    try {
      await api("/history", { method: "DELETE", body: {} });
      toast("Histórico limpo");
    } catch (error) { toast(error.message); }
  };
  submit("#delete-form", async (body) => {
    if (!confirm("Excluir sua conta de vez?")) return;
    await api("/me", { method: "DELETE", body });
    stop();
    await session();
    document.dispatchEvent(new Event("library-changed"));
    location.hash = "/";
    toast("Conta excluída");
  });
}
