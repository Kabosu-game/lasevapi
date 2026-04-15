<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Tools - Lasev</title>
    <style>
        :root {
            --bg: #f5f8f6;
            --card: #fff;
            --primary: #265533;
            --danger: #b42318;
            --text: #1f2a22;
            --muted: #5d6d62;
            --border: #d8e4dc;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 24px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 28px rgba(38, 85, 51, 0.08);
        }

        h1 {
            margin-top: 0;
            color: var(--primary);
        }

        p {
            color: var(--muted);
            line-height: 1.5;
        }

        .panel {
            margin-top: 16px;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fafcfb;
        }

        .success {
            border-color: #7ecf9c;
            background: #effaf3;
            color: #14532d;
        }

        .error {
            border-color: #f3b2b2;
            background: #fff4f4;
            color: #7a1919;
        }

        .btn {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:hover {
            background: #1d4428;
        }

        code {
            background: #f0f4f1;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 2px 6px;
        }

        pre {
            margin-top: 10px;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #f8fbf9;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .warning {
            margin-top: 20px;
            color: var(--danger);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <main class="container">
        <h1>Outils cache (sans cle)</h1>
        <p>
            Cette page permet de vider les caches Laravel avec la commande
            <code>optimize:clear</code>.
        </p>

        <form method="POST" action="{{ route('cache.tools.clear') }}">
            @csrf
            <button class="btn" type="submit">Vider les caches maintenant</button>
        </form>

        @if(session('success'))
            <div class="panel success">
                <strong>{{ session('success') }}</strong>
                @if(session('executed'))
                    <p>Commandes executees: {{ implode(', ', session('executed')) }}</p>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div class="panel error">
                <strong>{{ session('error') }}</strong>
            </div>
        @endif

        @if(session('log'))
            <pre>{{ json_encode(session('log'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif

        <p class="warning">
            Attention: cet acces est public. Il est recommande de limiter cette URL par IP
            ou via une protection serveur (Basic Auth / pare-feu).
        </p>
    </main>
</body>
</html>
