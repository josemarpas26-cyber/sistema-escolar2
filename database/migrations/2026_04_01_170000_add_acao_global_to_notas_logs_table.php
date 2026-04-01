<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_logs', function (Blueprint $table) {
            $table->boolean('acao_global')
                ->default(false)
                ->after('disciplina_id');

            $table->index('acao_global');
        });
    }

    public function down(): void
    {
        Schema::table('notas_logs', function (Blueprint $table) {
            $table->dropIndex(['acao_global']);
            $table->dropColumn('acao_global');
        });
    }
};
