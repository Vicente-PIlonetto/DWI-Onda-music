export const escape = (value) =>
  String(value ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        c
      ],
  );
export const time = (seconds) =>
  `${Math.floor((Number(seconds) || 0) / 60)}:${String(Math.floor((Number(seconds) || 0) % 60)).padStart(2, "0")}`;
export const cover = (path) =>
  !path || path === "placeholder.svg"
    ? "./assets/cover-default.svg"
    : path.startsWith("data:image/") ? path : "/covers/" + encodeURIComponent(path);
let timer;
export function toast(message) {
  const el = document.querySelector("#toast");
  el.textContent = message;
  clearTimeout(timer);
  timer = setTimeout(() => (el.textContent = ""), 4500);
}
export function cards(items, type = "albums") {
  return `<div class="cards">${items.map((x) => `<a class="card" href="#/${type}/${x.id}"><img class="cover" src="${cover(x.cover_path)}" alt="Capa de ${escape(x.title || x.name)}" loading="lazy"><div class="tag">${type === "albums" ? "Álbum · " + escape(x.year || "Independente") : "Sua seleção"}</div><h3>${escape(x.title || x.name)}</h3><p>${escape(x.artist_name || "Feita por você")}</p></a>`).join("")}</div>`;
}
