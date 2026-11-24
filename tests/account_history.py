#!/usr/bin/env python3
"""Conta, histórico, reordenação de playlist, faixas aleatórias, busca ampliada e health. Sem dependências."""

import http.cookiejar,json,os,secrets,urllib.request,urllib.error,urllib.parse,subprocess
from pathlib import Path
ENV_PORT=next((l.split('=',1)[1].strip() for l in (Path(__file__).resolve().parents[1]/'.env').read_text().splitlines() if l.startswith('APP_PORT=')),'8088')
BASE=os.environ.get('TEST_URL','http://127.0.0.1:'+ENV_PORT)
ROOT=Path(__file__).resolve().parents[1]
def db(code,*args):
 return subprocess.check_output(['docker','compose','exec','-T','php-fpm','php','-r','require "src/bootstrap.php"; '+code,*args],cwd=ROOT)
initial_buckets=json.loads(db('echo json_encode(Onda\\Database::query("SELECT bucket FROM login_attempts")->fetchAll(PDO::FETCH_COLUMN));'))
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.csrf=''
 def request(self,path,method='GET',body=None,status=200,headers=None,raw=False):
  h={'Content-Type':'application/json','X-CSRF-Token':self.csrf};h.update(headers or {})
  req=urllib.request.Request(BASE+'/api'+path,data=json.dumps(body).encode() if body is not None else None,method=method,headers=h)
  try: response=self.http.open(req)
  except urllib.error.HTTPError as e: response=e
  data=response.read();assert response.status in (status if isinstance(status,tuple) else (status,)),(path,response.status,data[:500]);
  if raw:return data,response.headers
  return json.loads(data) if data else None
 def refresh(self):
  data=self.request('/auth/me');self.csrf=data['csrf_token'];return data
 def register(self):
  self.email='test-'+secrets.token_hex(8)+'@example.test';self.password=secrets.token_urlsafe(24)
  self.refresh();self.request('/auth/register','POST',{'name':'Teste automatizado','email':self.email,'password':self.password},201);return self.refresh()['user']['id']

ids=[t['id'] for t in Client().request('/tracks?limit=3')['items']]
assert len(ids)==3,'precisa de pelo menos 3 faixas no catálogo'
users=[]
try:
 # health: sem sessão e com o estado do banco
 with urllib.request.urlopen(BASE+'/api/health') as r:
  assert r.status==200 and 'Set-Cookie' not in r.headers;h=json.load(r)
 assert h['status']=='ok' and h['database'] and h['tracks']>=3

 c=Client();users.append(c.register())
 o=Client();users.append(o.register())

 # lembrar de mim: cookie com validade só quando pedido
 r=Client();r.refresh();r.request('/auth/login','POST',{'email':c.email,'password':c.password,'remember':True})
 assert any(k.name=='onda_session' and k.expires for k in r.jar),'cookie persistente ausente'
 r2=Client();r2.refresh();r2.request('/auth/login','POST',{'email':c.email,'password':c.password})
 assert all(k.expires is None for k in r2.jar if k.name=='onda_session')

 # faixas aleatórias
 assert len(c.request('/tracks/random?limit=2')['items'])==2
 c.request('/tracks/random?limit=0',status=422);c.request('/tracks/random?limit=101',status=422)

 # busca: artistas e álbuns só na primeira página
 first=c.request('/tracks?limit=1')['items'][0]
 res=c.request('/search?q='+urllib.parse.quote(first['artist_name']))
 assert any(a['id']==first['artist_id'] for a in res['artists'])
 assert any(a['id']==first['album_id'] for a in res['albums'])
 if res['next_cursor']:assert 'artists' not in c.request('/search?q='+urllib.parse.quote(first['artist_name'])+'&cursor='+res['next_cursor'])

 # histórico
 c.request('/history',status=200)
 Client().request('/history',status=401)
 c.request('/history','POST',{'track_id':ids[0]},403,headers={'X-CSRF-Token':'x'})
 c.request('/history','POST',{'track_id':9999999},404)
 for t in [ids[0],ids[0],ids[1],ids[2],ids[0]]:c.request('/history','POST',{'track_id':t},204)
 recent=[t['id'] for t in c.request('/history')['items']]
 assert recent==[ids[0],ids[2],ids[1]],recent
 assert len(c.request('/history?limit=1')['items'])==1;c.request('/history?limit=51',status=422)
 assert not o.request('/history')['items'],'histórico vazou entre usuários'
 c.request('/history','DELETE',{},204);assert not c.request('/history')['items']

 # reordenar playlist
 pid=str(c.request('/playlists','POST',{'name':'Ordem'},201)['id'])
 for t in ids:c.request('/playlists/'+pid+'/tracks','POST',{'track_id':t},204)
 new=[ids[2],ids[0],ids[1]]
 c.request('/playlists/'+pid+'/tracks','PUT',{'track_ids':new},204)
 assert [t['id'] for t in c.request('/playlists/'+pid)['tracks']['items']]==new
 c.request('/playlists/'+pid+'/tracks','PUT',{'track_ids':new[:2]},422)
 c.request('/playlists/'+pid+'/tracks','PUT',{'track_ids':[ids[0],ids[0],ids[1]]},422)
 c.request('/playlists/'+pid+'/tracks','PUT',{'track_ids':'1,2'},422)
 o.request('/playlists/'+pid+'/tracks','PUT',{'track_ids':new},404)

 # conta: nome, senha, exclusão
 assert c.request('/me','PUT',{'name':'Ouvinte Teste'})['user']['name']=='Ouvinte Teste'
 c.request('/me','PUT',{'name':'<script>'},422)
 c.request('/me/password','PUT',{'current_password':'errada-errada','new_password':'x'*12},403)
 c.request('/me/password','PUT',{'current_password':c.password,'new_password':'curta'},422)
 old_cookies={k.name:k.value for k in c.jar}
 new_pw='nova-senha-'+c.password
 c.csrf=c.request('/me/password','PUT',{'current_password':c.password,'new_password':new_pw})['csrf_token']
 assert {k.name:k.value for k in c.jar}!=old_cookies,'sessão não foi regenerada'
 c.request('/auth/logout','POST',{},204);c.refresh()
 c.request('/auth/login','POST',{'email':c.email,'password':c.password},401)
 c.request('/auth/login','POST',{'email':c.email,'password':new_pw});c.refresh()
 c.request('/me','DELETE',{'password':'errada-errada'},403)
 c.request('/me','DELETE',{'password':new_pw},204)
 c.refresh();assert c.request('/auth/me')['user'] is None
 c.request('/auth/login','POST',{'email':c.email,'password':new_pw},401)
 print('PASS: lembrar de mim, health, aleatórias, busca ampliada, histórico, reordenação e conta (nome, senha, exclusão)')
finally:
 db('Onda\\Database::query("DELETE FROM users WHERE id IN (".implode(",",array_map("intval",json_decode($argv[1],true)?:[0])).")");',json.dumps(users))
 current=json.loads(db('echo json_encode(Onda\\Database::query("SELECT bucket FROM login_attempts")->fetchAll(PDO::FETCH_COLUMN));'))
 for bucket in set(current)-set(initial_buckets):
  db('Onda\\Database::query("DELETE FROM login_attempts WHERE bucket=?",[$argv[1]]);',bucket)
