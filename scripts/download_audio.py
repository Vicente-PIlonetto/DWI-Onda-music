#!/usr/bin/env python3
import csv, shutil, urllib.request, urllib.parse
from pathlib import Path
root=Path(__file__).resolve().parents[1]
audio=(root/'storage/audio').resolve()
with (root/'data/catalog.csv').open() as file:
    for row in csv.DictReader(file):
        url=row['origem_url']
        if not url:
            print('Sem URL pública; seed/manual:',row['arquivo']);continue
        if urllib.parse.urlparse(url).scheme!='https':
            raise SystemExit('Use uma origem HTTPS: '+row['arquivo'])
        target=(audio/row['arquivo']).resolve()
        if audio not in target.parents:raise SystemExit('Caminho inválido no catálogo')
        if target.exists():continue
        target.parent.mkdir(parents=True,exist_ok=True)
        temporary=target.with_suffix(target.suffix+'.part')
        try:
            with urllib.request.urlopen(url,timeout=60) as response,temporary.open('wb') as output:
                if urllib.parse.urlparse(response.url).scheme!='https':raise ValueError('Redirecionamento sem HTTPS')
                shutil.copyfileobj(response,output)
            temporary.replace(target)
            print('Baixado:',row['arquivo'])
        finally:
            temporary.unlink(missing_ok=True)
