<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {

            // Remove constraint antiga criada pelo enum
            DB::statement("
                ALTER TABLE turma_aluno
                DROP CONSTRAINT IF EXISTS turma_aluno_status_check
            ");

            // Cria nova constraint
            DB::statement("
                ALTER TABLE turma_aluno
                ADD CONSTRAINT turma_aluno_status_check
                CHECK (
                    status IN (
                        'matriculado',
                        'transferido',
                        'desistente',
                        'concluido',
                        'aprovado',
                        'reprovado'
                    )
                )
            ");

        } else {

            // MySQL
            DB::statement("
                ALTER TABLE turma_aluno
                MODIFY status ENUM(
                    'matriculado',
                    'transferido',
                    'desistente',
                    'concluido',
                    'aprovado',
                    'reprovado'
                ) NOT NULL DEFAULT 'matriculado'
            ");
        }
    }

    public function down(): void
    {
        DB::table('turma_aluno')
            ->whereIn('status', ['aprovado', 'reprovado'])
            ->update(['status' => 'concluido']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {

            DB::statement("
                ALTER TABLE turma_aluno
                DROP CONSTRAINT IF EXISTS turma_aluno_status_check
            ");

            DB::statement("
                ALTER TABLE turma_aluno
                ADD CONSTRAINT turma_aluno_status_check
                CHECK (
                    status IN (
                        'matriculado',
                        'transferido',
                        'desistente',
                        'concluido'
                    )
                )
            ");

        } else {

            DB::statement("
                ALTER TABLE turma_aluno
                MODIFY status ENUM(
                    'matriculado',
                    'transferido',
                    'desistente',
                    'concluido'
                ) NOT NULL DEFAULT 'matriculado'
            ");
        }
    }
};