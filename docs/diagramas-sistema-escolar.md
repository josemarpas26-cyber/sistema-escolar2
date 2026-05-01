# Diagramas do Sistema Escolar

## 1. Diagrama de Casos de Uso
```mermaid
usecaseDiagram
    actor Administrador as Admin
    actor Secretaria as Secretaria
    actor Professor as Professor
    actor Aluno as Aluno
    actor Coordenador as Coordenador

    Admin --> (Gerenciar usuários)
    Admin --> (Gerenciar papéis e permissões)
    Secretaria --> (Gerenciar usuários)
    Secretaria --> (Gerenciar cursos)
    Secretaria --> (Gerenciar disciplinas)
    Secretaria --> (Gerenciar turmas)
    Secretaria --> (Matricular aluno em turma)
    Secretaria --> (Gerenciar ano letivo)
    Secretaria --> (Gerar relatórios de boletim e histórico)
    Secretaria --> (Gerenciar backups)
    Secretaria --> (Visualizar logs de sistema)

    Professor --> (Consultar notas de turmas)
    Professor --> (Lançar notas e avaliações contínuas)
    Professor --> (Atribuir professor a turma/disciplina)
    Professor --> (Consultar histórico de notas de aluno)
    Professor --> (Gerenciar eventos do calendário)

    Aluno --> (Consultar boletim)
    Aluno --> (Consultar histórico acadêmico)
    Aluno --> (Visualizar calendário escolar)

    Coordenador --> (Consultar desempenho da turma)
    Coordenador --> (Aprovar solicitações de divisão aritmética)
    Coordenador --> (Gerenciar cursos e disciplinas coordenadas)
```

## 2. Diagrama de Fluxo de Dados (DFD) - Nível 1
```mermaid
flowchart TD
    subgraph E1[Usuários Externos]
        A1[Secretaria / Admin]
        A2[Professor]
        A3[Aluno]
    end

    subgraph P[Processos]
        P1[Autenticação e Autorização]
        P2[Gestão de Turmas e Matrículas]
        P3[Gestão de Notas e Avaliações]
        P4[Geração de Relatórios]
        P5[Gestão de Calendário]
        P6[Logs e Auditoria]
        P7[Backup e Recuperação]
    end

    subgraph D[Armazenamento de Dados]
        D1[(users)]
        D2[(roles)]
        D3[(cursos)]
        D4[(disciplinas)]
        D5[(turmas)]
        D6[(turma_aluno)]
        D7[(professor_turma_disciplina)]
        D8[(notas)]
        D9[(avaliacoes_continuas)]
        D10[(configuracoes_avaliacao)]
        D11[(provas_avaliacao)]
        D12[(historico_academico)]
        D13[(notas_logs)]
        D14[(calendario_eventos)]
        D15[(anos_letivos)]
    end

    A1 -->|Autentica/gera relatórios| P1
    A2 -->|Autentica/lança notas| P1
    A3 -->|Autentica/consulta boletim| P1

    P1 --> D1
    P1 --> D2

    A1 -->|Cria/edita turmas e matrículas| P2
    A1 -->|Atribui professores| P2
    A2 -->|Gerencia suas turmas| P2

    P2 --> D3
    P2 --> D4
    P2 --> D5
    P2 --> D6
    P2 --> D7
    P2 --> D15

    A2 -->|Lança notas e avaliações| P3
    A1 -->|Valida notas e finaliza pauta| P3
    A3 -->|Consulta notas| P3

    P3 --> D8
    P3 --> D9
    P3 --> D10
    P3 --> D11
    P3 --> D13

    A1 -->|Solicita relatórios| P4
    A2 -->|Solicita relatórios| P4
    A3 -->|Visualiza boletim| P4

    P4 --> D5
    P4 --> D8
    P4 --> D12
    P4 --> D15

    A2 -->|Gerencia eventos| P5
    A3 -->|Visualiza calendário| P5

    P5 --> D14
    P5 --> D5

    P6 -->|Captura alterações| D13
    P6 -->|Audita usuários| D1

    A1 -->|Executa backup| P7
    P7 --> D1
    P7 --> D5
    P7 --> D8
    P7 --> D14
```

## 3. Diagrama Entidade-Relacionamento (DER)
```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email
        bigint role_id FK
        string bi
        date data_nascimento
        enum genero
        string telefone
        string endereco
        string foto_perfil
        string numero_processo
        string nome_encarregado
        string contacto_encarregado
        boolean ativo
    }
    ROLES {
        bigint id PK
        string name
        string display_name
        text description
    }
    PERMISSIONS {
        bigint id PK
        string name
        string display_name
        text description
    }
    ROLE_PERMISSION {
        bigint id PK
        bigint role_id FK
        bigint permission_id FK
    }
    CURSOS {
        bigint id PK
        string nome
        string codigo
        text descricao
        bigint coordenador_id FK
        boolean ativo
    }
    DISCIPLINAS {
        bigint id PK
        string nome
        string codigo
        text descricao
        boolean leciona_10
        boolean leciona_11
        boolean leciona_12
        boolean disciplina_terminal
        boolean ativo
    }
    ANOS_LETIVOS {
        bigint id PK
        string nome
        date data_inicio
        date data_fim
        boolean ativo
        boolean encerrado
    }
    TURMAS {
        bigint id PK
        string nome
        enum classe
        bigint curso_id FK
        bigint ano_letivo_id FK
        bigint coordenador_turma_id FK
        int capacidade
        boolean ativo
    }
    TURMA_ALUNO {
        bigint id PK
        bigint turma_id FK
        bigint aluno_id FK
        date data_matricula
        enum status
    }
    TURMA_DISCIPLINA {
        bigint id PK
        bigint turma_id FK
        bigint disciplina_id FK
    }
    PROFESSOR_TURMA_DISCIPLINA {
        bigint id PK
        bigint professor_id FK
        bigint turma_id FK
        bigint disciplina_id FK
        bigint ano_letivo_id FK
    }
    NOTAS {
        bigint id PK
        bigint aluno_id FK
        bigint turma_id FK
        bigint disciplina_id FK
        bigint ano_letivo_id FK
        decimal mac1
        decimal pp1
        decimal pt1
        decimal mt1
        decimal mac2
        decimal pp2
        decimal pt2
        decimal mt2
        decimal mft2
        decimal mac3
        decimal pp3
        decimal mt3
        decimal cf
        decimal pg
        decimal ca
        decimal cfd
        decimal ca_10
        decimal ca_11
        enum status
        text observacoes
    }
    AVALIACOES_CONTINUAS {
        bigint id PK
        bigint nota_id FK
        bigint professor_id FK
        tinyint trimestre
        string descricao
        decimal valor
        date data_avaliacao
    }
    CONFIGURACOES_AVALIACAO {
        bigint id PK
        bigint ano_letivo_id FK
        decimal peso_pg
        decimal nota_minima_aprovacao
    }
    PROVAS_AVALIACAO {
        bigint id PK
        bigint configuracao_avaliacao_id FK
        tinyint periodo
        string nome
        string codigo
        decimal peso
        boolean ativo
        tinyint ordem
    }
    HISTORICO_ACADEMICO {
        bigint id PK
        bigint aluno_id FK
        bigint turma_id FK
        bigint disciplina_id FK
        bigint ano_letivo_id FK
        enum classe
        decimal classificacao_final
        enum resultado
        text observacoes
        timestamp data_conclusao
    }
    NOTAS_LOGS {
        bigint id PK
        bigint nota_id FK
        bigint usuario_id FK
        bigint aluno_id FK
        bigint turma_id FK
        bigint disciplina_id FK
        enum acao
        string campo_alterado
        decimal valor_anterior
        decimal valor_novo
        string trimestre
        text motivo
        string ip_address
        timestamp data_alteracao
    }
    CALENDARIO_EVENTOS {
        bigint id PK
        bigint turma_id FK
        bigint professor_id FK
        string titulo
        text descricao
        string local
        datetime inicio
        datetime fim
    }

    ROLES ||--o{ USERS : "atribui"
    ROLES ||--o{ ROLE_PERMISSION : "possui"
    PERMISSIONS ||--o{ ROLE_PERMISSION : "é usado em"
    USERS }o--|| CURSOS : "coordenador de"
    USERS }o--|| TURMAS : "coordenador de"
    USERS }o--|| DISCIPLINAS : "coordenador de"
    USERS ||--o{ TURMA_ALUNO : "matricula"
    TURMAS ||--o{ TURMA_ALUNO : "matriz"
    DISCIPLINAS ||--o{ TURMA_DISCIPLINA : "pertence a"
    TURMAS ||--o{ TURMA_DISCIPLINA : "possui"
    USERS ||--o{ PROFESSOR_TURMA_DISCIPLINA : "professor em"
    TURMAS ||--o{ PROFESSOR_TURMA_DISCIPLINA : "contém"
    DISCIPLINAS ||--o{ PROFESSOR_TURMA_DISCIPLINA : "cobre"
    ANOS_LETIVOS ||--o{ PROFESSOR_TURMA_DISCIPLINA : "ano"
    USERS ||--o{ NOTAS : "aluno de"
    TURMAS ||--o{ NOTAS : "pertence a"
    DISCIPLINAS ||--o{ NOTAS : "referencia"
    ANOS_LETIVOS ||--o{ NOTAS : "período"
    NOTAS ||--o{ AVALIACOES_CONTINUAS : "tem"
    ANOS_LETIVOS ||--o{ CONFIGURACOES_AVALIACAO : "possui"
    CONFIGURACOES_AVALIACAO ||--o{ PROVAS_AVALIACAO : "define"
    USERS ||--o{ HISTORICO_ACADEMICO : "aluno"
    TURMAS ||--o{ HISTORICO_ACADEMICO : "turma"
    DISCIPLINIAS ||--o{ HISTORICO_ACADEMICO : "disciplina"
    ANOS_LETIVOS ||--o{ HISTORICO_ACADEMICO : "ano"
    NOTAS ||--o{ NOTAS_LOGS : "audita"
    USERS ||--o{ NOTAS_LOGS : "criado por"
    TURMAS ||--o{ CALENDARIO_EVENTOS : "agenda"
    USERS ||--o{ CALENDARIO_EVENTOS : "criado por"
```

## 4. Modelo Lógico de Dados
```mermaid
classDiagram
    class User {
        +id: bigint
        +name: string
        +email: string
        +role_id: bigint
        +bi: string
        +data_nascimento: date
        +genero: enum
        +telefone: string
        +endereco: string
        +foto_perfil: string
        +numero_processo: string
        +nome_encarregado: string
        +contacto_encarregado: string
        +ativo: boolean
    }
    class Role {
        +id: bigint
        +name: string
        +display_name: string
        +description: text
    }
    class Permission {
        +id: bigint
        +name: string
        +display_name: string
        +description: text
    }
    class Curso {
        +id: bigint
        +nome: string
        +codigo: string
        +descricao: text
        +coordenador_id: bigint
        +ativo: boolean
    }
    class Disciplina {
        +id: bigint
        +nome: string
        +codigo: string
        +descricao: text
        +leciona_10: boolean
        +leciona_11: boolean
        +leciona_12: boolean
        +disciplina_terminal: boolean
        +ativo: boolean
    }
    class AnoLetivo {
        +id: bigint
        +nome: string
        +data_inicio: date
        +data_fim: date
        +ativo: boolean
        +encerrado: boolean
    }
    class Turma {
        +id: bigint
        +nome: string
        +classe: enum
        +curso_id: bigint
        +ano_letivo_id: bigint
        +coordenador_turma_id: bigint
        +capacidade: int
        +ativo: boolean
    }
    class Nota {
        +id: bigint
        +aluno_id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
        +ano_letivo_id: bigint
        +mac1: decimal
        +pp1: decimal
        +pt1: decimal
        +mt1: decimal
        +mac2: decimal
        +pp2: decimal
        +pt2: decimal
        +mt2: decimal
        +mft2: decimal
        +mac3: decimal
        +pp3: decimal
        +mt3: decimal
        +cf: decimal
        +pg: decimal
        +ca: decimal
        +cfd: decimal
        +ca_10: decimal
        +ca_11: decimal
        +status: enum
        +observacoes: text
    }
    class TurmaAluno {
        +id: bigint
        +turma_id: bigint
        +aluno_id: bigint
        +data_matricula: date
        +status: enum
    }
    class TurmaDisciplina {
        +id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
    }
    class ProfessorTurmaDisciplina {
        +id: bigint
        +professor_id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
        +ano_letivo_id: bigint
    }
    class HistoricoAcademico {
        +id: bigint
        +aluno_id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
        +ano_letivo_id: bigint
        +classe: enum
        +classificacao_final: decimal
        +resultado: enum
        +observacoes: text
        +data_conclusao: timestamp
    }
    class ConfiguracaoAvaliacao {
        +id: bigint
        +ano_letivo_id: bigint
        +peso_pg: decimal
        +nota_minima_aprovacao: decimal
    }
    class ProvaAvaliacao {
        +id: bigint
        +configuracao_avaliacao_id: bigint
        +periodo: tinyint
        +nome: string
        +codigo: string
        +peso: decimal
        +ativo: boolean
        +ordem: tinyint
    }
    class AvaliacaoContinua {
        +id: bigint
        +nota_id: bigint
        +professor_id: bigint
        +trimestre: tinyint
        +descricao: string
        +valor: decimal
        +data_avaliacao: date
    }
    class NotaLog {
        +id: bigint
        +nota_id: bigint
        +usuario_id: bigint
        +aluno_id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
        +acao: enum
        +campo_alterado: string
        +valor_anterior: decimal
        +valor_novo: decimal
        +trimestre: string
        +motivo: text
        +ip_address: string
        +data_alteracao: timestamp
    }
    class CalendarioEvento {
        +id: bigint
        +turma_id: bigint
        +professor_id: bigint
        +titulo: string
        +descricao: text
        +local: string
        +inicio: datetime
        +fim: datetime
    }

    User --> Role : "1..* pertence a"
    User --> TurmaAluno : "1..* matricula"
    User --> Nota : "1..* registra"
    User --> HistoricoAcademico : "1..* tem"
    User --> AvaliacaoContinua : "1..* registra"
    User --> CalendarioEvento : "1..* agenda"
    Role --> Permission : "*..*"
    Curso --> Turma : "1..* possui"
    Curso --> Disciplina : "*..* oferece"
    Disciplina --> TurmaDisciplina : "*..* atribui"
    Turma --> TurmaAluno : "1..* contém"
    Turma --> TurmaDisciplina : "1..* contém"
    Turma --> ProfessorTurmaDisciplina : "1..* lecionada em"
    Turma --> Nota : "1..* contém"
    AnoLetivo --> Turma : "1..* define"
    AnoLetivo --> Nota : "1..* define"
    AnoLetivo --> ProfessorTurmaDisciplina : "1..* define"
    AnoLetivo --> ConfiguracaoAvaliacao : "1..1 define"
    ConfiguracaoAvaliacao --> ProvaAvaliacao : "1..* define"
    Nota --> AvaliacaoContinua : "1..* compõe"
    Nota --> NotaLog : "1..* audita"
```

> Observações:
> - O DER identifica `users`, `cursos`, `disciplinas`, `turmas`, `notas`, `anos_letivos` como entidades centrais.
> - O DFD mostra como os atores usam processos-chave e alteram os dados de matrícula, notas, relatórios e calendário.
> - O Modelo Lógico detalha os atributos mais importantes de cada tabela e as principais ligações entre eles.

## 5. Diagrama de Classes (UML)
```mermaid
classDiagram
    class User {
        +id: bigint
        +name: string
        +email: string
        +password: string
        +role_id: bigint
        +bi: string
        +data_nascimento: date
        +genero: enum
        +telefone: string
        +endereco: string
        +foto_perfil: string
        +numero_processo: string
        +nome_encarregado: string
        +contacto_encarregado: string
        +ativo: boolean
    }
    class Role {
        +id: bigint
        +name: string
        +display_name: string
        +description: text
    }
    class Permission {
        +id: bigint
        +name: string
        +display_name: string
        +description: text
    }
    class Curso {
        +id: bigint
        +nome: string
        +codigo: string
        +descricao: text
        +coordenador_id: bigint
        +ativo: boolean
    }
    class Disciplina {
        +id: bigint
        +nome: string
        +codigo: string
        +descricao: text
        +leciona_10: boolean
        +leciona_11: boolean
        +leciona_12: boolean
        +disciplina_terminal: boolean
        +ativo: boolean
    }
    class Turma {
        +id: bigint
        +nome: string
        +classe: enum
        +curso_id: bigint
        +ano_letivo_id: bigint
        +coordenador_turma_id: bigint
        +capacidade: int
        +ativo: boolean
    }
    class Nota {
        +id: bigint
        +aluno_id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
        +ano_letivo_id: bigint
        +cf: decimal
        +pg: decimal
        +status: enum
        +observacoes: text
    }
    class AvaliacaoContinua {
        +id: bigint
        +nota_id: bigint
        +professor_id: bigint
        +trimestre: tinyint
        +descricao: string
        +valor: decimal
        +data_avaliacao: date
    }
    class NotaLog {
        +id: bigint
        +nota_id: bigint
        +usuario_id: bigint
        +acao: enum
        +campo_alterado: string
        +valor_anterior: decimal
        +valor_novo: decimal
        +trimestre: string
        +motivo: text
        +ip_address: string
        +data_alteracao: timestamp
    }
    class CalendarioEvento {
        +id: bigint
        +turma_id: bigint
        +professor_id: bigint
        +titulo: string
        +descricao: text
        +local: string
        +inicio: datetime
        +fim: datetime
    }

    User --> Role : "1..1 pertence a"
    Role --> Permission : "*..* possui"
    User --> Turma : "1..* matriculas / leciona"
    User --> Nota : "1..* registra"
    User --> AvaliacaoContinua : "1..* registra"
    User --> CalendarioEvento : "1..* agenda"
    Turma --> Nota : "1..* contém"
    Nota --> AvaliacaoContinua : "1..* compõe"
    Nota --> NotaLog : "1..* audita"
```

## 6. Arquitetura Física do Sistema
```mermaid
flowchart LR
    Browser[Usuário (Browser)] -->|HTTPS| WebServer[Nginx / Apache + PHP-FPM]
    WebServer -->|Requisição HTTP| Laravel[Aplicação Laravel]
    Laravel -->|Sessão / Auth| AuthDB[(MySQL / MariaDB)]
    Laravel -->|Consultas e transações| DB[(MySQL / MariaDB)]
    Laravel -->|Armazenamento de ficheiros| Storage[(storage/app/public)]
    Laravel -->|Backup / Export| Backup[(Disco / S3)]
    Laravel -->|Email / notificações| MailService[(SMTP / Mailgun)]
    Laravel -->|Serviços externos| External[(APIs e serviços terceiros)]
```

## 7. Script SQL Modelo Físico de Dados
```sql
CREATE DATABASE IF NOT EXISTS sistema_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_escolar;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permission (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY role_permission_unique (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    bi VARCHAR(50) NULL,
    data_nascimento DATE NULL,
    genero ENUM('M','F','O') NULL,
    telefone VARCHAR(50) NULL,
    endereco VARCHAR(255) NULL,
    foto_perfil VARCHAR(255) NULL,
    numero_processo VARCHAR(100) NULL,
    nome_encarregado VARCHAR(255) NULL,
    contacto_encarregado VARCHAR(100) NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cursos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(100) NULL UNIQUE,
    descricao TEXT NULL,
    coordenador_id BIGINT UNSIGNED NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (coordenador_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE disciplinas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(100) NULL UNIQUE,
    descricao TEXT NULL,
    leciona_10 BOOLEAN NOT NULL DEFAULT FALSE,
    leciona_11 BOOLEAN NOT NULL DEFAULT FALSE,
    leciona_12 BOOLEAN NOT NULL DEFAULT FALSE,
    disciplina_terminal BOOLEAN NOT NULL DEFAULT FALSE,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE anos_letivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    encerrado BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE turmas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    classe ENUM('10','11','12') NOT NULL,
    curso_id BIGINT UNSIGNED NOT NULL,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    coordenador_turma_id BIGINT UNSIGNED NULL,
    capacidade INT NOT NULL DEFAULT 0,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE RESTRICT,
    FOREIGN KEY (coordenador_turma_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE turma_aluno (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turma_id BIGINT UNSIGNED NOT NULL,
    aluno_id BIGINT UNSIGNED NOT NULL,
    data_matricula DATE NOT NULL,
    status ENUM('matriculado','transferido','desistente','concluido') NOT NULL DEFAULT 'matriculado',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY turma_aluno_unique (turma_id, aluno_id),
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE professor_turma_disciplina (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professor_id BIGINT UNSIGNED NOT NULL,
    turma_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id BIGINT UNSIGNED NOT NULL,
    turma_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    mac1 DECIMAL(5,2) NULL,
    pp1 DECIMAL(5,2) NULL,
    pt1 DECIMAL(5,2) NULL,
    mt1 DECIMAL(5,2) NULL,
    mac2 DECIMAL(5,2) NULL,
    pp2 DECIMAL(5,2) NULL,
    pt2 DECIMAL(5,2) NULL,
    mt2 DECIMAL(5,2) NULL,
    mft2 DECIMAL(5,2) NULL,
    mac3 DECIMAL(5,2) NULL,
    pp3 DECIMAL(5,2) NULL,
    mt3 DECIMAL(5,2) NULL,
    cf DECIMAL(5,2) NULL,
    pg DECIMAL(5,2) NULL,
    ca DECIMAL(5,2) NULL,
    cfd DECIMAL(5,2) NULL,
    ca_10 DECIMAL(5,2) NULL,
    ca_11 DECIMAL(5,2) NULL,
    status ENUM('pendente','validado','reprovado','aprovado') NOT NULL DEFAULT 'pendente',
    observacoes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (aluno_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE RESTRICT,
    FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE avaliacoes_continuas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id BIGINT UNSIGNED NOT NULL,
    professor_id BIGINT UNSIGNED NOT NULL,
    trimestre TINYINT UNSIGNED NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(5,2) NOT NULL,
    data_avaliacao DATE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE configuracoes_avaliacao (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    peso_pg DECIMAL(5,2) NOT NULL,
    nota_minima_aprovacao DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE provas_avaliacao (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    configuracao_avaliacao_id BIGINT UNSIGNED NOT NULL,
    periodo TINYINT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    codigo VARCHAR(100) NULL,
    peso DECIMAL(5,2) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    ordem TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (configuracao_avaliacao_id) REFERENCES configuracoes_avaliacao(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historico_academico (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id BIGINT UNSIGNED NOT NULL,
    turma_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    classe ENUM('10','11','12') NOT NULL,
    classificacao_final DECIMAL(5,2) NOT NULL,
    resultado ENUM('aprovado','reprovado','recuperacao','retido') NOT NULL,
    observacoes TEXT NULL,
    data_conclusao TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (aluno_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE RESTRICT,
    FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notas_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    aluno_id BIGINT UNSIGNED NOT NULL,
    turma_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    acao ENUM('criado','atualizado','removido') NOT NULL,
    campo_alterado VARCHAR(150) NULL,
    valor_anterior DECIMAL(10,2) NULL,
    valor_novo DECIMAL(10,2) NULL,
    trimestre VARCHAR(20) NULL,
    motivo TEXT NULL,
    ip_address VARCHAR(45) NULL,
    data_alteracao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (aluno_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE calendario_eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turma_id BIGINT UNSIGNED NULL,
    professor_id BIGINT UNSIGNED NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    local VARCHAR(255) NULL,
    inicio DATETIME NOT NULL,
    fim DATETIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL,
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 8. Script SQL de Criação das Tabelas Principais
```sql
-- Tabelas centrais do sistema
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cursos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(100) NULL UNIQUE,
    coordenador_id BIGINT UNSIGNED NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (coordenador_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE turmas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    classe ENUM('10','11','12') NOT NULL,
    curso_id BIGINT UNSIGNED NOT NULL,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id BIGINT UNSIGNED NOT NULL,
    turma_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    ano_letivo_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pendente','validado','reprovado','aprovado') NOT NULL DEFAULT 'pendente',
    observacoes TEXT NULL,
    FOREIGN KEY (aluno_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 9. Autenticação e Controlo de Acesso
O sistema usa o `guard` padrão `web` do Laravel para autenticação de sessão. A autenticação é tratada pelo middleware `Authenticate`, enquanto o controlo de acesso por papel e permissões é feito com RBAC usando `Role`, `Permission`, `CheckRole` e `CheckPermission`.

### 9.1 Configuração do guard web
```php
// config/auth.php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'web'),
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

### 9.2 Middleware de autenticação e RBAC
```php
// app/Http/Middleware/Authenticate.php
namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
```

```php
// app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $roleName = $user->role->name;

        if (!in_array($roleName, $roles)) {
            abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}
```

```php
// app/Http/Middleware/CheckPermission.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!$request->user()?->hasPermission($permission)) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        return $next($request);
    }
}
```

### 9.3 RBAC no modelo
```php
// app/Models/User.php
public function role()
{
    return $this->belongsTo(Role::class);
}

public function hasPermission(string $permissionName): bool
{
    $this->loadMissing('role.permissions');
    return $this->role?->hasPermission($permissionName) ?? false;
}
```

```php
// app/Models/Role.php
public function permissions()
{
    return $this->belongsToMany(Permission::class, 'role_permission')->withTimestamps();
}

public function hasPermission(string $permissionName): bool
{
    return $this->getPermissionNames()->contains($permissionName);
}
```

### 9.4 Exemplo de uso em rotas
```php
Route::middleware(['auth', 'role:admin,secretaria'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::middleware(['auth', 'permission:notas.lancar'])->post('notas/lancar', [NotaController::class, 'store']);
```
