#!/usr/bin/env python3
"""Exporta a Parte 2 estática para um diretório vazio."""
import argparse, csv, json, shutil
from pathlib import Path
root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--output', required=True)
output = Path(parser.parse_args().output).resolve()
if output == root or root in output.parents:
    raise SystemExit('Escolha uma pasta fora do projeto, por exemplo /tmp/onda-pages.')
if output.exists() and any(output.iterdir()):
    raise SystemExit('A pasta de saída deve estar vazia; nada foi sobrescrito.')
shutil.copytree(root / 'frontend', output, dirs_exist_ok=True)
artists, albums, tracks = [], [], []
with (root / 'data/catalog.csv').open() as file:
    for i, row in enumerate(csv.DictReader(file), 1):
        artist = next((a for a in artists if a['name'] == row['artista']), None)
        if artist is None:
            artist = {'id': len(artists) + 1, 'name': row['artista']}
            artists.append(artist)
        album = next((a for a in albums if a['title'] == row['album'] and a['artist_id'] == artist['id']), None)
        if album is None:
            album = {'id': len(albums) + 1, 'artist_id': artist['id'], 'artist_name': artist['name'], 'title': row['album'], 'year': int(row['ano']), 'cover_path': 'placeholder.svg'}
            albums.append(album)
        tracks.append({'id': i, 'title': row['titulo'], 'artist_id': artist['id'], 'artist_name': artist['name'], 'album_id': album['id'], 'album_title': album['title'], 'duration_seconds': 12, 'track_number': int(row['numero_faixa']), 'cover_path': 'placeholder.svg'})
(output / 'demo').mkdir(exist_ok=True)
(output / 'demo/catalog.json').write_text(json.dumps({'artists': artists, 'albums': albums, 'tracks': tracks}, ensure_ascii=False))
for path in output.rglob('*'):
    if path.suffix not in {'.html', '.js', '.css'}:
        continue
    text = path.read_text()
    for prefix in ('assets', 'css', 'js', 'favicon.svg'):
        text = text.replace('"/' + prefix, '"./' + prefix).replace("'/" + prefix, "'./" + prefix)
    if path.name == 'index.html':
        text = text.replace('<html lang="pt-BR">', '<html lang="pt-BR" data-demo="true">')
        text = text.replace('href="/admin/login.php"', 'href="#/admin"')
    path.write_text(text)
(output / '.nojekyll').touch()
print('Frontend estático exportado para', output)
print('Telas e interações da Parte 2 usam dados fictícios locais, sem PHP ou banco.')
