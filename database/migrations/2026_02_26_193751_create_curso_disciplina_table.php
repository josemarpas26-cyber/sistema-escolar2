<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }
};
