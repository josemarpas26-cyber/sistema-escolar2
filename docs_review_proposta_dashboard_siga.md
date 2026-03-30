# Review técnico — proposta de Dashboard SIGA-style

Data: 2026-03-29

## Conclusão curta
A proposta **é útil**, mas **não deve entrar 1:1** no projeto neste momento.

- **Sim, traz valor**: melhora UX por perfil, prepara gráficos, cache e leitura executiva.
- **Risco alto se copiar integralmente**: adiciona muita lógica no controller, usa mocks em produção e referencia campos/rotas/componentes que não existem hoje.

Recomendação: adotar em **3 fases** (service + métricas reais + componentes visuais), evitando acoplamento e regressão.

## O que já existe no projeto (base atual)

1. O `DashboardController` atual já separa dashboards por perfil e calcula métricas reais com dados do domínio.
2. O dashboard de aluno já recebeu bloco de metas com progresso e criação/desativação.
3. O sistema já possui componentes Blade reutilizáveis (`x-stat-card`, `x-card`, `x-badge`) e layout consistente.

## Onde a proposta é útil

### 1) Estrutura por perfil (Admin/Secretaria/Professor/Aluno)
**Útil e alinhado**: mantém o padrão já usado e melhora legibilidade funcional.

### 2) Introdução de cache
**Útil com ajustes**: cache para widgets pesados (logs agregados, séries semanais) ajuda performance em ambiente com muitos acessos.

### 3) Service layer (`DashboardService`)
**Muito útil**: reduz controller monolítico e facilita testes unitários de métricas.

### 4) Componentização visual SIGA-style
**Útil**: ajuda a remover visual genérico e trazer identidade mais moderna sem abandonar o azul principal.

## Riscos técnicos da proposta como está

1. **Controller gigante**
   - A proposta concentra muita regra de negócio num único controller.
   - Impacto: manutenção difícil, testes frágeis.

2. **Mocks misturados com produção**
   - Métodos como "próxima aula mock" e "eventos mock" introduzem dados artificiais no dashboard.
   - Impacto: experiência enganosa para utilizador final.

3. **Referências potencialmente inexistentes**
   - Campos como `last_login`, `ano` em `AnoLetivo`, componentes `x-dashboard.*` e algumas rotas podem não existir no estado atual.
   - Impacto: erros em runtime se aplicado sem adaptação.

4. **Acoplamento visual + regra de negócio**
   - Cálculo de tendência, status e progresso diretamente no controller.
   - Impacto: difícil reutilizar em API/relatórios/exportações.

## Diferença real que trará (se implementado corretamente)

### Ganhos
- Dashboard mais executivo e menos "tabela CRUD".
- Melhor percepção de valor por perfil (cada papel vê o que precisa decidir agora).
- Base para analytics (gráficos, tendência, alertas antecipados).
- Melhor performance em cenários de carga via cache segmentado.

### Custos
- Refatoração moderada de controller para service/query layer.
- Criação de novos componentes Blade.
- Testes extras de regressão para garantir números corretos por perfil.

## Plano recomendado de adoção

### Fase 1 (baixo risco)
- Criar `DashboardService` apenas para agregações já reais.
- Mover 3 métricas críticas por perfil para o service.
- Manter views atuais.

### Fase 2 (médio risco)
- Introduzir `x-dashboard.stats-card` compatível com design atual.
- Substituir gradualmente cards existentes por componentes novos.

### Fase 3 (alto impacto)
- Adicionar gráficos e timeline de atividade com dados reais.
- Só então ativar cache por widget e invalidar por eventos de nota/log.

## Decisão recomendada

**Adotar parcialmente agora**:
- ✅ Adotar: `DashboardService`, cache seletivo, cards de estatística evoluídos.
- ⚠️ Adiar: calendário/agenda e "proximas aulas" até existir módulo de horários/eventos.
- ❌ Evitar: subir mocks para produção.
