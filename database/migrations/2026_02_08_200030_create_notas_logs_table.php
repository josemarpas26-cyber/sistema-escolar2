<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('notas')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict'); // quem alterou
            
            // Dados do aluno/disciplina para facilitar consultas
            $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('disciplina_id')->constrained()->onDelete('cascade');
            $table->boolean('acao_global')->default(false);
            
            // Tipo de alteração
            $table->string('acao', 80)->default('edicao');
            
            // Campo alterado
            $table->string('campo_alterado'); // Ex: mac1, pp1, mt1, etc
            
            // Valores
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            
            // Contexto adicional
            $table->string('trimestre')->nullable(); // 1, 2, 3
            $table->text('motivo')->nullable(); // Opcional: motivo da alteração
            $table->ipAddress('ip_address')->nullable();
            
            $table->timestamp('data_alteracao')->useCurrent();
            
            // Índices para consultas rápidas
            $table->index(['nota_id', 'data_alteracao']);
            $table->index(['aluno_id', 'disciplina_id']);
            $table->index(['usuario_id', 'data_alteracao']);
            $table->index('acao_global');
            $table->index('data_alteracao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_logs');
    }
};
