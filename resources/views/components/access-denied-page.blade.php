@props([
    'title' => 'Acesso Restrito',
    'message' => 'Sem permissão para executar esta ação. Você não possui os privilégios necessários para visualizar este conteúdo.',
    'buttonLabel' => 'Voltar para o Dashboard',
    'redirectPath' => '/dashboard',
    'errorCode' => 403,
])

<div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-8">
    <div class="relative w-full max-w-xl text-center rounded-2xl bg-gradient-to-b from-rose-50 via-white to-white shadow-xl ring-1 ring-gray-200 p-8 md:p-12">
        <div class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-md ring-4 ring-rose-100">
            <svg class="h-12 w-12 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 10V7a5 5 0 1 1 10 0v3" />
                <rect x="5" y="10" width="14" height="11" rx="2" ry="2" />
                <path d="M12 15v3" />
            </svg>
        </div>

        <p class="text-6xl md:text-7xl font-extrabold tracking-tight text-slate-800">{{ $errorCode }}</p>
        <h1 class="mt-4 text-2xl font-bold text-slate-800">{{ $title }}</h1>
        <p class="mt-3 mx-auto max-w-md text-base leading-relaxed text-slate-500">{{ $message }}</p>

        <a href="{{ url($redirectPath) }}" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <span aria-hidden="true">&larr;</span>
            <span>{{ $buttonLabel }}</span>
        </a>
    </div>
</div>