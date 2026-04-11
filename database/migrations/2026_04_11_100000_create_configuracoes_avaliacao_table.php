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
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_avaliacao');
    }
};
