<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remover atribuições duplicadas por turma+disciplina+ano, mantendo o registro mais antigo.
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
            $table->dropUnique('professor_turma_disc_ano_unique');
            $table->unique(['turma_id', 'disciplina_id', 'ano_letivo_id'], 'turma_disc_ano_unique');
            $table->unique(['professor_id', 'turma_id', 'disciplina_id', 'ano_letivo_id'], 'professor_turma_disc_ano_unique');
        });

        // Remover diretores duplicados na mesma combinação professor+ano letivo.
        $duplicadosDiretores = DB::table('turmas')
            ->select('coordenador_turma_id', 'ano_letivo_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('coordenador_turma_id')
            ->groupBy('coordenador_turma_id', 'ano_letivo_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicadosDiretores as $duplo) {
            DB::table('turmas')
                ->where('coordenador_turma_id', $duplo->coordenador_turma_id)
                ->where('ano_letivo_id', $duplo->ano_letivo_id)
                ->where('id', '!=', $duplo->keep_id)
                ->update(['coordenador_turma_id' => null]);
        }

        Schema::table('turmas', function (Blueprint $table) {
            $table->unique(['coordenador_turma_id', 'ano_letivo_id'], 'turmas_coordenador_ano_unique');
        });
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropUnique('turmas_coordenador_ano_unique');
        });

        Schema::table('professor_turma_disciplina', function (Blueprint $table) {
            $table->dropUnique('turma_disc_ano_unique');
            $table->dropUnique('professor_turma_disc_ano_unique');
            $table->unique(['professor_id', 'turma_id', 'disciplina_id', 'ano_letivo_id'], 'professor_turma_disc_ano_unique');
        });
    }
};
