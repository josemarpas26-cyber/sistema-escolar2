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
            $table->foreignId('coordenador_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Controle de quando a disciplina é lecionada
            $table->boolean('leciona_10')->default(true);
            $table->boolean('leciona_11')->default(true);
            $table->boolean('leciona_12')->default(true);
            $table->boolean('leciona_13')->default(false);
            $table->boolean('disciplina_terminal')->default(false); // Ex: TIC apenas na 10ª
            
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique('coordenador_id', 'disciplinas_coordenador_id_unique');
        });

        Schema::create('curso_disciplina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disciplina_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('ano_terminal')->nullable()->comment('Ano/classe terminal para a disciplina neste curso (10, 11, 12).');
            $table->timestamps();

            $table->unique(['curso_id', 'disciplina_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_disciplina');
        Schema::dropIfExists('disciplinas');
    }
};
