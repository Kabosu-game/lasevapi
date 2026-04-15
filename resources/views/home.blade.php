<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lasev | Telechargement officiel</title>
    <meta name="description" content="Installez Lasev sur Android, iOS et Web. Meditations, bien-etre, serenite et routines quotidiennes.">
    <style>
        :root {
            --bg: #f2f7f4;
            --card: #ffffff;
            --primary: #265533;
            --primary-dark: #1f462b;
            --accent: #d8eadc;
            --text: #1d2a22;
            --muted: #5a6d61;
            --border: #d8e4dc;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 10% 10%, #eaf4ed 0, transparent 40%),
                radial-gradient(circle at 90% 20%, #e3efe7 0, transparent 35%),
                var(--bg);
        }

        .wrapper {
            max-width: 1080px;
            margin: 0 auto;
            padding: 24px;
        }

        .hero {
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--card);
            box-shadow: 0 16px 44px rgba(38, 85, 51, 0.10);
            padding: 36px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eef7f1;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        h1 {
            margin: 14px 0 10px;
            font-size: 42px;
            line-height: 1.1;
            color: var(--primary);
            max-width: 760px;
        }

        .subtitle {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.6;
        }

        .cta-grid {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            border: 1px solid var(--primary);
            transition: all .2s ease;
        }

        .btn-primary {
            color: #fff;
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            color: var(--primary);
            background: #fff;
        }

        .btn-secondary:hover {
            background: #f4faf6;
        }

        .cards {
            margin-top: 22px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            background: #fbfdfb;
        }

        .card h2 {
            margin: 0 0 8px;
            color: var(--primary);
            font-size: 18px;
        }

        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .links {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px dashed var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .notice {
            margin-top: 10px;
            color: #5b6b60;
            font-size: 13px;
        }

        @media (max-width: 760px) {
            .hero { padding: 24px; }
            h1 { font-size: 32px; }
            .subtitle { font-size: 15px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <main class="hero">
        <span class="brand">Lasev - officiel</span>
        <h1>Telechargez Lasev et commencez votre routine bien-etre</h1>
        <p class="subtitle">
            Accedez a vos affirmations, meditations, contenus et rappels quotidiens sur mobile ou web.
            Cette page est l'interface officielle de telechargement de l'application.
        </p>

        <section class="cta-grid" aria-label="Actions de telechargement">
            <a class="btn btn-primary" href="https://lasev.online" target="_blank" rel="noopener noreferrer">
                Ouvrir Lasev Web
            </a>
            <a class="btn btn-secondary" href="https://lasev.online" target="_blank" rel="noopener noreferrer">
                Telecharger Android (APK)
            </a>
            <a class="btn btn-secondary" href="https://lasev.online" target="_blank" rel="noopener noreferrer">
                Telecharger iOS
            </a>
        </section>

        <section class="cards" aria-label="Points forts Lasev">
            <article class="card">
                <h2>Bien-etre quotidien</h2>
                <p>Suivez une routine simple avec des rappels et contenus inspires chaque jour.</p>
            </article>
            <article class="card">
                <h2>Experience fluide</h2>
                <p>Synchronisation mobile et web pour continuer vos pratiques sans interruption.</p>
            </article>
            <article class="card">
                <h2>Contenu evolutif</h2>
                <p>Nouveaux contenus publies regulierement pour enrichir votre parcours personnel.</p>
            </article>
        </section>

        <div class="links">
            <a href="/politique-confidentialite.html">Politique de confidentialite</a>
            <a href="https://lasev.online" target="_blank" rel="noopener noreferrer">Support Lasev</a>
        </div>
        <p class="notice">
            Si un store n'est pas encore disponible, les boutons renvoient vers la plateforme officielle.
        </p>
    </main>
</div>
</body>
</html>
