# Planejamento do Onda — base para revisão

Este texto foi organizado depois da implementação para ajudar o grupo a preparar a Parte 1. Não deve ser apresentado como registro cronológico de planejamento anterior ao código.

## Propósito, público e conteúdo

O Onda é um mock educacional inspirado em serviços de streaming, pensado para demonstrar catálogo, busca, reprodução e organização de músicas. O público são pessoas que querem encontrar faixas e reuni-las em playlists. O conteúdo inclui páginas de início, busca, catálogo, artistas, álbuns, player, login/cadastro, biblioteca, playlists e curtidas; a área administrativa permite manter os álbuns e consultar cadastros.

Artistas, álbuns e títulos da demonstração são fictícios. Áudios são tons curtos gerados para o projeto. O rodapé declara que é um clone educacional sem vínculo com o Spotify.

## Regras de negócio

1. Só o dono de uma playlist pode consultá-la ou alterá-la.
2. Uma faixa pode constar no máximo uma vez em cada playlist.
3. A reprodução é autorizada somente quando há sessão autenticada.
4. Apenas uma conta com papel `admin` pode alterar álbuns e consultar os cadastros; álbum com faixas não pode ser removido pelo painel.

O grupo deve confirmar que essas regras expressam o comportamento que deseja apresentar e escolher pelo menos duas para explicar no PDF e no vídeo.

## Mapa e protótipos

O mapa e os wireframes de desktop e celular estão como arquivos editáveis em `docs/mapa-site.drawio` e `docs/wireframes.drawio`. São rascunhos gerados a partir das telas já existentes. Abram no diagrams.net, confirmem links, conteúdo e organização com todos os integrantes, ajustem antes de entregar e mantenham o arquivo-fonte. As 26 páginas do wireframe cobrem 13 telas em dois tamanhos.

Telas desenhadas: Início, Catálogo, Busca, Álbum, Artista, Login, Cadastro, Biblioteca, Playlist, Curtidas, Login administrativo, Painel de álbuns e Cadastros. Cada tela tem rascunho desktop e mobile. A playlist mostra a capa e o controle para enviar/trocar/remover uma imagem. Cada área restrita exige login apropriado. O menu mobile, player fixo nas telas públicas/usuário, formulários, erros e ações CRUD devem ser comparados com a aplicação.

## Guia visual em uso

- Preto `#080808` como fundo e `#151515` nas superfícies.
- Branco `#FFFFFF` no texto principal e cinza `#B8B8B8` no texto secundário.
- Azul claro `#ADD8E6` como cor auxiliar para foco, links e estado ativo.
- Família declarada Inter com fallback Arial/Helvetica/sans-serif. Inter não é baixada pelo projeto.
- Cards de álbum, tabelas, campos com rótulos, botões arredondados, foco visível e mensagens de estado.
- Logo textual, capa de fallback e favicon locais em SVG; sem biblioteca externa de ícones.

Essa paleta foi uma decisão do grupo após o pedido inicial de verde Spotify. O PDF pede identidade visual fiel ao site de referência; expliquem a decisão e confirmem se o professor aceita a adaptação.

## Preparação da entrega

Montar o PDF no template SBC fornecido pelo curso, sem abstract/resumo, contendo propósito, público, regras, mapa, wireframes, guia e prompts. Anexar o arquivo editável de cada diagrama e conferir o acesso. `PROMPTS.md` registra esta conversa; acrescentar os prompts das demais conversas e ferramentas sem inventar histórico ou percentuais de autoria.
