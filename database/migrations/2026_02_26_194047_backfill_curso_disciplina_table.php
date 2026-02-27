<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pairs = DB::table('turma_disciplina as td')
            ->join('turmas as t', 't.id', '=', 'td.turma_id')
            ->join('disciplinas as d', 'd.id', '=', 'td.disciplina_id')
            ->select('t.curso_id', 'td.disciplina_id', 'd.disciplina_terminal', 'd.leciona_10', 'd.leciona_11', 'd.leciona_12')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            $anoTerminal = null;

            if ($pair->disciplina_terminal) {
                $anoTerminal = 10;
            } elseif (!$pair->leciona_10 && $pair->leciona_11) {
                $anoTerminal = 11;
            } elseif (!$pair->leciona_10 && !$pair->leciona_11 && $pair->leciona_12) {
                $anoTerminal = 12;
            }

            DB::table('curso_disciplina')->updateOrInsert(
                [
                    'curso_id' => $pair->curso_id,
                    'disciplina_id' => $pair->disciplina_id,
                ],
                [
                    'ano_terminal' => $anoTerminal,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('curso_disciplina')->truncate();
    }
};
