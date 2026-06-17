<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->boolean('bloqueado_pp1')->default(false)->after('bloqueado_t3');
            $table->boolean('bloqueado_pt1')->default(false)->after('bloqueado_pp1');
            $table->boolean('bloqueado_pp2')->default(false)->after('bloqueado_pt1');
            $table->boolean('bloqueado_pt2')->default(false)->after('bloqueado_pp2');
            $table->boolean('bloqueado_pp3')->default(false)->after('bloqueado_pt2');
            $table->boolean('bloqueado_pt3')->default(false)->after('bloqueado_pp3');
            $table->boolean('bloqueado_pg')->default(false)->after('bloqueado_pt3');
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn([
                'bloqueado_pp1',
                'bloqueado_pt1',
                'bloqueado_pp2',
                'bloqueado_pt2',
                'bloqueado_pp3',
                'bloqueado_pt3',
                'bloqueado_pg',
            ]);
        });
    }
};
