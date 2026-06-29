# create-feature-readme — README.md por feature

Você é especialista em criar ou atualizar **README.md** na raiz de cada feature do projeto. O documento deve permitir que qualquer desenvolvedor entenda rapidamente o que a feature faz, como funciona e onde estão os artefatos.

<critical>Gere apenas o arquivo **README.md** na raiz de cada feature. Não crie outros arquivos.</critical>
<critical>O nome do arquivo deve ser exatamente **README.md** (maiúsculas).</critical>
<critical>Todo o conteúdo deve ser em **português brasileiro (pt-BR)**.</critical>
<critical>Se a feature tiver endpoint: use **curl** em bloco `bash`; a URL deve começar com **https://base-url/api/v1**; inclua os headers **authorization: Bearer token**, **content-type: application/json** e **x-localization: pt_BR**; o body JSON deve ter **todos os campos possíveis** (raiz e aninhados).</critical>

---

## Criar ou atualizar

O desenvolvedor pode estar **criando** o README ou **atualizando** o README:

- **README.md já existe** — O programador fez alterações relevantes na feature e quer que o README.md reflita o estado atual. **Atualize** o arquivo: leia o README atual e os arquivos da feature, incorpore as mudanças e regenere o conteúdo mantendo o padrão (visão geral, fluxo de execução; requisição/resposta HTTP apenas se a feature tiver endpoint).
- **README.md não existe** — O programador está documentando a feature pela primeira vez. **Crie** o README.md do zero na raiz da feature, seguindo o padrão deste comando.

Em ambos os casos o resultado é um único arquivo **README.md** na raiz da feature, atualizado e alinhado ao código atual.

---

## Entrada do comando

O usuário pode invocar de três formas:

1. **Uma feature** — caminho direto da pasta da feature  
   Ex.: `/create-feature-readme @app/Modules/Financial/Finance/Features/FinanceCreate`

2. **Várias features** — vários caminhos de pastas de features  
   Ex.: `/create-feature-readme @.../FinanceCreate @.../FinanceFind @.../FinanceDelete`  
   **Processe uma feature por vez:** leia apenas os arquivos da feature atual → gere e salve o README.md dessa feature → em seguida passe para a próxima, sem misturar conteúdo entre features. Cada feature recebe seu próprio README.md na sua própria pasta.

3. **Módulo/recurso** — caminho do módulo ou recurso (ex.: pasta do domínio/recurso)  
   Ex.: `/create-feature-readme @app/Modules/Financial/Finance`  
   Nesse caso: **descubra todas as pastas de feature** desse módulo e crie um README.md para cada uma.

### Como descobrir pastas de feature (quando a entrada é módulo/recurso)

Consulte **@.cursor/rules/project.md**. A convenção é:

- **Features no recurso:** `{caminho}/Features/{FeatureName}/` — cada subpasta direta de `Features/` é uma feature (ex.: FinanceCreate, FinanceFind).
- **Features em contextos:** `{caminho}/Contexts/{ContextName}/Features/{FeatureName}/` — para cada contexto, cada subpasta direta de `.../Features/` é uma feature (ex.: FinanceHistoryCreate, FinanceHistoryList).

Quando o usuário informar um caminho que **não** é uma pasta dentro de `.../Features/NomeDaFeature` (ex.: `app/Modules/Financial/Finance`), trate como **módulo/recurso**:

1. Liste as pastas em `{caminho}/Features/` (se existir) — cada uma é uma feature.
2. Para cada pasta em `{caminho}/Contexts/` (se existir), liste as pastas em `{contexto}/Features/` — cada uma é uma feature.
3. A lista final é o conjunto de todos esses caminhos de feature. Processe **um README.md por feature**, na raiz de cada uma.

---

## Fluxo de trabalho

### 1. Resolver a lista de features

- Se a entrada for **uma ou mais pastas de feature** (caminhos que já apontam para uma pasta dentro de `.../Features/NomeDaFeature`): use esses caminhos como lista de features.
- Se a entrada for **uma pasta de módulo/recurso**: descubra todas as pastas de feature (como acima) e monte a lista.
- Cada item da lista é o **caminho absoluto (ou relativo ao repo) da pasta da feature**, onde o README.md deve ficar.

### 2. Para cada feature (uma de cada vez)

- **Verificar se README.md existe:** se existir, você está **atualizando** (o dev alterou a feature); se não existir, você está **criando** do zero.
- **Ler a feature:** leia os arquivos relevantes dessa pasta (Controllers, Services, Requests, Dtos, FilterDtos, Repositories/Commands, Repositories/Queries, Dao/Queries, Enums, etc.). Se houver README atual, leia também para preservar o que ainda vale e refletir as mudanças no código.
- **Gerar o README.md:** escreva (ou reescreva) o conteúdo seguindo o padrão abaixo, claro e direto, alinhado ao código atual.
- **Salvar:** grave **apenas** o arquivo `README.md` na **raiz** da pasta da feature (ex.: `.../FinanceCreate/README.md`).
- Ao passar para a **próxima feature**, foque só nela; não misture descrições ou exemplos de outras features.

### 3. Padrão do README (formato obrigatório)

O README deve seguir **sempre** a estrutura abaixo. Seja **curto, claro e direto**.

#### Estrutura fixa (todas as features)

1. **Título**  
   `# {NomeDaFeature} — {Descrição em uma linha}`  
   Ex.: `# FinanceCreate — Criação de operação financeira`

2. **## Visão geral**  
   Um **único parágrafo** que mescla: o que a feature faz (objetivo e contexto) e, em seguida, o resumo do fluxo (validação, persistência/storage, histórico, resposta). Não separar em "o que faz" e "como faz"; tudo no mesmo bloco sob **## Visão geral**.

3. **## Fluxo de execução**  
   Lista numerada com os passos principais (Request, Controller, Service com subitens). Adapte ao que a feature realmente faz.

#### Quando a feature **tem** requisição HTTP (rota/endpoint de API)

4. **## Requisição HTTP** — Bloco de código **`bash`** com um exemplo **curl** completo.  
   - **Endpoint obrigatório:** a URL deve **sempre** começar com `https://base-url/api/v1` (ex.: `https://base-url/api/v1/financial/finances`). Não use variáveis de ambiente no exemplo; use literalmente `https://base-url` para que o desenvolvedor saiba o padrão da API.  
   - **Headers obrigatórios:** o exemplo curl deve incluir **sempre** estes headers (nesta ordem recomendada):  
     - `-H 'accept: application/json, text/plain, */*'`  
     - `-H 'authorization: Bearer token'` (substitua `token` pelo valor real em uso ou deixe como placeholder)  
     - `-H 'content-type: application/json'`  
     - `-H 'x-localization: pt_BR'`  
   - **Corpo JSON obrigatório:** o exemplo do corpo da requisição (`--data-raw '...'`) deve incluir **todas as possibilidades de campo** aceitas pela feature: todos os campos validados no Request (e nos DTOs correspondentes), incluindo opcionais e aninhados. Para objetos dentro de arrays (ex.: `finance_installments.*`, `finance_attachments.*`), liste **todos** os campos possíveis de cada item. **Não use um exemplo mínimo** — o desenvolvedor que lê o README deve ver naquele bloco a lista **completa** de campos que a API aceita. É fundamental que o body JSON do exemplo reflita a estrutura completa do contrato da API.

   **Exemplo de formato (bash/curl):**

   ```bash
   curl 'https://base-url/api/v1/financial/finances' \
     -X 'POST' \
     -H 'accept: application/json, text/plain, */*' \
     -H 'authorization: Bearer token' \
     -H 'content-type: application/json' \
     -H 'x-localization: pt_BR' \
     --data-raw '{
     "finance_type_id": 1,
     "entity_id": null,
     ...
     }'
   ```

5. **## Resposta HTTP** — Tabela (Status | Descrição) com 201, 4xx, 5xx etc. Em seguida: **Exemplo de resposta (201 Created)** em bloco de código JSON com o corpo típico (DTO de visualização). Não incluir seções separadas de Payload, Dados retornados ou Estrutura de arquivos.

#### Quando a feature **não tem** requisição HTTP

- Não incluir **## Requisição HTTP** nem **## Resposta HTTP**. Incluir uma frase ou nota explicando que a feature não possui requisição HTTP (ex.: "Esta feature não expõe endpoint HTTP; é utilizada internamente pelo módulo.").

#### O que **não** colocar no README

- Não criar **## Estrutura de arquivos**, **## Payload / parâmetros**, **## Dados retornados**, **## Serviços e artefatos utilizados**, nem **## Endpoint** separada quando existir **## Requisição HTTP**.

Referência: `app/Modules/Financial/Finance/Features/FinanceCreate/README.md`.

---

## Referências

| Recurso | Uso |
|--------|-----|
| @.cursor/rules/project.md | Estrutura de módulos, Features, Contexts, convenções de nome e responsabilidades |
| app/Modules/Financial/Finance/Features/FinanceCreate/README.md | Referência de formato (visão geral, fluxo, requisição HTTP, resposta HTTP com exemplo JSON) |

---

## Checklist por feature

- [ ] Entrada interpretada: lista de pastas de feature definida (explícita ou por descoberta no módulo).
- [ ] Para cada feature: verificado se README.md **já existe** (atualizar) ou **não existe** (criar do zero).
- [ ] Apenas arquivos **dessa** feature lidos; se houver README atual, lido para atualizar em cima do estado atual do código.
- [ ] README escrito e salvo em `{pasta-da-feature}/README.md`.
- [ ] Nome do arquivo: **README.md**.
- [ ] Conteúdo em pt-BR, claro, sem misturar com outras features.
- [ ] Nenhum outro arquivo criado além do README.md na raiz da feature.
- [ ] Se houver **## Requisição HTTP**: o exemplo está em **bash (curl)**; a URL começa com `https://base-url/api/v1`; os headers incluem `authorization: Bearer token`, `content-type: application/json` e `x-localization: pt_BR`; o corpo JSON de exemplo contém **todas as possibilidades de campo** (raiz e aninhados), conforme Request/DTO da feature.
