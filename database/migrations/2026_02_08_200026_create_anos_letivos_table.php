<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anos_letivos', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: 2024/2025
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->boolean('ativo')->default(true); // apenas 1 pode estar ativo
            $table->boolean('encerrado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anos_letivos');
    }
};