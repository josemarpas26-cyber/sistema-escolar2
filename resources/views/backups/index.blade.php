@extends('layouts.app')

@section('title', 'Backups')
@section('page-title', 'Backups do Sistema')
@section('page-subtitle', 'Gerar e baixar cópias de segurança da base de dados')

@section('content')
<div class="space-y-6">
    <x-card title="Backup da base de dados" icon="fas fa-database">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-gray-600">
                Apenas administradores podem gerar e descarregar backups.
            </p>

            <form method="POST" action="{{ route('backups.store') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle mr-1"></i> Gerar novo backup
                </button>
            </form>
        </div>
    </x-card>

    <x-card title="Backups disponíveis" icon="fas fa-file-archive">
        @if($files->isEmpty())
            <p class="text-sm text-gray-500">Nenhum backup encontrado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500 uppercase text-xs">
                            <th class="py-3 pr-3">Arquivo</th>
                            <th class="py-3 pr-3">Tamanho</th>
                            <th class="py-3 pr-3">Data</th>
                            <th class="py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                            <tr class="border-b last:border-b-0">
                                <td class="py-3 pr-3 font-medium text-gray-800">{{ $file['name'] }}</td>
                                <td class="py-3 pr-3 text-gray-600">{{ number_format($file['size'] / 1024, 2, ',', '.') }} KB</td>
                                <td class="py-3 pr-3 text-gray-600">{{ \Carbon\Carbon::createFromTimestamp($file['date'])->format('d/m/Y H:i:s') }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('backups.download', $file['name']) }}" class="btn btn-outline text-xs">
                                        <i class="fas fa-download mr-1"></i> Baixar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
@endsection
