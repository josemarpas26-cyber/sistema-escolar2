<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->boolean('bloqueado_t1')->default(false)->after('status');
            $table->boolean('bloqueado_t2')->default(false)->after('bloqueado_t1');
            $table->boolean('bloqueado_t3')->default(false)->after('bloqueado_t2');
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn(['bloqueado_t1', 'bloqueado_t2', 'bloqueado_t3']);
        });
    }
};
