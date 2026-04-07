<?php

use App\Models\Nota;
use App\Services\NotaService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $notaService = app(NotaService::class);

        Nota::query()
            ->with(['aluno.turmas', 'anoLetivo', 'turma.curso', 'disciplina', 'turma'])
            ->chunkById(100, function ($notas) use ($notaService) {
                foreach ($notas as $nota) {
                    $notaService->recalcularNota($nota);

                    if (! $nota->isDirty()) {
                        continue;
                    }

                    $nota->timestamps = false;
                    $nota->saveQuietly();
                }
            });

        if (Schema::hasColumn('notas', 'usar_divisao_aritmetica_por_2')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->dropColumn('usar_divisao_aritmetica_por_2');
            });
        }

        Schema::dropIfExists('divisao_aritmetica_solicitacoes');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('notas', 'usar_divisao_aritmetica_por_2')) {
            Schema::table('notas', function (Blueprint $table) {
                $table->boolean('usar_divisao_aritmetica_por_2')
                    ->default(false)
                    ->after('observacoes');
            });
        }

        if (! Schema::hasTable('divisao_aritmetica_solicitacoes')) {
            Schema::create('divisao_aritmetica_solicitacoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nota_id')->constrained('notas')->cascadeOnDelete();
                $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('coordenador_id')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('pendente');
                $table->text('mensagem')->nullable();
                $table->timestamp('respondida_em')->nullable();
                $table->foreignId('respondida_por')->nullable()->constrained('users')->nullOnDelete();
                $table->text('resposta')->nullable();
                $table->timestamps();

                $table->index(['coordenador_id', 'status']);
                $table->index(['professor_id', 'status']);
            });
        }
    }
};
