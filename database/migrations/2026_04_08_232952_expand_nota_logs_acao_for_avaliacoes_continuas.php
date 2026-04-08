<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_logs', function (Blueprint $table) {
            $table->string('acao', 80)->default('edicao')->change();
            $table->text('valor_anterior')->nullable()->change();
            $table->text('valor_novo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notas_logs', function (Blueprint $table) {
            $table->string('valor_anterior', 20)->nullable()->change();
            $table->string('valor_novo', 20)->nullable()->change();
            $table->enum('acao', ['criacao', 'edicao', 'exclusao', 'finalizacao', 'reabertura'])->default('edicao')->change();
        });
    }
};
