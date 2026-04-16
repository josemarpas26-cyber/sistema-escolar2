<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendario_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->text('descricao')->nullable();
            $table->string('local', 120)->nullable();
            $table->dateTime('inicio');
            $table->dateTime('fim')->nullable();
            $table->timestamps();

            $table->index(['turma_id', 'inicio']);
            $table->index(['professor_id', 'inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_eventos');
    }
};
