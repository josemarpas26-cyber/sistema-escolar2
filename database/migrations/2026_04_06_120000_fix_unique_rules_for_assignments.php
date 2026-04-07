<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // limpar duplicados
        $duplicadosAtribuicoes = DB::table('professor_turma_disciplina')
            ->select('turma_id', 'disciplina_id', 'ano_letivo_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('turma_id', 'disciplina_id', 'ano_letivo_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicadosAtribuicoes as $duplo) {
            DB::table('professor_turma_disciplina')
                ->where('turma_id', $duplo->turma_id)
                ->where('disciplina_id', $duplo->disciplina_id)
                ->where('ano_letivo_id', $duplo->ano_letivo_id)
                ->where('id', '!=', $duplo->keep_id)
                ->delete();
        }

        Schema::table('professor_turma_disciplina', function (Blueprint $table) {
            $table->unique(['turma_id', 'disciplina_id', 'ano_letivo_id'], 'turma_disc_ano_unique');
        });
    }

    public function down(): void
    {
        Schema::table('professor_turma_disciplina', function (Blueprint $table) {
            // remover foreign keys primeiro (ajusta os nomes!)
            $table->dropForeign(['turma_id']);
            $table->dropForeign(['disciplina_id']);
            $table->dropForeign(['ano_letivo_id']);

            // depois remover o índice
            $table->dropUnique('turma_disc_ano_unique');
        });
    }
};
