import { api } from "../api.js";
import { trackList } from "./tracks.js";
import { escape, cards } from "../ui.js";
export async function search(el) {
  el.innerHTML =
    '<h1>Buscar</h1><label for="query" class="visually-hidden">Buscar faixa, artista ou álbum</label><input id="query" class="search-input" type="search" placeholder="⌕  O que você quer ouvir?" maxlength="160" autocomplete="off"><div id="results" aria-live="polite"></div>';
  const input = el.querySelector("#query"),
    results = el.querySelector("#results");
  // Sem texto digitado, mostra os álbuns para navegar (como a tela "Navegar por todos").
  let browse = '<p class="empty">Experimente “Aurora”, “Mar Aberto” ou “Horizonte”.</p>';
  try {
    const albums = await api("/albums?limit=8");
    browse += `<div class="section-heading"><h2>Navegar por todos</h2><a href="#/catalog">Ver catálogo ↗</a></div>${cards(albums.items)}`;
  } catch { /* a busca continua funcionando sem a vitrine */ }
  el.querySelector("#results").innerHTML = browse;
  let timer,
    revision = 0,
    controller;
  async function run() {
    const version = ++revision;
    controller?.abort();
    controller = new AbortController();
    const q = input.value.trim();
    if (!q) {
      results.innerHTML = browse;
      return;
    }
    results.innerHTML = '<p class="empty">Buscando…</p>';
    let tracks = [],
      cursor = null,
      extras = "";
    async function load() {
      try {
        const page = await api(
          "/search?q=" +
            encodeURIComponent(q) +
            "&limit=20" +
            (cursor ? "&cursor=" + cursor : ""),
          { signal: controller.signal },
        );
        if (version !== revision || !el.isConnected) return;
        tracks.push(...page.items);
        if (!cursor) extras =
          (page.artists?.length ? `<div class="section-heading"><h2>Artistas</h2></div>${cards(page.artists, "artists")}` : "") +
          (page.albums?.length ? `<div class="section-heading"><h2>Álbuns</h2></div>${cards(page.albums)}` : "");
        cursor = page.next_cursor;
        const wrapper = document.createElement("div");
        await trackList(wrapper, tracks);
        if (version !== revision) return;
        results.innerHTML = extras + (extras && tracks.length ? '<div class="section-heading"><h2>Músicas</h2></div>' : "");
        if (tracks.length || !extras) results.append(wrapper);
        if (cursor) {
          const more = document.createElement("button");
          more.textContent = "Carregar mais";
          more.className = "load-more";
          more.onclick = () => {
            more.disabled = true;
            load();
          };
          results.append(more);
        }
      } catch (e) {
        if (e.name !== "AbortError" && version === revision)
          results.innerHTML = `<p class="error">${escape(e.message)}</p>`;
      }
    }
    await load();
  }
  input.oninput = () => {
    clearTimeout(timer);
    revision++;
    controller?.abort();
    timer = setTimeout(run, 250);
  };
  input.focus();
}
