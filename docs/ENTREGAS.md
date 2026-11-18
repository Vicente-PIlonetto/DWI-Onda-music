# Entregas e divisão do grupo

Grupo 2 — Spotify: **Cauan Riffel, Giovani Pott e Vicente Pilonetto**. O commit inicial mínimo já está publicado; os seis pacotes abaixo o seguem em ordem e acompanham as etapas do PDF. Cada pessoa deve revisar, entender e registrar suas alterações reais. Os nomes de autores não foram atribuídos automaticamente.

| Pacote | Etapa | Conteúdo |
| --- | --- | --- |
| 01 — Planejamento e requisitos | Parte 1 | Requisitos, planejamento, mapa, wireframes e prompts. |
| 02 — Estrutura e visual | Parte 2 | HTML, CSS, favicon, assets e componentes visuais. |
| 03 — Navegação e interações | Parte 2 | Módulos JS, rotas, player, formulários, menu mobile e exportação estática. |
| 04 — Dados e infraestrutura | Parte 3 | Docker, MariaDB, migrations, seed e conexão. |
| 05 — Backend e acesso | Parte 3 | API, autenticação, includes PHP, regras, painel e CRUD. |
| 06 — Importação, testes e documentação | Parte 3 e preparação da Parte 4 | Importador, catálogo, testes, README e créditos. |

Divisão sugerida em pares: uma pessoa 01+06, outra 02+03, outra 04+05. É uma sugestão por área; acertem a distribuição entre si e façam commits identificados com as contas de cada integrante. O conteúdo precisa ser revisado no GitHub após a integração.

## Datas do enunciado

- **10/11/2026:** proposta, mapa/wireframes editáveis, template SBC sem resumo e prompts anexos.
- **17/11/2026:** frontend, repositório e publicação GitHub Pages, README com site, nomes e instruções.
- **24/11/2026:** implementação PHP, banco SQL, painel, credenciais de demonstração e prompts atualizados.
- **24/11/2026:** vídeo de 8–10 minutos, com todos os integrantes apresentando.

## Pendências antes da entrega

1. Conferir a compatibilidade do MariaDB no ambiente de avaliação e revisar o relatório SBC.
2. Revisar os rascunhos Draw.io, montar o documento SBC e acrescentar os prompts completos usados em outros chats/ferramentas.
3. Enviar a `main` ao GitHub, selecionar GitHub Actions como origem do Pages, conferir a URL prevista no README e testar as 13 telas da versão estática.
4. Revisar as páginas no celular e teclado, incluindo o fluxo de acessibilidade e as credenciais para avaliação.
5. Gravar o vídeo explicando protótipos, funcionamento, regras e partes do código por cada integrante.

## Roteiro de vídeo

O roteiro detalhado, com tempos, falas, telas e trechos de código divididos por integrante conforme os commits, está em [ROTEIRO-VIDEO.md](ROTEIRO-VIDEO.md).

## Roteiro rápido de teste local

1. Rode `python3 scripts/configure.py` e `docker compose up -d --build`.
2. Entre com a conta comum indicada no `.env` e crie uma playlist.
3. Abra a playlist, escolha uma imagem JPEG, PNG ou WebP de até 3 MB e envie a capa.
4. Confira a capa na playlist, nos cards da biblioteca e na navegação lateral; teste reproduzir uma faixa e avançar pelo seek.
5. Reduza a janela para celular e repita a navegação. Em tela estreita, o menu inferior mostra as seções e a página de playlist reorganiza capa e controles.

O guia de distribuição está em `entrega-contribuintes/COMO-DISTRIBUIR.md`. Não aplicar pacotes antigos sobre a sequência nova.
