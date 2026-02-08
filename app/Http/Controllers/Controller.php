<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Retorna sucesso com mensagem
     */
    protected function success(string $message, $data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Retorna erro com mensagem
     */
    protected function error(string $message, $errors = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Verifica permissão
     */
    protected function checkPermission(string $permission): void
    {
        if (!auth()->user()->role->hasPermission($permission)) {
            abort(403, 'Sem permissão para executar esta ação');
        }
    }
}
