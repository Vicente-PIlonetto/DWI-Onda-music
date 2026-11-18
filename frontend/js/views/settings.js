import { isDemo } from "../api.js";
import { toast } from "../ui.js";

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
        <legend>Sobre este servidor</legend>
        <dl class="about">
          <div><dt>Modo</dt><dd>${isDemo ? "Demonstração estática (dados fictícios, sem servidor)" : "Servidor Onda com PHP e MariaDB"}</dd></div>
          <div><dt>Armazenamento das preferências</dt><dd>localStorage deste navegador</dd></div>
        </dl>
        <button type="button" class="pill" id="settings-reset">Restaurar padrões</button>
      </fieldset>
    </form>`;

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
