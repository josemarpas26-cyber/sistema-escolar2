<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes_continuas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('notas')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('trimestre');
            $table->string('descricao', 120);
            $table->decimal('valor', 5, 2);
            $table->date('data_avaliacao')->nullable();
            $table->timestamps();

            $table->index(['nota_id', 'trimestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_continuas');
    }
};
