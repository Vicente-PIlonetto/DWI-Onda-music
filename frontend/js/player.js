import { user, isDemo } from "./api.js";
import { cover, escape, time, toast, replay } from "./ui.js";
const audio = document.querySelector("#audio");
let queue = [],
  index = -1,
  shuffle = false,
  repeat = 0;
let demoAudioUrl;
function demoTone(id) {
  const rate = 8000, seconds = 5, count = rate * seconds;
  const buffer = new ArrayBuffer(44 + count * 2), view = new DataView(buffer);
  const word = (offset, value) => [...value].forEach((character, i) => view.setUint8(offset + i, character.charCodeAt(0)));
  word(0, 'RIFF'); view.setUint32(4, 36 + count * 2, true); word(8, 'WAVE'); word(12, 'fmt ');
  view.setUint32(16, 16, true); view.setUint16(20, 1, true); view.setUint16(22, 1, true);
  view.setUint32(24, rate, true); view.setUint32(28, rate * 2, true); view.setUint16(32, 2, true); view.setUint16(34, 16, true);
  word(36, 'data'); view.setUint32(40, count * 2, true);
  const frequency = 220 + (id % 12) * 24;
  for (let i = 0; i < count; i++) {
    const fade = Math.min(1, i / 800, (count - i) / 1600);
    view.setInt16(44 + i * 2, Math.sin(2 * Math.PI * frequency * i / rate) * 4500 * fade, true);
  }
  return URL.createObjectURL(new Blob([buffer], { type: 'audio/wav' }));
}
const $ = (s) => document.querySelector(s);
export function playTracks(tracks, start = 0) {
  if (!user) {
    location.hash = "/login";
    toast("Entre para ouvir");
    return;
  }
  queue = [...tracks];
  index = start;
  load();
}
export function stop() {
  audio.pause();
  audio.removeAttribute("src");
  audio.load();
  if (demoAudioUrl) { URL.revokeObjectURL(demoAudioUrl); demoAudioUrl = null; }
  queue = [];
  index = -1;
  $("#now-title").textContent = "Nada tocando";
  $("#now-artist").textContent = "";
  renderQueue();
  markNowPlaying();
}
function load() {
  const track = queue[index];
  if (!track) return;
  if (demoAudioUrl) URL.revokeObjectURL(demoAudioUrl);
  demoAudioUrl = isDemo ? demoTone(track.id) : null;
  audio.src = demoAudioUrl || "/api/stream/" + track.id;
  $("#now-title").textContent = track.title;
  $("#now-artist").textContent = track.artist_name;
  $("#now-cover").src = cover(track.cover_path, { id: track.album_id, title: track.album_title });
  replay(document.querySelector(".now"), "swap");
  updateMediaSession(track);
  audio
    .play()
    .catch(() =>
      toast("Não foi possível tocar. Confira sua sessão e o arquivo de áudio."),
    );
  renderQueue();
  markNowPlaying();
}
// Destaca na lista de faixas a música atual (o CSS mostra o equalizador enquanto toca).
export function markNowPlaying() {
  const id = String(queue[index]?.id ?? "");
  document.querySelectorAll(".track-row[data-track-id]").forEach((row) =>
    row.classList.toggle("current", row.dataset.trackId === id),
  );
}
// Preenchimento das barras (progresso e volume): o CSS usa --pct no gradiente do fundo.
function fill(input) {
  const max = Number(input.max) || 1;
  input.style.setProperty("--pct", (Number(input.value) / max) * 100 + "%");
}
// Media Session API: mostra a faixa e os controles na tela de bloqueio do
// celular, na central de mídia do sistema e nas teclas de mídia do teclado.
function updateMediaSession(track) {
  if (!("mediaSession" in navigator)) return;
  navigator.mediaSession.metadata = new MediaMetadata({
    title: track.title,
    artist: track.artist_name,
    album: track.album_title || "",
    artwork: [{ src: $("#now-cover").src, sizes: "300x300" }],
  });
}
if ("mediaSession" in navigator) {
  const actions = {
    play: () => audio.play().catch(() => {}),
    pause: () => audio.pause(),
    previoustrack: () => move(-1),
    nexttrack: () => move(1),
    seekto: (details) => { audio.currentTime = details.seekTime; },
  };
  for (const [action, handler] of Object.entries(actions)) {
    try { navigator.mediaSession.setActionHandler(action, handler); } catch { /* ação não suportada neste navegador */ }
  }
}
function move(step, ended = false) {
  if (!queue.length) return;
  if (ended && repeat === 2) {
    audio.currentTime = 0;
    audio.play().catch(() => {});
    return;
  }
  let next = index + step;
  if (shuffle && step > 0 && queue.length > 1) {
    next =
      (index + 1 + Math.floor(Math.random() * (queue.length - 1))) %
      queue.length;
  }
  if (next >= queue.length) {
    if (!repeat) {
      audio.pause();
      return;
    }
    next = 0;
  }
  if (next < 0) next = queue.length - 1;
  index = next;
  load();
}
function renderQueue() {
  $("#queue-items").innerHTML =
    queue
      .map(
        (t, i) =>
          `<li class="${i === index ? "current" : ""}"><button data-index="${i}">${escape(t.title)}<small>${escape(t.artist_name)}</small></button></li>`,
      )
      .join("") || "<li>A fila está vazia.</li>";
}
$("#queue-items").onclick = (e) => {
  const button = e.target.closest("[data-index]");
  if (button) {
    index = Number(button.dataset.index);
    load();
  }
};
$("#play").onclick = () => {
  if (!audio.src) {
    toast("Escolha uma faixa para começar");
    return;
  }
  if (audio.paused)
    audio.play().catch(() => toast("Não foi possível reproduzir"));
  else audio.pause();
};
$("#previous").onclick = () => {
  if (audio.currentTime > 3) audio.currentTime = 0;
  else move(-1);
};
$("#next").onclick = () => move(1);
$("#shuffle").onclick = () => {
  shuffle = !shuffle;
  $("#shuffle").setAttribute("aria-pressed", String(shuffle));
};
$("#repeat").onclick = () => {
  repeat = (repeat + 1) % 3;
  $("#repeat").classList.toggle("enabled", repeat > 0);
  $("#repeat").textContent = repeat === 2 ? "↻¹" : "↻";
  $("#repeat").setAttribute(
    "aria-label",
    "Repetição: " + ["desligada", "fila", "faixa"][repeat],
  );
};
$("#seek").oninput = (e) => {
  if (Number.isFinite(audio.duration))
    audio.currentTime = Number(e.target.value);
  fill(e.target);
};
for (const selector of ["#volume", "#queue-volume"]) {
  $(selector).oninput = (e) => {
    audio.volume = Number(e.target.value);
    $("#volume").value = e.target.value;
    $("#queue-volume").value = e.target.value;
    fill($("#volume"));
    fill($("#queue-volume"));
  };
}
// Volume: começa em 65% ou, se a preferência "Lembrar o volume" estiver ligada, no último usado.
function setVolume(value) {
  audio.volume = value;
  $("#volume").value = String(value);
  $("#queue-volume").value = String(value);
  fill($("#volume"));
  fill($("#queue-volume"));
}
let savedVolume = NaN;
try { savedVolume = parseFloat(localStorage.getItem("onda-volume")); } catch { /* sem armazenamento */ }
setVolume(window.OndaPrefs?.get().rememberVolume && savedVolume >= 0 && savedVolume <= 1 ? savedVolume : 0.65);
audio.addEventListener("volumechange", () => {
  if (!window.OndaPrefs?.get().rememberVolume) return;
  try { localStorage.setItem("onda-volume", String(audio.volume)); } catch { /* ignora */ }
});
for (const event of [
  "timeupdate",
  "loadedmetadata",
  "durationchange",
  "emptied",
])
  audio.addEventListener(event, () => {
    const duration = Number.isFinite(audio.duration) ? audio.duration : 0;
    $("#seek").max = String(duration || 100);
    $("#seek").value = String(audio.currentTime || 0);
    fill($("#seek"));
    $("#current").textContent = time(audio.currentTime);
    $("#duration").textContent = time(duration);
  });
for (const event of ["play", "pause", "ended"])
  audio.addEventListener(event, () => {
    $("#play").textContent = audio.paused ? "▶" : "Ⅱ";
    $("#play").setAttribute(
      "aria-label",
      audio.paused ? "Reproduzir" : "Pausar",
    );
    document.body.classList.toggle("is-playing", !audio.paused);
    replay($("#play"), "pop");
    if ("mediaSession" in navigator)
      navigator.mediaSession.playbackState = audio.paused ? "paused" : "playing";
  });
audio.addEventListener("ended", () => move(1, true));
audio.addEventListener("error", () => {
  if (audio.getAttribute("src"))
    toast("Áudio indisponível. Entre novamente ou confira o arquivo.");
});
function toggleQueue(show) {
  $("#queue").hidden = !show;
  $("#queue-toggle").setAttribute("aria-expanded", String(show));
}
$("#queue-toggle").onclick = () => toggleQueue($("#queue").hidden);
$("#queue-close").onclick = () => toggleQueue(false);
