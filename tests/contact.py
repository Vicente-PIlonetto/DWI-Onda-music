#!/usr/bin/env python3
"""Testa as páginas PHP públicas (contato e catálogo) e a listagem de mensagens no painel."""
import html, http.cookiejar, os, re, secrets, subprocess, urllib.request, urllib.parse, urllib.error
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
env = dict(line.split('=', 1) for line in (ROOT / '.env').read_text().splitlines() if '=' in line and not line.startswith('#'))
BASE = os.environ.get('TEST_URL', 'http://127.0.0.1:' + env.get('APP_PORT', '8088'))

def php(code, *args):
    return subprocess.run(['docker', 'compose', 'exec', '-T', 'php-fpm', 'php', '-r', 'require "src/bootstrap.php"; ' + code, *args], cwd=ROOT, check=True, capture_output=True, text=True).stdout

def client():
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(path, body=None):
        data = urllib.parse.urlencode(body).encode() if body is not None else None
        try: r = opener.open(urllib.request.Request(BASE + path, data=data), timeout=20)
        except urllib.error.HTTPError as e: r = e
        return r.status, r.read().decode(), r.url
    return request

def csrf(body): return html.unescape(re.search(r'name="csrf" value="([^"]+)"', body).group(1))

email = 'contato-' + secrets.token_hex(5) + '@example.test'
valid = {'name': 'Pessoa Teste', 'email': email, 'phone': '(49) 99999-0000', 'subject': 'sugestao', 'message': 'Mensagem automatizada de teste.', 'consent': '1'}
php('Onda\\Database::query("DELETE FROM login_attempts");')
try:
    # Catálogo público por GET: busca, filtro inválido e escape de saída.
    request = client()
    status, body, _ = request('/catalogo.php')
    assert status == 200 and '<table>' in body and 'Álbuns do catálogo' in body
    status, body, _ = request('/catalogo.php?' + urllib.parse.urlencode({'q': '<script>x</script>'}))
    assert status == 200 and '<script>x</script>' not in body and '&lt;script&gt;' in body
    status, body, _ = request('/catalogo.php?ano=abc')
    assert 'Informe um ano' in body
    # Paginação por GET: página fora do intervalo cai na última, valor inválido cai na primeira.
    status, body, _ = request('/catalogo.php?pagina=abc')
    assert status == 200 and ('aria-current="page">1<' in body or 'página' not in body)
    pages = re.search(r'página \d+ de (\d+)', body)
    if pages:
        status, body, _ = request('/catalogo.php?pagina=9999')
        assert f'aria-current="page">{pages.group(1)}<' in body

    # Contato: CSRF, validação no servidor com dados preservados, envio e regra de 3 mensagens/dia.
    status, body, _ = request('/contato.php')
    token = csrf(body)
    status, body, _ = request('/contato.php', {**valid, 'csrf': 'invalido'})
    assert status == 403 and 'expirada' in body
    status, body, _ = request('/contato.php', {**valid, 'csrf': token, 'email': 'invalido', 'message': 'curta'})
    assert status == 422 and 'Informe um email válido' in body and '10 a 2000' in body
    assert 'value="Pessoa Teste"' in body and 'value="invalido"' in body, 'campos devem ser mantidos'
    for i in range(3):
        status, body, url = request('/contato.php', {**valid, 'csrf': token, 'message': f'Mensagem automatizada número {i}.'})
        assert status == 200 and url.endswith('/contato.php') and 'Mensagem enviada' in body, (status, url)
    status, body, _ = request('/contato.php', {**valid, 'csrf': token})
    assert status == 422 and '24 horas' in body, 'regra de limite diário'

    # Painel: mensagens exigem login, aparecem na lista e podem ser marcadas e excluídas.
    admin = client()
    status, body, url = admin('/admin/messages.php')
    assert url.endswith('/admin/login.php')
    status, body, url = admin('/admin/login.php', {'csrf': csrf(body), 'email': env['SEED_ADMIN_EMAIL'], 'password': env['SEED_ADMIN_PASSWORD']})
    assert url.endswith('/admin/'), (status, url)
    status, body, _ = admin('/admin/')
    unread = int(re.search(r'filtro=nao-lidas">(\d+)<', body).group(1))
    assert 'Resumo do catálogo' in body and unread >= 3, 'contadores do painel'
    status, body, _ = admin('/admin/messages.php')
    assert status == 200 and email in body and 'Mensagem automatizada número 2.' in body
    ids = re.findall(r'name="id" value="(\d+)"', body)
    token = csrf(body)
    status, body, _ = admin('/admin/messages.php', {'csrf': token, 'id': ids[0], 'action': 'toggle'})
    assert 'Mensagem atualizada' in body
    status, body, _ = admin('/admin/messages.php', {'csrf': token, 'id': ids[0], 'action': 'delete'})
    assert 'Mensagem excluída' in body
    status, body, _ = admin('/admin/messages.php', {'csrf': token, 'id': '999999999', 'action': 'delete'})
    assert 'não encontrada' in body
    print('PASS: catálogo GET, paginação, escape, contato POST, CSRF, validação, dados mantidos, limite diário, contadores e mensagens no painel.')
finally:
    php('Onda\\Database::query("DELETE FROM contact_messages WHERE email=?", [$argv[1]]);', email)
