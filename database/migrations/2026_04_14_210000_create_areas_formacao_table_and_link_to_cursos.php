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
                'nome' => 'Construção civil',
                'descricao' => 'Cursos da área de construção civil.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Informática',
                'descricao' => 'Cursos da área de tecnologias de informação e sistemas.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Eletricidade',
                'descricao' => 'Cursos ligados à área de eletricidade e instalações.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Mecânica',
                'descricao' => 'Cursos da área de mecânica industrial.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Tecnologia de móveis',
                'descricao' => 'Cursos da área de tecnologia e produção de móveis.',
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
            ->where('nome', 'Informática')
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
