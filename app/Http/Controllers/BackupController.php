<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class BackupController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $backupPath = storage_path('app/backups/database');

        $files = collect(File::files($backupPath))
            ->map(function ($file): array {
                return [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'date' => $file->getMTime(),
                ];
            })
            ->sortByDesc('date')
            ->values();

        return view('backups.index', compact('files'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $user = $request->user();

        Log::info('Backup manual iniciado.', [
            'usuario_id' => $user?->id,
            'usuario_nome' => $user?->name,
            'ip' => $request->ip(),
        ]);

        Artisan::call('backup:database');
        $output = trim(Artisan::output());

        if (str_contains($output, 'Falha') || str_contains($output, 'não suportado') || str_contains($output, 'insuficientes')) {
            Log::warning('Backup manual falhou.', [
                'usuario_id' => $user?->id,
                'usuario_nome' => $user?->name,
                'ip' => $request->ip(),
                'output' => $output,
            ]);

            return back()->with('error', 'Não foi possível gerar o backup. Consulte os logs do sistema.');
        }

        preg_match('/Backup (?:SQLite )?criado em:\s*(.+)$/mi', $output, $matches);
        $backupGerado = $matches[1] ?? null;

        Log::info('Backup manual concluído.', [
            'usuario_id' => $user?->id,
            'usuario_nome' => $user?->name,
            'ip' => $request->ip(),
            'arquivo' => $backupGerado,
        ]);

        return back()->with('success', 'Backup gerado com sucesso.');
    }

    public function download(Request $request, string $file): BinaryFileResponse
    {
        $this->authorizeAdmin($request);

        $file     = basename($file);
        $fullPath = storage_path("app/backups/database/{$file}");

        if (! File::exists($fullPath)) {
            abort(404);
        }

        Log::info('Download de backup efetuado.', [
            'usuario_id'   => $request->user()?->id,
            'usuario_nome' => $request->user()?->name,
            'ip'           => $request->ip(),
            'arquivo'      => $file,
        ]);

        return response()->download($fullPath);
    }

    private function authorizeAdmin(Request $request): void
    {
        $this->checkPermission('system.backup');

        if (! $request->user()?->isAdmin()) {
            abort(403, 'Apenas administradores podem gerir backups.');
        }
    }
}
