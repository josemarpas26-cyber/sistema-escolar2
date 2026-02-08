<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('disciplina_id')->constrained()->onDelete('cascade');
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->onDelete('cascade');
            
            // === 1º TRIMESTRE ===
            $table->decimal('mac1', 5, 2)->nullable(); // Média Avaliações Contínuas
            $table->decimal('pp1', 5, 2)->nullable();  // Prova Professor
            $table->decimal('pt1', 5, 2)->nullable();  // Prova Trimestral
            $table->decimal('mt1', 5, 2)->nullable();  // Média Trimestre 1 (calculada)
            
            // === 2º TRIMESTRE ===
            $table->decimal('mac2', 5, 2)->nullable();
            $table->decimal('pp2', 5, 2)->nullable();
            $table->decimal('pt2', 5, 2)->nullable();
            $table->decimal('mt2', 5, 2)->nullable();  // Média Trimestre 2 (calculada)
            $table->decimal('mft2', 5, 2)->nullable(); // Média Final até T2 (calculada)
            
            // === 3º TRIMESTRE ===
            $table->decimal('mac3', 5, 2)->nullable();
            $table->decimal('pp3', 5, 2)->nullable();
            $table->decimal('mt3', 5, 2)->nullable();  // Média Trimestre 3 (calculada)
            $table->decimal('cf', 5, 2)->nullable();   // Classificação Final (calculada)
            
            // === PROVA GLOBAL ===
            $table->decimal('pg', 5, 2)->nullable();   // Prova Global
            
            // === CLASSIFICAÇÕES ANUAIS ===
            $table->decimal('ca', 5, 2)->nullable();   // Classificação Anual (calculada)
            
            // === CLASSIFICAÇÃO FINAL DO CICLO ===
            $table->decimal('cfd', 5, 2)->nullable(); // Classificação Final da Disciplina (calculada)
            
            // === NOTAS ANTERIORES (para cálculo CFD) ===
            $table->decimal('ca_10', 5, 2)->nullable(); // CA da 10ª (importado)
            $table->decimal('ca_11', 5, 2)->nullable(); // CA da 11ª (importado)
            
            // === CONTROLE ===
            $table->enum('status', ['em_lancamento', 'finalizado'])->default('em_lancamento');
            $table->text('observacoes')->nullable();
            
            $table->timestamps();
            
            // Um aluno tem apenas 1 registro de nota por disciplina/turma/ano
            $table->unique(['aluno_id', 'turma_id', 'disciplina_id', 'ano_letivo_id'], 'nota_aluno_turma_disc_ano_unique');
            
            // Índices para performance
            $table->index(['turma_id', 'disciplina_id']);
            $table->index(['aluno_id', 'ano_letivo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};