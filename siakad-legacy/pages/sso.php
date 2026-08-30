<?php

/**
 * Penerima tiket login dari portal LMS.
 * Pasang SIAKAD_SSO_SECRET di environment hosting dengan nilai yang sama seperti
 * LEGACY_SIAKAD_SSO_SECRET pada LMS. File ini tidak menyimpan secret di source code.
 */
session_start();
include '../config/koneksi.php';

function sso_fail($message, $status = 403)
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Login SIAKAD</title><style>body{font-family:system-ui,sans-serif;background:#f4f6fa;margin:0;padding:2rem;color:#182433}.box{max-width:520px;margin:10vh auto;background:#fff;padding:2rem;border-radius:14px;box-shadow:0 12px 36px #1f29371a}a{color:#206bc4}</style></head><body>';
    echo '<main class="box"><h1>Login SIAKAD gagal</h1><p>'.$safe.'</p><a href="login">Masuk secara manual</a></main></body></html>';
    exit;
}

function base64url_decode_strict($value)
{
    if (! is_string($value) || ! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
        return false;
    }

    $padding = strlen($value) % 4;
    if ($padding) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

$secret = siakad_config('SIAKAD_SSO_SECRET', 'sso_secret');
if (! $secret) {
    sso_fail('Integrasi login belum dikonfigurasi pada server SIAKAD.', 503);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    sso_fail('Tiket login hanya dapat dikirim langsung dari portal.', 405);
}

$token = isset($_POST['token']) ? $_POST['token'] : '';
$signature = isset($_POST['signature']) ? $_POST['signature'] : '';
$providedSignature = base64url_decode_strict($signature);
$expectedSignature = hash_hmac('sha256', $token, $secret, true);

if ($providedSignature === false || ! hash_equals($expectedSignature, $providedSignature)) {
    sso_fail('Tiket login tidak sah atau sudah rusak.');
}

$json = base64url_decode_strict($token);
$payload = $json === false ? null : json_decode($json, true);
$now = time();

if (! is_array($payload)
    || empty($payload['sub'])
    || empty($payload['role'])
    || empty($payload['nonce'])
    || empty($payload['iss'])
    || empty($payload['aud'])
    || ! isset($payload['iat'], $payload['exp'])
    || (int) $payload['iat'] > $now + 30
    || (int) $payload['exp'] < $now
    || (int) $payload['exp'] - (int) $payload['iat'] > 120) {
    sso_fail('Tiket login tidak lengkap atau sudah kedaluwarsa. Silakan buka kembali dari portal.');
}

$expectedIssuer = rtrim((string) siakad_config('SIAKAD_SSO_ISSUER', 'sso_issuer'), '/');
$expectedAudience = rtrim((string) siakad_config('SIAKAD_PUBLIC_URL', 'public_url'), '/');
if ($expectedIssuer === '' || $expectedAudience === '') {
    sso_fail('Issuer dan alamat publik SIAKAD belum dikonfigurasi.', 503);
}
if (($expectedIssuer !== '' && ! hash_equals($expectedIssuer, rtrim((string) $payload['iss'], '/')))
    || ($expectedAudience !== '' && ! hash_equals($expectedAudience, rtrim((string) $payload['aud'], '/')))) {
    sso_fail('Tiket login ditujukan untuk aplikasi yang berbeda.');
}

// Nonce hanya dapat dipakai sekali. Pembuatan file eksklusif bersifat atomik,
// sehingga dua request paralel tidak bisa memakai tiket yang sama.
$nonce = (string) $payload['nonce'];
if (! preg_match('/^[a-f0-9]{32}$/', $nonce)) {
    sso_fail('Identitas tiket login tidak valid.');
}
$nonceDirectory = dirname(__DIR__).'/storage/sso-nonces';
if (! is_dir($nonceDirectory) && ! @mkdir($nonceDirectory, 0700, true) && ! is_dir($nonceDirectory)) {
    sso_fail('Penyimpanan tiket SIAKAD belum siap.', 503);
}
foreach ((array) glob($nonceDirectory.'/*.used') as $usedFile) {
    if (@filemtime($usedFile) < $now - 300) {
        @unlink($usedFile);
    }
}
$nonceFile = $nonceDirectory.'/'.$nonce.'.used';
$nonceHandle = @fopen($nonceFile, 'x');
if ($nonceHandle === false) {
    sso_fail('Tiket login sudah pernah digunakan. Buka kembali dari portal.');
}
fwrite($nonceHandle, (string) $now);
fclose($nonceHandle);

$allowedLevels = ['admin', 'Jurusan/Prodi', 'dosen', 'mhs'];
if (! in_array($payload['role'], $allowedLevels, true)) {
    sso_fail('Hak akses pengguna tidak dikenali.');
}

$username = (string) $payload['sub'];
$level = (string) $payload['role'];
$stmt = mysqli_prepare($koneksi, 'SELECT username, password, level, kode_prodi FROM user WHERE username = ? AND level = ? LIMIT 1');
if (! $stmt) {
    sso_fail('SIAKAD tidak dapat memproses login saat ini.', 500);
}

mysqli_stmt_bind_param($stmt, 'ss', $username, $level);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $dbUsername, $dbPassword, $dbLevel, $dbKodeProdi);
$user = mysqli_stmt_fetch($stmt) ? [
    'username' => $dbUsername,
    'password' => $dbPassword,
    'level' => $dbLevel,
    'kode_prodi' => $dbKodeProdi,
] : null;
mysqli_stmt_close($stmt);

if (! $user) {
    sso_fail('Akun yang sama belum ditemukan di SIAKAD. Pastikan NIM/NIP pada portal sesuai dengan username SIAKAD.');
}

session_regenerate_id(true);
$_SESSION['password'] = $user['password'];
$_SESSION['username'] = $user['username'];
$_SESSION['level'] = $user['level'];
$_SESSION['kode_prodi'] = $user['kode_prodi'];
$_SESSION['login'] = true;
siakad_refresh_session_cookie();

header('Location: dashboard');
exit;
