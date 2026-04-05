<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Acesso Negado | SIGA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            text-align: center;
            max-width: 440px;
            width: 100%;
        }
        .icon-wrap {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #fef2f2;
            border: 8px solid #fee2e2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
        }
        .icon-wrap svg {
            width: 36px;
            height: 36px;
            color: #dc2626;
        }
        .code {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #dc2626;
            background: #fee2e2;
            padding: 4px 12px;
            border-radius: 9999px;
            display: inline-block;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.5px;
            margin-bottom: 12px;
        }
        p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.65;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 44px;
            padding: 0 24px;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M7 10V7a5 5 0 1 1 10 0v3"/>
                <rect x="5" y="10" width="14" height="11" rx="2"/>
                <path d="M12 15v3"/>
            </svg>
        </div>
        <div class="code">Erro 403</div>
        <h1>Acesso Negado</h1>
        <p>Não tem permissão para aceder a este conteúdo.<br>Contacte o administrador se acredita que isto é um erro.</p>
        <a href="{{ url('/dashboard') }}" class="btn">
            ← Voltar ao Dashboard
        </a>
    </div>
</body>
</html>