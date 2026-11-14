#!/usr/bin/env python3
"""Generate local credentials without storing secrets in source control."""
from pathlib import Path
import secrets
root=Path(__file__).resolve().parents[1]
path=root/'.env'
if path.exists():
    raise SystemExit('.env já existe; preservado. Edite-o para mudar a configuração.')
text=(root/'.env.example').read_text()
for key in ('MYSQL_PASSWORD','MYSQL_ROOT_PASSWORD','SEED_ADMIN_PASSWORD','SEED_USER_PASSWORD'):
    text=text.replace(key+'=\n',key+'='+secrets.token_urlsafe(24)+'\n')
with path.open('x') as file:
    path.chmod(0o600)
    file.write(text)
print('.env criado. Consulte nele as credenciais locais de teste.')
