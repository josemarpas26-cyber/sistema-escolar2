<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormulaAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'formulas_avaliacao';

    protected $fillable = [
        'ano_letivo_id',
        'nome',
        'componentes',
        'regras',
        'ativa',
    ];

    protected $casts = [
        'componentes' => 'array',
        'regras' => 'array',
        'ativa' => 'boolean',
    ];

    public function anoLetivo()
    {
        return $this->belongsTo(AnoLetivo::class);
    }

    public function versoes()
    {
        return $this->hasMany(FormulaAvaliacaoVersao::class)->orderByDesc('versao');
    }

    public function avaliacoes()
    {
        return $this->hasMany(AvaliacaoDinamica::class);
    }

    public function proximaVersao(): int
    {
        return ((int) $this->versoes()->max('versao')) + 1;
    }

    public function somaPesosComponentes(): float
    {
        return collect($this->componentes ?? [])->sum(fn ($item) => (float) ($item['peso'] ?? 0));
    }
}
