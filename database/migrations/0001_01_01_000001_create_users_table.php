<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->constrained()->onDelete('restrict');
            
            // Dados pessoais
            $table->string('bi')->nullable()->unique(); // Bilhete de Identidade
            $table->date('data_nascimento')->nullable();
            $table->enum('genero', ['M', 'F'])->nullable();
            $table->string('telefone')->nullable();
            $table->string('endereco')->nullable();
            $table->string('foto_perfil')->nullable(); // path da imagem
            
            // Dados académicos (para alunos)
            $table->string('numero_processo')->nullable()->unique();
            $table->string('nome_encarregado')->nullable();
            $table->string('contacto_encarregado')->nullable();
            
            // Status
            $table->boolean('ativo')->default(true);
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // para não perder histórico
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};