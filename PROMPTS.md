# Uso de IA no projeto

Ferramentas: **OpenAI Codex** e **Claude Code (Claude Opus 5.5, Anthropic)**. O grupo declara ter desenvolvido a maior parte do projeto; o registro abaixo resume os pedidos de apoio feitos nesta conversa e não atribui autoria por arquivo nem estima percentuais.

| Etapa | Pedido resumido | Como foi usado |
| --- | --- | --- |
| Estilização | Deixar a interface preta, branca e azul; usar a imagem enviada como referência e adicionar favicon. | Ajustes de CSS, favicon e capa padrão. |
| Correção | “coloque o projeto rodar” | Conferência local; foi removida uma sequência de caracteres inválidos de `frontend/js/api.js`. |
| Requisitos | “verifique se esta de acordo” e “mantenha a estrutura do site com psql”; incluir casos de uso e mapa do site. | Comparação dos requisitos, rascunhos editáveis e documentação. A diferença para MySQL/MariaDB ficou registrada. |

Os pedidos de estilização e correção estão separados dos pedidos de implementação e organização. Esta versão é um **resumo**, não uma transcrição literal dos prompts longos. O enunciado pede prompts completos: antes de entregar, o grupo deve anexar o histórico integral exigido pelo professor e acrescentar pedidos feitos em outras conversas ou ferramentas.

## Claude Code — revisão das Partes 2 e 3 (17/11/2026)

**Ferramenta:** Claude Code (modelo Claude Opus 5.5). **Partes:** 2 (frontend) e 3 (PHP).

**Prompt 1 (completo):**

> verifique o com esta o projeto e o que pode ser melhrado. verifique o pdf que esta na pasta pois ele descreve o trabalho

**Como foi usado:** a IA comparou o enunciado em PDF com o código, rodou os testes e consultou o repositório no GitHub. Ela apontou estes problemas: o GitHub Pages não estava habilitado (a workflow falhava), o `docker-compose.frontend.yml` citado no README não existia, havia diferença entre o repositório remoto e a pasta local, faltavam commits de um integrante, o `PROMPTS.md` estava incompleto e o `tests/admin.py` falhava por causa do limite de tentativas de login. O grupo usou a lista para priorizar a entrega.

**Prompt 2 (completo):**

> pode fazer o que for preciso

**Como foi usado:** a IA gerou as alterações abaixo, que o grupo revisou e testou localmente:

- tema claro/escuro com variáveis CSS e escolha salva no `localStorage` (`frontend/css/app.css` e `frontend/js/site.js`);
- página de contato na versão estática (`frontend/js/views/contact.js`) e aba de mensagens no painel de demonstração;
- atributos `pattern` e `type="tel"`, com mensagens de validação para `pattern`, `min` e `max`;
- páginas PHP públicas `/catalogo.php` (GET) e `/contato.php` (POST), a migration `004_contact_messages.sql`, o `ContactRepository` com a regra de no máximo 3 mensagens por email a cada 24 horas e a página `/admin/messages.php`;
- validação do nome no cadastro também no PHP;
- `docker-compose.frontend.yml`, o teste `tests/contact.py`, a correção do `tests/admin.py` e a atualização do README.

**Prompt 3 (completo):**

> quanto a UI consegue dar uma melhorada no processo final? e fazer um script do video dividindo entre os commits

**Como foi usado:** a IA revisou as telas com capturas no desktop e no celular e aplicou estas melhorias:

- capas SVG geradas para cada álbum e playlist (`frontend/js/ui.js`);
- botão ▶ que aparece ao passar o mouse sobre os cards;
- conta e botão de tema agrupados à direita do topo, com versão compacta no celular;
- vitrine "Navegar por todos" na busca vazia;
- aviso de demonstração em formato de selo;
- remoção do espaço vazio no login;
- liberação de `data:` no `img-src` da CSP do nginx, para as capas geradas aparecerem.

Também escreveu o roteiro do vídeo em `docs/ROTEIRO-VIDEO.md`, dividido por integrante conforme os commits. O grupo ajustou falas e tempos.
