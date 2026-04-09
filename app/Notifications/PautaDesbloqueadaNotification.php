<?php

namespace App\Notifications;

use App\Models\Disciplina;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PautaDesbloqueadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Turma $turma,
        private readonly Disciplina $disciplina,
        private readonly User $autor,
        private readonly ?string $trimestre,
        private readonly ?string $motivo
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $titulo = $this->trimestre
            ? "{$this->trimestre}º trimestre desbloqueado"
            : 'Pauta reaberta para edição';

        $descricao = $this->trimestre
            ? "A secretaria desbloqueou o {$this->trimestre}º trimestre da pauta."
            : 'A secretaria reabriu a pauta completa para edição.';

        return [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'turma_id' => $this->turma->id,
            'turma_nome' => $this->turma->nome,
            'disciplina_id' => $this->disciplina->id,
            'disciplina_nome' => $this->disciplina->nome,
            'trimestre' => $this->trimestre,
            'motivo' => $this->motivo,
            'autor_nome' => $this->autor->name,
            'link' => route('notas.professor-index', [
                'turma_id' => $this->turma->id,
                'disciplina_id' => $this->disciplina->id,
            ]),
        ];
    }
}
