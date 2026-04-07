<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->foreignId('coordenador_id')
                ->nullable()
                ->after('descricao')
                ->constrained('users')
                ->nullOnDelete();

            $table->unique('coordenador_id', 'disciplinas_coordenador_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            // 1. remove foreign key + coluna
            $table->dropConstrainedForeignId('coordenador_id');

            // 2. depois remove o índice unique
            $table->dropUnique('disciplinas_coordenador_id_unique');
        });
    }
};
