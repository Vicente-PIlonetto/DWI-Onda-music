export const isDemo = document.documentElement.dataset.demo === "true";
let csrf = "";
export let user = null;
export async function api(path, options = {}) {
  if (isDemo) { const { demoApi } = await import("./demo.js"); return demoApi(path, options); }
  const method = options.method || "GET";
  const multipart = options.body instanceof FormData;
  const response = await fetch("/api" + path, {
    credentials: "same-origin",
    ...options,
    headers: {
      ...(multipart ? {} : { "Content-Type": "application/json" }),
      ...(method !== "GET" ? { "X-CSRF-Token": csrf } : {}),
      ...options.headers,
    },
    ...(options.body !== undefined
      ? { body: multipart ? options.body : JSON.stringify(options.body) }
      : {}),
  });
  const data = response.status === 204 ? null : await response.json();
  if (!response.ok) {
    const error = new Error(data?.error || "Não foi possível concluir");
    error.status = response.status;
    throw error;
  }
  return data;
}
export async function session() {
  const data = await api("/auth/me");
  csrf = data.csrf_token;
  user = data.user;
  return user;
}
export async function all(path, key) {
  const items = [];
  let cursor = null;
  do {
    const data = await api(
      path +
        (path.includes("?") ? "&" : "?") +
        "limit=100" +
        (cursor ? "&cursor=" + cursor : ""),
    );
    const page = key ? data[key] : data;
    items.push(...page.items);
    cursor = page.next_cursor;
  } while (cursor);
  return items;
}
