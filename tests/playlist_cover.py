#!/usr/bin/env python3
"""Teste local de upload, exibição, autorização e remoção de capa."""
import base64, http.cookiejar, json, os, secrets, urllib.error, urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ENV = dict(line.split("=", 1) for line in (ROOT / ".env").read_text().splitlines() if "=" in line and not line.startswith("#"))
BASE = os.environ.get("TEST_URL", "http://127.0.0.1:" + ENV.get("APP_PORT", "8088"))

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(browser, method, path, body=None, headers=None):
    data = json.dumps(body).encode() if isinstance(body, dict) else body
    req = urllib.request.Request(BASE + path, data=data, method=method, headers=headers or {})
    try:
        response = browser.open(req, timeout=20)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.headers, response.read()

def json_request(browser, method, path, body=None, csrf=None):
    headers = {"Content-Type": "application/json"}
    if csrf:
        headers["X-CSRF-Token"] = csrf
    status, _, payload = request(browser, method, path, body, headers)
    return status, json.loads(payload) if payload else None

def login(browser, email, password):
    _, _, payload = request(browser, "GET", "/api/auth/me")
    status, result = json_request(browser, "POST", "/api/auth/login", {
        "email": email, "password": password
    }, json.loads(payload)["csrf_token"])
    assert status == 200
    return result["csrf_token"]

owner = client()
CSRF = login(owner, ENV["SEED_USER_EMAIL"], ENV["SEED_USER_PASSWORD"])
PLAYLIST = None
FILENAME = None
try:
    status, playlist = json_request(owner, "POST", "/api/playlists", {
        "name": "Capa teste " + secrets.token_hex(5)
    }, CSRF)
    assert status == 201
    PLAYLIST = playlist["id"]
    boundary = "onda" + secrets.token_hex(12)
    png = base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9ioAAAAASUVORK5CYII=")
    multipart = (
        f"--{boundary}\r\nContent-Disposition: form-data; name=\"cover\"; filename=\"cover.png\"\r\n"
        "Content-Type: image/png\r\n\r\n"
    ).encode() + png + f"\r\n--{boundary}--\r\n".encode()
    status, _, payload = request(owner, "POST", f"/api/playlists/{PLAYLIST}/cover", multipart, {
        "Content-Type": f"multipart/form-data; boundary={boundary}", "X-CSRF-Token": CSRF
    })
    assert status == 200, (status, payload)
    FILENAME = json.loads(payload)["cover_path"]
    assert FILENAME.startswith(f"playlist-{PLAYLIST}-") and FILENAME.endswith(".png")
    status, _, payload = request(owner, "GET", f"/api/playlists/{PLAYLIST}")
    assert status == 200 and json.loads(payload)["cover_path"] == FILENAME
    status, headers, payload = request(owner, "GET", "/covers/" + FILENAME)
    assert status == 200 and headers.get_content_type() == "image/png" and payload == png

    admin = client()
    admin_csrf = login(admin, ENV["SEED_ADMIN_EMAIL"], ENV["SEED_ADMIN_PASSWORD"])
    status, _, _ = request(admin, "GET", f"/api/playlists/{PLAYLIST}")
    assert status == 404
    status, _ = json_request(admin, "DELETE", f"/api/playlists/{PLAYLIST}/cover", {}, admin_csrf)
    assert status == 404

    status, _ = json_request(owner, "DELETE", f"/api/playlists/{PLAYLIST}/cover", {}, CSRF)
    assert status == 204
    FILENAME = None
    status, _, payload = request(owner, "GET", f"/api/playlists/{PLAYLIST}")
    assert status == 200 and json.loads(payload)["cover_path"] is None
    print("PASS: upload, exibição pública do arquivo, isolamento por usuário e remoção da capa.")
finally:
    if PLAYLIST:
        if FILENAME:
            request(owner, "DELETE", f"/api/playlists/{PLAYLIST}/cover", {}, {
                "Content-Type": "application/json", "X-CSRF-Token": CSRF
            })
        request(owner, "DELETE", f"/api/playlists/{PLAYLIST}", {}, {
            "Content-Type": "application/json", "X-CSRF-Token": CSRF
        })
