<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migration de correção
        Schema::table('notas_logs', function (Blueprint $table) {
            $table->string('valor_anterior', 20)->nullable()->change();
            $table->string('valor_novo', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_logs', function (Blueprint $table) {
            //
        });
    }
};
