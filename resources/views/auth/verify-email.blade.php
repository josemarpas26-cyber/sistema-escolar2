<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        A sua conta foi criada internamente, mas o email ainda precisa de ser verificado.
        Use o link enviado para a sua caixa de entrada antes de continuar.
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-green-600">
            Enviamos um novo link de verificacao para o email associado a esta conta.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                Reenviar Link de Verificacao
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Terminar Sessao
            </button>
        </form>
    </div>
</x-guest-layout>
