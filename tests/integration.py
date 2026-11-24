#!/usr/bin/env python3
"""Integration checks against the real Nginx/PHP/MariaDB stack. No dependencies."""
import http.cookiejar,json,os,secrets,urllib.request,urllib.error,subprocess
from pathlib import Path
BASE=os.environ.get('TEST_URL','http://127.0.0.1:8088')
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
c=Client();c.refresh()
assert c.request('/tracks?limit=2')['next_cursor']=='2'
assert len(c.request('/tracks?cursor=2&limit=2')['items'])==2
c.request('/tracks?cursor=-1',status=422);c.request('/tracks?limit=101',status=422)
c.request('/tracks/9999999',status=404);c.request('/stream/1',status=401)
u=c.register();other=Client();v=other.register()
try:
 c.request('/playlists','POST',{'name':'bloqueada'},403,headers={'X-CSRF-Token':'invalid'})
 playlist=c.request('/playlists','POST',{'name':'Teste de integração'},201);pid=str(playlist['id'])
 other.request('/playlists/'+pid,status=404)
 other.request('/playlists/'+pid,'PUT',{'name':'roubada'},404)
 other.request('/playlists/'+pid+'/tracks','POST',{'track_id':1},404)
 for track in [1,2,1]:c.request('/playlists/'+pid+'/tracks','POST',{'track_id':track},204)
 page=c.request('/playlists/'+pid+'?limit=1')['tracks'];assert page['next_cursor']=='1'
 assert len(c.request('/playlists/'+pid)['tracks']['items'])==2
 assert c.request('/playlists/'+pid+'?cursor=1')['tracks']['items'][0]['id']==2
 c.request('/playlists/'+pid,'PUT',{'name':'Renomeada'})
 c.request('/playlists/'+pid+'/tracks','DELETE',{'track_id':1},204)
 c.request('/likes/1','POST',{},204);c.request('/likes/1','POST',{},204)
 assert len(c.request('/likes')['items'])==1
 c.request('/likes/1','DELETE',{},204);assert not c.request('/likes')['items']
 data,headers=c.request('/stream/1',headers={'Range':'bytes=100-199'},status=206,raw=True)
 assert len(data)==100 and headers['Content-Range'].startswith('bytes 100-199/')
 assert 'nginx' in headers['Server'].lower()
 c.request('/stream/1',headers={'Range':'bytes=999999999-'},status=416,raw=True)
 try:urllib.request.urlopen(BASE+'/_audio/tone-01.mp3');raise AssertionError('áudio exposto')
 except urllib.error.HTTPError as e:assert e.code==404
 c.request('/playlists/'+pid,'DELETE',{},204)
 c.request('/auth/logout','POST',{},204);c.request('/stream/1',status=401)
 c.refresh();c.request('/auth/login','POST',{'email':c.email,'password':c.password});c.refresh()
 for q,count in [('Horizonte',1),('Aurora',8),('Mar Aberto',4),('%',0)]:assert len(c.request('/search?q='+urllib.parse.quote(q))['items'])==count
 for attempt in range(11):
  response=c.request('/auth/login','POST',{'email':c.email,'password':'invalid-password'},(401,429))
  if 'Muitas tentativas' in response['error']:break
 else:raise AssertionError('Rate limit não acionou')
 print('PASS: cursor, validação, cadastro/login, CSRF, isolamento, playlists, curtidas, Range 206/416, logout, busca e rate limit')
finally:
 db('Onda\\Database::query("DELETE FROM users WHERE id IN (?,?)",json_decode($argv[1],true));',json.dumps([u,v]))
 current=json.loads(db('echo json_encode(Onda\\Database::query("SELECT bucket FROM login_attempts")->fetchAll(PDO::FETCH_COLUMN));'))
 for bucket in set(current)-set(initial_buckets):
  db('Onda\\Database::query("DELETE FROM login_attempts WHERE bucket=?",[$argv[1]]);',bucket)
