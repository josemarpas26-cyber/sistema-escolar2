<?php

namespace App\Notifications;

use App\Models\Disciplina;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PautaFinalizadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Turma $turma,
        private readonly Disciplina $disciplina,
        private readonly User $autor,
        private readonly ?string $trimestre,
        private readonly ?string $campo,
        private readonly ?string $motivo
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $campoLabel = $this->campo ? strtoupper($this->campo) : null;
        $titulo = $this->campo && $this->trimestre
            ? "{$campoLabel} do {$this->trimestre}Âº trimestre bloqueado"
            : ($this->trimestre
                ? "{$this->trimestre}Âº trimestre finalizado"
                : 'Pauta finalizada');

        $descricao = $this->campo && $this->trimestre
            ? "A secretaria bloqueou o campo {$campoLabel} do {$this->trimestre}Âº trimestre."
            : ($this->trimestre
                ? "A secretaria finalizou e bloqueou o {$this->trimestre}Âº trimestre da pauta."
                : 'A secretaria finalizou a pauta e bloqueou novas ediÃ§Ãµes.');

        return [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'turma_id' => $this->turma->id,
            'turma_nome' => $this->turma->nome,
            'disciplina_id' => $this->disciplina->id,
            'disciplina_nome' => $this->disciplina->nome,
            'trimestre' => $this->trimestre,
            'campo' => $this->campo,
            'motivo' => $this->motivo,
            'autor_nome' => $this->autor->name,
            'link' => route('notas.professor-index', [
                'turma_id' => $this->turma->id,
                'disciplina_id' => $this->disciplina->id,
            ]),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
