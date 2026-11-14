import { api } from "../api.js";
import { trackList } from "./tracks.js";
import { escape } from "../ui.js";
export async function search(el) {
  el.innerHTML =
    '<div class="eyebrow">ENCONTRE SUA FREQUÊNCIA</div><h1>O que toca você?</h1><label for="query" class="muted">Busque uma faixa, artista ou álbum</label><p></p><input id="query" class="search-input" type="search" placeholder="⌕  Comece por um nome…" maxlength="160" autocomplete="off"><div id="results" aria-live="polite"><p class="empty">Experimente “Aurora”, “Mar Aberto” ou “Horizonte”.</p></div>';
  const input = el.querySelector("#query"),
    results = el.querySelector("#results");
  let timer,
    revision = 0,
    controller;
  async function run() {
    const version = ++revision;
    controller?.abort();
    controller = new AbortController();
    const q = input.value.trim();
    if (!q) {
      results.innerHTML =
        '<p class="empty">Um nome é o começo de uma descoberta.</p>';
      return;
    }
    results.innerHTML = '<p class="empty">Buscando…</p>';
    let tracks = [],
      cursor = null;
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
        cursor = page.next_cursor;
        const wrapper = document.createElement("div");
        await trackList(wrapper, tracks);
        if (version !== revision) return;
        results.replaceChildren(wrapper);
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
