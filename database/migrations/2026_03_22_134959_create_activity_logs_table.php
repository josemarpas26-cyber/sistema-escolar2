<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Quem executou a ação (usa withTrashed pois o ADM pode ser deletado)
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('acao', 50); // 'user_deleted', 'user_restored', etc.
            $table->string('modelo', 50); // 'User', 'Turma', etc.
            $table->unsignedBigInteger('modelo_id');
            $table->json('dados_anteriores')->nullable(); // snapshot antes da ação
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('criado_em')->useCurrent();

            $table->index(['modelo', 'modelo_id']);
            $table->index('usuario_id');
            $table->index('criado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};