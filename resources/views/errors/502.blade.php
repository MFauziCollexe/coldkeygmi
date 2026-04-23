<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>502 | Server Sedang Perbaikan</title>
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
        .card {
            width: min(840px, 100%);
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(9, 17, 31, 0.78);
            backdrop-filter: blur(14px);
            border-radius: 28px;
            padding: 38px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
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
        .brand strong { display: block; font-size: 18px; color: #e5eefb; }
        .brand span { display: block; margin-top: 4px; color: #9ab0c9; font-size: 13px; }
        .eyebrow {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.18);
            color: #ffd58a;
            border: 1px solid rgba(245, 158, 11, 0.24);
            font-size: 13px;
            font-weight: 600;
        }
        h1 {
            margin: 18px 0 0;
            font-size: clamp(32px, 5vw, 54px);
            line-height: 1;
            letter-spacing: -0.04em;
            max-width: 10ch;
        }
        p {
            margin: 16px 0 0;
            color: #9ab0c9;
            line-height: 1.75;
            font-size: 17px;
            max-width: 56ch;
        }
        .info {
            margin-top: 24px;
            padding: 18px 20px;
            border-radius: 18px;
            background: rgba(2, 6, 23, 0.42);
            border: 1px solid rgba(148, 163, 184, 0.18);
            color: #9ab0c9;
            font-size: 14px;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <section class="card">
        <div class="brand">
            <img src="/image/logo-gmi-clean.png" alt="GMI Logo">
            <div>
                <strong>Golden Multi Indotama</strong>
                <span>Gateway error / layanan sementara terganggu</span>
            </div>
        </div>

        <div class="eyebrow">502 Bad Gateway</div>
        <h1>Server sedang perbaikan</h1>
        <p>
            Kami sedang melakukan pengecekan koneksi layanan agar sistem dapat kembali diakses dengan normal.
            Silakan coba lagi beberapa saat ke depan.
        </p>

        <div class="info">
            Jika error ini datang dari reverse proxy atau Nginx, gunakan file statis <strong>/maintenance.html</strong>
            di web server agar halaman maintenance tetap tampil walau aplikasi utama sedang tidak merespons.
        </div>
    </section>
</body>
</html>
