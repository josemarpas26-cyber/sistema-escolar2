<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            // Índice antigo já não existe, apenas criar o novo
            $table->unique(['curso_id', 'classe', 'nome', 'turno'], 'turmas_nome_completo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropUnique('turmas_nome_completo_unique');
        });
    }
};