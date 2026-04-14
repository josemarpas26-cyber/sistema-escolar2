<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaFormacao extends Model
{
    use HasFactory;

    protected $table = 'areas_formacao';

    protected $fillable = [
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'area_formacao_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
