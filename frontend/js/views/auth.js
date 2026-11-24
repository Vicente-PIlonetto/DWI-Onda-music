import { api, session, isDemo } from "../api.js";
import { toast } from "../ui.js";
export async function auth(el, register) {
  el.innerHTML = `<section class="auth"><h1>${register ? "Criar conta" : "Entrar no Onda"}</h1><form novalidate>${register ? '<label>Nome<input name="name" autocomplete="name" required maxlength="160" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\' ]{2,160}" data-pattern-message="Use apenas letras e espaços."></label>' : ""}<label>Email<input type="email" name="email" autocomplete="email" required maxlength="254"></label><label>Senha<input type="password" name="password" autocomplete="${register ? "new-password" : "current-password"}" required minlength="12" maxlength="1024"></label><small>Use pelo menos 12 caracteres.</small>${register ? "" : '<label class="check remember"><input type="checkbox" name="remember" value="1" checked><span>Manter conectado por 30 dias</span></label>'}<p class="error" role="alert"></p><button class="primary">${register ? "Criar conta" : "Entrar"} →</button></form><p class="muted">${register ? 'Já está na onda? <a href="#/login">Entrar</a>' : 'Ainda não tem conta? <a href="#/register">Cadastre-se</a>'}</p></section>`;
  if (isDemo) {
    const hint = document.createElement('p');
    hint.className = 'demo-hint';
    hint.textContent = register ? 'Cadastro fictício salvo apenas neste navegador.' : 'Contas de teste: demo@onda.test e admin@onda.test. Use qualquer senha com 12 caracteres.';
    el.querySelector('.auth').prepend(hint);
  }
  el.querySelector("form").onsubmit = async (e) => {
    e.preventDefault();
    const button = e.target.querySelector("button");
    button.disabled = true;
    try {
      await api("/auth/" + (register ? "register" : "login"), {
        method: "POST",
        body: { ...Object.fromEntries(new FormData(e.target)), remember: e.target.remember?.checked ?? false },
      });
      await session();
      document.dispatchEvent(new Event("library-changed"));
      location.hash = "/";
      toast("Conta criada");
    } catch (error) {
      el.querySelector(".error").textContent = error.message;
    } finally {
      button.disabled = false;
    }
  };
}
