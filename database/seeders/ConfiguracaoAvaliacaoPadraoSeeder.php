<?php

namespace Database\Seeders;

use App\Models\AnoLetivo;
use App\Models\ConfiguracaoAvaliacao;
use Illuminate\Database\Seeder;

class ConfiguracaoAvaliacaoPadraoSeeder extends Seeder
{
    public function run(): void
    {
        $ano = AnoLetivo::ativo()->first() ?? AnoLetivo::latest('id')->first();

        if (! $ano) {
            return;
        }

        $padrao = ConfiguracaoAvaliacao::estruturaPadrao();

        $config = ConfiguracaoAvaliacao::firstOrCreate(
            ['ano_letivo_id' => $ano->id],
            [
                'peso_pg' => $padrao['peso_pg'],
                'nota_minima_aprovacao' => $padrao['nota_minima_aprovacao'],
            ]
        );

        if ($config->provas()->exists()) {
            return;
        }

        foreach ($padrao['provas'] as $periodo => $provas) {
            foreach ($provas as $index => $prova) {
                $config->provas()->create([
                    ...$prova,
                    'periodo' => $periodo,
                    'ordem' => $prova['ordem'] ?? ($index + 1),
                ]);
            }
        }
    }
}
