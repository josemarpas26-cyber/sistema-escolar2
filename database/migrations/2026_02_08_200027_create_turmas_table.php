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
            $table->enum('classe', ['10', '11', '12']);
            $table->foreignId('curso_id')->constrained()->onDelete('restrict');
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->onDelete('restrict');
            $table->foreignId('coordenador_turma_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('capacidade')->default(40);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Uma turma é única por curso + classe + ano letivo
            $table->unique(['curso_id', 'classe', 'nome', 'ano_letivo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};