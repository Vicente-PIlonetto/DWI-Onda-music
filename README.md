<p align="center">
  <img src="docs/img/banner.png" alt="Onda: player de música com a tela de um álbum no desktop e a tela inicial no celular" width="100%">
</p>

<h1 align="center">Onda Music</h1>

<p align="center">
  <a href="https://vicente-pilonetto.github.io/DWI-Onda-music/"><b>Demonstração estática</b></a> ·
  <a href="#rodar-localmente"><b>Rodar com Docker</b></a> ·
  <a href="#telas"><b>Telas</b></a> ·
  <a href="#parte-3-páginas-php"><b>Páginas PHP</b></a> ·
  <a href="#painel-administrativo"><b>Painel</b></a>
</p>

<p align="center">
  <img alt="PHP 8.2" src="https://img.shields.io/badge/PHP-8.2-0b1d26?style=flat-square&logo=php&logoColor=add8e6">
  <img alt="MariaDB 11.4" src="https://img.shields.io/badge/MariaDB-11.4-0b1d26?style=flat-square&logo=mariadb&logoColor=add8e6">
  <img alt="JavaScript sem framework" src="https://img.shields.io/badge/JavaScript-sem%20framework-0b1d26?style=flat-square&logo=javascript&logoColor=add8e6">
  <img alt="Docker Compose" src="https://img.shields.io/badge/Docker-Compose-0b1d26?style=flat-square&logo=docker&logoColor=add8e6">
</p>

Projeto de Desenvolvimento Web I do Grupo 2: **Cauan Riffel, Giovani Pott e Vicente Pilonetto**. Onda é um mock educacional de streaming inspirado no Spotify, sem vínculo com a empresa. O catálogo de exemplo usa artistas fictícios e tons curtos gerados para o projeto.

**Parte 2 (frontend estático):** [GitHub Pages](https://vicente-pilonetto.github.io/DWI-Onda-music/). **Parte 3 (PHP + MariaDB):** hospedagem prevista em `onda.papotorto.com.br` (ainda não publicada); até lá, rode localmente com Docker conforme abaixo.

## Telas

<table>
  <tr>
    <td width="50%"><img src="docs/img/inicio.png" alt="Tela inicial com atalhos e álbuns"><br><sub><b>Início:</b> atalhos, álbuns e tocar aleatório.</sub></td>
    <td width="50%"><img src="docs/img/tocando.png" alt="Página de álbum com uma faixa tocando"><br><sub><b>Álbum:</b> faixa tocando, curtir e adicionar à playlist.</sub></td>
  </tr>
  <tr>
    <td><img src="docs/img/busca.png" alt="Busca com a vitrine de categorias"><br><sub><b>Busca:</b> faixas, artistas e álbuns.</sub></td>
    <td><img src="docs/img/catalogo.png" alt="Catálogo público renderizado em PHP"><br><sub><b>Catálogo (PHP, GET):</b> busca, filtro por ano e paginação.</sub></td>
  </tr>
</table>

<p align="center">
  <img src="docs/img/mobile-inicio.png" alt="Tela inicial no celular" width="30%">
  &nbsp;
  <img src="docs/img/mobile-album.png" alt="Página de álbum no celular" width="30%">
  &nbsp;
  <img src="docs/img/contato.png" alt="Formulário de contato em PHP" width="36%">
</p>
<p align="center"><sub>No celular o menu vira gaveta e o player fica fixo embaixo. À direita, o contato em PHP (POST).</sub></p>

## Casos de uso

| Perfil | O que pode fazer |
| --- | --- |
| Visitante | Navegar pelo catálogo, artistas e álbuns; buscar músicas; enviar mensagem pelo formulário de contato; criar conta ou entrar. |
| Usuário autenticado | Reproduzir faixas, criar e organizar playlists, curtir músicas. |
| Administrador | Entrar no painel, cadastrar/editar álbuns, remover álbuns sem faixas, enviar e excluir faixas de áudio, ler/marcar/excluir mensagens de contato e consultar registros de usuários. |

Regras principais: só o dono acessa e altera suas playlists; uma faixa não se repete na mesma playlist; a reprodução exige sessão; somente administradores alteram o catálogo; um álbum com faixas não pode ser excluído pelo painel; cada email envia no máximo 3 mensagens de contato a cada 24 horas. Todas são validadas no PHP, independentemente do JavaScript.

## Mapa do site

```mermaid
flowchart TD
  Início --> Catálogo
  Início --> Busca
  Início --> Contato
  Catálogo --> Álbum --> Artista
  Busca --> Álbum
  Visitante --> Login --> Início
  Visitante --> Cadastro --> Início
  Usuário --> Biblioteca --> Playlist
  Usuário --> Curtidas
  Login -->|administrador| Painel
  Painel --> ÁlbunsCRUD
  Painel --> Faixas
  Painel --> Mensagens
  Painel --> Registros
  Painel --> Catálogo
```

O mapa editável e os wireframes desktop/mobile estão em [docs/mapa-site.drawio](docs/mapa-site.drawio) e [docs/wireframes.drawio](docs/wireframes.drawio). São rascunhos montados depois da implementação para revisão do grupo; precisam ser conferidos e usados na entrega SBC conforme o enunciado. O relatório completo de requisitos e limitações está em [docs/REQUISITOS.md](docs/REQUISITOS.md), e o planejamento de referência em [docs/PLANEJAMENTO.md](docs/PLANEJAMENTO.md).

## Tecnologias

PHP 8.2+ sem framework, PDO com MariaDB, HTML/CSS/JavaScript sem framework, módulos ES e Docker Compose com Nginx, PHP-FPM e MariaDB. A interface principal é uma SPA com rotas por hash; inclui também páginas PHP compartilhadas e um painel administrativo.

## Rodar localmente

Requer Docker Compose e Python 3. Clone o repositório, configure o `.env` e suba os serviços:

```sh
git clone https://github.com/Vicente-PIlonetto/DWI-Onda-music.git
cd DWI-Onda-music
python3 scripts/configure.py
docker compose up -d --build
```

Abra [http://localhost:8088](http://localhost:8088). O entrypoint executa migrations e seed. Para repetir manualmente:

```sh
docker compose exec php-fpm php scripts/migrate.php
docker compose exec php-fpm php scripts/seed.php
```

Para testar o importador com arquivos locais, copie os áudios para `storage/audio/` e execute:

```sh
docker compose exec php-fpm php scripts/import_tracks.php --dir=/app/storage/audio --license="Licença informada pelo grupo"
```

O importador lê tags ID3, mantém os arquivos em `storage/audio/` e usa o caminho/hash para evitar duplicatas.

O `.env` local é criado com senhas aleatórias e fica ignorado pelo Git. As contas de exemplo usam `admin@example.test` e `aluno@example.test`; consulte `SEED_ADMIN_PASSWORD` e `SEED_USER_PASSWORD` no `.env` local para as respectivas senhas. O professor precisa receber as credenciais de demonstração em canal privado, caso sejam solicitadas. Alterar o `.env` não troca a senha de uma conta já criada.

Para encerrar, use `docker compose down`. Os dados ficam nos volumes; `docker compose down -v` remove o banco. A porta padrão é 8088 e pode ser configurada por `APP_PORT`. Em hospedagem HTTPS, defina `SESSION_SECURE=1`.

## Parte 3: páginas PHP

| Endereço | O que demonstra |
| --- | --- |
| `/catalogo.php` | Listagem pública dinâmica dos álbuns cadastrados no painel, com busca, filtro por ano e paginação (`?pagina=`) via **GET**. |
| `/contato.php` | Formulário **POST** com validação no servidor, sanitização (`filter_var`, `htmlspecialchars`), CSRF, mensagens de sucesso/erro e dados mantidos em caso de erro. Grava em `contact_messages`. |
| `/admin/` | CRUD de álbuns (login com `$_SESSION`, `password_hash`/`password_verify`, redirecionamento com `header` sem login). |
| `/admin/tracks.php` | Envio de faixas (`multipart/form-data`): MP3, OGG ou M4A de até 20 MB, licença obrigatória, tags ID3 e duração lidas com getID3. Excluir uma faixa remove o arquivo enviado e o álbum/artista que ficarem vazios. |
| `/admin/messages.php` | Registros do formulário de contato: listar, filtrar não lidas, marcar como lida e excluir. |
| `/admin/users.php` | Cadastros recebidos pelo formulário de criação de conta. |

Cabeçalho, menu e rodapé ficam em `backend/views/` (`header.php`, `menu.php`, `footer.php`) e são incluídos com `require` pela SPA, pelas páginas públicas e pelo painel. O acesso a dados e as regras de negócio ficam em `backend/src/Repositories/` e `backend/src/Controllers/`, separados do HTML. O script SQL de criação está em `backend/migrations/` (`001_schema.sql` e seguintes).

### Painel administrativo

<table>
  <tr>
    <td width="50%"><img src="docs/img/admin-albuns.png" alt="Painel: resumo e cadastro de álbuns"><br><sub><b>Álbuns:</b> resumo com contagens e CRUD.</sub></td>
    <td width="50%"><img src="docs/img/admin-faixas.png" alt="Painel: envio de faixas"><br><sub><b>Faixas:</b> envio de áudio com licença.</sub></td>
  </tr>
</table>

**Usuário de teste do painel:** criado pelo seed com `SEED_ADMIN_EMAIL`/`SEED_ADMIN_PASSWORD` do `.env`. Informe o email e a senha de teste no envio da Parte 3 pelo Classroom (não versionar a senha).

## Parte 2: frontend e GitHub Pages

A versão estática cobre Início, Catálogo, Busca, Álbum, Artista, Contato, Login, Cadastro, Biblioteca, Playlist, Curtidas, entrada administrativa, Painel de álbuns, Mensagens e Cadastros. O botão ☀/☾ no topo alterna entre tema claro e escuro, e a escolha fica salva no `localStorage`. Use `demo@onda.test` para o perfil comum ou `admin@onda.test` para o painel; qualquer senha com pelo menos 12 caracteres passa pela validação visual. Os dados são fictícios e as alterações ficam no `localStorage` deste navegador. O player reproduz tons gerados localmente. Essa demonstração não usa PHP nem banco.

Para executar somente o frontend com Docker Compose:

```sh
docker compose -f docker-compose.frontend.yml up -d --build
```

Abra [http://localhost:8089](http://localhost:8089). Encerre com `docker compose -f docker-compose.frontend.yml down`. `FRONTEND_PORT` altera a porta.

O exportador produz os mesmos arquivos para GitHub Pages:

```sh
python3 scripts/export_pages.py --output /tmp/onda-pages
```

A workflow `.github/workflows/pages.yml` publica essa pasta quando houver push na `main` ou execução manual. Nas configurações do repositório, escolha **GitHub Actions** como origem do Pages. Depois da primeira execução, abra a URL acima e confira as telas no celular e no desktop. O domínio próprio informado acima não substitui a URL do Pages.

## Importação e dados

`data/catalog.csv` guarda os metadados versionáveis. Áudios e capas locais ficam em `storage/audio/` e `storage/covers/`, fora do Git. Instruções de origem, licença e arquivos estão em [data/README.md](data/README.md); créditos em [ATTRIBUTIONS.md](ATTRIBUTIONS.md).

```sh
cp /caminho/faixa.mp3 storage/audio/
docker compose exec php-fpm php scripts/import_tracks.php \
  --dir=/app/storage/audio --csv=/app/data/meu_catalogo.csv
```

Também é possível enviar uma faixa pelo painel, em **Faixas**: o arquivo vai para `storage/audio/uploads/` (fora do Git) com nome derivado do conteúdo, e a licença é obrigatória.

Use um CSV com as colunas de `data/catalog.csv`. Metadados preenchidos no CSV têm prioridade sobre tags lidas com getID3. O importador é idempotente. Para baixar origens HTTPS autorizadas, use `sh scripts/download_audio.sh` e revise os créditos com `python3 scripts/attributions.py`.

No site, o dono de cada playlist pode enviar uma capa JPEG, PNG ou WebP de até 3 MB, trocar a imagem ou removê-la. A validação acontece no PHP; os arquivos ficam em `storage/covers/`, fora do Git. Para repetir o teste local com as faixas já importadas, mantenha o arquivo extraído em `storage/audio/_local-import/` e rode o mesmo comando `import_tracks.php --dir=/app/storage/audio/_local-import --license='Licença de uso local declarada pelo usuário; teste local, não redistribuir'`. Esse áudio não faz parte dos CSVs nem dos pacotes.

## Banco de dados

```mermaid
erDiagram
    users ||--o{ playlists : possui
    users ||--o{ liked_tracks : curte
    artists ||--o{ albums : publica
    artists ||--o{ tracks : interpreta
    albums ||--o{ tracks : contem
    playlists ||--o{ playlist_tracks : organiza
    tracks ||--o{ playlist_tracks : participa
    tracks ||--o{ liked_tracks : recebe
    contact_messages
```

As migrations SQL usam MariaDB/InnoDB. As chaves estrangeiras removem relações dependentes em cascata; faixas impedem excluir um álbum ocupado. Índices atendem relações e busca textual; listagens usam cursor. O áudio passa pelo Nginx via `X-Accel-Redirect`, com suporte a HTTP Range.

## API complementar

Além do catálogo, playlists e curtidas, a API JSON tem:

| Rota | O que faz |
| --- | --- |
| `PUT /api/me` · `PUT /api/me/password` · `DELETE /api/me` | Conta do próprio usuário: nome, troca de senha (pede a atual e renova a sessão) e exclusão (pede a senha). |
| `GET/POST/DELETE /api/history` | "Tocadas recentemente": o player registra a faixa após 10 s; guarda as últimas 500 por usuário. Tabela `play_history` (`005_play_history.sql`). |
| `PUT /api/playlists/{id}/tracks` | Reordena a playlist (`{"track_ids":[...]}` com todas as faixas). Na tela, arraste as linhas ou use Alt + ↑/↓. |
| `GET /api/tracks/random` | Faixas sorteadas para o botão "Tocar aleatório". |
| `GET /api/search?q=` | Na primeira página também traz `artists` e `albums`. |
| `GET /api/health` | Estado do banco e do áudio, usado pelo healthcheck do Docker. |

O login tem "Manter conectado por 30 dias". O player guarda a fila e a posição no navegador: ao recarregar, ou ao voltar de `/catalogo.php` e `/contato.php` (páginas PHP fora da SPA), ele continua de onde parou. Atalhos: Espaço/K toca e pausa, ←/→ avançam 5 s, Shift+←/→ trocam de faixa, M deixa mudo, / abre a busca.

## Validar

Com Docker em execução:

```sh
python3 tests/integration.py
python3 tests/admin.py
python3 tests/admin_tracks.py
python3 tests/importer.py
python3 tests/playlist_cover.py
python3 tests/contact.py
python3 tests/account_history.py
python3 scripts/check_repository.py
```

Os testes criam e removem registros temporários. A verificação de repositório detecta segredos conhecidos e arquivos acima de 5 MB; revise o resultado antes de publicar.

## Escalabilidade

O MariaDB, os índices e a paginação por cursor dão suporte a catálogos maiores. O Nginx serve os arquivos de áudio diretamente e permite seek por Range. PHP com PDO organiza API, controllers e repositórios. A autenticação atual usa sessão PHP, então a API não é totalmente stateless; para escalar múltiplas instâncias, a próxima etapa seria compartilhar sessões com Redis. Outras evoluções possíveis: fila para conversão com ffmpeg, réplicas de leitura e CDN.

Os pedidos feitos a ferramentas de IA estão em [PROMPTS.md](PROMPTS.md), com ferramenta, parte do trabalho, prompt e como a resposta foi usada.
