# Arquitetura de Integração SIGA → sistema-escolar2

> Data da análise: 2026-03-29.
> Escopo analisado diretamente: repositório local `sistema-escolar2`.
> Limitação: o repositório remoto `sigamos` não pôde ser clonado neste ambiente (bloqueio de rede HTTP 403). Portanto, os pontos sobre SIGA foram inferidos a partir da descrição técnica fornecida no briefing.

## 1) Diagnóstico Executivo

### sistema-escolar2 (Laravel 11)
**Grau de maturidade estimado: 7.5/10 (backend), 5.0/10 (frontend), 6.8/10 (produto geral).**

**Valências**
- Estrutura de domínio escolar já consistente (usuários, turmas, disciplinas, notas, ano letivo) e com boa cobertura de fluxos administrativos e académicos.
- Controle por perfil/role claro no backend e roteamento de dashboards por papel.
- Base de logs e relatórios já operacional (incluindo exportações), o que reduz risco para analytics.
- Organização por componentes Blade (`x-card`, `x-stat-card`, `x-badge`) facilita evolução visual incremental sem reescrever tudo.

**Pontos negativos**
- Frontend ainda muito "CRUD genérico" e pouco orientado a storytelling pedagógico.
- Linguagem visual está coerente, mas previsível: cards/tabelas padrão com pouca hierarquia de informação, microinterações e diferenciação de produto.
- Falta de recursos de UX adaptativa por papel (ex.: aluno sem foco em metas e progresso futuro; professor sem insights comparativos).
- README ainda padrão do Laravel, sinal de maturidade documental baixa para onboarding do time.

### SIGA (Vue 3 SPA, protótipo UI)
**Grau de maturidade estimado: 4.0/10 (engenharia), 8.0/10 (UI/UX), 5.5/10 (produto geral).**

**Valências (conforme briefing)**
- Refinamento visual superior e experiência mais moderna (provável melhor uso de espaçamento, tipografia, blocos de contexto e feedback visual).
- Estrutura SPA favorece fluidez e sensação de aplicação "viva".

**Pontos negativos (conforme briefing)**
- Ausência de backend e de persistência real limita confiabilidade do protótipo.
- Sem contratos de dados reais, há risco de overdesign desacoplado da realidade operacional escolar.

## 2) Proposta de Híbrido (sem perder identidade do sistema-escolar2)

Princípio: **evoluir, não substituir** `primary-600 #2563eb`.

### Estratégia visual em 3 camadas
1. **Foundation tokens (Tailwind + CSS custom props)**
   - Manter paleta primary atual.
   - Introduzir tokens semânticos: `--surface`, `--surface-soft`, `--text-strong`, `--text-muted`, `--success`, `--danger`, `--warning`.
2. **Componentização de alto nível**
   - Criar variantes de `x-card` com densidade (`compact`, `comfortable`), estados (`default`, `focus`, `warning`) e cabeçalho contextual.
3. **Padrões de UX por papel**
   - Aluno: progresso, metas, risco e próximos passos.
   - Professor: pendências, comparação por turma, variação trimestral.
   - Coordenação/Direção: visão comparativa, tendência e outliers.

## 3) Funcionalidade 1 — Metas Académicas do Aluno

## 3.1 Arquitetura recomendada

### Tabela `metas_disciplina`
Campos recomendados:
- `id`
- `aluno_id` (FK users)
- `disciplina_id` (FK disciplinas)
- `ano_letivo_id` (FK anos_letivos)
- `meta_nota` decimal(4,2)
- `data_definicao` date
- `data_conclusao_prevista` date nullable
- `status` enum(`ativa`,`atingida`,`em_risco`,`inviavel`,`desativada`)
- `created_at`, `updated_at`

### Regras críticas
- `meta_nota` entre 0 e 20.
- Índice único lógico: uma meta ativa por `(aluno_id, disciplina_id, ano_letivo_id)`.
- Auditoria de alterações (quem alterou/quando).

## 3.2 Integração de UX (Opção A recomendada)

Inserir no dashboard do aluno em bloco **"Minhas Metas por Disciplina"**, com:
- progresso percentual;
- diferença para meta;
- média necessária nos períodos restantes;
- CTA claro: `Definir/Alterar Meta`.

Evitar modal pesado como primeira versão: começar com **accordion inline** por disciplina melhora usabilidade e acessibilidade.

## 3.3 Motor de sugestões

Criar serviço de domínio (ex.: `MetaAcademicaService`) que calcule:
1. distância para meta;
2. nota média necessária no(s) trimestre(s) restante(s);
3. viabilidade matemática;
4. recomendação textual curta (máx. 120 chars) + severidade (`info|warning|danger|success`).

## 3.4 Notificações

- Evento `MetaAtingida`.
- Job agendado mensal para revisão de metas.
- Alerta de risco por janela de tempo (ex.: 14 dias antes do fim do período).

## 4) Funcionalidade 2 — Análise de Desempenho Docente

## 4.1 Camada de dados e agregação

Implementar uma camada de consulta dedicada (query service), evitando lógica de BI dentro de controllers.

Sugestão:
- `App/Services/Analytics/DesempenhoDocenteService.php`
- métodos:
  - `porCurso($coordenador, $anoLetivoId)`
  - `porDisciplina($coordenadorDisciplina, $anoLetivoId)`
  - `institucional($diretor, $anoLetivoId)`

## 4.2 Permissões e recortes

Usar policy + scoping SQL:
- coordenador de curso: filtra por curso(s) vinculado(s);
- coordenador de disciplina: filtra por disciplina/departamento;
- diretor: visão agregada sem identificação nominal (se política institucional exigir);
- admin/secretaria: visão total.

## 4.3 Visualizações (ordem de entrega)

1. **Cards + ranking** (MVP, baixa complexidade)
2. **Barras comparativas**
3. **Linha de tendência trimestral**
4. **Heatmap** (fase 2, maior custo cognitivo)

## 4.4 Métricas mínimas

- média por professor (ponderada por nº de alunos);
- variação vs período anterior;
- taxa de aprovação por turma;
- desvio padrão entre turmas para identificar consistência docente.

## 5) Críticas construtivas ao frontend atual (e correções)

1. **Excesso de padrão tabela/card sem narrativa**
   - Correção: introduzir "cards de decisão" (o que fazer agora) e não apenas "cards de estado" (o que existe).
2. **Hierarquia visual tímida**
   - Correção: reforçar contraste semântico (títulos, subtítulos, meta-info, alertas).
3. **Pouca identidade de produto**
   - Correção: criar assinatura visual própria (padrão de ícones, raio de borda, sombras, spacing scale e motion).
4. **Baixa densidade de feedback contextual**
   - Correção: tooltips inteligentes, badges de risco, mensagens de ação recomendada.
5. **Sem camada de empty states memoráveis**
   - Correção: estados vazios com orientação prática (ex.: "Ainda não definiu metas — definir agora").

## 6) Roadmap de maturidade (6 semanas)

### Sprint 1
- Modelagem de metas + CRUD + validações.
- UI básica de metas no dashboard aluno.

### Sprint 2
- Serviço de cálculo de viabilidade e sugestões.
- Notificações de meta atingida/em risco.

### Sprint 3
- Analytics docente MVP (ranking + barras).
- Filtro por perfil e escopo.

### Sprint 4
- Tendência trimestral + refinamento visual global (tokens e microinterações).
- Hardening (testes de regressão e performance).

## 7) Riscos e mitigação

- **Risco:** copiar UI de SPA para Blade sem adaptar fluxo.
  - **Mitigação:** priorizar padrões e tokens, não copiar componentes cegamente.
- **Risco:** dashboard virar "poluição visual".
  - **Mitigação:** arquitetura por prioridade (insights > detalhes).
- **Risco:** regras de negócio divergirem da visualização.
  - **Mitigação:** centralizar cálculos em serviços e cobrir com testes.

## 8) Recomendação final

- O caminho correto é **backend robusto do sistema-escolar2 + linguagem visual e ritmo de interação inspirados no SIGA**.
- Não é uma migração tecnológica, e sim uma **evolução de design system + experiência contextual por papel**.
- Começar por metas académicas (alto valor para aluno) e analytics docente MVP (alto valor para gestão) entrega impacto rápido e mensurável.
