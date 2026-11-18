import { api, user } from "../api.js";
import { escape, toast } from "../ui.js";

import { SUBJECTS } from "./contact.js";

// mode: "albums" (CRUD de álbuns), "users" (cadastros) ou "messages" (formulário de contato).
export async function admin(el, mode = "albums") {
  const usersPage = mode === "users";
  if (user?.role !== "admin") {
    el.innerHTML = '<section class="auth"><div class="eyebrow">ÁREA RESTRITA</div><h1>Administração</h1><p>Entre com a conta fictícia <strong>admin@onda.test</strong> para abrir o painel.</p><a class="primary" href="#/login">Entrar como administrador →</a></section>';
    return;
  }
  const tabs = `<nav class="admin-tabs" aria-label="Administração"><a href="#/admin">Álbuns</a><a href="#/admin/messages">Mensagens</a><a href="#/admin/users">Cadastros</a><a href="#/catalog">Ver catálogo</a></nav>`;
  if (mode === "messages") return messages(el, tabs);
  const albums = await api("/admin/albums"), users = await api("/admin/users");
  el.innerHTML = `<div class="eyebrow">ÁREA ADMINISTRATIVA · DEMONSTRAÇÃO</div><h1>${usersPage ? "Cadastros" : "Painel de álbuns"}</h1>${tabs}${usersPage ? `
    <div class="table-wrap"><table><caption>Usuários fictícios cadastrados neste navegador</caption><thead><tr><th scope="col">Nome</th><th scope="col">Email</th><th scope="col">Perfil</th></tr></thead><tbody>${users.map(x => `<tr><td>${escape(x.name)}</td><td>${escape(x.email)}</td><td>${escape(x.role)}</td></tr>`).join("")}</tbody></table></div>` : `
    <section><h2>Cadastrar álbum</h2><form id="album-form" class="admin-form" novalidate><label>Título<input name="title" required maxlength="160"></label><label>Artista<select name="artist_id" required>${[...new Map(albums.map(x => [x.artist_id, x.artist_name])).entries()].map(([id, name]) => `<option value="${id}">${escape(name)}</option>`).join("")}</select></label><label>Ano<input name="year" type="number" min="1900" max="2100" value="2026" required></label><div class="admin-actions"><button class="primary">Salvar álbum</button><button type="reset" class="pill">Limpar</button></div></form></section>
    <div class="table-wrap"><table><caption>Álbuns do catálogo</caption><thead><tr><th scope="col">Álbum</th><th scope="col">Artista</th><th scope="col">Ano</th><th scope="col">Ações</th></tr></thead><tbody>${albums.map(x => `<tr><td><a href="#/albums/${x.id}">${escape(x.title)}</a></td><td>${escape(x.artist_name)}</td><td>${escape(x.year)}</td><td>${x.id > 1000000 ? `<button data-edit="${x.id}" class="pill">Editar</button> <button data-delete="${x.id}" class="pill">Excluir</button>` : "Álbum de exemplo"}</td></tr>`).join("")}</tbody></table></div>`}`;
  if (usersPage) return;
  const form = el.querySelector("#album-form");
  form.onsubmit = async event => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(form));
    const id = form.dataset.edit;
    try {
      await api(id ? "/admin/albums/" + id : "/admin/albums", { method: id ? "PUT" : "POST", body: payload });
      toast(id ? "Álbum atualizado" : "Álbum criado");
      document.dispatchEvent(new Event("route-refresh"));
    } catch (error) { toast(error.message); }
  };
  el.addEventListener("click", async event => {
    const edit = event.target.closest("[data-edit]"), remove = event.target.closest("[data-delete]");
    if (edit) {
      const item = albums.find(x => x.id === Number(edit.dataset.edit));
      if (!item) return;
      form.dataset.edit = item.id;
      form.elements.title.value = item.title;
      form.elements.artist_id.value = item.artist_id;
      form.elements.year.value = item.year;
      form.querySelector(".primary").textContent = "Salvar alterações";
      form.scrollIntoView({ behavior: "smooth" });
      form.elements.title.focus();
    }
    if (remove) {
      const item = albums.find(x => x.id === Number(remove.dataset.delete));
      if (!item || !confirm("Excluir o álbum " + item.title + "?")) return;
      try { await api("/admin/albums/" + item.id, { method: "DELETE", body: {} }); toast("Álbum excluído"); document.dispatchEvent(new Event("route-refresh")); }
      catch (error) { toast(error.message); }
    }
  });
}

// Lista das mensagens enviadas pelo formulário de contato (salvas no localStorage da demonstração).
async function messages(el, tabs) {
  const items = await api("/admin/messages");
  const rows = items.map(m => `<tr class="${m.read ? "" : "unread"}"><td>${escape(m.name)}<br><small>${escape(m.email)}${m.phone ? " · " + escape(m.phone) : ""}</small></td><td>${escape(SUBJECTS[m.subject] || m.subject)}</td><td class="message-cell">${escape(m.message)}</td><td>${new Date(m.created_at).toLocaleString("pt-BR")}<br><span class="badge">${m.read ? "Lida" : "Nova"}</span></td><td><div class="admin-actions"><button class="pill" data-toggle="${m.id}">${m.read ? "Marcar como nova" : "Marcar como lida"}</button><button class="pill" data-remove="${m.id}">Excluir</button></div></td></tr>`).join("");
  el.innerHTML = `<div class="eyebrow">ÁREA ADMINISTRATIVA · DEMONSTRAÇÃO</div><h1>Mensagens de contato</h1>${tabs}${items.length ? `<div class="table-wrap"><table><caption>Mensagens recebidas pelo formulário de contato</caption><thead><tr><th scope="col">Remetente</th><th scope="col">Assunto</th><th scope="col">Mensagem</th><th scope="col">Data</th><th scope="col">Ações</th></tr></thead><tbody>${rows}</tbody></table></div>` : '<p class="empty">Nenhuma mensagem ainda. Envie uma pela página <a href="#/contact">Contato</a>.</p>'}`;
  el.addEventListener("click", async event => {
    const toggle = event.target.closest("[data-toggle]"), remove = event.target.closest("[data-remove]");
    try {
      if (toggle) await api("/admin/messages/" + toggle.dataset.toggle, { method: "PUT", body: {} });
      else if (remove) {
        if (!confirm("Excluir esta mensagem?")) return;
        await api("/admin/messages/" + remove.dataset.remove, { method: "DELETE", body: {} });
        toast("Mensagem excluída");
      } else return;
      document.dispatchEvent(new Event("route-refresh"));
    } catch (error) { toast(error.message); }
  });
}
