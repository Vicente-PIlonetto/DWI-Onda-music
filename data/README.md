# Catálogo reproduzível

`catalog.csv` é a fonte de dados textual do seed e pode ser usada pelo importador. As colunas são `arquivo,titulo,artista,album,numero_faixa,ano,licenca,origem_url`, em UTF-8. Artistas e álbuns são deduplicados por nome e por (artista, título).

O catálogo inicial contém somente tons de 12 segundos gerados por ffmpeg; os nomes de artistas são fictícios. Não há URLs públicas para esses tons: `seed.php` os cria automaticamente em `storage/audio/`. A capa SVG também é gerada localmente em `storage/covers/`. Nenhum binário entra no Git.

Para seu próprio catálogo, copie arquivos autorizados para `storage/audio/`, mantenha os caminhos relativos na coluna `arquivo` e passe seu CSV ao importador. Subpastas são aceitas. Mantenha licença e origem corretas. O seed é exclusivo da demonstração; use um CSV separado para importar outras faixas.

Se houver URLs HTTPS públicas para arquivos de licença livre, registre-as em `origem_url` e execute `sh scripts/download_audio.sh`. Sem URL, copie manualmente. O script preserva arquivos existentes. Depois execute o importador e `python3 scripts/attributions.py` para atualizar `ATTRIBUTIONS.md` se o catálogo versionado mudou.

Não é necessário um dump: CSV + migrations + seed reproduzem os dados com arquivos pequenos e revisáveis.

O grupo informou que tem licença para testar localmente as faixas extraídas do arquivo de músicas enviado. Elas foram importadas no banco e em `storage/audio/` local, com a observação de licença declarada para teste local. Os arquivos e registros não entram no Git, no catálogo versionado, nos créditos de redistribuição ou nos pacotes; depois de clonar, cada pessoa pode importar arquivos próprios/autorizados pelo procedimento acima.

Para capas de playlists, o dono pode enviar JPEG, PNG ou WebP de até 3 MB. As imagens ficam localmente em `storage/covers/`; essa pasta também é ignorada pelo Git.
