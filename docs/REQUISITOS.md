# Conferência do trabalho

Revisão do PDF **Trabalho III — Desenvolvimento Web I**, IFC Campus Concórdia, professor Alisson Borges Zanetti, e dos arquivos locais em 03/11/2026. O código atualizado foi exercitado localmente; esta lista não confirma publicação pública, autoria, aprovação do professor nem a gravação da apresentação.

**Resultado: atendimento parcial.** O banco foi convertido para MariaDB para atender ao requisito do PDF. Os documentos de planejamento e diagramas abaixo são rascunhos retrospectivos, ainda precisam da revisão dos três integrantes e não substituem o template SBC.

## Identificação e prazos

Grupo 2 — Spotify: **Cauan Riffel, Giovani Pott e Vicente Pilonetto**.

| Etapa | Prazo no PDF | Peso | Estado |
| --- | --- | --- | --- |
| Parte 1 — proposta, mapa e wireframes | 10/11/2026 | 1,5 | Rascunhos editáveis disponíveis; PDF no template SBC e validação pelo grupo pendentes. |
| Parte 2 — frontend | 17/11/2026 | 3,5 | Site e modo de exportação estática preparados; publicar e conferir GitHub Pages ainda pendente. |
| Parte 3 — PHP e banco | 24/11/2026 | 3,5 | API, includes PHP, painel com CRUD e MariaDB implementados. |
| Parte 4 — vídeo | 24/11/2026 | 1,5 | O grupo precisa gravar 8–10 minutos com participação de todos. |

## Parte 1 — proposta e protótipo

| Exigência | Estado e evidência |
| --- | --- |
| Propósito, público e conteúdo | Rascunho em `docs/PLANEJAMENTO.md`; o grupo deve revisar. |
| Duas regras de negócio | Mais de duas estão descritas no planejamento: propriedade da playlist, faixa sem duplicação, sessão para tocar e autorização administrativa. Confirmar a redação final. |
| Mapa em ferramenta editável | Rascunho Draw.io em `docs/mapa-site.drawio`. Abrir no diagrams.net, conferir fluxos e salvar versão aprovada. |
| Wireframes de todas as telas para desktop e celular | `docs/wireframes.drawio` tem 26 páginas editáveis (13 telas x 2 tamanhos) em estado de rascunho. Revisar dimensões e fidelidade antes de entregar. |
| Pelo menos cinco páginas públicas e duas restritas | O site tem rotas públicas para início, catálogo, busca, álbum, artista, login e cadastro; rotas restritas para biblioteca, playlist, curtidas e painel. A implementação principal ainda é SPA; confirmar com o professor se as rotas são aceitas como páginas distintas. |
| Paleta, tipografia e componentes | Guia no planejamento e CSS. A paleta atual preta/branca/azul foi escolhida pelo grupo e se afasta do verde Spotify; confirmar se a adaptação é aceita como fidelidade visual. |
| PDF no template SBC, sem abstract/resumo, fontes editáveis | Ainda não produzido. Anexar PROMPTS.md como pede o enunciado e enviar também os arquivos editáveis. |

## Parte 2 — frontend

| Exigência | Estado e evidência |
| --- | --- |
| HTML5, CSS3 e JavaScript próprios, sem frameworks proibidos | Implementado em `frontend/` com módulos ES, sem framework de interface nem etapa de build. |
| Elementos semânticos | `header`, `nav`, `main`, `section`, `aside` e `footer`. |
| Pelo menos dois formulários | Login, cadastro, contato, busca e formulários de playlist/painel. Campos têm rótulos, tipos `email`/`tel`/`number`/`search`, `required`, `minlength`, `min`/`max` e `pattern` (nome e telefone), com mensagens de validação. |
| Tabela HTML | Catálogo público e painel administrativo usam tabela semântica. |
| Mídia com texto alternativo | Capas do catálogo recebem `alt` descritivo; o player usa `<audio>`. Revisar textos e conteúdo final. |
| CSS externo, variáveis de cor/fonte, Grid/Flex, breakpoints, estados e transições | Implementado em `frontend/css/`; conferido no código, precisa de revisão visual no navegador e comparação com os wireframes. |
| Validação JavaScript inline e menu mobile abrir/fechar | Implementados em `frontend/js/site.js`; erros aparecem junto aos campos e o botão de menu atualiza `aria-expanded`. |
| Duas interações JavaScript e acessibilidade de teclado | Busca dinâmica, conteúdo gerado a partir de JSON (`demo/catalog.json`), dados salvos no `localStorage`, alternância de tema claro/escuro, player e modais; foco visível e rótulos nos controles. Recomenda-se testar manualmente a ordem de foco em todos os estados. |
| README com site, integrantes e instruções | Incluído neste README. |
| GitHub Pages | `scripts/export_pages.py` produz as 13 telas com dados fictícios locais; `docker-compose.frontend.yml` executa somente o frontend e `.github/workflows/pages.yml` publica na `main` (o Pages precisa estar com origem **GitHub Actions** nas configurações). |

## Parte 3 — PHP, dados e administração

| Exigência | Estado e evidência |
| --- | --- |
| PHP sem framework e persistência via PDO | Controllers/repositórios usam PDO e prepared statements. |
| MySQL/MariaDB | Atendido com MariaDB/InnoDB, PDO MySQL e migrations SQL em `backend/migrations/`. |
| Páginas PHP com includes header/menu/footer | Includes compartilhados em `backend/views/`, usados pela SPA (`site.php`), pelas páginas públicas tradicionais `catalogo.php` (GET) e `contato.php` (POST) e pelo painel. |
| Separar regras e acesso a dados | Repositórios e controllers separados dos templates. |
| Formulários GET/POST, validação no servidor, mensagens e preservação de campos | Login administrativo e operações do painel usam POST, CSRF, validação PHP, mensagens e valores preservados; API também valida operações JSON. Rever se o professor exige esse fluxo em cada formulário. |
| Sessão, hash de senha e logout | Sessão com cookie seguro conforme configuração, `password_hash`/`password_verify`, regeneração de sessão e logout POST. |
| Painel protegido por redirect | `backend/public/admin/` exige role admin e redireciona visitantes para login. Usuário comum recebe bloqueio. |
| CRUD de uma entidade e listagem pública | Painel permite criar/editar/excluir álbuns vazios; álbum criado aparece no catálogo. Exclusão de álbum com faixas é recusada. |
| Listar registros de formulário no painel | `/admin/messages.php` lista, filtra, marca como lida e exclui as mensagens do formulário de contato; `/admin/users.php` lista cadastros sem expor hashes de senha. |
| Duas regras de negócio em PHP | Faixa não se repete na playlist e só o dono a altera; álbum com faixas não pode ser excluído; no máximo 3 mensagens de contato por email em 24 horas. Descrever e demonstrar no vídeo. |
| Script SQL de criação | Migrations numeradas em `backend/migrations/` compatíveis com MariaDB. |
| Credencial de teste do painel | A senha é criada no `.env` local. Enviar acesso demonstrativo ao professor por canal privado se exigido; nunca adicionar a senha ao Git. |

## Parte 4 — apresentação

O grupo deve gravar 8–10 minutos com os três participantes. Mostrar protótipo, desktop e celular, formulários, busca/player, painel CRUD, e explicar HTML/CSS/JS/PHP e as regras de negócio. Cada integrante deve explicar o código que desenvolveu. O roteiro sugerido está em `docs/ENTREGAS.md`; a gravação não foi feita nesta revisão.

## Prompts e contribuições

`PROMPTS.md` resume os pedidos visíveis nesta conversa por categoria e informa a ferramenta e o uso. O PDF pede o histórico completo; portanto, o resumo precisa ser complementado antes da entrega. O grupo declara ter desenvolvido a maior parte do projeto; esse registro não mede autoria nem substitui o histórico do Git. Cada integrante deve explicar suas contribuições reais.

## Verificações feitas

O painel foi exercitado com conta admin e usuário comum: redirect, acesso, CSRF, validação, criação/edição/exclusão, catálogo público, listagem de cadastros e logout. Também foram conferidos sintaxe PHP/JavaScript e o arquivo de configuração do Nginx. Os comandos reproduzíveis estão no README. A publicação pública e revisão visual de todas as telas continuam fora desta conferência.
