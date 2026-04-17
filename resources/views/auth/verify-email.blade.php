<x-guest-layout>

        <div class="space-y-4 text-center">
        <h1 class="text-2xl font-bold text-gray-900">
            Bem-vindo(a) ao Sistema Escolar do IPIKK
        </h1>

        <p class="text-sm text-gray-600 leading-relaxed">
            A sua conta foi criada com sucesso e já está pronta para uso.
            Desejamos uma excelente experiência na plataforma.
        </p>

        <div class="pt-2">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Entrar no sistema
            </a>
        </div>
    </div>
</x-guest-layout>
