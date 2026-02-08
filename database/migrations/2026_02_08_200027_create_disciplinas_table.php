<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinas', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: Matemática, Física, TIC
            $table->string('codigo')->unique(); // Ex: MAT, FIS, TIC
            $table->text('descricao')->nullable();
            
            // Controle de quando a disciplina é lecionada
            $table->boolean('leciona_10')->default(true);
            $table->boolean('leciona_11')->default(true);
            $table->boolean('leciona_12')->default(true);
            $table->boolean('disciplina_terminal')->default(false); // Ex: TIC apenas na 10ª
            
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinas');
    }
};