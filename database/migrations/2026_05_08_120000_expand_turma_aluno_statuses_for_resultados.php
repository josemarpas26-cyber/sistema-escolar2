<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE turma_aluno MODIFY status ENUM('matriculado', 'transferido', 'desistente', 'concluido', 'aprovado', 'reprovado') NOT NULL DEFAULT 'matriculado'");
    }

    public function down(): void
    {
        DB::table('turma_aluno')
            ->whereIn('status', ['aprovado', 'reprovado'])
            ->update(['status' => 'concluido']);

        DB::statement("ALTER TABLE turma_aluno MODIFY status ENUM('matriculado', 'transferido', 'desistente', 'concluido') NOT NULL DEFAULT 'matriculado'");
    }
};
