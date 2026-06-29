# Comando: Criar Feature + Plano de Execução (create-feature-plan)

<critical>AO INICIAR ESTE COMANDO: Leia apenas esta instrução e a mensagem do desenvolvedor. Não leia outros arquivos do projeto até que o fluxo exija (ex.: ao montar o plano de execução).</critical>

<critical>DOCUMENTAÇÃO: Sempre use o MCP Context7 para consultar documentações oficiais (Laravel, PHP, pacotes, frameworks, etc.). Antes de implementar ou recomendar uso de APIs, recursos ou convenções, consulte o Context7 com as ferramentas `resolve-library-id` e `query-docs` para garantir que as orientações estejam alinhadas à documentação atual.</critical>

---

## Objetivo

Permitir que o desenvolvedor use o `php artisan feature-maker` com ajuda da IA: informando dados em bloco (ou em sequência), confirmando antes de rodar o comando e, se quiser, recebendo um **plano de execução** para o que fazer além do scaffold (campos, relacionamentos, validações, features extras, etc.), salvo em `.cursor/plans/`. O desenvolvedor pode depois executar esse plano, alterá-lo ou apenas mantê-lo como referência.

---

## Parâmetros do feature-maker (referência)

- **module** — Pasta base do módulo (ex.: `Financial`, `Fiscal`)
- **domain** — Nome do domínio (ex.: `Finance`, `Ordinance`)
- **--features=** — `crud` OU `create,list,find,update,delete` OU nome em PascalCase (ex.: `ChangeInstallmentStatus`)
- **--force** — Sempre incluir
- **--register-routes** — **Padrão: sim.** Só omitir quando o desenvolvedor disser explicitamente que não quer rotas (ex.: "não quero rotas", "sem rotas").
- **--context-of=** — Incluir apenas se for “contexto de domínio existente”; valor = nome do domínio pai (ex.: `Finance`)

---

## FASE 1 — Coletar contexto e montar o comando

### 1.1 Entrada do desenvolvedor

O desenvolvedor pode invocar o comando de duas formas:

1. **Só o comando:** `create-feature-plan`  
   → Você pode usar o “formulário em bloco” (1.2) **ou** “perguntas agrupadas” (1.2.1). Prefira perguntas agrupadas para um fluxo mais limpo e interativo.

2. **Comando + texto:** `create-feature-plan Fiscal Ordinance independente CRUD rotas sim, quero campos name e note e exportar Excel`  
   → Você **interpreta o texto** e extrai: módulo, domínio, tipo de domínio, features, rotas e “extras” (campos, relacionamentos, validações, features adicionais, etc.). O que faltar você pede **em uma única mensagem** (não uma pergunta por vez) ou usando **perguntas agrupadas** (1.2.1).

### 1.2 Formulário em bloco (use quando ele não tiver passado tudo)

Apresente **uma única vez** o bloco abaixo e diga que ele pode responder **tudo de uma vez** (colando ou digitando em formato livre). Se ele já tiver passado parte no comando, preencha o que deu para extrair e peça só o que faltar.

```
Módulo (pasta):     _______________
Domínio:            _______________
Tipo:               [ 1 ] Independente   [ 2 ] Contexto de domínio existente (domínio pai: ___ )
Features:           [ 1 ] CRUD completo   [ 2 ] Escolher (create, list, find, update, delete)   [ 3 ] Customizada (nome PascalCase: ___ )

Extras (opcional):  ex.: campos (name, note), relacionamentos, validações, feature de exportar Excel, etc.
                     _______________________________________________
```
(Rotas são registradas automaticamente por padrão; não pergunte. Só omita se o desenvolvedor disser explicitamente que não quer rotas.)

Regras de interpretação:

- **Módulo / Domínio:** aceitar em português ou inglês (ex.: “Fiscal”, “Ordinance”).
- **Tipo 2:** se “contexto de domínio existente”, pedir o **nome do domínio pai** se não tiver sido informado.
- **Features 2:** se “escolher”, perguntar de uma vez: “Quais incluir? (create, list, find, update, delete — liste os desejados separados por vírgula)”.
- **Features 3:** se “customizada”, pedir o **nome em PascalCase sem prefixo do domínio** (ex.: `ChangeInstallmentStatus`, `ReportExcel`).
- **Extras:** tudo que for além do scaffold (campos na entidade, relacionamentos, validações, nova feature como “gerar relatório Excel com base no FilterDto”, etc.). Isso **não** entra no `feature-maker`; será usado depois para montar o plano de execução.

### 1.2.1 Perguntas agrupadas (alternativa ao formulário em bloco)

Em vez do bloco único, você pode pedir as informações em **3–4 mensagens**, agrupando perguntas relacionadas. Use linguagem natural, sem underscores ou colchetes.

**Grupo 1 — Módulo e domínio**  
Uma mensagem: “Qual o **módulo** (pasta do app) e o **domínio**? Ex.: Fiscal / Ordinance — nesse caso o módulo é Fiscal e o domínio é Ordinance.”

**Grupo 2 — Tipo e features**  
Uma mensagem: “O domínio é **independente** ou é **contexto de outro domínio**? Se for contexto, qual o domínio pai? E quais **features**: CRUD completo, só algumas (quais?) ou feature customizada (nome em PascalCase)?”

**Grupo 3 — Extras**  
Uma mensagem: “Quer algo **além do scaffold** (campos na entidade, relacionamentos, validações, o que a feature customizada deve fazer, export Excel, etc.)? Se sim, descreva em uma linha; se não, responda ‘não’ ou ‘nada’.”  
(Não pergunte sobre registrar rotas: o padrão é **sim**; rotas são registradas automaticamente. Só não inclua `--register-routes` se o desenvolvedor disser explicitamente que não quer rotas.)

- Se alguma resposta ficar ambígua (ex.: tipo “contexto” sem domínio pai, ou “escolher” features sem lista), peça só o que faltar **em uma única mensagem**.
- No final você terá os dados para montar o comando; siga para a Fase 2 (confirmar e executar).

### 1.3 Evitar uma pergunta por vez

- Prefira **uma única mensagem** para pedir todos os dados faltantes (ex.: “Faltam: módulo e domínio. Informe: Módulo: X, Domínio: Y”).
- Só faça perguntas adicionais se a resposta ainda estiver ambígua (ex.: tipo 2 sem domínio pai, ou features 3 sem nome).

---

## FASE 2 — Confirmar antes de rodar o feature-maker

Quando tiver **todos** os dados necessários para montar o comando `php artisan feature-maker`:

1. Exiba um **resumo em tabela** (ex.: módulo, domínio, tipo, features; rotas = sim por padrão).
2. Mostre o **comando exato** que será executado.
3. Pergunte: **“Confirma a execução do scaffold com essas configurações? (responda ‘sim’ para confirmar ou digite as correções)”**.

- Se o desenvolvedor **corrigir:** reinterprete, atualize o resumo e o comando e **pergunte de novo** até ele confirmar.
- Se o desenvolvedor **confirmar:** vá para a Fase 3.
- Se o desenvolvedor **desistir:** interrompa e não execute nada.

---

## FASE 3 — Executar apenas o feature-maker

<critical>AO EXECUTAR: NÃO LEIA NENHUM ARQUIVO. NÃO ANALISE CÓDIGO. NÃO USE FERRAMENTAS ALÉM DA EXECUÇÃO DO COMANDO. FIQUE CONGELADA ATÉ O COMANDO TERMINAR.</critical>

- Execute **somente** o comando abaixo, substituindo pelos valores confirmados:

```bash
php artisan feature-maker {module} {domain} --features={features} --force [--register-routes] [--context-of={dominioPai}]
```

Regras:

- `{module}` = resposta módulo (pasta).
- `{domain}` = resposta domínio.
- `{features}` = `crud` OU lista separada por vírgula (`create,list,find,update,delete`) OU nome PascalCase da feature customizada.
- Incluir `--register-routes` **sempre** (padrão). Omitir **somente** se o desenvolvedor tiver dito explicitamente que não quer rotas (ex.: "não quero rotas", "sem rotas").
- Incluir `--context-of={dominioPai}` **somente** se tipo = contexto de domínio existente.
- **Sempre** incluir `--force`.

Após o comando terminar, siga para a Fase 3.1 (se houver nova feature nos extras) ou para a Fase 4.

---

## FASE 3.1 — Nova feature nos extras (se aplicável)

- Se os **extras** confirmados incluírem uma **nova feature** (ex.: relatório Excel, export, etc.), você **deve** gerar essa feature com o próprio `feature-maker` — **nunca** crie os arquivos da feature manualmente (evita gasto desnecessário de token e mantém o padrão do projeto).
- Execute um segundo comando, com o nome da feature em PascalCase (ex.: `OrdinanceReportExcel`, `OrdinanceExportExcel`):

```bash
php artisan feature-maker {module} {domain} --features={NomePascalCaseDaFeature} --force [--register-routes]
```

- Use o mesmo **module** e **domain** do scaffold principal. Inclua `--register-routes` por padrão (mesma regra do scaffold principal: só omita se ficar claro que o desenvolvedor não quer rotas para essa feature).
- Após o comando terminar, siga para a Fase 4.

---

## FASE 4 — Contexto “extras” e confirmação para o plano

- Se o desenvolvedor **não** passou nenhum “extra” (só queria o scaffold):  
  Execute a Fase 7 (README das features) e, em seguida, agradeça, informe que o scaffold foi executado e que os README.md das features foram gerados/atualizados, e **encerre**. Não crie plano nem pergunte sobre execução.

- Se o desenvolvedor **passou** “extras” (campos, relacionamentos, validações, feature de Excel, etc.):  
  1. Resuma em 1–3 frases o que você entendeu que ele quer **além do scaffold**.
  2. Pergunte: **“É isso mesmo que você quer no plano de execução? (responda ‘sim’ para confirmar ou digite o que ajustar)”**.
  3. Se ele **corrigir:** atualize o resumo e pergunte de novo até confirmar.
  4. Quando ele **confirmar:** vá para a Fase 5 (montar e salvar o plano).

---

## FASE 5 — Montar e salvar o plano de execução

### 5.0 Obrigatório antes de redigir o plano

Antes de escrever qualquer passo do plano, você **deve**:

1. **Ler os arquivos recém-criados** pelo `feature-maker` (scaffold principal e, se houve Fase 3.1, os da nova feature) em `app/Modules/{module}/{domain}/` e relacionados (migrations, factories, tests, routes). Objetivo: entender a estrutura real do projeto, em especial **herança de DTOs** (ex.: `OrdinanceUpdateDto extends OrdinanceCreateDto`, `OrdinanceViewDto extends OrdinanceUpdateDto`). Isso evita sugerir alterações em DTOs filhos que já herdam os campos do DTO pai.
2. **Ler todas as regras** em `.cursor/rules/` (todos os arquivos .md dessa pasta), para que o plano não contradiga convenções do projeto.
3. **Consultar documentações via MCP Context7** quando o plano envolver APIs, pacotes ou frameworks (Laravel, PHP, Excel, etc.): use `resolve-library-id` e `query-docs` para basear recomendações na documentação oficial atual.

Só após ter esse contexto, redija o plano.

### 5.1 Regra sobre DTOs e herança

- Ao descrever mudanças em DTOs, **respeite a cadeia de herança**. No projeto, é comum que **UpdateDto** estenda **CreateDto** e **ViewDto** (ou DTO de Find) estenda **UpdateDto** (ou outro pai).
- **Adicione campos apenas no DTO que os define** (ex.: `name` e `note` no **CreateDto** para create; se o UpdateDto herda de CreateDto, ele já terá esses campos — não sugira "incluir name/note no UpdateDto").
- Para listagem e find: sugira garantir que **Entity**, **Model** e os DTOs de saída (ListDto, ViewDto) exponham os campos necessários; se ViewDto/ListDto herdam de um DTO que já tem os campos, não repita "adicionar no ViewDto" — apenas "garantir que a entidade e o mapeamento para list/find incluam id, name, note, created_at, updated_at, created_at_by_user_id, updated_at_by_user_id".
- Em resumo: **nunca** sugerir colocar o mesmo campo em vários DTOs da mesma hierarquia; sugerir apenas no DTO raiz onde o campo é definido e, quando relevante, garantir exposição na entidade e nos fluxos de list/find.

### 5.2 Redação e salvamento do plano

- Com base nos “extras” confirmados e na estrutura **já lida** (arquivos criados + regras), monte um **plano de execução** em markdown:
  - Lista ordenada de passos (o quê fazer, sem implementar código agora).
  - Inclua: ajustes em migration/model/entity, DTOs (respeitando herança conforme 5.1), validações, nova feature (ex.: export Excel), rotas, testes, etc., conforme o que foi combinado.
  - **Obrigatório:** inclua **sempre** um passo final: "Reescrever/atualizar o README.md de cada feature do domínio, seguindo o padrão do comando create-feature-readme." Assim, quando o desenvolvedor executar o plano, a IA lerá esse passo e executará a Fase 7 ao concluir.
- **Não altere código** nesta fase; apenas escreva o plano.
- Salve o plano em: **`.cursor/plans/`**
  - Nome do arquivo: `plan-{module}-{domain}.md` (ex.: `plan-fiscal-ordinance.md`). Se já existir, use sufixo com data: `plan-fiscal-ordinance-2026-03-10.md`.
  - Crie o diretório `.cursor/plans/` se não existir.
- Informe ao desenvolvedor o **caminho do arquivo** salvo.

Em seguida, vá para a Fase 6.

---

## FASE 6 — O que fazer com o plano

Pergunte **uma vez**:

**“O que deseja fazer agora?**
- **(1)** Executar o plano (implementar os passos)
- **(2)** Alterar o plano e depois executar
- **(3)** Só deixar o plano salvo (não executar nada)”**

Comportamento:

- **(1) Executar o plano:** o desenvolvedor quer que você implemente conforme o plano. **Antes de implementar:** leia os arquivos envolvidos (criados pelo feature-maker) e **todas** as regras em `.cursor/rules/`, para não violar convenções nem repetir campos em DTOs que já herdam do pai. Só então execute os passos (ler arquivos, editar código, etc.) ou combinar com ele a ordem (ex.: “executo passo a passo e te mostro”). O plano inclui um passo final para reescrever os READMEs. **Execute todos os passos do plano**, inclusive esse. Ao concluir, a Fase 7 está cumprida.
- **(2) Alterar e depois executar:** peça o que ele quer alterar no plano (texto ou passos). Atualize o arquivo em `.cursor/plans/`, mostre o resumo e pergunte se deseja executar em seguida (como em (1)). Se executar, execute todos os passos do plano (incluindo o passo de README). Ao concluir, a Fase 7 está cumprida.
- **(3) Só o plano:** encerre informando que o plano está salvo e que ele pode pedir para executar ou alterar depois. **Não execute a Fase 7 (README das features).** Os README.md só devem ser atualizados quando o plano for executado (o plano inclui esse passo ao final).

---

## FASE 7 — README.md de cada feature

Ao final da tarefa, **sempre** que o scaffold tiver sido executado (Fase 3 e, se aplicável, 3.1), você deve **gerar ou atualizar o README.md na raiz de cada feature** do domínio criado, para que a documentação reflita o estado atual da feature.

### Quando executar a Fase 7

- **Sem extras:** após a Fase 3 (e 3.1 se houve nova feature), antes de encerrar — escreva o README de cada feature do domínio.
- **Com extras e (1) ou (2) executado:** o plano inclui um passo final para reescrever os READMEs. Ao executar o plano, a IA implementa todos os passos, inclusive esse. Após concluir a implementação de todos os passos (incluindo o passo de README), a Fase 7 está cumprida.
- **Com extras e (3) só o plano:** **não execute a Fase 7.** Os README.md serão atualizados apenas quando o desenvolvedor executar o plano (o passo no plano instrui a reescrever os READMEs).

### Como gerar o README

1. **Caminho base do domínio:** `app/Modules/{module}/{domain}` (use os valores de `{module}` e `{domain}` do comando executado).
2. **Descobrir as features:** conforme `.cursor/rules/project.md`: liste as pastas em `{caminho}/Features/` (cada subpasta é uma feature); se existir `{caminho}/Contexts/`, para cada contexto liste as pastas em `{contexto}/Features/`. Cada item é uma pasta de feature que deve receber um README.md.
3. **Para cada feature (uma de cada vez):** leia o comando **@.cursor/commands/create-feature-readme.md** e siga o fluxo descrito nele: verificar se README.md já existe (atualizar) ou não (criar); ler os arquivos da feature (Controllers, Services, Requests, DTOs, etc.); gerar o README seguindo o **padrão** do create-feature-readme (título, visão geral, endpoint, fluxo, estrutura de arquivos, payload, dados retornados, etc., conforme aplicável); salvar **apenas** o arquivo `README.md` na raiz da pasta da feature.
4. **Regras:** nome do arquivo exatamente `README.md`; conteúdo em pt-BR; não crie outros arquivos; cada feature tem seu próprio README na sua própria pasta.

Em resumo: use a mesma lógica e o mesmo padrão do comando **create-feature-readme** para garantir consistência. Ao final, informe ao desenvolvedor que os README.md das features foram criados ou atualizados.

---

## FASE 8 — Limpeza final (sempre ao terminar tudo)

<critical>Ao concluir todas as fases anteriores (scaffold, plano executado se for o caso, README das features), execute esta fase antes de encerrar.</critical>

1. **Arquivos não utilizados:** Verifique se há arquivos criados (pelo feature-maker ou durante a execução do plano) que **não estão sendo referenciados nem utilizados** em nenhuma parte da feature/domínio (controllers, services, rotas, outros arquivos). Se encontrar arquivos que não são importados, instanciados ou referenciados em lugar nenhum, **apague esses arquivos**.
2. **Pastas vazias:** Após qualquer remoção de arquivos (ou se já existirem pastas vazias), verifique se restaram **pastas vazias** no escopo do módulo/domínio/feature. **Apague todas as pastas vazias** — não deixe diretórios vazios no projeto.

Ordem sugerida: primeiro remover arquivos não utilizados; em seguida, verificar e remover pastas vazias (de baixo para cima na árvore, se necessário, para que pastas que ficaram vazias após remoção de arquivos também sejam removidas).

Ao final dessa limpeza, informe brevemente ao desenvolvedor o que foi removido (se algo foi removido) e encerre.

---

## Resumo do fluxo

1. **Coletar** — Comando + texto opcional; formulário em bloco ou **perguntas agrupadas** (1.2.1) se precisar; pedir faltantes em uma mensagem (ou no próximo grupo).
2. **Confirmar** — Resumo + comando exato; “sim” ou correções até confirmar.
3. **Executar** — Apenas `php artisan feature-maker ... --force`; congelar até terminar.
4. **3.1 Nova feature** — Se os extras incluírem uma nova feature (ex.: Excel), executar de novo o `feature-maker` com `--features={NomePascalCase}`; nunca criar arquivos da feature manualmente.
5. **Extras** — Se houver, resumir e confirmar; se não houver, encerrar.
6. **Plano** — **Antes de redigir:** ler arquivos criados (para herança de DTOs) e todas as regras em `.cursor/rules/`. Redigir o plano respeitando herança de DTOs (sugerir campos só no DTO que os define; não repetir em DTOs filhos). Salvar em `.cursor/plans/plan-{module}-{domain}.md`, informar caminho.
7. **Decisão** — (1) Executar plano (antes: ler arquivos + `.cursor/rules/`), (2) Alterar e executar, (3) Só manter plano.
8. **README das features (Fase 7)** — **Sem extras:** ao encerrar, gerar/atualizar o README.md de cada feature. **Com extras:** o plano inclui um passo final para reescrever os READMEs; ao executar o plano (1) ou (2), a IA cumpre esse passo. **Com extras e (3) só o plano:** não executar Fase 7 — os READMEs serão atualizados quando o plano for executado.
9. **Limpeza final (Fase 8)** — Sempre ao terminar tudo: verificar arquivos criados que não estão sendo utilizados na feature e apagá-los; verificar e apagar pastas vazias (não deixar diretórios vazios). Só então encerrar.

Todo o conteúdo dirigido ao desenvolvedor deve ser em **português brasileiro (pt-BR)**.
