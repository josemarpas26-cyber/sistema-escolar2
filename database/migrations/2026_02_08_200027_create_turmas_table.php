<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: 10ª A, 11ª B
            $table->enum('turno', ['M', 'T'])->default('M')->comment('M=Manhã, T=Tarde');
            $table->enum('classe', ['10', '11', '12', '13']);
            $table->foreignId('curso_id')->constrained()->onDelete('restrict');
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->onDelete('restrict');
            $table->foreignId('coordenador_turma_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('capacidade')->default(40);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Uma turma é única por curso + classe + ano letivo
            $table->unique(['curso_id', 'classe', 'nome', 'ano_letivo_id']);
            $table->unique(['curso_id', 'classe', 'nome', 'turno'], 'turmas_nome_completo_unique');
        });

        Schema::create('turma_aluno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
            $table->date('data_matricula');
            $table->enum('status', [
                'matriculado',
                'transferido',
                'desistente',
                'concluido',
                'aprovado',
                'reprovado',
                'recurso',
            ])->default('matriculado');
            $table->timestamps();

            // Um aluno só pode estar matriculado uma vez por turma
            $table->unique(['turma_id', 'aluno_id']);
        });

        Schema::create('turma_disciplina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('disciplina_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['turma_id', 'disciplina_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turma_disciplina');
        Schema::dropIfExists('turma_aluno');
        Schema::dropIfExists('turmas');
    }
};
