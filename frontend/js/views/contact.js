// Tela de contato da versão estática (GitHub Pages).
// No site com PHP o mesmo formulário é renderizado e validado por /contato.php.
import { api } from "../api.js";
import { escape } from "../ui.js";

const SUBJECTS = {
  suporte: "Suporte com a conta",
  sugestao: "Sugestão de música ou recurso",
  artista: "Sou artista e quero publicar",
  outro: "Outro assunto",
};

export async function contact(el) {
  const options = Object.entries(SUBJECTS)
    .map(([value, label]) => `<option value="${value}">${escape(label)}</option>`)
    .join("");
  el.innerHTML = `<section class="auth">
    <h1>Contato</h1>
    <p class="muted">Dúvidas, sugestões ou pedidos de artistas. Use apenas dados fictícios.</p>
    <p class="success" role="status"></p><p class="error" role="alert"></p>
    <form class="admin-form contact-form" novalidate>
      <label>Nome<input name="name" autocomplete="name" required maxlength="160" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,160}" data-pattern-message="Use apenas letras e espaços."></label>
      <label>Email<input type="email" name="email" autocomplete="email" required maxlength="254"></label>
      <label>Telefone (opcional)<input type="tel" name="phone" autocomplete="tel" maxlength="20" placeholder="(49) 99999-9999" pattern="\\(?[0-9]{2}\\)?\\s?[0-9]{4,5}-?[0-9]{4}" data-pattern-message="Use o formato (49) 99999-9999."></label>
      <label>Assunto<select name="subject" required><option value="">Selecione</option>${options}</select></label>
      <label>Mensagem<textarea name="message" required minlength="10" maxlength="2000" rows="6"></textarea></label>
      <label class="check"><input type="checkbox" name="consent" value="1" required><span>Confirmo que os dados enviados são fictícios (projeto acadêmico).</span></label>
      <small class="muted">Limite: 3 mensagens por email a cada 24 horas.</small>
      <div class="admin-actions"><button class="primary">Enviar mensagem →</button></div>
    </form>
  </section>`;
  const form = el.querySelector("form");
  const success = el.querySelector(".success");
  const error = el.querySelector(".error");
  // A validação dos campos (site.js) roda antes e cancela o envio se algo estiver inválido.
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    success.textContent = error.textContent = "";
    const button = form.querySelector("button");
    button.disabled = true;
    try {
      const data = Object.fromEntries(new FormData(form));
      await api("/contact", { method: "POST", body: data });
      success.textContent = `Mensagem enviada! Obrigado, ${data.name}. Ela aparece no painel administrativo.`;
      form.reset();
    } catch (err) {
      error.textContent = err.message;
    } finally {
      button.disabled = false;
    }
  });
}

export { SUBJECTS };
