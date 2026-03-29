<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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


    public function metasDisciplina()
    {
        return $this->hasMany(MetaDisciplina::class, 'ano_letivo_id');
    }

    // Scope para pegar o ano letivo ativo
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true)->where('encerrado', false);
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