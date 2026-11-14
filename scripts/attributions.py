#!/usr/bin/env python3
"""Regenerate the tracked attribution manifest directly from catalog.csv."""
import csv
from pathlib import Path
root=Path(__file__).resolve().parents[1]
def cell(value): return value.replace('|','\\|').replace('\n',' ')
lines=['# Atribuições de áudio', '', 'Gerado por `python3 scripts/attributions.py` a partir de `data/catalog.csv`.', '', 'Os artistas são fictícios. Todas as faixas de demonstração são tons sintéticos gerados pelo projeto com ffmpeg. Nenhuma gravação comercial é distribuída.', '', '| Faixa | Autor fictício | Licença | Origem |', '| --- | --- | --- | --- |']
with (root/'data/catalog.csv').open() as file:
    for row in csv.DictReader(file):
        lines.append('| '+' | '.join(cell(row[key]) for key in ('titulo','artista','licenca'))+' | '+cell(row['origem_url'] or 'Gerada localmente por seed.php / ffmpeg')+' |')
(root/'ATTRIBUTIONS.md').write_text('\n'.join(lines)+'\n')
