<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'coordenador_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function coordenador()
    {
        return $this->belongsTo(User::class, 'coordenador_id');
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}