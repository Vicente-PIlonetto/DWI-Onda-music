// Estado fictício da Parte 2, salvo apenas neste navegador.
const key = "onda-parte2-v1";
let catalog, state;
const fail = message => { throw new Error(message); };
function data() {
  if (!state) {
    try { state = JSON.parse(localStorage.getItem(key)) || {}; } catch { state = {}; }
    state.users ||= [{ id: 1, name: "Pessoa de demonstração", email: "demo@onda.test", role: "user" }, { id: 2, name: "Admin de demonstração", email: "admin@onda.test", role: "admin" }];
    state.playlists ||= [{ id: 1, owner_id: 1, name: "Minha playlist", track_ids: [1, 2] }];
    state.likes ||= [1];
  }
  return state;
}
const save = () => localStorage.setItem(key, JSON.stringify(state));
const account = () => data().users.find(x => x.id === state.currentUserId) || null;
const needUser = () => account() || fail("Entre para usar esta função.");
const needAdmin = () => account()?.role === "admin" || fail("Acesso reservado ao administrador.");
const page = (rows, url) => {
  const cursor = Number(url.searchParams.get("cursor") || 0);
  const limit = Math.max(1, Math.min(100, Number(url.searchParams.get("limit") || 20)));
  const rest = rows.filter(x => x.id > cursor).sort((a, b) => a.id - b.id);
  const items = rest.slice(0, limit);
  return { items, next_cursor: rest.length > limit ? String(items.at(-1).id) : null };
};
const list = id => data().playlists.find(x => x.id === Number(id) && x.owner_id === account()?.id) || fail("Playlist não encontrada.");
const withCover = x => ({ ...x, cover_path: x.cover_path || "placeholder.svg" });
async function readCover(file) {
  if (!file || !["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 3 * 1024 * 1024) fail("Escolha JPEG, PNG ou WebP de até 3 MB.");
  return await new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error("Não foi possível ler a imagem."));
    reader.readAsDataURL(file);
  });
}
export async function demoApi(path, options = {}) {
  const method = options.method || "GET", url = new URL(path, location.origin), body = options.body || {};
  data();
  if (!catalog) {
    const response = await fetch(new URL("../demo/catalog.json", import.meta.url));
    if (!response.ok) fail("Catálogo de demonstração indisponível.");
    catalog = await response.json();
  }
  const albums = [...catalog.albums, ...(state.albums || [])], tracks = catalog.tracks;
  if (url.pathname === "/auth/me") return { user: account(), csrf_token: "" };
  if (url.pathname === "/auth/logout" && method === "POST") { state.currentUserId = null; save(); return {}; }
  if (url.pathname === "/auth/login" && method === "POST") {
    const found = state.users.find(x => x.email.toLowerCase() === String(body.email || "").toLowerCase());
    if (!found) fail("Use demo@onda.test ou admin@onda.test nesta demonstração.");
    state.currentUserId = found.id; save(); return { user: found };
  }
  if (url.pathname === "/auth/register" && method === "POST") {
    if (state.users.some(x => x.email.toLowerCase() === String(body.email).toLowerCase())) fail("Este email já está cadastrado.");
    const user = { id: Date.now(), name: String(body.name).trim(), email: String(body.email).trim(), role: "user" };
    state.users.push(user); state.currentUserId = user.id; save(); return { user };
  }
  if (url.pathname === "/tracks" && method === "GET") return page(tracks, url);
  if (url.pathname === "/tracks/random" && method === "GET") return { items: [...tracks].sort(() => Math.random() - 0.5).slice(0, 50) };
  if (url.pathname === "/albums" && method === "GET") return page(albums, url);
  if (url.pathname === "/artists" && method === "GET") return page(catalog.artists, url);
  if (url.pathname === "/search" && method === "GET") {
    const q = (url.searchParams.get("q") || "").toLocaleLowerCase();
    return page(tracks.filter(t => [t.title, t.album_title, t.artist_name].some(v => v.toLocaleLowerCase().includes(q))), url);
  }
  let match = url.pathname.match(/^\/(albums|artists)\/(\d+)$/);
  if (match && method === "GET") {
    const item = (match[1] === "albums" ? albums : catalog.artists).find(x => x.id === Number(match[2])) || fail("Não encontrado.");
    return { ...item, tracks: page(tracks.filter(t => t.album_id === item.id), url), albums: page(albums.filter(a => a.artist_id === item.id), url) };
  }
  if (url.pathname === "/playlists" && method === "GET") { needUser(); return page(state.playlists.filter(x => x.owner_id === account().id).map(withCover), url); }
  if (url.pathname === "/playlists" && method === "POST") {
    needUser(); const name = String(body.name || "").trim() || fail("Informe o nome da playlist.");
    const item = { id: Date.now(), owner_id: account().id, name, track_ids: [] }; state.playlists.push(item); save(); return item;
  }
  match = url.pathname.match(/^\/playlists\/(\d+)(?:\/(tracks|cover))?$/);
  if (match) {
    needUser(); const item = list(match[1]), operation = match[2];
    if (!operation && method === "GET") return { ...withCover(item), tracks: page(tracks.filter(t => item.track_ids.includes(t.id)), url) };
    if (!operation && method === "PUT") { item.name = String(body.name || "").trim() || fail("Informe o nome."); save(); return item; }
    if (!operation && method === "DELETE") { state.playlists = state.playlists.filter(x => x.id !== item.id); save(); return {}; }
    if (operation === "tracks" && method === "POST") { const id = Number(body.track_id); if (!tracks.some(t => t.id === id)) fail("Faixa não encontrada."); if (item.track_ids.includes(id)) fail("Esta faixa já está na playlist."); item.track_ids.push(id); save(); return {}; }
    if (operation === "tracks" && method === "DELETE") { item.track_ids = item.track_ids.filter(x => x !== Number(body.track_id)); save(); return {}; }
    if (operation === "cover" && method === "POST") { item.cover_path = await readCover(body.get("cover")); save(); return {}; }
    if (operation === "cover" && method === "DELETE") { delete item.cover_path; save(); return {}; }
  }
  if (url.pathname === "/likes" && method === "GET") { needUser(); return page(tracks.filter(t => state.likes.includes(t.id)), url); }
  match = url.pathname.match(/^\/likes\/(\d+)$/);
  if (match) { needUser(); const id = Number(match[1]); if (method === "POST" && !state.likes.includes(id)) state.likes.push(id); if (method === "DELETE") state.likes = state.likes.filter(x => x !== id); save(); return {}; }
  if (url.pathname === "/contact" && method === "POST") {
    // Mesmas regras do PHP: campos válidos e no máximo 3 mensagens por email em 24 horas.
    const name = String(body.name || "").trim(), email = String(body.email || "").trim().toLowerCase(), text = String(body.message || "").trim();
    if (!/^[\p{L}' ]{2,160}$/u.test(name)) fail("Use apenas letras e espaços no nome.");
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) fail("Informe um email válido.");
    if (text.length < 10 || text.length > 2000) fail("A mensagem deve ter de 10 a 2000 caracteres.");
    if (body.consent !== "1") fail("Confirme que os dados enviados são fictícios.");
    state.messages ||= [];
    const dayAgo = Date.now() - 24 * 60 * 60 * 1000;
    if (state.messages.filter(m => m.email === email && m.created_at > dayAgo).length >= 3) fail("Este email já enviou 3 mensagens nas últimas 24 horas. Tente amanhã.");
    const item = { id: Date.now(), name, email, phone: String(body.phone || "").trim(), subject: String(body.subject || "outro"), message: text, read: false, created_at: Date.now() };
    state.messages.push(item); save(); return item;
  }
  if (url.pathname === "/admin/messages" && method === "GET") { needAdmin(); return [...(state.messages || [])].reverse(); }
  match = url.pathname.match(/^\/admin\/messages\/(\d+)$/);
  if (match) {
    needAdmin(); const item = (state.messages || []).find(x => x.id === Number(match[1])) || fail("Mensagem não encontrada.");
    if (method === "PUT") { item.read = !item.read; save(); return item; }
    if (method === "DELETE") { state.messages = state.messages.filter(x => x.id !== item.id); save(); return {}; }
  }
  if (url.pathname === "/admin/users" && method === "GET") { needAdmin(); return state.users; }
  if (url.pathname === "/admin/albums" && method === "GET") { needAdmin(); return albums; }
  if (url.pathname === "/admin/albums" && method === "POST") {
    needAdmin(); const artist = catalog.artists.find(x => x.id === Number(body.artist_id)) || fail("Escolha um artista.");
    const title = String(body.title || "").trim() || fail("Informe o título.");
    const item = { id: Date.now(), title, year: Number(body.year), artist_id: artist.id, artist_name: artist.name, cover_path: "placeholder.svg" };
    state.albums ||= []; state.albums.push(item); save(); return item;
  }
  match = url.pathname.match(/^\/admin\/albums\/(\d+)$/);
  if (match) {
    needAdmin(); const item = (state.albums || []).find(x => x.id === Number(match[1])) || fail("Só é possível editar álbuns criados nesta demonstração.");
    if (method === "PUT") { const artist = catalog.artists.find(x => x.id === Number(body.artist_id)) || fail("Escolha um artista."); item.title = String(body.title || "").trim() || fail("Informe o título."); item.year = Number(body.year); item.artist_id = artist.id; item.artist_name = artist.name; save(); return item; }
    if (method === "DELETE") { state.albums = state.albums.filter(x => x.id !== item.id); save(); return {}; }
  }
  fail("Função indisponível nesta demonstração.");
}
