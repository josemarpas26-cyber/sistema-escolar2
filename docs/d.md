# Diagramas principais do Sistema Escolar

## 1) Diagrama de Caso de Uso
```mermaid
usecaseDiagram
    actor Administrador as Admin
    actor Secretaria as Secretaria
    actor Professor as Professor
    actor Aluno as Aluno
    actor Coordenador as Coordenador

    Admin --> (Gerenciar usuários)
    Admin --> (Gerenciar papéis e permissões)
    Admin --> (Visualizar auditoria e logs)

    Secretaria --> (Gerenciar cursos)
    Secretaria --> (Gerenciar disciplinas)
    Secretaria --> (Gerenciar turmas)
    Secretaria --> (Matricular alunos)
    Secretaria --> (Gerar relatórios)

    Professor --> (Lançar notas)
    Professor --> (Lançar avaliações contínuas)
    Professor --> (Consultar turmas e pautas)
    Professor --> (Gerenciar eventos do calendário)

    Aluno --> (Consultar boletim)
    Aluno --> (Consultar histórico acadêmico)
    Aluno --> (Visualizar calendário)

    Coordenador --> (Acompanhar desempenho da turma)
    Coordenador --> (Aprovar solicitações acadêmicas)
```

## 2) Diagrama de Classes (UML)
```mermaid
classDiagram
    class User {
        +id: bigint
        +name: string
        +email: string
        +role_id: bigint
        +ativo: boolean
    }

    class Role {
        +id: bigint
        +name: string
        +display_name: string
    }

    class Curso {
        +id: bigint
        +nome: string
        +codigo: string
        +coordenador_id: bigint
    }

    class Disciplina {
        +id: bigint
        +nome: string
        +codigo: string
        +coordenador_id: bigint
    }

    class Turma {
        +id: bigint
        +nome: string
        +classe: enum
        +curso_id: bigint
        +ano_letivo_id: bigint
    }

    class Nota {
        +id: bigint
        +aluno_id: bigint
        +turma_id: bigint
        +disciplina_id: bigint
        +cf: decimal
        +pg: decimal
        +status: enum
    }

    class AvaliacaoContinua {
        +id: bigint
        +nota_id: bigint
        +professor_id: bigint
        +trimestre: tinyint
        +valor: decimal
    }

    class HistoricoAcademico {
        +id: bigint
        +aluno_id: bigint
        +disciplina_id: bigint
        +classificacao_final: decimal
        +resultado: enum
    }

    User --> Role : pertence a
    Curso --> Turma : possui
    Turma --> Nota : agrega
    Disciplina --> Nota : avaliada em
    Nota --> AvaliacaoContinua : compõe
    User --> Nota : aluno/professor
    User --> HistoricoAcademico : possui
```

## 3) Diagrama Entidade-Relacionamento (DER)
```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email
        bigint role_id FK
        boolean ativo
    }

    ROLES {
        bigint id PK
        string name
        string display_name
    }

    CURSOS {
        bigint id PK
        string nome
        string codigo
        bigint coordenador_id FK
    }

    DISCIPLINAS {
        bigint id PK
        string nome
        string codigo
        bigint coordenador_id FK
    }

    TURMAS {
        bigint id PK
        string nome
        enum classe
        bigint curso_id FK
        bigint ano_letivo_id FK
    }

    TURMA_ALUNO {
        bigint id PK
        bigint turma_id FK
        bigint aluno_id FK
        date data_matricula
        enum status
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
        decimal cf
        decimal pg
        enum status
    }

    AVALIACOES_CONTINUAS {
        bigint id PK
        bigint nota_id FK
        bigint professor_id FK
        tinyint trimestre
        decimal valor
    }

    HISTORICO_ACADEMICO {
        bigint id PK
        bigint aluno_id FK
        bigint turma_id FK
        bigint disciplina_id FK
        bigint ano_letivo_id FK
        decimal classificacao_final
        enum resultado
    }

    ROLES ||--o{ USERS : atribui
    CURSOS ||--o{ TURMAS : organiza
    USERS ||--o{ TURMA_ALUNO : matricula
    TURMAS ||--o{ TURMA_ALUNO : contem
    USERS ||--o{ PROFESSOR_TURMA_DISCIPLINA : leciona
    TURMAS ||--o{ PROFESSOR_TURMA_DISCIPLINA : oferece
    DISCIPLINAS ||--o{ PROFESSOR_TURMA_DISCIPLINA : componente
    USERS ||--o{ NOTAS : aluno
    TURMAS ||--o{ NOTAS : pauta
    DISCIPLINAS ||--o{ NOTAS : disciplina
    NOTAS ||--o{ AVALIACOES_CONTINUAS : detalha
    USERS ||--o{ HISTORICO_ACADEMICO : possui
    DISCIPLINAS ||--o{ HISTORICO_ACADEMICO : registra
```
