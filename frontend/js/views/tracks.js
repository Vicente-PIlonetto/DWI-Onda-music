import { api, all, user } from "../api.js";
import { escape, time, toast, replay } from "../ui.js";
import { playTracks, markNowPlaying } from "../player.js";
export async function trackList(container, tracks, playlistId = null) {
  let likes = new Set();
  if (user) likes = new Set((await all("/likes")).map((x) => String(x.id)));
  const reorder = playlistId && tracks.length > 1;
  container.innerHTML = tracks.length
    ? (reorder ? '<p class="muted reorder-hint">Arraste as faixas, ou use Alt + ↑/↓, para mudar a ordem.</p>' : "") +
      `<div class="track-row header"><span>#</span><span>TÍTULO</span><span class="album-col">ÁLBUM</span><span>TEMPO</span><span></span><span></span></div>` +
      tracks
        .map(
          (t, i) =>
            `<div class="track-row" data-track-id="${t.id}"${reorder ? ' draggable="true"' : ""}><button class="track-index" data-play="${i}" aria-label="Reproduzir ${escape(t.title)}"><span class="num">${i + 1}</span><span class="row-play" aria-hidden="true">▶</span><span class="eq" aria-hidden="true"><i></i><i></i><i></i></span></button><div><button class="track-name" data-play="${i}">${escape(t.title)}</button><small><a href="#/artists/${t.artist_id}">${escape(t.artist_name)}</a></small></div><a class="album-col" href="#/albums/${t.album_id}">${escape(t.album_title)}</a><span class="muted">${time(t.duration_seconds)}</span><button data-like="${t.id}" class="${likes.has(String(t.id)) ? "liked" : ""}" aria-label="Curtir ${escape(t.title)}" aria-pressed="${likes.has(String(t.id))}">${likes.has(String(t.id)) ? "♥" : "♡"}</button><button data-add="${t.id}" aria-label="${playlistId ? "Remover da" : "Adicionar à"} playlist">${playlistId ? "−" : "＋"}</button></div>`,
        )
        .join("")
    : '<p class="empty">Nenhuma faixa por aqui ainda.</p>';
  markNowPlaying();
  if (reorder) enableReorder(container, tracks, playlistId);
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
// Reordenação da playlist: arrastar a linha ou Alt + ↑/↓ com o foco na faixa.
function enableReorder(container, tracks, playlistId) {
  const rows = () => [...container.querySelectorAll(".track-row[data-track-id]")];
  let from = -1;
  async function move(source, target) {
    if (target < 0 || target >= tracks.length || source === target) return;
    const previous = [...tracks];
    const [track] = tracks.splice(source, 1);
    tracks.splice(target, 0, track); // muda o array original: "Reproduzir" segue a nova ordem
    try {
      await api("/playlists/" + playlistId + "/tracks", {
        method: "PUT",
        body: { track_ids: tracks.map((t) => t.id) },
      });
    } catch (error) {
      tracks.splice(0, tracks.length, ...previous);
      toast(error.message);
    }
    await trackList(container, tracks, playlistId);
    container.querySelector(`.track-row[data-track-id="${track.id}"] .track-name`)?.focus();
  }
  container.ondragstart = (e) => {
    const row = e.target.closest?.(".track-row[data-track-id]");
    if (!row) return;
    from = rows().indexOf(row);
    row.classList.add("dragging");
    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData("text/plain", row.dataset.trackId);
  };
  container.ondragover = (e) => {
    const row = e.target.closest(".track-row[data-track-id]");
    if (from < 0 || !row) return;
    e.preventDefault();
    rows().forEach((r) => r.classList.toggle("drop-target", r === row));
  };
  container.ondragend = () => {
    from = -1;
    rows().forEach((r) => r.classList.remove("dragging", "drop-target"));
  };
  container.ondrop = (e) => {
    const row = e.target.closest(".track-row[data-track-id]");
    if (from < 0 || !row) return;
    e.preventDefault();
    const source = from;
    container.ondragend();
    move(source, rows().indexOf(row));
  };
  container.onkeydown = (e) => {
    if (!e.altKey || (e.key !== "ArrowUp" && e.key !== "ArrowDown")) return;
    const row = e.target.closest(".track-row[data-track-id]");
    if (!row) return;
    e.preventDefault();
    const i = rows().indexOf(row);
    move(i, i + (e.key === "ArrowUp" ? -1 : 1));
  };
}
