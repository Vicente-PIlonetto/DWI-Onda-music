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
// Caminho SVG de uma senoide (y = meio − amplitude·sen(2πx/período + fase)).
function sinePath(width, mid, amp, period, phase, step = 8) {
  let d = "";
  for (let x = 0; x <= width; x += step)
    d += (x ? "L" : "M") + x + " " + (mid - amp * Math.sin((2 * Math.PI * x) / period + phase)).toFixed(1);
  return d;
}
// Gera uma capa SVG única para cada item a partir do id: fundo em degradê e camadas de ondas.
function generatedCover(seed) {
  const id = Number(seed.id) || 0;
  let state = (id * 2654435761) % 4294967296 || 1;
  const random = () => ((state = (state * 1664525 + 1013904223) % 4294967296) / 4294967296);
  const hue = (id * 47 + 190) % 360;
  const period = 160 + random() * 180, amp = 14 + random() * 26, phase = random() * 6.28, tilt = random() * 0.8 - 0.4;
  const waves = Array.from({ length: 9 }, (_, i) =>
    `<path d="${sinePath(300, 70 + i * 22 + i * i * tilt, amp * (0.5 + i * 0.12), period, phase + i * 0.35)}" stroke-opacity="${(0.12 + i * 0.09).toFixed(2)}"/>`,
  ).join("");
  const hill = sinePath(300, 236, amp * 0.9, period * 1.3, phase + 1.2);
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"><defs><linearGradient id="b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl(${hue} 45% 12%)"/><stop offset="1" stop-color="hsl(${(hue + 40) % 360} 55% 30%)"/></linearGradient></defs><rect width="300" height="300" fill="url(#b)"/><g fill="none" stroke="hsl(${(hue + 20) % 360} 85% 80%)" stroke-width="2" stroke-linecap="round">${waves}</g><path d="${hill} L300 300 L0 300Z" fill="hsl(${(hue + 20) % 360} 80% 75%)" fill-opacity=".14"/></svg>`;
  return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
}
// seed (opcional): item com id e título; sem capa enviada, gera uma capa própria.
export const cover = (path, seed) =>
  !path || path === "placeholder.svg"
    ? seed ? generatedCover(seed) : "/assets/cover-default.svg"
    : path.startsWith("data:image/") ? path : "/covers/" + encodeURIComponent(path);
// Cor média de uma imagem (desenhada num canvas 8x8), usada no degradê do topo das páginas.
export async function dominantColor(img) {
  try {
    await img.decode();
    const canvas = document.createElement("canvas");
    canvas.width = canvas.height = 8;
    const context = canvas.getContext("2d", { willReadFrequently: true });
    context.drawImage(img, 0, 0, 8, 8);
    const data = context.getImageData(0, 0, 8, 8).data;
    let r = 0, g = 0, b = 0;
    for (let i = 0; i < data.length; i += 4) { r += data[i]; g += data[i + 1]; b += data[i + 2]; }
    const n = data.length / 4;
    // Mantém o tom (matiz) da capa, mas com saturação e luminosidade fixas, como no player original.
    [r, g, b] = [r / n / 255, g / n / 255, b / n / 255];
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    let h = 0;
    if (d) h = max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
    h = Math.round(h * 60 + 360) % 360;
    const saturation = d < 0.04 ? 0 : 55; // capa quase cinza continua neutra
    return `hsl(${h} ${saturation}% 42%)`;
  } catch {
    return null; // imagem de outra origem ou que não carregou: fica sem degradê
  }
}
// Reinicia uma animação CSS no elemento (remove a classe, força o reflow e coloca de novo).
export function replay(el, className) {
  if (!el) return;
  el.classList.remove(className);
  void el.offsetWidth;
  el.classList.add(className);
}
let timer;
export function toast(message) {
  const el = document.querySelector("#toast");
  el.textContent = message;
  clearTimeout(timer);
  timer = setTimeout(() => (el.textContent = ""), 4500);
}
export function cards(items, type = "albums") {
  return `<div class="cards">${items.map((x) => `<a class="card" href="#/${type}/${x.id}"><span class="cover-wrap"><img class="cover" src="${cover(x.cover_path, x)}" alt="Capa de ${escape(x.title || x.name)}" loading="lazy"><span class="card-play" aria-hidden="true">▶</span></span><div class="tag">${type === "albums" ? "Álbum · " + escape(x.year || "Independente") : type === "artists" ? "Artista" : "Sua seleção"}</div><h3>${escape(x.title || x.name)}</h3><p>${type === "artists" ? "" : escape(x.artist_name || "Feita por você")}</p></a>`).join("")}</div>`;
}
