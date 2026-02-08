<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professor_turma_disciplina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('disciplina_id')->constrained()->onDelete('cascade');
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->onDelete('cascade');
            $table->timestamps();
            
            // Um professor pode lecionar várias disciplinas na mesma turma
            // Mas não pode lecionar a mesma disciplina na mesma turma 2x
            $table->unique(['professor_id', 'turma_id', 'disciplina_id', 'ano_letivo_id'], 'professor_turma_disc_ano_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_turma_disciplina');
    }
};