#!/usr/bin/env python3
"""Envio de faixa pelo painel: login admin, CSRF, licença obrigatória, cadastro, API e exclusão."""
import html, http.cookiejar, json, os, re, secrets, subprocess, tempfile, urllib.request, urllib.parse, urllib.error
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
env=dict(line.split('=',1) for line in (ROOT/'.env').read_text().splitlines() if '=' in line and not line.startswith('#'))
BASE=os.environ.get('TEST_URL','http://127.0.0.1:'+env.get('APP_PORT','8088'))
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def send(path,data=None,headers=None):
 try:r=client.open(urllib.request.Request(BASE+path,data=data,headers=headers or {}),timeout=30)
 except urllib.error.HTTPError as e:r=e
 return r.status,r.read().decode(),r.url
def form(path,body):return send(path,urllib.parse.urlencode(body).encode())
def multipart(path,fields,filename,content):
 b='----onda'+secrets.token_hex(8);parts=[]
 for k,v in fields.items():parts.append(f'--{b}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
 parts.append(f'--{b}\r\nContent-Disposition: form-data; name="audio"; filename="{filename}"\r\nContent-Type: audio/mpeg\r\n\r\n'.encode()+content+b'\r\n')
 parts.append(f'--{b}--\r\n'.encode())
 return send(path,b''.join(parts),{'Content-Type':'multipart/form-data; boundary='+b})
def csrf(body):return html.unescape(re.search(r'name="csrf" value="([^"]+)"',body).group(1))

subprocess.run(['docker','compose','exec','-T','php-fpm','php','-r','require "src/bootstrap.php"; Onda\\Database::query("DELETE FROM login_attempts");'],cwd=ROOT,check=True)
# Áudio único de 2 s gerado no contêiner (ffmpeg já está na imagem do PHP para o seed).
freq=str(300+secrets.randbelow(500))
audio=subprocess.run(['docker','compose','exec','-T','php-fpm','sh','-c',f'ffmpeg -loglevel error -f lavfi -i sine=frequency={freq}:duration=2 -f mp3 -'],cwd=ROOT,check=True,capture_output=True).stdout
assert len(audio)>1000

status,body,url=send('/admin/tracks.php');assert url.endswith('/admin/login.php')
status,body,url=form('/admin/login.php',{'csrf':csrf(body),'email':env['SEED_ADMIN_EMAIL'],'password':env['SEED_ADMIN_PASSWORD']})
status,body,url=send('/admin/tracks.php');assert status==200 and 'Faixas cadastradas' in body
token=csrf(body);title='Teste faixa '+secrets.token_hex(5);track_id=None
try:
 status,body,url=multipart('/admin/tracks.php',{'csrf':'x','action':'upload','licenca':'CC0'},'t.mp3',audio);assert status==403
 status,body,url=multipart('/admin/tracks.php',{'csrf':token,'action':'upload','titulo':title,'licenca':''},'t.mp3',audio)
 assert 'Informe a licença' in body
 status,body,url=multipart('/admin/tracks.php',{'csrf':token,'action':'upload','titulo':title,'licenca':''},'t.txt',b'oi')
 assert 'MP3, OGG ou M4A' in body
 status,body,url=multipart('/admin/tracks.php',{'csrf':token,'action':'upload','titulo':title,'artista':'Teste Painel','album':'Envios','licenca':'CC0-1.0 (teste)'},'t.mp3',audio)
 assert status==200 and 'Faixa &quot;'+title+'&quot; cadastrada' in body,re.search(r'role="alert">([^<]*)',body).group(1)
 _,data,_=send('/api/search?q='+urllib.parse.quote(title));items=json.loads(data)['items']
 track=next(t for t in items if t['title']==title);track_id=track['id']
 status,body,url=form('/admin/tracks.php',{'csrf':token,'action':'delete','id':track_id});assert 'Faixa excluída' in body and title not in body
 track_id=None
 _,data,_=send('/api/search?q='+urllib.parse.quote('Teste Painel'));assert not json.loads(data)['artists'],'artista vazio ficou'
 print('PASS: envio de faixa (CSRF, licença, formato, tags), busca pela API e exclusão.')
finally:
 if track_id:
  subprocess.run(['docker','compose','exec','-T','php-fpm','php','-r','require "src/bootstrap.php"; Onda\\Database::query("DELETE FROM tracks WHERE id=?",[$argv[1]]);',str(track_id)],cwd=ROOT,check=True)
