import { api } from '../api.js';
import { escape, toast } from '../ui.js';
export async function catalog(el) {
  el.innerHTML = '<h1>Catálogo de álbuns</h1><p class="muted">Os álbuns cadastrados no painel aparecem aqui.</p><div class="table-wrap"><table><caption>Álbuns do catálogo</caption><thead><tr><th scope="col">Álbum</th><th scope="col">Artista</th><th scope="col">Ano</th></tr></thead><tbody id="album-cards"></tbody></table></div><button class="load-more">Carregar mais</button>';
  let cursor = null;
  const button = el.querySelector('button');
  async function load() {
    button.disabled = true;
    try {
      const page = await api('/albums?limit=8' + (cursor ? '&cursor=' + cursor : ''));
      el.querySelector('#album-cards').insertAdjacentHTML('beforeend', page.items.map(album => `<tr><td><a href="#/albums/${album.id}">${escape(album.title)}</a></td><td><a href="#/artists/${album.artist_id}">${escape(album.artist_name)}</a></td><td>${escape(album.year)}</td></tr>`).join(''));
      cursor = page.next_cursor; button.hidden = !cursor;
    } catch (error) { toast(error.message); }
    finally { button.disabled = false; }
  }
  button.addEventListener('click', load); await load();
}
