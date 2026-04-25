<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AnoLetivo extends Model
{
    use HasFactory;

    protected $table = 'anos_letivos';

    protected $fillable = [
        'nome',
        'data_inicio',
        'data_fim',
        'ativo',
        'encerrado',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'ativo' => 'boolean',
        'encerrado' => 'boolean',
    ];

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    public function atribuicoes()
    {
        return $this->hasMany(ProfessorTurmaDisciplina::class);
    }

    public function configuracaoAvaliacao()
    {
        return $this->hasOne(ConfiguracaoAvaliacao::class);
    }

    // Scope para pegar o ano letivo ativo
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true)->where('encerrado', false);
    }

    public function trimestreNaData(?Carbon $data = null): int
    {
        [$inicio, $fim] = $this->intervaloTrimestres();

        if (! $inicio || ! $fim) {
            return 1;
        }

        $referencia = ($data ?? now())->copy()->startOfDay();

        if ($referencia->lt($inicio)) {
            return 1;
        }

        if ($referencia->gt($fim)) {
            return 3;
        }

        if ($referencia->gte($this->inicioDoTrimestre(3))) {
            return 3;
        }

        if ($referencia->gte($this->inicioDoTrimestre(2))) {
            return 2;
        }

        return 1;
    }

    public function inicioDoTrimestre(int $trimestre): ?Carbon
    {
        [$inicio, $fim] = $this->intervaloTrimestres();

        if (! $inicio || ! $fim) {
            return null;
        }

        if ($trimestre <= 1) {
            return $inicio->copy();
        }

        $trimestre = min(3, $trimestre);
        $duracaoTotal = $inicio->diffInDays($fim) + 1;
        $offset = intdiv(($duracaoTotal * ($trimestre - 1)) + 2, 3);

        return $inicio->copy()->addDays($offset);
    }

    private function intervaloTrimestres(): array
    {
        $inicio = $this->data_inicio?->copy()?->startOfDay();
        $fim = $this->data_fim?->copy()?->startOfDay();

        if (! $inicio || ! $fim || $fim->lte($inicio)) {
            return [null, null];
        }

        return [$inicio, $fim];
    }

    
    public static function encerrarAutomaticamente()
    {
        DB::transaction(function () {
            self::where('encerrado', false)
            ->whereDate('data_fim', '<=', today())
            ->update(['encerrado' => true, 'ativo' => false]);
        });
    }


}
