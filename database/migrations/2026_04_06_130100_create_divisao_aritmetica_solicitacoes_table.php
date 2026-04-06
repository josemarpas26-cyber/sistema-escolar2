<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisao_aritmetica_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->constrained('notas')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coordenador_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pendente'); // pendente, aprovada, rejeitada
            $table->text('mensagem')->nullable();
            $table->timestamp('respondida_em')->nullable();
            $table->foreignId('respondida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resposta')->nullable();
            $table->timestamps();

            $table->index(['coordenador_id', 'status']);
            $table->index(['professor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisao_aritmetica_solicitacoes');
    }
};
