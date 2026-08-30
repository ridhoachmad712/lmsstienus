<?php

/**
 * Lapisan pengaman kompatibilitas untuk SIAKAD lama.
 *
 * File halaman lama masih memuat query dan tampilan dalam satu berkas. Lapisan
 * ini memberi satu gerbang autentikasi/role, CSRF, validasi upload, header
 * keamanan, dan migrasi password bertahap tanpa mengubah skema akademik.
 */
function siakad_security_fail($message, $status = 403)
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Akses ditolak</title><style>body{font-family:system-ui,sans-serif;background:#f4f6fa;margin:0;padding:2rem;color:#182433}.box{max-width:560px;margin:10vh auto;background:#fff;padding:2rem;border-radius:14px;box-shadow:0 12px 36px #1f29371a}a{color:#206bc4}</style></head><body>';
    echo '<main class="box"><h1>Permintaan tidak dapat diproses</h1><p>'.$safe.'</p><a href="dashboard">Kembali ke beranda</a></main></body></html>';
    exit;
}

function siakad_is_https()
{
    return (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

function siakad_refresh_session_cookie()
{
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }

    setcookie(session_name(), session_id(), [
        'expires' => 0,
        'path' => '/',
        'secure' => siakad_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function siakad_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (headers_sent()) {
        siakad_security_fail('Sesi tidak dapat dimulai. Periksa konfigurasi output buffering PHP.', 500);
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => siakad_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function siakad_csrf_token()
{
    if (empty($_SESSION['_siakad_csrf'])) {
        $_SESSION['_siakad_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_siakad_csrf'];
}

function siakad_validate_csrf()
{
    $provided = isset($_POST['_siakad_csrf']) ? (string) $_POST['_siakad_csrf'] : '';
    if ($provided === '' && isset($_GET['_siakad_csrf'])) {
        $provided = (string) $_GET['_siakad_csrf'];
    }

    if ($provided === '' || ! hash_equals(siakad_csrf_token(), $provided)) {
        siakad_security_fail('Sesi formulir sudah tidak berlaku. Muat ulang halaman lalu coba kembali.', 419);
    }
}

function siakad_secure_output($html)
{
    $token = htmlspecialchars(siakad_csrf_token(), ENT_QUOTES, 'UTF-8');

    // Semua form POST lama otomatis memperoleh token tanpa menyalin perubahan
    // yang sama ke puluhan template.
    $html = preg_replace_callback(
        '/<form\b([^>]*\bmethod\s*=\s*(["\']?)post\2[^>]*)>/i',
        function ($match) use ($token) {
            return '<form'.$match[1].'><input type="hidden" name="_siakad_csrf" value="'.$token.'">';
        },
        $html
    );

    // Tautan aksi lama tetap kompatibel, tetapi kini hanya dapat dijalankan
    // dari halaman yang memegang token session sah.
    $html = preg_replace_callback(
        '/href\s*=\s*(["\'])([^"\']*[?&](?:amp;)?aksi=[^"\']*)\1/i',
        function ($match) use ($token) {
            $url = $match[2];
            if (strpos($url, '_siakad_csrf=') === false) {
                $separator = strpos($url, '?') === false ? '?' : '&amp;';
                $url .= $separator.'_siakad_csrf='.$token;
            }

            return 'href='.$match[1].$url.$match[1];
        },
        $html
    );

    return $html;
}

function siakad_validate_identifier_inputs()
{
    foreach ($_GET as $key => $value) {
        if ($key === '_siakad_csrf') {
            continue;
        }
        if (is_array($value)) {
            siakad_security_fail('Parameter URL tidak valid.', 422);
        }
        if (preg_match('/^(?:id(?:_|$)|kode_|nim|nip|username|qwe|aksi|angkatan|semester|thn)/i', (string) $key)
            && ! preg_match('/^[A-Za-z0-9._:\/-]{0,100}$/', (string) $value)) {
            siakad_security_fail('Format parameter URL tidak valid.', 422);
        }
    }
}

function siakad_validate_uploads()
{
    if (empty($_FILES)) {
        return;
    }

    $imageFields = ['foto', 'logo'];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $sheetExtensions = ['xls', 'xlsx'];
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

    foreach ($_FILES as $field => &$upload) {
        if (! is_array($upload) || is_array(isset($upload['name']) ? $upload['name'] : null)) {
            siakad_security_fail('Format upload tidak didukung.', 422);
        }
        if ((int) $upload['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ((int) $upload['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($upload['tmp_name'])) {
            siakad_security_fail('Berkas gagal diunggah.', 422);
        }
        if ((int) $upload['size'] > 10 * 1024 * 1024) {
            siakad_security_fail('Ukuran berkas maksimal 10 MB.', 422);
        }

        $extension = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));
        $isImage = in_array($field, $imageFields, true) || strpos($field, 'foto') !== false;
        $allowed = $isImage ? $imageExtensions : $sheetExtensions;
        if (! in_array($extension, $allowed, true)) {
            siakad_security_fail('Jenis berkas tidak diizinkan.', 422);
        }

        $mime = $finfo ? finfo_file($finfo, $upload['tmp_name']) : '';
        $allowedMimes = $isImage
            ? ['image/jpeg', 'image/png', 'image/webp']
            : ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream', 'application/x-ole-storage'];
        if ($mime && ! in_array($mime, $allowedMimes, true)) {
            siakad_security_fail('Isi berkas tidak sesuai dengan jenis yang diizinkan.', 422);
        }

        // Kode lama menggunakan nama ini sebagai tujuan move_uploaded_file.
        // Ganti dengan nama acak untuk mencegah overwrite/path abuse.
        $upload['name'] = 'upload_'.bin2hex(random_bytes(12)).'.'.$extension;
    }
    unset($upload);
    if ($finfo) {
        finfo_close($finfo);
    }
}

function siakad_allowed_roles_for_script($script)
{
    $admin = [
        'fakultas', 'jurusan', 'dosen', 'mhs', 'mata_kuliah', 'thn_akademik',
        'grade', 'akun_admin', 'akun_jurusan', 'akun_dosen', 'akun_mhs',
        'pengaturan', 'fak-has-jur',
        'ruangan', 'search_mhs', 'search_dosen',
        'search_fakultas', 'search_jurusan', 'search_matkul', 'search_fak_has_jur',
        'detail_mhs',
    ];
    $prodi = [
        'jurusan_has_matkul', 'jurusan_has_dosen', 'jurusan_has_mhs',
        'rekap_jadwal', 'buat_jadwal', 'ambil_jadwal', 'sks_mhs', 'krs_mhs',
        'khs_mhs', 'input_nilai', 'transkip_mhs', 'add_matkul_jurusan',
        'add_dosen_jurusan', 'add_mhs_jurusan', 'get_add_matkul',
        'get_add_dosen', 'get_add_mhs',
    ];
    $dosen = [
        'dosen_has_mhs', 'jadwal_mengajar', 'input_nilai_dosen', 'get_absen',
        'get_input_nilai', 'get_daftar_mahasiswa', 'mhs_krs', 'mhs_khs', 'jadwal_mengajar',
    ];
    $mahasiswa = ['jadwal_kuliah', 'krs', 'khs', 'transkip', 'tambah_krs'];

    $normalizedPath = str_replace('\\', '/', strtolower(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : ''));
    if (strpos($normalizedPath, '/pages/cetak/') !== false) {
        return ['mhs', 'Jurusan/Prodi', 'admin'];
    }

    if (in_array($script, ['dashboard', 'logout'], true)) {
        return ['admin', 'Jurusan/Prodi', 'dosen', 'mhs'];
    }
    if (in_array($script, ['mhs_krs', 'mhs_khs'], true)) {
        return ['dosen', 'admin'];
    }
    if (in_array($script, $admin, true)) {
        return ['admin'];
    }
    if (in_array($script, $prodi, true)) {
        return ['Jurusan/Prodi', 'admin'];
    }
    if (in_array($script, $dosen, true)) {
        return ['dosen', 'Jurusan/Prodi', 'admin'];
    }
    if (in_array($script, $mahasiswa, true)) {
        return ['mhs'];
    }

    // Fail closed: berkas baru tidak dapat diakses sebelum perannya ditetapkan.
    return [];
}

function siakad_scoped_student_username($koneksi, $requested)
{
    $level = isset($_SESSION['level']) ? $_SESSION['level'] : '';
    if ($level === 'mhs') {
        return (string) $_SESSION['username'];
    }

    $requested = trim((string) $requested);
    if ($requested === '') {
        siakad_security_fail('NIM mahasiswa tidak valid.', 422);
    }
    if ($level === 'admin') {
        return $requested;
    }
    if ($level === 'Jurusan/Prodi') {
        $prodi = (string) $_SESSION['kode_prodi'];
        $stmt = mysqli_prepare($koneksi, 'SELECT 1 FROM prodi_has_mhs WHERE nim_npm = ? AND kode_prodi = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $requested, $prodi);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $allowed = mysqli_stmt_num_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        if ($allowed) {
            return $requested;
        }
    }

    siakad_security_fail('Mahasiswa berada di luar lingkup akun Anda.', 403);
}

function siakad_scoped_advisee_username($koneksi, $requested)
{
    if ($_SESSION['level'] === 'admin') {
        return trim((string) $requested);
    }
    $lecturer = (string) $_SESSION['username'];
    $requested = trim((string) $requested);
    $stmt = mysqli_prepare($koneksi, 'SELECT 1 FROM mhs_has_pa WHERE nim_npm = ? AND nip = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ss', $requested, $lecturer);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $allowed = mysqli_stmt_num_rows($stmt) === 1;
    mysqli_stmt_close($stmt);
    if (! $allowed) {
        siakad_security_fail('Mahasiswa bukan bagian dari perwalian Anda.', 403);
    }

    return $requested;
}

function siakad_authenticate_session($koneksi)
{
    if (empty($_SESSION['login']) || empty($_SESSION['username']) || empty($_SESSION['level'])) {
        return false;
    }

    $stmt = mysqli_prepare($koneksi, 'SELECT username, password, level, kode_prodi FROM user WHERE username = ? AND level = ? LIMIT 1');
    if (! $stmt) {
        siakad_security_fail('SIAKAD tidak dapat memeriksa sesi saat ini.', 500);
    }
    $username = (string) $_SESSION['username'];
    $level = (string) $_SESSION['level'];
    mysqli_stmt_bind_param($stmt, 'ss', $username, $level);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $dbUsername, $dbPassword, $dbLevel, $dbKodeProdi);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (! $found) {
        return false;
    }

    $_SESSION['username'] = $dbUsername;
    $_SESSION['password'] = $dbPassword; // kompatibilitas sementara halaman lama
    $_SESSION['level'] = $dbLevel;
    $_SESSION['kode_prodi'] = $dbKodeProdi;

    return true;
}

function siakad_verify_password($plain, $stored)
{
    $info = password_get_info((string) $stored);
    if (! empty($info['algo'])) {
        return password_verify((string) $plain, (string) $stored);
    }
    if (preg_match('/^[a-f0-9]{32}$/i', (string) $stored)) {
        return hash_equals(strtolower((string) $stored), md5((string) $plain));
    }

    return hash_equals((string) $stored, (string) $plain);
}

function siakad_password_hashing_ready($koneksi)
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $result = mysqli_query($koneksi, "SHOW COLUMNS FROM user LIKE 'password'");
    $column = $result ? mysqli_fetch_assoc($result) : null;
    $type = strtolower((string) ($column['Type'] ?? ''));
    $ready = preg_match('/(?:var)?char\((\d+)\)/', $type, $matches)
        ? (int) $matches[1] >= 60
        : str_contains($type, 'text');

    return $ready;
}

function siakad_hash_password($koneksi, $plain)
{
    if (! siakad_password_hashing_ready($koneksi)) {
        siakad_security_fail('Kolom password database harus diperbesar menjadi VARCHAR(255) sebelum membuat atau mengganti kata sandi.', 503);
    }

    return password_hash((string) $plain, PASSWORD_DEFAULT);
}

function siakad_upgrade_password($koneksi, $username, $level, $plain, $stored)
{
    if (! siakad_verify_password($plain, $stored)) {
        return false;
    }
    if (password_needs_rehash((string) $stored, PASSWORD_DEFAULT) && siakad_password_hashing_ready($koneksi)) {
        $newHash = siakad_hash_password($koneksi, $plain);
        $stmt = mysqli_prepare($koneksi, 'UPDATE user SET password = ? WHERE username = ? AND level = ?');
        mysqli_stmt_bind_param($stmt, 'sss', $newHash, $username, $level);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['password'] = $newHash;
    }

    return true;
}

function siakad_delete_own_krs($koneksi, $username, $kodeProdi, $idKrs)
{
    $idKrs = (int) $idKrs;
    $stmt = mysqli_prepare($koneksi, 'SELECT id_jadwal, id_thn_akademik, kode_prodi FROM krs_mhs WHERE id_krs = ? AND nim_npm = ? AND kode_prodi = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'iss', $idKrs, $username, $kodeProdi);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $idJadwal, $idTahun, $rowProdi);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    if (! $found) {
        siakad_security_fail('KRS tidak ditemukan atau bukan milik akun Anda.', 404);
    }

    mysqli_begin_transaction($koneksi);
    try {
        $deleteKhs = mysqli_prepare($koneksi, 'DELETE FROM khs_mhs WHERE kode_prodi = ? AND nim_npm = ? AND id_jadwal = ? AND id_thn_akademik = ?');
        mysqli_stmt_bind_param($deleteKhs, 'ssii', $rowProdi, $username, $idJadwal, $idTahun);
        mysqli_stmt_execute($deleteKhs);
        mysqli_stmt_close($deleteKhs);

        $deleteKrs = mysqli_prepare($koneksi, 'DELETE FROM krs_mhs WHERE id_krs = ? AND nim_npm = ? AND kode_prodi = ?');
        mysqli_stmt_bind_param($deleteKrs, 'iss', $idKrs, $username, $rowProdi);
        mysqli_stmt_execute($deleteKrs);
        mysqli_stmt_close($deleteKrs);
        mysqli_commit($koneksi);
    } catch (Throwable $exception) {
        mysqli_rollback($koneksi);
        error_log('Gagal menghapus KRS: '.$exception->getMessage());
        siakad_security_fail('KRS tidak dapat dihapus saat ini.', 500);
    }

    return (int) $idTahun;
}

function siakad_login_rate_limit($success = false)
{
    $ip = isset($_SERVER['HTTP_CF_CONNECTING_IP'])
        ? (string) $_SERVER['HTTP_CF_CONNECTING_IP']
        : (isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown');
    $key = hash('sha256', $ip);
    $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'siakad_login_'.$key.'.json';
    $now = time();

    if ($success) {
        if (is_file($path)) {
            @unlink($path);
        }

        return;
    }

    $attempts = [];
    if (is_file($path)) {
        $decoded = json_decode((string) @file_get_contents($path), true);
        if (is_array($decoded)) {
            $attempts = $decoded;
        }
    }
    $attempts = array_values(array_filter($attempts, function ($timestamp) use ($now) {
        return is_numeric($timestamp) && (int) $timestamp > $now - 60;
    }));
    if (count($attempts) >= 6) {
        siakad_security_fail('Terlalu banyak percobaan login. Tunggu satu menit lalu coba kembali.', 429);
    }
    $attempts[] = $now;
    @file_put_contents($path, json_encode($attempts), LOCK_EX);
}

function siakad_security_bootstrap($koneksi)
{
    siakad_start_session();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    siakad_refresh_session_cookie();

    $script = strtolower(pathinfo(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '', PATHINFO_FILENAME));
    $publicScripts = ['index', 'login', 'login3', 'sso'];
    $isPublic = in_array($script, $publicScripts, true);

    if (! $isPublic) {
        if (! siakad_authenticate_session($koneksi)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
            }
            header('Location: login');
            exit;
        }

        $allowedRoles = siakad_allowed_roles_for_script($script);
        if ($allowedRoles !== null && ! in_array($_SESSION['level'], $allowedRoles, true)) {
            siakad_security_fail('Akun Anda tidak memiliki wewenang untuk membuka halaman ini.', 403);
        }

        siakad_validate_identifier_inputs();
        // Endpoint pencarian lama memakai POST hanya untuk membaca. Mutasi tetap
        // selalu membutuhkan token CSRF.
        $readOnlyPostScripts = [
            'search_mhs', 'search_dosen', 'search_fakultas', 'search_jurusan',
            'search_matkul', 'search_fak_has_jur', 'get_add_matkul',
            'get_add_dosen', 'get_add_mhs',
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! in_array($script, $readOnlyPostScripts, true)) {
            siakad_validate_csrf();
            siakad_validate_uploads();
        } elseif (isset($_GET['aksi'])) {
            siakad_validate_csrf();
        }

        ob_start('siakad_secure_output');
    }
}
