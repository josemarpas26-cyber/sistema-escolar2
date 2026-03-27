<?php

namespace App\Policies;

use App\Models\Nota;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Nota $nota): bool
    {
        // Admin pode tudo (opcional, se tiver isso no sistema)
        if ($user->isAdmin()) {
            return true;
        }

        // Professor: verificar atribuição real
        if ($user->isProfessor()) {
            return $user->atribuicoes()
                ->where([
                    'turma_id'       => $nota->turma_id,
                    'disciplina_id'  => $nota->disciplina_id,
                    'ano_letivo_id'  => $nota->ano_letivo_id,
                ])
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Nota $nota): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Nota $nota): bool
    {
        return false;
    }
}
