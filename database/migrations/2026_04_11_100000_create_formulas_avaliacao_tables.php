<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('formulas_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->cascadeOnDelete();
            $table->string('nome');
            $table->json('componentes');
            $table->json('regras')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->unique(['ano_letivo_id', 'nome']);
        });

        Schema::create('formula_avaliacao_versoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_avaliacao_id')->constrained('formulas_avaliacao')->cascadeOnDelete();
            $table->unsignedInteger('versao');
            $table->json('componentes');
            $table->json('regras')->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['formula_avaliacao_id', 'versao']);
        });

        Schema::create('avaliacoes_dinamicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->cascadeOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->cascadeOnDelete();
            $table->foreignId('formula_avaliacao_id')->constrained('formulas_avaliacao')->cascadeOnDelete();
            $table->string('nome');
            $table->enum('tipo', ['prova_escrita', 'trabalho', 'avaliacao_continua', 'exame_final']);
            $table->decimal('peso', 5, 2);
            $table->json('excecoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_dinamicas');
        Schema::dropIfExists('formula_avaliacao_versoes');
        Schema::dropIfExists('formulas_avaliacao');
    }
};
