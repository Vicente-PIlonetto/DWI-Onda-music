import { home, library } from "./views/home.js";
import { collection } from "./views/collection.js";
import { escape } from "./ui.js";
import { isDemo } from "./api.js";
let revision = 0;
export async function route() {
  const version = ++revision;
  const path = (location.hash.slice(1) || "/").split("?")[0];
  const el = document.createElement("div");
  el.className = "view";
  // Esqueleto com brilho enquanto os dados chegam (como no player original).
  el.innerHTML = `<div class="skeleton" aria-busy="true" aria-label="Carregando"><div class="sk sk-title"></div><div class="sk sk-line"></div><div class="sk-cards">${'<div class="sk sk-card"></div>'.repeat(4)}</div></div>`;
  document.querySelector(".workspace")?.style.removeProperty("--hero-color");
  const main = document.querySelector("#main");
  main.replaceChildren(el);
  document
    .querySelectorAll("nav>a")
    .forEach((a) => a.classList.toggle("active", a.hash === "#" + path));
  try {
    const match = path.match(/^\/(albums|artists|playlists)\/([0-9]+)$/);
    if (path === "/") await home(el);
    else if (path === "/catalog") { const { catalog } = await import("./views/catalog.js"); await catalog(el); }
    else if (path === "/library") await library(el);
    else if (path === "/settings") { const { settings } = await import("./views/settings.js"); settings(el); }
    else if (path === "/contact") {
      // No site com PHP o contato é uma página tradicional (/contato.php).
      if (!isDemo) { location.href = "/contato.php"; return; }
      const { contact } = await import("./views/contact.js");
      await contact(el);
    }
    else if (path === "/likes") await collection(el, "likes");
    else if (isDemo && ["/admin", "/admin/users", "/admin/messages"].includes(path)) {
      const { admin } = await import("./views/admin.js");
      await admin(el, path.split("/")[2] || "albums");
    }
    else if (match) await collection(el, match[1], match[2]);
    else if (path === "/login" || path === "/register") {
      const { auth } = await import("./views/auth.js");
      await auth(el, path === "/register");
    } else if (path === "/search") {
      const { search } = await import("./views/search.js");
      await search(el);
    } else
      el.innerHTML = `<section class="not-found" aria-labelledby="not-found-title">
        <p class="not-found-code" aria-hidden="true">404</p>
        <h1 id="not-found-title">Página não encontrada</h1>
        <p class="muted">O endereço <code>${escape(path)}</code> não existe.</p>
        <div class="not-found-actions"><a class="primary" href="#/">Voltar ao início</a><a class="pill" href="#/search">Buscar músicas</a></div>
      </section>`;
  } catch (error) {
    if (version === revision)
      el.innerHTML = `<h1>Algo deu errado</h1><p class="error">${escape(error.message)}</p><a class="pill" href="#/">Voltar ao início</a>`;
  }
}
window.addEventListener("hashchange", route);
document.addEventListener("route-refresh", route);
