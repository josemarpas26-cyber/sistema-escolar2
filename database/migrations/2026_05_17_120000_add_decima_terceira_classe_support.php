<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->boolean('leciona_13')->default(false)->after('leciona_12');
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->decimal('ca_12', 5, 2)->nullable()->after('ca_11');
        });

        Schema::create('classificacoes_ensino_medio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('turma_id')->constrained()->onDelete('cascade');
            $table->foreignId('ano_letivo_id')->constrained('anos_letivos')->onDelete('cascade');
            $table->decimal('pap', 5, 2)->nullable();
            $table->decimal('ecs', 5, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(
                ['aluno_id', 'turma_id', 'ano_letivo_id'],
                'classificacoes_ensino_medio_aluno_turma_ano_unique'
            );
        });

        $this->atualizarEnumClasse(['10', '11', '12', '13']);
    }

    public function down(): void
    {
        $this->atualizarEnumClasse(['10', '11', '12']);

        Schema::dropIfExists('classificacoes_ensino_medio');

        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn('ca_12');
        });

        Schema::table('disciplinas', function (Blueprint $table) {
            $table->dropColumn('leciona_13');
        });
    }

    private function atualizarEnumClasse(array $valores): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $enum = implode("','", $valores);

        DB::statement("ALTER TABLE turmas MODIFY classe ENUM('{$enum}') NOT NULL");
        DB::statement("ALTER TABLE historico_academico MODIFY classe ENUM('{$enum}') NOT NULL");
    }
};
