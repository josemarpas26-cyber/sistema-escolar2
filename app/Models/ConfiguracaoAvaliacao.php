<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'configuracoes_avaliacao';

    protected $fillable = [
        'ano_letivo_id',
        'peso_pg',
        'nota_minima_aprovacao',
    ];

    protected $casts = [
        'peso_pg' => 'decimal:2',
        'nota_minima_aprovacao' => 'decimal:2',
    ];

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class);
    }

    public function provas()
    {
        return $this->hasMany(ProvaAvaliacao::class, 'configuracao_avaliacao_id')
            ->orderBy('periodo')
            ->orderBy('ordem');
    }

    public function provasAtivas()
    {
        return $this->provas()->where('ativo', true);
    }

    public static function estruturaPadrao(): array
    {
        return [
            'peso_pg' => 40,
            'nota_minima_aprovacao' => 10,
            'provas' => [
                1 => [
                    ['nome' => 'Média de Avaliações Contínuas', 'codigo' => 'mac1', 'peso' => 1, 'ativo' => true, 'ordem' => 1],
                    ['nome' => 'Prova do Professor', 'codigo' => 'pp1', 'peso' => 1, 'ativo' => true, 'ordem' => 2],
                    ['nome' => 'Prova Trimestral', 'codigo' => 'pt1', 'peso' => 1, 'ativo' => true, 'ordem' => 3],
                ],
                2 => [
                    ['nome' => 'Média de Avaliações Contínuas', 'codigo' => 'mac2', 'peso' => 1, 'ativo' => true, 'ordem' => 1],
                    ['nome' => 'Prova do Professor', 'codigo' => 'pp2', 'peso' => 1, 'ativo' => true, 'ordem' => 2],
                    ['nome' => 'Prova Trimestral', 'codigo' => 'pt2', 'peso' => 1, 'ativo' => true, 'ordem' => 3],
                ],
                3 => [
                    ['nome' => 'Média de Avaliações Contínuas', 'codigo' => 'mac3', 'peso' => 1, 'ativo' => true, 'ordem' => 1],
                    ['nome' => 'Prova do Professor', 'codigo' => 'pp3', 'peso' => 1, 'ativo' => true, 'ordem' => 2],
                ],
            ],
        ];
    }
}
