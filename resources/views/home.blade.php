<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lasev - Telechargement de l'application</title>
    <style>
        :root {
            --bg: #f6faf7;
            --card: #ffffff;
            --primary: #265533;
            --secondary: #3e7a52;
            --text: #1e2a22;
            --muted: #5f6f64;
            --border: #d9e7dc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(145deg, #f8fcf9 0%, #eef7f1 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 780px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 14px 42px rgba(38, 85, 51, 0.10);
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(38, 85, 51, 0.10);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.2;
            color: var(--primary);
        }

        p {
            margin: 0;
            line-height: 1.55;
            color: var(--muted);
        }

        .actions {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: all .2s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: #1f462a;
        }

        .btn-secondary {
            color: var(--primary);
            background: #fff;
        }

        .btn-secondary:hover {
            background: #f2f8f4;
        }

        .note {
            margin-top: 18px;
            font-size: 13px;
        }

        .help {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px dashed var(--border);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">Lasev</span>
        <h1>Telechargez l'application Lasev</h1>
        <p>
            Bienvenue sur la page officielle de telechargement.
            Choisissez votre plateforme pour installer l'application et commencer votre parcours bien-etre.
        </p>

        <section class="actions" aria-label="Liens de telechargement">
            <a class="btn btn-primary" href="https://lasev.online" target="_blank" rel="noopener noreferrer">
                Ouvrir la version web
            </a>
            <a class="btn btn-secondary" href="https://lasev.online" target="_blank" rel="noopener noreferrer">
                Telecharger Android (APK)
            </a>
            <a class="btn btn-secondary" href="https://lasev.online" target="_blank" rel="noopener noreferrer">
                Telecharger iOS
            </a>
        </section>

        <p class="note">
            Si un store n'est pas encore disponible, vous serez redirige vers la page officielle `lasev.online`.
        </p>

        <p class="help">
            Besoin d'aide ? Contactez l'equipe Lasev depuis la plateforme officielle.
        </p>
    </main>
</body>
</html>
