import { api, all, user } from "../api.js";
import { cards, cover, escape, toast } from "../ui.js";
import { playTracks } from "../player.js";
import { trackList } from "./tracks.js";
export async function collection(el, type, id) {
  if (type === "likes" && !user) {
    location.hash = "/login";
    return;
  }
  let item,
    tracks = [];
  if (type === "likes") {
    item = { name: "Músicas Curtidas" };
    tracks = await all("/likes");
  } else {
    item = await api("/" + type + "/" + id);
    if (type !== "artists") tracks = await all("/" + type + "/" + id, "tracks");
  }
  el.innerHTML = `<div class="collection-header"><img src="${cover(item.cover_path, type === "likes" ? { id: 3, title: "Curtidas" } : item)}" alt="Capa de ${escape(item.title || item.name)}"><div><div class="eyebrow">${{ albums: "ÁLBUM", artists: "ARTISTA", playlists: "PLAYLIST", likes: "SUA COLEÇÃO" }[type]}</div><h1>${escape(item.title || item.name)}</h1><p class="muted">${escape(item.artist_name || "Seu espaço, sua frequência")}${item.year ? " · " + item.year : ""}</p>${type === "playlists" ? `<form id="playlist-cover-form" class="playlist-cover-form"><label for="playlist-cover">Capa da playlist</label><input id="playlist-cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" required><button class="pill">Enviar capa</button>${item.cover_path ? '<button class="pill" id="remove-cover" type="button">Remover capa</button>' : ""}</form>` : ""}</div></div>${type === "artists" ? cards(await all("/artists/" + id, "albums")) : `<button class="primary" id="collection-play">▶ Reproduzir</button> ${type === "playlists" ? '<button class="pill" id="rename">Renomear</button> <button class="pill" id="delete">Excluir</button>' : ""}<div class="track-list"></div>`}`;
  if (type !== "artists") {
    await trackList(
      el.querySelector(".track-list"),
      tracks,
      type === "playlists" ? id : null,
    );
    el.querySelector("#collection-play").onclick = () => playTracks(tracks);
  }
  if (type === "playlists") {
    el.querySelector("#playlist-cover-form").onsubmit = async (event) => {
      event.preventDefault();
      try {
        await api("/playlists/" + id + "/cover", { method: "POST", body: new FormData(event.currentTarget) });
        toast("Capa atualizada");
        document.dispatchEvent(new Event("library-changed"));
        document.dispatchEvent(new Event("route-refresh"));
      } catch (error) { toast(error.message); }
    };
    el.querySelector("#remove-cover")?.addEventListener("click", async () => {
      try {
        await api("/playlists/" + id + "/cover", { method: "DELETE", body: {} });
        toast("Capa removida");
        document.dispatchEvent(new Event("library-changed"));
        document.dispatchEvent(new Event("route-refresh"));
      } catch (error) { toast(error.message); }
    });
    el.querySelector("#rename").onclick = async () => {
      const name = prompt("Novo nome", item.name);
      if (!name) return;
      try {
        await api("/playlists/" + id, { method: "PUT", body: { name } });
        document.dispatchEvent(new Event("library-changed"));
        document.dispatchEvent(new Event("route-refresh"));
      } catch (e) {
        toast(e.message);
      }
    };
    el.querySelector("#delete").onclick = async () => {
      if (!confirm("Excluir esta playlist?")) return;
      try {
        await api("/playlists/" + id, { method: "DELETE", body: {} });
        document.dispatchEvent(new Event("library-changed"));
        location.hash = "/library";
      } catch (e) {
        toast(e.message);
      }
    };
  }
}
