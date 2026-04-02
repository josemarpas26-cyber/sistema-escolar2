<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('metas_disciplina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->cascadeOnDelete();
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->cascadeOnDelete();
            $table->decimal('meta_nota', 4, 2);
            $table->date('data_definicao');
            $table->date('data_conclusao_prevista')->nullable();
            $table->enum('status', ['ativa', 'atingida', 'em_risco', 'inviavel', 'desativada'])->default('ativa');
            $table->timestamps();

            $table->index(['aluno_id', 'ano_letivo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_disciplina');
    }
};
