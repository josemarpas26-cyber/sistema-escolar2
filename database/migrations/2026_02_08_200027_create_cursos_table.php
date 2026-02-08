<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: Ciências Físicas e Biológicas
            $table->string('codigo')->unique(); // Ex: CFB
            $table->text('descricao')->nullable();
            $table->foreignId('coordenador_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};