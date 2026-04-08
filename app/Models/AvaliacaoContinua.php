<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoContinua extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes_continuas';

    protected $fillable = [
        'nota_id',
        'professor_id',
        'trimestre',
        'descricao',
        'valor',
        'data_avaliacao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_avaliacao' => 'date',
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }
}
