<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turma_aluno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
            $table->date('data_matricula');
            $table->enum('status', ['matriculado', 'transferido', 'desistente', 'concluido'])->default('matriculado');
            $table->timestamps();
            
            // Um aluno só pode estar matriculado uma vez por turma
            $table->unique(['turma_id', 'aluno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turma_aluno');
    }
};