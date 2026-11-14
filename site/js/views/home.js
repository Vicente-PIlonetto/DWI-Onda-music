import { api, all, user } from "../api.js";
import { cards } from "../ui.js";
import { playTracks } from "../player.js";
export async function home(el) {
  const albums = await api("/albums?limit=8");
  const playlists = user ? await api("/playlists?limit=4") : { items: [] };
  el.innerHTML = `<section class="hero"><div class="hero-copy"><div class="eyebrow">DESCOBERTA DA SEMANA / VOL. 01</div><h1>Entre no seu<br>próprio ritmo.</h1><p>Novas frequências, novas possibilidades.<br>Um espaço para o som que combina com você.</p><button class="primary" id="hero-play">▶ &nbsp; Explorar os sons</button></div><div class="hero-art" aria-hidden="true"></div></section><div class="section-heading"><h2>Uma nova frequência</h2><a href="#/catalog">Ver todos os álbuns ↗</a></div>${cards(albums.items)}${playlists.items.length ? `<div class="section-heading"><h2>Suas playlists</h2><a href="#/library">Ver biblioteca ↗</a></div>${cards(playlists.items, "playlists")}` : ""}<div class="section-heading"><h2>Seu próximo encontro</h2><a href="#/search">Explorar catálogo ↗</a></div><p class="muted">Cinco artistas fictícios. Oito universos sonoros. Descubra nossa coleção de demonstração.</p>`;
  el.querySelector("#hero-play").onclick = async () => {
    try {
      playTracks(await all("/tracks"));
    } catch {
      location.hash = "/login";
    }
  };
}
export async function library(el) {
  if (!user) {
    location.hash = "/login";
    return;
  }
  const playlists = await all("/playlists");
  el.innerHTML = `<div class="eyebrow">FEITO POR VOCÊ</div><h1>Sua biblioteca.</h1><p class="muted">Cada playlist, um momento.</p><button class="primary" id="new-list">＋ Criar playlist</button><div class="section-heading"><h2>Suas seleções</h2></div>${playlists.length ? cards(playlists, "playlists") : '<p class="empty">Sua primeira playlist está esperando por você.</p>'}`;
  el.querySelector("#new-list").onclick = () =>
    document.querySelector("#create-playlist").click();
}
