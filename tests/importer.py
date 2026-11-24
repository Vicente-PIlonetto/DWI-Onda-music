#!/usr/bin/env python3
"""Verify ID3 fallback, external copying and repeatable imports using a generated fixture."""
import json,secrets,subprocess
from pathlib import Path
root=Path(__file__).resolve().parents[1]
token=secrets.token_hex(8)
artist='Teste ID3 '+token
folder='/tmp/onda-import-'+token

def run(*args,check=True):
 return subprocess.run(['docker','compose','exec','-T','php-fpm',*args],cwd=root,check=check,capture_output=True,text=True)

def db(code,*args):
 return run('php','-r','require "src/bootstrap.php"; '+code,*args).stdout

try:
 run('mkdir','-p',folder)
 run('ffmpeg','-nostdin','-v','error','-f','lavfi','-i','sine=frequency=300:duration=2','-metadata','title=Faixa de teste ID3','-metadata','artist='+artist,'-metadata','album=Álbum de teste','-metadata','track=3','-metadata','date=2026',folder+'/tagged.mp3')
 for _ in range(2):
  run('php','scripts/import_tracks.php','--dir='+folder,'--license=CC0-1.0')
 rows=json.loads(db('echo json_encode(Onda\\Database::query("SELECT t.* FROM tracks t JOIN artists a ON a.id=t.artist_id WHERE a.name=?",[$argv[1]])->fetchAll());',artist))
 assert len(rows)==1 and rows[0]['title']=='Faixa de teste ID3' and rows[0]['track_number']==3
 assert rows[0]['audio_path'].startswith('import-') and float(rows[0]['duration_seconds'])>1
 missing=run('php','scripts/import_tracks.php','--dir='+folder,check=False)
 assert missing.returncode==1 and 'licença' in missing.stderr
 print('PASS: tags ID3, duração, cópia externa, licença obrigatória e idempotência')
finally:
 db('foreach(Onda\\Database::query("SELECT t.audio_path FROM tracks t JOIN artists a ON a.id=t.artist_id WHERE a.name=?",[$argv[1]])->fetchAll() as $r) unlink("/app/storage/audio/".$r["audio_path"]); Onda\\Database::query("DELETE FROM artists WHERE name=?",[$argv[1]]);',artist)
 run('php','-r','unlink($argv[1]."/tagged.mp3");rmdir($argv[1]);',folder,check=False)
