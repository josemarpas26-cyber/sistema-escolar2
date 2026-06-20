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
            $table->decimal('pt3', 5, 2)->nullable();  // Prova Trimestral 3
            $table->decimal('mt3', 5, 2)->nullable();  // Média Trimestre 3 (calculada)
            $table->decimal('cf', 5, 2)->nullable();   // Classificação Final (calculada)
            
            // === PROVA GLOBAL ===
            $table->decimal('pg', 5, 2)->nullable();   // Prova Global
            
            // === CLASSIFICAÇÕES ANUAIS ===
            $table->decimal('ca', 5, 2)->nullable();   // Classificação Anual (calculada)
            
            // === CLASSIFICAÇÃO FINAL DO CICLO ===
            $table->decimal('cfd', 5, 2)->nullable(); // Classificação Final da Disciplina (calculada)
            $table->decimal('nota_recurso', 5, 2)->nullable();
            
            // === NOTAS ANTERIORES (para cálculo CFD) ===
            $table->decimal('ca_10', 5, 2)->nullable(); // CA da 10ª (importado)
            $table->decimal('ca_11', 5, 2)->nullable(); // CA da 11ª (importado)
            $table->decimal('ca_12', 5, 2)->nullable(); // CA da 12ª (importado)
            
            // === CONTROLE ===
            $table->enum('status', ['em_lancamento', 'finalizado'])->default('em_lancamento');
            $table->boolean('bloqueado_t1')->default(false);
            $table->boolean('bloqueado_t2')->default(false);
            $table->boolean('bloqueado_t3')->default(false);
            $table->boolean('bloqueado_pp1')->default(false);
            $table->boolean('bloqueado_pt1')->default(false);
            $table->boolean('bloqueado_pp2')->default(false);
            $table->boolean('bloqueado_pt2')->default(false);
            $table->boolean('bloqueado_pp3')->default(false);
            $table->boolean('bloqueado_pt3')->default(false);
            $table->boolean('bloqueado_pg')->default(false);
            $table->text('observacoes')->nullable();
            $table->boolean('usar_divisao_aritmetica_por_2')->default(false);
            
            $table->timestamps();
            
            // Um aluno tem apenas 1 registro de nota por disciplina/turma/ano
            $table->unique(['aluno_id', 'turma_id', 'disciplina_id', 'ano_letivo_id'], 'nota_aluno_turma_disc_ano_unique');
            
            // Índices para performance
            $table->index(['turma_id', 'disciplina_id']);
            $table->index(['aluno_id', 'ano_letivo_id']);
        });

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

    public function down(): void
    {
        Schema::dropIfExists('divisao_aritmetica_solicitacoes');
        Schema::dropIfExists('notas');
    }
};
