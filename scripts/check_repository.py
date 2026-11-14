#!/usr/bin/env python3
"""Check tracked/index/history content without printing secrets."""
import re,subprocess
from pathlib import Path
root=Path(__file__).resolve().parents[1]
def git(*args):return subprocess.check_output(['git',*args],cwd=root)
secrets=[]
if (root/'.env').exists():
 for line in (root/'.env').read_text().splitlines():
  key,_,value=line.partition('=')
  if ('PASSWORD' in key or 'SECRET' in key or 'TOKEN' in key) and len(value)>=8:secrets.append(value.encode())
patterns=[rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----',rb'AKIA[0-9A-Z]{16}',rb'gh[pousr]_[A-Za-z0-9]{30,}']
errors=[]
paths=git('ls-files','-z').decode().split('\0')
for name in filter(None,paths):
 path=root/name
 if name=='.env' or '/vendor/' in '/'+name or '/node_modules/' in '/'+name:errors.append('Arquivo proibido: '+name)
 if name.startswith(('storage/audio/','storage/covers/')) and not name.endswith('/.gitkeep'):errors.append('Mídia versionada: '+name)
 if path.is_file():
  content=path.read_bytes()
  if len(content)>5*1024*1024:errors.append('Arquivo acima de 5 MB: '+name)
  if any(secret in content for secret in secrets):errors.append('Segredo local encontrado: '+name)
  if any(re.search(pattern,content) for pattern in patterns):errors.append('Padrão de credencial: '+name)
# Check all reachable Git blobs, including earlier commits and current staged files.
objects=set()
for line in git('rev-list','--objects','--all').splitlines():objects.add(line.split(b' ',1)[0])
for line in git('ls-files','-s').splitlines():objects.add(line.split()[1])
for oid in objects:
 if git('cat-file','-t',oid.decode()).strip()!=b'blob':continue
 content=git('cat-file','-p',oid.decode())
 if len(content)>5*1024*1024:errors.append('Blob acima de 5 MB no histórico: '+oid.decode())
 if any(secret in content for secret in secrets):errors.append('Segredo local no histórico: '+oid.decode())
 if any(re.search(pattern,content) for pattern in patterns):errors.append('Padrão de credencial no histórico: '+oid.decode())
if errors:raise SystemExit('\n'.join(errors))
print('PASS: nenhum segredo local/padrão conhecido, mídia versionada ou arquivo acima de 5 MB no índice/histórico.')
print('A checagem é heurística; revise o diff antes de publicar.')
