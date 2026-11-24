#!/usr/bin/env python3
"""Smoke test do painel PHP com a stack local; usa a conta admin do .env."""
import html, http.cookiejar, json, os, re, secrets, subprocess, urllib.request, urllib.parse, urllib.error
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
env=dict(line.split('=',1) for line in (ROOT/'.env').read_text().splitlines() if '=' in line and not line.startswith('#'))
BASE=os.environ.get('TEST_URL','http://127.0.0.1:'+env.get('APP_PORT','8088'))
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(path,body=None):
 data=urllib.parse.urlencode(body).encode() if body is not None else None
 try:r=client.open(urllib.request.Request(BASE+path,data=data),timeout=20)
 except urllib.error.HTTPError as e:r=e
 return r.status,r.read().decode(),r.url

# O teste de integração exercita o limite de tentativas de login; limpa os contadores antes de entrar.
subprocess.run(['docker','compose','exec','-T','php-fpm','php','-r','require "src/bootstrap.php"; Onda\\Database::query("DELETE FROM login_attempts");'],cwd=ROOT,check=True)
def csrf(body):return html.unescape(re.search(r'name="csrf" value="([^"]+)"',body).group(1))
status,body,url=request('/admin/');assert url.endswith('/admin/login.php')
status,body,url=request('/admin/login.php',{'csrf':csrf(body),'email':env['SEED_ADMIN_EMAIL'],'password':env['SEED_ADMIN_PASSWORD']})
assert status==200 and url.endswith('/admin/'),(status,url)
assert '<table>' in body and 'Álbuns cadastrados' in body
status,body,url=request('/admin/',{'csrf':'invalid','action':'save'});assert status==403
status,body,url=request('/admin/');token=csrf(body)
name='Teste painel '+secrets.token_hex(6)
album_id=None
try:
 status,body,url=request('/admin/',{'csrf':token,'action':'save','title':name,'artist_id':1,'year':'999'})
 assert 'Informe um ano' in body and name in body
 status,body,url=request('/admin/',{'csrf':token,'action':'save','title':name,'artist_id':1,'year':'2026'})
 assert status==200 and 'Catálogo atualizado' in body
 _,data,_=request('/api/albums?limit=100');album=next(a for a in json.loads(data)['items'] if a['title']==name);album_id=album['id']
 status,body,url=request('/admin/',{'csrf':token,'action':'save','id':album_id,'title':name+' editado','artist_id':1,'year':'2025'})
 _,data,_=request('/api/albums/'+str(album_id));assert json.loads(data)['title']==name+' editado'
 status,body,url=request('/admin/',{'csrf':token,'action':'delete','id':1});assert 'sem faixas' in body
 status,body,url=request('/admin/users.php');assert status==200 and env['SEED_USER_EMAIL'] in body and 'password_hash' not in body
 status,body,url=request('/admin/',{'csrf':token,'action':'delete','id':album_id});assert 'Catálogo atualizado' in body
 status,body,url=request('/api/albums/'+str(album_id));assert status==404
 album_id=None
 status,body,url=request('/admin/logout.php',{'csrf':token});assert url.endswith('/admin/login.php')
 status,body,url=request('/admin/login.php',{'csrf':csrf(body),'email':env['SEED_USER_EMAIL'],'password':env['SEED_USER_PASSWORD']})
 assert 'administrador inválidos' in body
 status,body,url=request('/admin/');assert url.endswith('/admin/login.php')
 print('PASS: redirecionamento, login admin, CSRF, CRUD público, validação, registros e logout.')
finally:
 if album_id:
  subprocess.run(['docker','compose','exec','-T','php-fpm','php','-r','require "src/bootstrap.php"; Onda\\Database::query("DELETE FROM albums WHERE id=?",[$argv[1]]);',str(album_id)],cwd=ROOT,check=True)
