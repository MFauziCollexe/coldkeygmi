<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 | Server Sedang Perbaikan</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        :root {
            --bg: #09111f;
            --bg-soft: #13233c;
            --panel: rgba(9, 17, 31, 0.78);
            --line: rgba(148, 163, 184, 0.18);
            --text: #e5eefb;
            --muted: #9ab0c9;
            --accent: #f59e0b;
            --accent-soft: rgba(245, 158, 11, 0.18);
            --ok: #7dd3fc;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(14, 165, 233, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.2), transparent 28%),
                linear-gradient(145deg, #08101d, #10203a 58%, #08101d);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 36px 36px;
            pointer-events: none;
        }
        .card {
            width: min(920px, 100%);
            border: 1px solid var(--line);
            background: var(--panel);
            backdrop-filter: blur(14px);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }
        .content {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
        }
        .copy, .side { padding: 38px; }
        .side {
            border-left: 1px solid var(--line);
            background:
                linear-gradient(180deg, rgba(245, 158, 11, 0.12), rgba(14, 165, 233, 0.08)),
                rgba(255, 255, 255, 0.02);
        }
        .brand {
            display: inline-flex;
            gap: 14px;
            align-items: center;
            margin-bottom: 24px;
        }
        .brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
        }
        .brand strong { display: block; font-size: 18px; }
        .brand span { display: block; margin-top: 4px; color: var(--muted); font-size: 13px; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(15, 23, 42, 0.56);
            color: var(--muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .eyebrow::before {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.14);
        }
        h1 {
            margin: 20px 0 0;
            font-size: clamp(32px, 5vw, 56px);
            line-height: 1;
            letter-spacing: -0.04em;
            max-width: 9ch;
        }
        p {
            margin: 18px 0 0;
            color: var(--muted);
            line-height: 1.75;
            font-size: 17px;
            max-width: 58ch;
        }
        .panel {
            padding: 20px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(2, 6, 23, 0.42);
        }
        .panel h2 {
            margin: 0 0 12px;
            font-size: 18px;
        }
        .panel ul {
            margin: 0;
            padding-left: 18px;
            color: var(--muted);
            line-height: 1.75;
            font-size: 14px;
        }
        .footer {
            padding: 18px 38px 26px;
            color: var(--muted);
            border-top: 1px solid var(--line);
            font-size: 13px;
        }
        .footer strong { color: var(--ok); }
        @media (max-width: 820px) {
            .content { grid-template-columns: 1fr; }
            .side { border-left: 0; border-top: 1px solid var(--line); }
        }
        @media (max-width: 640px) {
            .copy, .side, .footer { padding-left: 26px; padding-right: 26px; }
        }
    </style>
</head>
<body>
    <section class="card">
        <div class="content">
            <div class="copy">
                <div class="brand">
                    <img src="/image/logo-gmi-clean.png" alt="GMI Logo">
                    <div>
                        <strong>Golden Multi Indotama</strong>
                        <span>Layanan sementara tidak tersedia</span>
                    </div>
                </div>

                <div class="eyebrow">Maintenance Mode</div>
                <h1>Server sedang perbaikan</h1>
                <p>
                    Kami sedang melakukan perbaikan sistem agar layanan dapat kembali berjalan dengan normal.
                    Silakan coba lagi beberapa saat ke depan.
                </p>
            </div>

            <aside class="side">
                <div class="panel">
                    <h2>Informasi</h2>
                    <ul>
                        <li>Layanan sedang dalam proses pemeliharaan atau pemulihan.</li>
                        <li>Silakan refresh halaman beberapa saat lagi.</li>
                        <li>Hubungi admin IT bila kendala berlangsung lebih lama.</li>
                    </ul>
                </div>
            </aside>
        </div>
        <div class="footer">
            <strong>503 Service Unavailable</strong> · halaman ini tampil saat aplikasi berada dalam maintenance mode.
        </div>
    </section>
</body>
</html>
