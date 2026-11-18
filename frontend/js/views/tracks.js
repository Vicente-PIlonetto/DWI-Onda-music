import { api, all, user } from "../api.js";
import { escape, time, toast, replay } from "../ui.js";
import { playTracks, markNowPlaying } from "../player.js";
export async function trackList(container, tracks, playlistId = null) {
  let likes = new Set();
  if (user) likes = new Set((await all("/likes")).map((x) => String(x.id)));
  container.innerHTML = tracks.length
    ? `<div class="track-row header"><span>#</span><span>TÍTULO</span><span class="album-col">ÁLBUM</span><span>TEMPO</span><span></span><span></span></div>` +
      tracks
        .map(
          (t, i) =>
            `<div class="track-row" data-track-id="${t.id}"><button class="track-index" data-play="${i}" aria-label="Reproduzir ${escape(t.title)}"><span class="num">${i + 1}</span><span class="row-play" aria-hidden="true">▶</span><span class="eq" aria-hidden="true"><i></i><i></i><i></i></span></button><div><button class="track-name" data-play="${i}">${escape(t.title)}</button><small><a href="#/artists/${t.artist_id}">${escape(t.artist_name)}</a></small></div><a class="album-col" href="#/albums/${t.album_id}">${escape(t.album_title)}</a><span class="muted">${time(t.duration_seconds)}</span><button data-like="${t.id}" class="${likes.has(String(t.id)) ? "liked" : ""}" aria-label="Curtir ${escape(t.title)}" aria-pressed="${likes.has(String(t.id))}">${likes.has(String(t.id)) ? "♥" : "♡"}</button><button data-add="${t.id}" aria-label="${playlistId ? "Remover da" : "Adicionar à"} playlist">${playlistId ? "−" : "＋"}</button></div>`,
        )
        .join("")
    : '<p class="empty">Nenhuma faixa por aqui ainda.</p>';
  markNowPlaying();
  container.onclick = async (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    try {
      if (btn.dataset.play !== undefined)
        playTracks(tracks, Number(btn.dataset.play));
      if (btn.dataset.like) {
        if (!user) {
          location.hash = "/login";
          return;
        }
        const id = btn.dataset.like;
        await api("/likes/" + id, {
          method: likes.has(id) ? "DELETE" : "POST",
          body: {},
        });
        if (likes.has(id)) likes.delete(id);
        else likes.add(id);
        btn.classList.toggle("liked", likes.has(id));
        btn.textContent = likes.has(id) ? "♥" : "♡";
        if (likes.has(id)) replay(btn, "pop");
        btn.setAttribute("aria-pressed", String(likes.has(id)));
      }
      if (btn.dataset.add) {
        if (!user) {
          location.hash = "/login";
          return;
        }
        if (playlistId) {
          await api("/playlists/" + playlistId + "/tracks", {
            method: "DELETE",
            body: { track_id: Number(btn.dataset.add) },
          });
          document.dispatchEvent(new Event("route-refresh"));
        } else
          document.dispatchEvent(
            new CustomEvent("add-track", { detail: Number(btn.dataset.add) }),
          );
      }
    } catch (error) {
      toast(error.message);
    }
  };
}
