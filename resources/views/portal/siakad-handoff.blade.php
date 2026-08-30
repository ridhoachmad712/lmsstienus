<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Membuka SIAKAD</title>
</head>
<body>
    <main style="font-family:system-ui,sans-serif;max-width:36rem;margin:12vh auto;padding:2rem;text-align:center">
        <h1>Membuka SIAKAD…</h1>
        <p>Tiket masuk aman sedang diteruskan. Jika tidak berpindah otomatis, tekan tombol berikut.</p>
        <form id="siakad-handoff" method="post" action="{{ $ssoUrl }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="signature" value="{{ $signature }}">
            <button type="submit">Lanjut ke SIAKAD</button>
        </form>
    </main>
    <script>document.getElementById('siakad-handoff').submit();</script>
</body>
</html>
