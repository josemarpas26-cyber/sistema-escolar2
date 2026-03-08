@props([
    'title' => 'Acesso Restrito',
    'message' => 'Sem permissão para executar esta ação. Você não possui os privilégios necessários para visualizar este conteúdo.',
    'buttonLabel' => 'Voltar para o Dashboard',
    'redirectPath' => '/dashboard',
    'errorCode' => 403,
])

<style>
    html, body {
        height: 100%;
        overflow: hidden;
    }

    .access-denied-page {
        height: 100vh;
        overflow: hidden;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .access-denied-page__content { width: 100%; max-width: 720px; text-align: center; }

    .access-denied-page__icon-wrap {
        margin: 0 auto 30px;
        height: 120px;
        width: 120px;
        border-radius: 9999px;
        background: radial-gradient(circle at 50% 35%, #ffffff 0%, #ffffff 62%, #fff5f6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 28px rgba(239, 68, 68, .16), inset 0 0 0 1px rgba(239, 68, 68, .15);
    }

    .access-denied-page__code {
        margin: 0;
        color: #1E293B;
        font-size: 72px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .access-denied-page__divider {
        margin: 18px auto 0;
        height: 1px;
        width: 64px;
        background: rgba(148, 163, 184, .45);
    }

    .access-denied-page__title {
        margin: 18px 0 0;
        color: #0F172A;
        font-size: 32px;
        font-weight: 700;
        line-height: 1.2;
    }

    .access-denied-page__message {
        margin: 22px auto 0;
        max-width: 560px;
        color: #64748B;
        font-size: 24px;
        line-height: 1.45;
    }

    .access-denied-page__button {
        margin-top: 40px;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        border-radius: 12px;
        background: #2563EB;
        padding: 18px 34px;
        color: #ffffff;
        font-size: 22px;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        box-shadow: 0 14px 26px rgba(37, 99, 235, .28);
    }

    .access-denied-page__button:hover { background: #1D4ED8; }

    @media (max-width: 640px) {
        .access-denied-page__title { font-size: 20px; }
        .access-denied-page__message { font-size: 16px; max-width: 400px; }
        .access-denied-page__button { font-size: 16px; padding: 14px 22px; }
    }
</style>

<div class="access-denied-page">
    <div class="access-denied-page__content">
        <div class="access-denied-page__icon-wrap">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 10V7a5 5 0 1 1 10 0v3" />
                <rect x="5" y="10" width="14" height="11" rx="2" ry="2" />
                <path d="M12 15v3" />
            </svg>
        </div>

        <p class="access-denied-page__code">{{ $errorCode }}</p>
        <div class="access-denied-page__divider"></div>
        <h1 class="access-denied-page__title">{{ $title }}</h1>

        <p class="access-denied-page__message">{{ $message }}</p>

        <a href="{{ url($redirectPath) }}" class="access-denied-page__button">
            <span aria-hidden="true">&larr;</span>
            <span>{{ $buttonLabel }}</span>
        </a>
    </div>
</div>