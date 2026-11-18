import { api, all, user } from "../api.js";
import { cards, cover, escape, toast } from "../ui.js";
import { playTracks } from "../player.js";
// Saudação conforme a hora, como na tela inicial do player original.
function greeting() {
  const hour = new Date().getHours();
  return hour < 5 ? "Boa noite" : hour < 12 ? "Bom dia" : hour < 18 ? "Boa tarde" : "Boa noite";
}
// Atalhos: blocos horizontais com capa pequena (curtidas, playlists e álbuns).
function shortcuts(items) {
  return `<div class="shortcuts">${items
    .map(
      (x) => `<div class="shortcut"><a href="${x.href}"><img src="${x.cover}" alt="" loading="lazy"><span>${escape(x.title)}</span></a>${x.play ? `<button class="shortcut-play" data-play="${escape(x.play)}" aria-label="Reproduzir ${escape(x.title)}">▶</button>` : ""}</div>`,
    )
    .join("")}</div>`;
}
export async function home(el) {
  const albums = await api("/albums?limit=8");
  const playlists = user ? await api("/playlists?limit=4") : { items: [] };
  const quick = [
    ...(user ? [{ href: "#/likes", title: "Músicas Curtidas", cover: cover(null, { id: 3, title: "Curtidas" }), play: "/likes" }] : []),
    ...playlists.items.map((p) => ({ href: "#/playlists/" + p.id, title: p.name, cover: cover(p.cover_path, p), play: "/playlists/" + p.id })),
    ...albums.items.map((a) => ({ href: "#/albums/" + a.id, title: a.title, cover: cover(a.cover_path, a), play: "/albums/" + a.id })),
  ].slice(0, 8);
  el.innerHTML = `<h1 class="greeting">${greeting()}</h1>${shortcuts(quick)}
    <div class="section-heading"><h2>Álbuns</h2><a href="#/catalog">Mostrar tudo</a></div>${cards(albums.items)}
    ${playlists.items.length ? `<div class="section-heading"><h2>Suas playlists</h2><a href="#/library">Mostrar tudo</a></div>${cards(playlists.items, "playlists")}` : ""}`;
  el.querySelector(".shortcuts").onclick = async (event) => {
    const button = event.target.closest("[data-play]");
    if (!button) return;
    try {
      const path = button.dataset.play;
      playTracks(await all(path, path === "/likes" ? undefined : "tracks"));
    } catch {
      toast("Entre para ouvir");
    }
  };
}
export async function library(el) {
  if (!user) {
    location.hash = "/login";
    return;
  }
  const playlists = await all("/playlists");
  el.innerHTML = `<h1>Sua Biblioteca</h1><button class="primary" id="new-list">＋ Criar playlist</button><div class="section-heading"><h2>Playlists</h2></div>${playlists.length ? cards(playlists, "playlists") : '<p class="empty">Você ainda não criou playlists.</p>'}`;
  el.querySelector("#new-list").onclick = () =>
    document.querySelector("#create-playlist").click();
}
