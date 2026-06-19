<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ano_letivo_id')->unique()->constrained('anos_letivos')->cascadeOnDelete();
            $table->decimal('peso_pg', 5, 2)->default(40.00);
            $table->decimal('nota_minima_aprovacao', 5, 2)->default(10.00);
            $table->timestamps();
        });

        Schema::create('provas_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuracao_avaliacao_id')->constrained('configuracoes_avaliacao')->cascadeOnDelete();
            $table->unsignedTinyInteger('periodo');
            $table->string('nome');
            $table->string('codigo');
            $table->decimal('peso', 8, 4)->default(1);
            $table->boolean('ativo')->default(true);
            $table->unsignedTinyInteger('ordem')->default(1);

            $table->unique(['configuracao_avaliacao_id', 'codigo']);
            $table->index(['configuracao_avaliacao_id', 'periodo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provas_avaliacao');
        Schema::dropIfExists('configuracoes_avaliacao');
    }
};
