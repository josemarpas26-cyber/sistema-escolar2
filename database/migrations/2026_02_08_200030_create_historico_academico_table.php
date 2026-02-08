<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_academico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('turma_id')->constrained()->onDelete('restrict');
            $table->foreignId('disciplina_id')->constrained()->onDelete('restrict');
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->onDelete('restrict');
            
            // Dados da avaliação final
            $table->enum('classe', ['10', '11', '12']);
            $table->decimal('classificacao_final', 5, 2); // CFD ou CA dependendo do contexto
            $table->enum('resultado', ['aprovado', 'reprovado', 'dispensado'])->default('aprovado');
            
            // Metadata
            $table->text('observacoes')->nullable();
            $table->timestamp('data_conclusao');
            
            $table->timestamps();
            
            // Índices
            $table->index(['aluno_id', 'ano_letivo_id']);
            $table->index(['turma_id', 'disciplina_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_academico');
    }
};