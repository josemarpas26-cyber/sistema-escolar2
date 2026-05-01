# Manual do Utilizador - SIGA
## Sistema Integrado de Gestão Académica

**Versão:** 1.0  
**Data:** Maio de 2026  
**Última Atualização:** 01/05/2026

---

## Índice

1. [Introdução](#introdução)
2. [Acesso ao Sistema](#acesso-ao-sistema)
3. [Funcionalidades por Perfil](#funcionalidades-por-perfil)
4. [Procedimentos Principais](#procedimentos-principais)
5. [Troubleshooting](#troubleshooting)
6. [Perguntas Frequentes](#perguntas-frequentes)
7. [Contactos de Suporte](#contactos-de-suporte)

---

## Introdução

### O que é o SIGA?

O **SIGA** (Sistema Integrado de Gestão Académica) é uma plataforma web desenvolvida para automatizar e centralizar a gestão de operações escolares, incluindo:

- **Gestão de Matrículas** — Registro de alunos em turmas
- **Lançamento de Notas** — Registro de avaliações por disciplina
- **Relatórios Académicos** — Boletins, pautas, históricos
- **Calendário Escolar** — Eventos e datas importantes
- **Controlo de Acessos** — Permissões por papel de utilizador
- **Auditoria** — Registros de todas as alterações

### Públicos-alvo

O SIGA é utilizado por cinco perfis principais:

| Perfil | Principais Funções |
|--------|-------------------|
| **Administrador** | Gestão de utilizadores, papéis, permissões, backups |
| **Secretaria** | Gestão de turmas, matrículas, cursos, anos letivos |
| **Professor** | Lançamento de notas, gestão de eventos, consulta de turmas |
| **Aluno** | Consulta de boletim, histórico académico, calendário |
| **Coordenador** | Consultoria de desempenho, aprovação de alterações |

---

## Acesso ao Sistema

### Login

1. **Abra o navegador** e aceda a `https://siga.escola.pt` (ou o endereço configurado)
2. **Introduza suas credenciais:**
   - **Email:** o email registado no sistema
   - **Senha:** a sua password pessoal
3. **Clique em "Entrar"**

#### Recuperação de Senha

Se esquecer a sua senha:

1. Na página de login, clique em **"Esqueceu a sua senha?"**
2. Introduza o seu email registado
3. Verifique seu email para a ligação de recuperação
4. Clique na ligação e defina uma nova senha
5. Volte a fazer login com a nova senha

### Segurança

- ✅ Use uma **senha forte** (mínimo 8 caracteres com números e símbolos)
- ✅ **Nunca compartilhe** suas credenciais
- ✅ Termine a sessão após cada utilização
- ✅ Limpe o histórico do navegador se usar computadores partilhados
- ⚠️ O sistema registra todas as ações por IP para auditoria

---

## Funcionalidades por Perfil

### 1. Administrador

#### Acesso
- Acesso total a todas as funcionalidades
- Dashboard com estatísticas completas

#### Principais Operações

**Gestão de Utilizadores**
1. Vá a **Configurações > Utilizadores**
2. Clique em **+ Novo Utilizador**
3. Preencha os dados: nome, email, papel (role)
4. Defina uma senha temporária ou deixe para ser gerada
5. Clique em **Guardar**

**Gestão de Papéis e Permissões**
1. Aceda a **Configurações > Papéis e Permissões**
2. Selecione um papel existente ou crie um novo
3. Marque/desmarque as permissões desejadas
4. Clique em **Guardar Permissões**

**Backup e Recuperação**
1. Vá a **Sistema > Backup e Recuperação**
2. Clique em **Criar Backup Completo**
3. Guarde o ficheiro .sql em local seguro
4. Para restaurar: **Sistema > Restaurar**, selecione o ficheiro

**Visualizar Logs**
1. Aceda a **Sistema > Logs de Auditoria**
2. Filtre por utilizador, ação ou data
3. Consulte o IP, hora e detalhes da alteração

---

### 2. Secretaria

#### Acesso
- Gestão de cursos, turmas, matrículas, anos letivos
- Geração de relatórios

#### Operação 1: Criar Turma

1. **Menu > Turmas > Nova Turma**
2. Preencha:
   - **Nome:** ex. "10-A-Informática"
   - **Classe:** 10, 11 ou 12
   - **Curso:** selecione da lista
   - **Ano Letivo:** ano letivo ativo
   - **Coordenador:** (opcional) professor responsável
   - **Capacidade:** número máximo de alunos
3. Clique em **Guardar**
4. Sistema gera ID da turma automaticamente

#### Operação 2: Matricular Aluno

1. **Menu > Turmas > [Selecionar turma]**
2. Abra a aba **Matrículas**
3. Clique em **+ Adicionar Aluno**
4. Selecione o aluno da lista ou crie novo utilizador
5. Defina a data de matrícula
6. Clique em **Guardar Matrícula**

#### Operação 3: Gerar Relatório de Boletim

1. **Menu > Relatórios > Boletins**
2. Selecione:
   - **Turma**
   - **Ano Letivo**
   - **Formato:** PDF ou Excel
3. Clique em **Gerar**
4. O ficheiro é descarregado automaticamente

---

### 3. Professor

#### Acesso
- Visualização de turmas atribuídas
- Lançamento de notas
- Gestão de eventos de calendário

#### Operação 1: Lançar Notas de Avaliação

1. **Menu > Notas > Lançar Notas**
2. Selecione:
   - **Turma**
   - **Disciplina**
   - **Período** (Trimestre 1, 2 ou 3)
3. Preencha as notas:
   - **MAC:** Média de Avaliação Contínua
   - **PP:** Prova Prática
   - **PT:** Prova Teórica
   - **MT:** Média Trimestral (calculada automaticamente)
4. Clique em **Guardar Rascunho** ou **Submeter para Validação**
5. Após validação pela secretaria, as notas ficam definitivas

#### Operação 2: Consultar Turmas

1. **Menu > Turmas > Minhas Turmas**
2. Veja a lista de turmas onde leciona
3. Clique em uma turma para ver:
   - Lista de alunos
   - Disciplinas atribuídas
   - Calendário de aulas

#### Operação 3: Criar Evento de Calendário

1. **Menu > Calendário > Novo Evento**
2. Preencha:
   - **Título:** ex. "Teste de Informática"
   - **Descrição:** detalhes do evento
   - **Turma:** selecione (opcional)
   - **Data/Hora de Início e Fim**
   - **Local:** sala de aula ou online
3. Clique em **Guardar**
4. O evento aparece no calendário para alunos

---

### 4. Aluno

#### Acesso (Limitado e Seguro)
- Visualização apenas do próprio boletim
- Consulta de histórico académico
- Visualização do calendário escolar

#### Operação 1: Consultar Boletim

1. **Menu > Meu Boletim**
2. Selecione o **Ano Letivo** (se houver múltiplos)
3. Veja suas notas por disciplina:
   - Notas de cada trimestre
   - Classificação final
   - Status (Aprovado/Reprovado)
4. Clique em **Descarregar PDF** para guardar

#### Operação 2: Consultar Histórico Académico

1. **Menu > Histórico Académico**
2. Veja o resumo de aprovações/reprovações por ano
3. Visualize disciplinas e classificações finais
4. Clique em uma entrada para ver detalhes

#### Operação 3: Visualizar Calendário Escolar

1. **Menu > Calendário**
2. Veja todos os eventos:
   - Feriados
   - Avaliações
   - Reuniões de pais
   - Eventos da sua turma
3. Clique num evento para ver detalhes completos

---

### 5. Coordenador

#### Acesso
- Consulta de desempenho de turmas coordenadas
- Aprovação de solicitações especiais
- Gestão de disciplinas ou cursos

#### Operação 1: Consultar Desempenho da Turma

1. **Menu > Desempenho > Minha Turma**
2. Veja:
   - Média geral por disciplina
   - Taxa de aprovação
   - Alunos em risco
3. Clique num aluno para ver boletim detalhado

#### Operação 2: Aprovar Divisão Aritmética

1. **Menu > Solicitações > Divisões Aritméticas**
2. Revise solicitações pendentes
3. Para cada solicitação:
   - Analise a justificação
   - Clique **Aprovar** ou **Rejeitar**
   - Adicione observações (opcional)
4. O aluno é notificado da decisão

---

## Procedimentos Principais

### Ciclo Completo: Do Acesso ao Relatório Final

#### 1️⃣ Preparação (Secretaria - Início do Ano)

```
Semana 1
├─ Criar Ano Letivo
├─ Definir Cursos
├─ Criar Turmas
└─ Registar Alunos

Semana 2
├─ Atribuir Professores a Turmas/Disciplinas
├─ Definir Configurações de Avaliação
└─ Configurar Calendário Escolar
```

#### 2️⃣ Avaliação (Professor - Durante Trimestre)

```
Ao longo do Trimestre
├─ Registar Avaliações Contínuas (MAC)
├─ Lançar Provas Práticas (PP)
├─ Lançar Provas Teóricas (PT)
└─ Sistema Calcula MT automaticamente

Fim do Trimestre
├─ Submeter Notas para Validação
└─ Aguardar Aprovação da Secretaria
```

#### 3️⃣ Validação (Secretaria - Fim de Trimestre)

```
Validação
├─ Revisar Notas Submetidas
├─ Verificar Completude de Dados
├─ Validar ou Rejeitar
└─ Notificar Professor sobre Rejeições

Se Rejeitado
├─ Professor Corrige
└─ Resubmete para Validação
```

#### 4️⃣ Relatórios (Secretaria - Após Validação)

```
Geração de Relatórios
├─ Pauta por Turma
├─ Boletim por Aluno
├─ Histórico Académico
└─ Exportar para PDF / Excel
```

#### 5️⃣ Visualização (Aluno - Após Publicação)

```
Acesso do Aluno
├─ Consultar Boletim
├─ Ver Histórico
└─ Descarregar PDF
```

### Procedimento: Lançar Notas em Lote (Import)

Para alunos com muitas notas, use o import em Excel:

1. **Descarregue o Template**
   - Menu > Notas > Importar Notas
   - Clique **Descarregar Template Excel**

2. **Preencha o ficheiro**
   ```
   Aluno_ID | Disciplina_ID | MAC | PP | PT
   001      | 5             | 14  | 13 | 15
   002      | 5             | 16  | 14 | 17
   ```

3. **Faça Upload**
   - Selecione o ficheiro preenchido
   - Sistema valida automaticamente
   - Clique **Importar**

4. **Revise e Submeta**
   - Verifique os dados carregados
   - Clique **Submeter para Validação**

---

## Troubleshooting

### Problema: Não consigo fazer login

**Soluções:**
1. ✅ Verifique se o email está correto
2. ✅ Clique **Esqueceu a senha?** para redefinir
3. ✅ Limpe cookies do navegador (Ctrl+Shift+Delete)
4. ✅ Tente outro navegador
5. ✅ Verifique se sua conta está ativa (fale com Administrador)

---

### Problema: As minhas notas não aparecem

**Possíveis causas:**

| Causa | Solução |
|-------|---------|
| Notas não foram submetidas | Submeta as notas no menu Notas > Lançar |
| Notas estão em rascunho | Clique "Submeter para Validação" |
| Secretaria não validou | Aguarde validação pela secretaria |
| Permissões insuficientes | Contacte o administrador |

---

### Problema: Relatório não gera

**Passos de resolução:**

1. **Verifique se há dados:**
   - Existem alunos matriculados?
   - Existem notas lançadas?

2. **Verifique as permissões:**
   - Menu > Perfil > Minhas Permissões
   - Procure por "relatorios.boletins"

3. **Tente formatos diferentes:**
   - Se PDF não funciona, tente Excel
   - Se um ano letivo não funciona, tente outro

4. **Contacte suporte:**
   - Envie screenshot do erro
   - Indique qual turma/aluno

---

### Problema: Aluno não aparece na turma

**Verificações:**

1. **Aluno está matriculado?**
   - Menu > Turmas > [Turma] > Matrículas
   - Se não aparecer, adicione (ver Operação 2 da Secretaria)

2. **Matrícula está ativa?**
   - Status deve ser "matriculado", não "transferido" ou "desistente"

3. **Ano letivo correto?**
   - Matrículas em turmas do ano letivo errado não aparecem

4. **Atualizar página:**
   - Pressione F5 para recarregar
   - Dados podem estar em cache

---

### Problema: Permissão negada ao aceder a página

**Resolução:**

1. **Confirme seu papel:**
   - Menu > Perfil > Meu Perfil
   - Veja qual é o seu "Papel"

2. **Verifique permissões:**
   - Menu > Perfil > Minhas Permissões
   - Procure a ação que quer fazer

3. **Se falta permissão:**
   - Contacte o Administrador para solicitar acesso

---

## Perguntas Frequentes

### P: Posso corrigir uma nota já validada?

**R:** Não diretamente. A nota validada é bloqueada. Contacte a Secretaria para desvalidar e então você poderá corrigir.

---

### P: Quanto tempo leva para as notas aparecerem aos alunos?

**R:** As notas aparecem imediatamente após a validação pela secretaria. Pode levar 1-2 dias úteis.

---

### P: Posso descarregar o meu boletim em PDF?

**R:** Sim! No menu **Meu Boletim**, clique em **Descarregar PDF**. Funciona offline.

---

### P: O que acontece se reprovei a uma disciplina?

**R:** O sistema mostra "Reprovado" na disciplina. Você pode tentar recuperação se disponível no próximo ano, conforme regulamento escolar.

---

### P: Como posso ver o calendário escolar?

**R:** Menu > Calendário. Todos os eventos (aulas, feriados, avaliações) aparecem lá.

---

### P: O sistema é seguro? Meus dados estão protegidos?

**R:** Sim! 
- ✅ Encriptação HTTPS
- ✅ Passwords são hash com algoritmo bcrypt
- ✅ Logs de auditoria de todas as ações
- ✅ Conformidade com regulamentos de dados

---

### P: E se esquecer de lançar uma nota?

**R:** 
1. Pode lançar a qualquer momento antes do fecho do trimestre
2. Após fecho, contacte a Secretaria
3. Para anos passados, contacte o Administrador

---

### P: Quantos filhos posso registar no sistema?

**R:** Se é encarregado, tem um utilizador. Pode ver os boletins de seus filhos a partir do seu painel após autenticação (dependendo de configurações).

---

### P: O que fazer se perdi acesso à minha conta?

**R:** 
1. **Acesso via email:** Clique "Esqueceu a senha?"
2. **Sem acesso ao email:** Contacte o Administrador
3. **Conta desativada:** Fale com Secretaria ou Admin

---

### P: Posso imprimir o boletim?

**R:** Sim! Descarregue o PDF e imprima (Ctrl+P ou Menu > Imprimir).

---

### P: Como mudo a minha foto de perfil?

**R:** 
1. Menu > Perfil
2. Clique em sua foto atual
3. Selecione novo ficheiro (JPG ou PNG)
4. Clique **Guardar**

---

## Contactos de Suporte

### Níveis de Suporte

| Nível | Contacto | Tempo Resposta |
|-------|----------|----------------|
| **N1 - Suporte Técnico** | suporte@escola.pt | 2-4 horas |
| **N2 - Admin Sistema** | admin@escola.pt | 4-24 horas |
| **N3 - Desenvolvedor** | dev@escola.pt | 24-48 horas |

### Como Reportar um Problema

1. **Recolha informações:**
   - Tipo de problema (login, nota, relatório, etc.)
   - Screenshot do erro (se aplicável)
   - Quando ocorreu?
   - Seu papel e ID utilizador

2. **Envie email para suporte:**
   ```
   Para: suporte@escola.pt
   Assunto: [SIGA] Problema com Lançamento de Notas
   
   Corpo:
   Utilizador: João Silva (ID: 042)
   Papel: Professor
   Problema: Ao tentar submeter notas da turma 10-A, 
   recebo erro "Permissão negada"
   Data/Hora: 01/05/2026 14:30
   Screenshot: [Anexado]
   ```

3. **Aguarde resposta:**
   - Equipa de suporte responde no prazo indicado
   - Pode ser pedida mais informação

### Horário de Suporte

- **Segunda a Sexta:** 08:00 - 18:00
- **Sábado:** 09:00 - 13:00
- **Domingo e Feriados:** Apenas emergências críticas

### Emergências Críticas

Se o sistema está completamente indisponível:

- **Telefone:** +244 222 445 566
- **WhatsApp:** +244 922 445 566
- **Email urgente:** admin@escola.pt com "CRÍTICO" no assunto

---

## Dicas e Boas Práticas

### Para Secretaria

✅ **Faça backup regularmente** — Sistema > Backup > Diariamente  
✅ **Valide notas no prazo** — Não acumule muitas para validar  
✅ **Mantenha ano letivo único ativo** — Evita confusões de matrículas  
✅ **Teste relatórios antes de precisar** — Verificar formatos com antecedência  

### Para Professor

✅ **Lance notas regularmente** — Não deixe para última hora  
✅ **Revise antes de submeter** — Valores estranhos são rejeitados  
✅ **Use template Excel para lotes** — Mais rápido que digitar um a um  
✅ **Crie eventos no calendário** — Alunos vêem e se preparam  

### Para Aluno

✅ **Consulte boletim regularmente** — Detete problemas cedo  
✅ **Guarde PDF do boletim** — Comprovante para futuras situações  
✅ **Veja o calendário** — Não perca datas importantes  
✅ **Contacte professor se nota parece errada** — Pode ser corrigida antes de validação  

---

## Glossário de Termos

| Termo | Explicação |
|-------|-----------|
| **Guard Web** | Sistema de autenticação e sessão do SIGA |
| **RBAC** | Role-Based Access Control — Controlo de acesso por papéis |
| **MAC** | Média de Avaliação Contínua (trabalhos, testes pequenos) |
| **PP** | Prova Prática (em laboratório ou presencialmente) |
| **PT** | Prova Teórica (exame escrito) |
| **MT** | Média Trimestral (calculada por fórmula) |
| **CF** | Classificação Final (resultado do trimestre) |
| **PG** | Pauta Geral (consolidado de todo o ano) |
| **Permissão** | Autorização para fazer uma ação específica (ex: "notas.lancar") |
| **Papel** | Grupo de permissões (ex: "professor", "aluno") |
| **Validação** | Aprovação de dados sensíveis (ex: notas) |
| **Auditoria** | Registo de todas as ações para segurança |

---

## Informações Técnicas (Opcional)

### Requisitos de Sistema

**Navegadores suportados:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Requisitos de conectividade:**
- Conexão de internet estável
- Mínimo 2 Mbps para downloads de relatórios
- Cookie habilitados

**Dispositivos:**
- Desktop/Laptop: Suportado completamente
- Tablet: Suportado (interface responsiva)
- Telemóvel: Parcialmente suportado (funcionalidades limitadas)

---

## Histórico de Alterações

| Versão | Data | Alterações |
|--------|------|-----------|
| 1.0 | 01/05/2026 | Versão inicial do manual |
| TBD | TBD | Futuras atualizações |

---

## Notas Finais

Este manual é um guia completo para utilizadores do SIGA. Se encontrar alguma discrepância entre o manual e o sistema real, contacte suporte para que possamos atualizar.

**Última atualização:** 01 de Maio de 2026  
**Próxima revisão prevista:** Setembro de 2026

---

**SIGA — Sistema Integrado de Gestão Académica**  
Desenvolvido para escolas modernas. Seguro. Fiável. Intuitivo.
