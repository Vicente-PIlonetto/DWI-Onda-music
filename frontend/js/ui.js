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
// Pares de cores da identidade (preto, branco e azul) usados nas capas geradas.
const COVER_COLORS = [
  ["#0b1d26", "#add8e6"], ["#080808", "#3a6f86"], ["#12324a", "#e8f4f8"], ["#1d6a85", "#080808"],
  ["#202020", "#add8e6"], ["#0e3b4f", "#7fb8cc"], ["#162028", "#c9e6ef"], ["#05141c", "#5aa3bf"],
];
const svgText = (value) => String(value).replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
// Gera uma capa SVG única para cada item a partir do id e do título (sem imagens externas).
function generatedCover(seed) {
  const id = Number(seed.id) || 0;
  const [dark, light] = COVER_COLORS[id % COVER_COLORS.length];
  const cx = 60 + ((id * 37) % 180), cy = 60 + ((id * 53) % 180);
  const rings = Array.from({ length: 7 }, (_, i) => `<circle cx="${cx}" cy="${cy}" r="${18 + i * 22}" fill="none" stroke="${light}" stroke-opacity="${0.5 - i * 0.06}" stroke-width="1.5"/>`).join("");
  const title = svgText((seed.title || seed.name || "onda").slice(0, 22));
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${dark}"/><stop offset="1" stop-color="${light}" stop-opacity=".55"/></linearGradient><linearGradient id="s" x1="0" y1="0" x2="0" y2="1"><stop offset=".55" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".65"/></linearGradient></defs><rect width="300" height="300" fill="${dark}"/><rect width="300" height="300" fill="url(#g)"/>${rings}<circle cx="${cx}" cy="${cy}" r="7" fill="${light}"/><rect width="300" height="300" fill="url(#s)"/><text x="20" y="276" font-family="Inter, Arial, sans-serif" font-size="22" font-weight="800" fill="#ffffff">${title}</text></svg>`;
  return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
}
// seed (opcional): item com id e título; sem capa enviada, gera uma capa própria.
export const cover = (path, seed) =>
  !path || path === "placeholder.svg"
    ? seed ? generatedCover(seed) : "/assets/cover-default.svg"
    : path.startsWith("data:image/") ? path : "/covers/" + encodeURIComponent(path);
let timer;
export function toast(message) {
  const el = document.querySelector("#toast");
  el.textContent = message;
  clearTimeout(timer);
  timer = setTimeout(() => (el.textContent = ""), 4500);
}
export function cards(items, type = "albums") {
  return `<div class="cards">${items.map((x) => `<a class="card" href="#/${type}/${x.id}"><span class="cover-wrap"><img class="cover" src="${cover(x.cover_path, x)}" alt="Capa de ${escape(x.title || x.name)}" loading="lazy"><span class="card-play" aria-hidden="true">▶</span></span><div class="tag">${type === "albums" ? "Álbum · " + escape(x.year || "Independente") : "Sua seleção"}</div><h3>${escape(x.title || x.name)}</h3><p>${escape(x.artist_name || "Feita por você")}</p></a>`).join("")}</div>`;
}
