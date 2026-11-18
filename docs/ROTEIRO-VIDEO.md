# Roteiro do vídeo (Parte 4)

Grupo 2 — Spotify (Onda). Duração-alvo: **9min30** (o enunciado pede de 8 a 10 minutos). Os três aparecem na abertura e cada um narra e explica a parte que commitou.

## Divisão por commits

| Integrante | Commits | O que apresenta |
| --- | --- | --- |
| **Vicente** | `c6217f2` estrutura inicial · `beb1b21` materiais da Parte 1 · `0fee29b` README · `07a3b35` JS em `frontend/js` para o Pages · `b2da631` melhorias de UI (tema claro/escuro, capas geradas, contato no front, 404, Media Session) · documentação (roteiro, PROMPTS, README) | Site original, planejamento (mapa, wireframes, guia de estilo), tema e capas, uso de IA |
| **Cauan** | `ed5509c` HTML/CSS · `13ed63f` restante do front, Docker e testes (o JS dele foi movido para `frontend/js` em `07a3b35`, sem alterar o conteúdo; o `git blame` continua mostrando a autoria dele) | Navegação desktop/mobile, HTML semântico, CSS (variáveis, Grid/Flex, media queries), JavaScript (rotas, busca, player, validação, menu) |
| **Giovani** | *(a commitar)* `backend/`, `docker/php`, `docker/nginx`, `docker-compose.yml`, `.env.example`, `.dockerignore`, `tests/` | PHP: banco e PDO, includes, login do admin com sessão, CRUD, contato (POST), catálogo (GET), regras de negócio |

> O Giovani ainda não tem commits no GitHub. Ele precisa commitar o backend (Parte 3) com a própria conta **antes** da gravação, para que o que ele explica no vídeo bata com o histórico do Git.

## Antes de gravar

- [ ] **Álbum pessoal:** o banco local tem o *Acústico MTV – Engenheiros do Hawaii*, ripado de um CD comprado, para uso pessoal (o Onda vai virar um servidor de música self-hosted). Para o vídeo e para qualquer instalação pública, use só o catálogo fictício: grave a partir de um banco limpo (`docker compose -p onda-video ...`) ou evite abrir esse álbum na tela.
- [ ] Subir a stack (`docker compose up -d`) e abrir http://localhost:8088. Ter em mãos as contas do `.env` (`SEED_ADMIN_*` e `SEED_USER_*`).
- [ ] Enviar 1 ou 2 mensagens de teste pelo `/contato.php`, para o painel não aparecer vazio.
- [ ] Deixar abertos: o Spotify (site original), o Draw.io com o mapa e os wireframes, o VS Code e o DevTools no modo celular (iPhone 12/13, 390 px).
- [ ] Zoom do navegador e do editor em 125% para o código ficar legível.

---

## 0:00 – 0:40 · Abertura (todos aparecem)

**Na tela:** página inicial do Onda.

**Vicente:** "Somos o grupo 2: Cauan, Giovani e Vicente. Fizemos o Onda, um clone educacional do Spotify em HTML, CSS, JavaScript e PHP com MariaDB. Cada um vai mostrar a parte que desenvolveu."

**Cauan e Giovani:** cada um se apresenta em uma frase.

## 0:40 – 2:30 · Vicente: site original e Parte 1

1. **Spotify (20 s):** mostrar a home, a busca, uma página de álbum, a biblioteca e o player fixo embaixo. "Reproduzimos essa estrutura: menu lateral, conteúdo central e player."
2. **Planejamento (`docs/PLANEJAMENTO.md`):** público-alvo, objetivo e as regras de negócio:
   - só o dono altera a própria playlist, e uma faixa não se repete nela;
   - um álbum que tem faixas não pode ser excluído;
   - cada email envia no máximo 3 mensagens de contato em 24 horas.
3. **Mapa do site (`docs/mapa-site.drawio`):** páginas públicas (Início, Busca, Catálogo, Álbum, Artista, Contato), restritas (Biblioteca, Playlist, Curtidas, Painel) e formulários.
4. **Wireframes (`docs/wireframes.drawio`):** uma tela no desktop e a mesma no celular.
5. **Guia de estilo:** paleta preto `#080808`, painel `#151515`, azul `#add8e6`, branco, e a fonte Inter. "Trocamos o verde do Spotify por azul para ter identidade própria."

## 2:30 – 5:30 · Cauan: front-end (Parte 2)

**Navegação no desktop (1 min):**
- Início, cards de álbuns, clique num álbum, ▶ Reproduzir (o player embaixo mostra capa e progresso), avançar faixa e fila.
- Busca: digitar "Aurora". O resultado aparece sem recarregar a página.
- Entrar com a conta comum, curtir uma faixa (♡), criar uma playlist no modal e adicionar uma faixa a ela.
- Tentar enviar o login vazio. A mensagem de erro aparece abaixo do campo, sem `alert`.

**Navegação no celular (30 s, DevTools a 390 px):** botão ☰ Menu abrindo e fechando, cards em 2 colunas, player compacto.

**Código (1min30):**
- `frontend/index.html`: tags semânticas `header`, `nav`, `main`, `section`, `aside` e `footer`; o rodapé com o aviso de clone educacional; o `alt` das imagens.
- `frontend/css/app.css:1`: variáveis CSS (`:root`) com cores e fonte.
- `frontend/css/layout.css:249-285`: os breakpoints (1500 px, 1100 px e 720 px), Grid nos cards e Flex no player.
- `frontend/js/router.js:6`: rotas por hash, com cada tela carregada sob demanda.
- `frontend/js/site.js:37`: menu mobile com `aria-expanded`.
- `frontend/js/site.js:52`: validação dos formulários com `checkValidity()` e mensagem inserida no DOM.
- `frontend/js/views/search.js:64`: busca com espera de 250 ms (debounce) e cancelamento da requisição anterior.
- **GitHub Pages:** `scripts/export_pages.py` gera a versão estática, com os dados lidos de `demo/catalog.json` e as alterações salvas no `localStorage`.

## 5:30 – 8:30 · Giovani: back-end PHP (Parte 3)

**Demonstração (1min30):**
1. Abrir `/admin/` sem login. O PHP redireciona para `/admin/login.php`.
2. Errar a senha e ver a mensagem de erro. Entrar com a conta admin. No topo do painel aparecem os contadores (álbuns, faixas, usuários e mensagens não lidas), calculados com `COUNT` no banco.
3. **CRUD:** cadastrar um álbum, editar o ano, mostrar o álbum em `/catalogo.php` (a listagem pública), voltar e excluí-lo. Tentar excluir um álbum que tem faixas: aparece o bloqueio da regra de negócio.
4. **Contato:** em `/contato.php`, enviar com email inválido. O PHP devolve o erro e mantém os campos preenchidos. Enviar uma mensagem válida e mostrar o aviso de sucesso. No painel, abrir **Mensagens**, marcar como lida e excluir.
5. Em `/catalogo.php?q=Mar`, mostrar a busca por **GET** com o parâmetro na URL. Depois clicar em **Próxima →** e mostrar `?pagina=2` na URL (paginação com `LIMIT`/`OFFSET`, 6 álbuns por página).
6. Clicar em **Sair** para fazer o logout.

**Código (1min30):**
- `backend/views/header.php`, `menu.php` e `footer.php`: os includes com `require`, usados em `site.php`, `catalogo.php`, `contato.php` e no painel.
- `backend/src/Database.php:10`: conexão PDO com `ERRMODE_EXCEPTION` e prepared statements (sem emulação).
- `backend/src/Admin.php:10`: `guard()` com `header('Location: ...')` quando não há sessão.
- `backend/src/Admin.php:36-46`: `password_verify`, `session_regenerate_id` e `$_SESSION`. A senha é gravada com `password_hash` em `backend/scripts/seed.php:61`.
- `backend/public/contato.php`: CSRF, validação, `htmlspecialchars` na saída e redirecionamento depois do POST (Post/Redirect/Get).
- `backend/src/Repositories/ContactRepository.php:27`: `filter_var` para email, regex para nome e telefone e a regra de 3 mensagens por dia (linha 57).
- `backend/src/Repositories/AdminRepository.php:28`: a regra "não excluir álbum com faixas".
- `backend/public/catalogo.php`: paginação (conta o total, calcula as páginas, valida `pagina` com `filter_var` e monta os links com `http_build_query`, mantendo a busca).
- `backend/public/admin/index.php`: contadores do painel com subconsultas `COUNT(*)`.
- `backend/migrations/001_schema.sql` e `004_contact_messages.sql`: o script SQL de criação das tabelas.

## 8:30 – 9:30 · Vicente: melhorias finais e encerramento

- Abrir **⚙ Configurações** no menu: trocar o tema (Escuro, Claro ou Sistema) e as animações; tudo muda na hora e fica salvo no `localStorage`. Código em `frontend/js/prefs.js` (aplica as escolhas no `<head>`, antes de a página aparecer, para não piscar), `frontend/js/views/settings.js` (a tela) e `frontend/css/app.css` (`:root[data-theme="light"]` redefine as mesmas variáveis).
- **Animações no estilo do Spotify:** abrir um álbum (o topo ganha um degradê com a cor da capa, calculada num `canvas` em `dominantColor`, `frontend/js/ui.js`); passar o mouse num card (o fundo clareia e o botão ▶ sobe), numa faixa (o número vira ▶) e na barra de progresso; curtir uma faixa (o coração pula); tocar (a capa e o título do player entram com fade, a faixa atual fica azul com equalizador); abrir a fila (desliza da direita). Em Configurações → Animações → Desligadas tudo fica parado. CSS no fim de `frontend/css/layout.css`.
- Capas geradas: cada álbum recebe uma capa SVG própria, criada a partir do id. Código em `frontend/js/ui.js:18`.
- **Media Session:** com uma faixa tocando, usar as teclas de mídia do teclado (ou a central de mídia do sistema) para pausar e avançar; o título e a capa aparecem fora do navegador. Código em `frontend/js/player.js`, função `updateMediaSession` (`navigator.mediaSession.metadata` e `setActionHandler`).
- Abrir `#/nao-existe` para mostrar a página 404.
- **Uso de IA:** "Usamos Codex e Claude Code, com tudo registrado no `PROMPTS.md`: ferramenta, prompt completo e como usamos a resposta. Revisamos o código e sabemos explicar cada parte."
- **Encerramento:** "O código está no GitHub, a versão estática no GitHub Pages, e o README tem as instruções e o usuário de teste. Obrigado!"

---

## Perguntas que o professor pode fazer

| Para | Pergunta provável | Onde está a resposta |
| --- | --- | --- |
| Cauan | Como o menu mobile abre e fecha? | `site.js:37`: `classList.toggle('menu-open')` mais o CSS da classe no breakpoint de 720 px |
| Vicente | O que é a Media Session? | API do navegador que expõe a faixa atual e os controles ao sistema operacional (`player.js`, `updateMediaSession`) |
| Cauan | Por que `novalidate` nos formulários? | Para usar mensagens próprias no DOM em vez das bolhas do navegador (`site.js:52`) |
| Giovani | O que impede SQL injection? | Prepared statements do PDO com `?` e `EMULATE_PREPARES => false` |
| Giovani | E XSS? | `htmlspecialchars` com `ENT_QUOTES` em toda saída (`h()` em `views/layout.php`) |
| Giovani | Como funciona a paginação? | `catalogo.php`: `count(*)` dá o total, `ceil(total/6)` as páginas, e `OFFSET (pagina-1)*6`; página inválida volta para a 1 e acima do total vai para a última |
| Giovani | Diferença entre GET e POST aqui? | GET para consulta, sem efeito colateral (`catalogo.php`); POST para gravar (`contato.php`, painel) |
| Vicente | Onde a regra de negócio é validada? | No PHP, independentemente do JS: `ContactRepository::validate`, `AdminRepository::delete` e `PlaylistRepository` |
| Vicente | Como o tema funciona sem recarregar? | `prefs.js` coloca `data-theme` e `data-motion` no `<html>`, e o CSS troca as variáveis; a escolha fica no `localStorage` |
| Vicente | E quem tem sensibilidade a movimento? | A opção "Automático" respeita `prefers-reduced-motion`; "Desligadas" zera `animation` e `transition` |
