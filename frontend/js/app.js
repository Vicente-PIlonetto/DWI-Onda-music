import { api, all, session, user, isDemo } from "./api.js";
import { route } from "./router.js";
import { stop } from "./player.js";
import { escape, toast } from "./ui.js";
import { cover } from "./ui.js";
const $ = (s) => document.querySelector(s);
$(".skip").addEventListener("click", (event) => {
  event.preventDefault();
  $("#main").focus();
});
async function refreshLibrary() {
  $("#account").innerHTML = user
    ? `<span>${escape(user.name)}</span>${user.role === "admin" ? `<a href="${isDemo ? "#/admin" : "/admin/"}">Painel</a>` : ""}<button class="pill" id="logout">Sair</button>`
    : '<a class="pill" href="#/login">Entrar ↗</a>';
  if (user) {
    $("#logout").onclick = async () => {
      try {
        await api("/auth/logout", { method: "POST", body: {} });
        stop();
        await session();
        await refreshLibrary();
        location.hash = "/";
      } catch (e) {
        toast(e.message);
      }
    };
  }
  try {
    const lists = user ? await all("/playlists") : [];
    $("#sidebar-playlists").innerHTML = lists
      .map((p) => `<a class="playlist-link" href="#/playlists/${p.id}"><img class="playlist-thumb" src="${cover(p.cover_path, p)}" alt=""> <span>${escape(p.name)}</span></a>`)
      .join("");
    return lists;
  } catch (e) {
    toast(e.message);
    return [];
  }
}
$("#create-playlist").onclick = () => {
  if (!user) {
    location.hash = "/login";
    return;
  }
  $("#playlist-dialog").showModal();
};
$("#cancel-playlist").onclick = () => $("#playlist-dialog").close();
$("#playlist-form").onsubmit = async (e) => {
  e.preventDefault();
  try {
    const list = await api("/playlists", {
      method: "POST",
      body: Object.fromEntries(new FormData(e.target)),
    });
    $("#playlist-dialog").close();
    e.target.reset();
    await refreshLibrary();
    location.hash = "/playlists/" + list.id;
  } catch (error) {
    toast(error.message);
  }
};
let selectedTrack;
document.addEventListener("add-track", async (e) => {
  selectedTrack = e.detail;
  const lists = await refreshLibrary();
  if (!lists.length) {
    toast("Crie uma playlist primeiro");
    $("#playlist-dialog").showModal();
    return;
  }
  $("#add-form select").innerHTML = lists
    .map((p) => `<option value="${p.id}">${escape(p.name)}</option>`)
    .join("");
  $("#add-dialog").showModal();
});
$("#cancel-add").onclick = () => $("#add-dialog").close();
$("#add-form").onsubmit = async (e) => {
  e.preventDefault();
  try {
    const id = new FormData(e.target).get("playlist");
    await api("/playlists/" + id + "/tracks", {
      method: "POST",
      body: { track_id: selectedTrack },
    });
    $("#add-dialog").close();
    toast("Faixa adicionada à playlist");
  } catch (error) {
    toast(error.message);
  }
};
document.addEventListener("library-changed", refreshLibrary);
try {
  await session();
  await refreshLibrary();
} catch (e) {
  toast(e.message);
}
await route();
