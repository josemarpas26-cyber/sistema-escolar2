<div align="center">

# 🏫 SIGA — Sistema Integrado de Gestão Académica

> Plataforma web para gestão escolar completa: lançamento de notas, alunos, professores, turmas e cursos.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red?logo=laravel&logoColor=white)](https://laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-active-success)]()

</div>

---

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração do .env](#-configuração-do-env)
- [Como Rodar](#-como-rodar)
- [Papéis e Permissões](#-papéis-e-permissões)
- [Estrutura de Pastas](#-estrutura-de-pastas)
- [Rotas Principais](#-rotas-principais)
- [Screenshots](#-screenshots)
- [Comandos Artisan Úteis](#-comandos-artisan-úteis)
- [Contribuição](#-contribuição)
- [Licença](#-licença)

---

## 📖 Sobre o Projeto

O **SIGA** é um sistema de gestão académica escolar desenvolvido com **Laravel 10**, destinado a institutos de ensino que necessitam de uma solução centralizada para:

- Gestão de **alunos**, **professores**, **turmas** e **cursos**
- **Lançamento e visualização de notas** por trimestre
- Geração de **boletins**, **pautas** e **histórico académico**
- Controlo de **permissões por papel** (administrador, secretaria, professor, aluno)
- **Auditoria completa** de todas as alterações de notas via sistema de logs

> Desenvolvido para o Instituto Politécnico Industrial do Kilamba Kiaxi — Angola 🇦🇴

---

## ✨ Funcionalidades

### 👥 Gestão de Utilizadores
- Cadastro de administradores, secretaria, professores e alunos
- Login por **e-mail** ou **número de processo**
- Gestão de fotos de perfil
- Lixeira com restauração de utilizadores eliminados (soft delete)

### 🎓 Gestão Académica
- Criação e gestão de **anos letivos** com encerramento automático
- Configuração dinâmica do **sistema de avaliação** por ano letivo
- Gestão de **cursos**, **disciplinas** e **turmas**
- **Matrícula de alunos** e atribuição de professores a disciplinas
- **Promoção automática de turmas** para a classe seguinte

### 📝 Sistema de Notas
- Lançamento de notas por trimestre (MAC, PP, PT, PG)
- **Cálculo automático** de MT1, MT2, MT3, MFT2, CF, CA, CFD
- Avaliações contínuas (MAC) calculadas automaticamente
- Bloqueio e reabertura de pautas por trimestre ou campo específico
- Suporte a alunos transferidos com CAs de anos anteriores

### 📊 Relatórios e Exportação
- **Boletim individual** do aluno (PDF / Excel)
- **Boletins em massa** para a turma completa (Excel / PDF)
- **Pauta por disciplina** e **pauta geral** do ano letivo
- **Histórico académico** do aluno
- Exportação de **logs** para Excel com filtros avançados

### 🔍 Auditoria e Logs
- Registo de todas as alterações de notas
- Dashboard de logs com estatísticas
- Filtros por utilizador, turma, disciplina, data e ação

---

## ⚙️ Requisitos

| Requisito | Versão Mínima |
|-----------|--------------|
| PHP | 8.1 |
| Composer | 2.x |
| MySQL | 8.0 |
| Node.js | 18.x |
| NPM | 9.x |

### Extensões PHP Necessárias

php-mbstring
php-xml
php-bcmath
php-json
php-pdo
php-pdo_mysql
php-gd
php-zip
php-curl

---

## 🚀 Instalação

### 1. Clonar o repositório

```bash
git clone https://github.com/seu-usuario/siga.git
cd siga
```

### 2. Instalar dependências PHP

```bash
composer install
```

### 3. Instalar dependências JavaScript

```bash
npm install
```

### 4. Copiar o ficheiro de ambiente

```bash
cp .env.example .env
```

### 5. Gerar a chave da aplicação

```bash
php artisan key:generate
```

### 6. Configurar o `.env`

Edite o ficheiro `.env` com as suas credenciais (ver secção abaixo).

### 7. Executar as migrações e seeders

```bash
# Criar as tabelas na base de dados
php artisan migrate

# Popular com dados iniciais (papéis, permissões, utilizador admin)
php artisan db:seed
```

### 8. Criar o link simbólico para o storage

```bash
php artisan storage:link
```

### 9. Compilar os assets

```bash
# Desenvolvimento
npm run dev

# Produção
npm run build
```

---

## 🔧 Configuração do .env

Abaixo estão as variáveis de ambiente mais importantes:

```env
# ── Aplicação ─────────────────────────────────────────────
APP_NAME="SIGA"
APP_ENV=local
APP_KEY=base64:GERE_COM_php_artisan_key_generate
APP_DEBUG=true
APP_URL=http://localhost

# ── Base de Dados ─────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siga_db
DB_USERNAME=root
DB_PASSWORD=sua_senha

# ── E-mail (para recuperação de senha) ───────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@escola.ao"
MAIL_FROM_NAME="${APP_NAME}"

# ── Configurações da Escola ───────────────────────────────
APP_NOME_ESCOLA="INST. POLITÉCN. INDUSTRIAL Nº 8050 LDA - NOVA VIDA"
APP_AREA_FORMACAO="ÁREA DE FORMAÇÃO DE INFORMÁTICA"
APP_CAMINHO_LOGO=           # Deixe vazio para usar o logo padrão (public/images/logo1.png)

# ── Backup da Base de Dados ───────────────────────────────
DB_BACKUP_RETENTION_DAYS=14
DB_BACKUP_SCHEDULE="02:00"
# Windows: caminho para o mysqldump
MYSQLDUMP_PATH="C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe"

# ── Cache / Sessão ────────────────────────────────────────
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
QUEUE_CONNECTION=sync
```

---

## ▶️ Como Rodar

### Desenvolvimento Local

```bash
# Iniciar o servidor de desenvolvimento Laravel
php artisan serve

# Em outro terminal, compilar assets com hot reload
npm run dev
```

A aplicação estará disponível em: **http://localhost:8000**

**Credenciais padrão após o seeder:**

| Papel | E-mail | Senha |
|-------|--------|-------|
| Administrador | admin@escola.ao | password |
| Aluno (e-mail) | aluno1@escola.ao | password |
| Aluno (Nº Processo) | 2024001 | password |

### Produção

```bash
# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Compilar assets para produção
npm run build

# Executar migrações em produção
php artisan migrate --force
```

---

## 🔐 Papéis e Permissões

O sistema possui 4 papéis com permissões distintas:

| Papel | Descrição |
|-------|-----------|
| **Administrador** | Acesso total ao sistema |
| **Secretaria** | Gestão de utilizadores, turmas e pautas |
| **Professor** | Lançamento de notas nas suas disciplinas |
| **Aluno** | Visualização das próprias notas e boletim |

### Hierarquia de Professor
Os professores podem acumular funções de:
- **Coordenador de Turma** — acesso à turma que coordena
- **Coordenador de Curso** — acesso a todas as turmas do curso
- **Coordenador de Disciplina** — acesso às notas da disciplina em todas as turmas

---

## 📁 Estrutura de Pastas


siga/
├── app/
│   ├── Exports/                    # Classes de exportação Excel (Boletim, Pauta, Logs)
│   │   └── Sheets/                 # Folhas individuais para exportações multi-sheet
│   ├── Http/
│   │   ├── Controllers/            # Controllers principais
│   │   │   └── Auth/               # Controllers de autenticação
│   │   ├── Middleware/             # Middlewares (permissões, ano letivo, etc.)
│   │   └── Requests/               # Form Requests de validação
│   ├── Models/                     # Modelos Eloquent
│   │   ├── User.php                # Utilizador (aluno, professor, admin, etc.)
│   │   ├── Nota.php                # Notas com cálculo automático de médias
│   │   ├── Turma.php               # Turmas escolares
│   │   ├── Disciplina.php          # Disciplinas
│   │   ├── Curso.php               # Cursos
│   │   ├── AnoLetivo.php           # Anos letivos
│   │   ├── NotaLog.php             # Auditoria de alterações de notas
│   │   └── ...
│   ├── Notifications/              # Notificações (ex: pauta desbloqueada)
│   ├── Observers/                  # Observers Eloquent (NotaObserver)
│   ├── Policies/                   # Políticas de autorização
│   ├── Providers/                  # Service Providers
│   └── Services/                   # Serviços de negócio
│       ├── NotaService.php         # Lógica de cálculo e importação de notas
│       ├── EstatisticasAcademicasService.php
│       ├── EstadoMatriculaService.php
│       └── PautaGeralTemplateExporter.php
├── database/
│   ├── migrations/                 # Migrações da base de dados
│   └── seeders/                    # Seeders (papéis, permissões, admin)
├── public/
│   └── images/                     # Logo e imagens estáticas
├── resources/
│   ├── css/app.css                 # Estilos globais com tokens CSS
│   ├── js/app.js                   # Alpine.js e lógica de UI
│   ├── templates/                  # Templates Excel para pautas
│   └── views/
│       ├── layouts/                # Layouts principal e de autenticação
│       ├── components/             # Componentes Blade reutilizáveis
│       ├── dashboard/              # Dashboards por papel
│       ├── notas/                  # Vistas de lançamento de notas
│       ├── relatorios/             # Relatórios e PDFs
│       │   └── pdf/                # Templates PDF (boletim, pauta, histórico)
│       ├── turmas/                 # Gestão de turmas
│       ├── users/                  # Gestão de utilizadores
│       └── ...
├── routes/
│   ├── web.php                     # Rotas web principais
│   ├── auth.php                    # Rotas de autenticação
│   └── console.php                 # Comandos agendados (backup DB)
└── storage/
└── app/
└── backups/database/       # Backups automáticos da base de dados

---

## 🗺️ Rotas Principais

### Autenticação
| Método | URI | Descrição |
|--------|-----|-----------|
| `GET` | `/login` | Formulário de login |
| `POST` | `/login` | Processar login (e-mail ou nº processo) |
| `POST` | `/logout` | Terminar sessão |
| `GET` | `/forgot-password` | Recuperação de senha |

### Dashboard e Perfil
| Método | URI | Descrição |
|--------|-----|-----------|
| `GET` | `/dashboard` | Dashboard (adaptado ao papel do utilizador) |
| `GET` | `/profile` | Perfil do utilizador autenticado |

### Gestão Académica
| Método | URI | Descrição |
|--------|-----|-----------|
| `GET` | `/anos-letivos` | Listar anos letivos |
| `GET` | `/cursos` | Listar cursos |
| `GET` | `/disciplinas` | Listar disciplinas |
| `GET` | `/turmas` | Listar turmas |
| `POST` | `/turmas/{turma}/matricular-aluno` | Matricular aluno numa turma |
| `POST` | `/turmas/{turma}/promover` | Promover turma para a classe seguinte |

### Notas
| Método | URI | Descrição |
|--------|-----|-----------|
| `GET` | `/notas` | Pauta de notas (adaptada ao papel) |
| `POST` | `/notas/trimestre/{1\|2\|3}` | Lançar notas de um trimestre |
| `GET` | `/notas/{nota}/edit` | Editar nota individual |
| `POST` | `/notas/finalizar` | Finalizar/bloquear pauta |
| `POST` | `/notas/reabrir` | Reabrir pauta bloqueada |
| `POST` | `/notas/avaliacoes-continuas` | Adicionar avaliação contínua |

### Relatórios
| Método | URI | Descrição |
|--------|-----|-----------|
| `GET` | `/relatorios` | Painel de relatórios |
| `GET` | `/relatorios/boletim/{aluno?}` | Boletim individual (PDF/Excel) |
| `GET` | `/relatorios/boletins-massa` | Boletins em massa (Excel/PDF) |
| `GET` | `/relatorios/pauta/{turma}/{disciplina?}` | Pauta por disciplina |
| `GET` | `/relatorios/pauta-geral/{turma}` | Pauta geral do ano letivo |
| `GET` | `/relatorios/historico/{aluno?}` | Histórico académico |

### Logs e Estatísticas
| Método | URI | Descrição |
|--------|-----|-----------|
| `GET` | `/logs` | Lista de logs com filtros |
| `GET` | `/logs/dashboard` | Dashboard de auditoria |
| `GET` | `/logs/exportar` | Exportar logs para Excel |
| `GET` | `/estatisticas` | Estatísticas académicas |

---

## 📸 Screenshots

> As capturas de ecrã abaixo mostram as principais interfaces do sistema.

| Dashboard (Admin) | Lançamento de Notas |
|:-:|:-:|
| ![Dashboard](.github/screenshots/dashboard.png) | ![Notas](.github/screenshots/notas.png) |

| Boletim do Aluno | Logs de Auditoria |
|:-:|:-:|
| ![Boletim](.github/screenshots/boletim.png) | ![Logs](.github/screenshots/logs.png) |

---

## 🛠️ Comandos Artisan Úteis

```bash
# ── Desenvolvimento ────────────────────────────────────────

# Resetar e recriar toda a base de dados com dados de teste
php artisan migrate:fresh --seed

# Limpar todos os caches
php artisan optimize:clear

# Listar todas as rotas registadas
php artisan route:list

# ── Produção ──────────────────────────────────────────────

# Otimizar a aplicação (config + route + view cache)
php artisan optimize

# Limpar e recriar o cache de configurações
php artisan config:clear && php artisan config:cache

# ── Backup da Base de Dados ───────────────────────────────

# Executar backup manualmente
php artisan backup:database

# ── Agendamento (necessário configurar cron em produção) ──

# Ver tarefas agendadas
php artisan schedule:list

# Executar o scheduler manualmente (para testes)
php artisan schedule:run

# ── Fila de Jobs (se configurada) ─────────────────────────

php artisan queue:work
```

### Configurar o Cron em Produção (Linux)

```bash
# Adicionar ao crontab do servidor
crontab -e

# Adicionar a seguinte linha:
* * * * * cd /caminho/para/siga && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Testes

```bash
# Executar todos os testes
php artisan test

# Executar com cobertura de código
php artisan test --coverage

# Executar um ficheiro de teste específico
php artisan test tests/Feature/NotaTest.php
```

---

## 🤝 Contribuição

Contribuições são bem-vindas! Para contribuir:

1. Faça um fork do repositório
2. Crie uma branch para a sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Faça commit das suas alterações (`git commit -m 'Adiciona nova funcionalidade'`)
4. Faça push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

### Padrões de Código

- Siga os padrões **PSR-12** para PHP
- Use **Conventional Commits** para as mensagens de commit
- Escreva testes para novas funcionalidades
- Documente métodos públicos com PHPDoc

---

## 📄 Licença

Este projeto está licenciado sob a licença **MIT**. Consulte o ficheiro [LICENSE](LICENSE) para mais detalhes.

---

## 👨‍💻 Autor

Desenvolvido com ❤️ para o **IPIKK-NV** — Instituto Politécnico Industrial do Kilamba Kiaxi "Nova Vida" — Angola 🇦🇴

---

<div align="center">

**[⬆ Voltar ao topo](#-siga--sistema-integrado-de-gestão-académica)**

</div>


## Backup automático da base de dados

Foi adicionado o comando `php artisan backup:database`, com agendamento diário pelo Laravel Scheduler.

- **Horário padrão:** `02:00` (configurável via `DB_BACKUP_SCHEDULE` no `.env`).
- **Retenção padrão:** 14 dias (configurável via `DB_BACKUP_RETENTION_DAYS`).
- **Destino dos ficheiros:** `storage/app/backups/database`.

### Ativar execução automática no servidor

Garanta que o cron do sistema executa o scheduler do Laravel a cada minuto:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Também pode testar manualmente:

```bash
php artisan backup:database
```