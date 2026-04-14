<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_formacao', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        DB::table('areas_formacao')->insert([
            [
                'nome' => 'Informatica',
                'descricao' => 'Cursos da area de tecnologias de informacao e sistemas.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Saude',
                'descricao' => 'Cursos ligados a cuidados de saude e areas clinicas.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Gestao',
                'descricao' => 'Cursos ligados a administracao, economia e gestao.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Ciencias',
                'descricao' => 'Cursos da area de ciencias gerais.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('area_formacao_id')
                ->nullable()
                ->constrained('areas_formacao')
                ->restrictOnDelete();
        });

        $areaInformaticaId = DB::table('areas_formacao')
            ->where('nome', 'Informatica')
            ->value('id');

        if ($areaInformaticaId) {
            DB::table('cursos')
                ->whereNull('area_formacao_id')
                ->update(['area_formacao_id' => $areaInformaticaId]);
        }
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_formacao_id');
        });

        Schema::dropIfExists('areas_formacao');
    }
};
