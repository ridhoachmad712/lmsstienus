# Integrasi SIAKAD Lama dan LMS

## Batas tanggung jawab

SIAKAD lama tetap menjadi sumber data resmi untuk KRS, jadwal akademik, nilai,
KHS, transkrip, serta data akademik lain. Laravel digunakan sebagai LMS dan
portal login. Tidak ada migrasi atau penggabungan database pada tahap ini.

## Konfigurasi LMS Laravel

Tambahkan konfigurasi berikut ke `.env` produksi LMS. Gunakan secret acak yang
panjang dan jangan pernah commit nilainya ke Git.

```dotenv
LEGACY_SIAKAD_ENABLED=true
LEGACY_SIAKAD_URL=https://siakad.stienus.ac.id/
LEGACY_SIAKAD_SSO_URL=https://siakad.stienus.ac.id/pages/sso.php
LEGACY_SIAKAD_SSO_SECRET=ganti-dengan-secret-acak-minimal-32-karakter
```

Setelah mengubah `.env`:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Konfigurasi SIAKAD PHP lama

Source runtime SIAKAD lama sudah tersedia pada folder `siakad-legacy` di
repository yang sama. Deploy folder tersebut ke document root SIAKAD yang sudah
ada. Folder itu tidak boleh diletakkan di dalam `public` Laravel karena kedua
aplikasi memiliki entry point dan konfigurasi server yang berbeda.

Git sengaja tidak membawa database dump, kredensial, foto pengguna, log, arsip,
serta spreadsheet impor/ekspor. Saat melakukan `git pull` pada instalasi lama,
data-data yang tidak dilacak Git tersebut tidak akan ditimpa.

Pasang environment variable pada hosting SIAKAD:

```text
SIAKAD_SSO_SECRET = nilai yang sama dengan LEGACY_SIAKAD_SSO_SECRET
LMS_URL = https://lms.stienus.ac.id/portal
SIAKAD_DB_HOST = alamat server database
SIAKAD_DB_PORT = 3306
SIAKAD_DB_NAME = nama database SIAKAD lama
SIAKAD_DB_USER = pengguna database SIAKAD
SIAKAD_DB_PASSWORD = kata sandi database SIAKAD
SIAKAD_TIMEZONE = Asia/Makassar
```

Cara pemasangan environment variable mengikuti panel hosting/PHP-FPM yang
digunakan. Jangan menaruh secret langsung di file PHP atau repositori publik.
Contoh lengkap tersedia pada `siakad-legacy/.env.example`; file tersebut hanya
dokumentasi dan tidak dibaca otomatis oleh PHP lama.

Jika panel hosting tidak menyediakan environment variable, salin
`siakad-legacy/config/local.example.php` menjadi
`siakad-legacy/config/local.php`, isi seluruh nilai, dan jangan paksa file lokal
tersebut masuk ke Git. Pola `config/local.php` sudah tercantum dalam `.gitignore`.

## Syarat pemetaan akun

Nilai `nim_nip` di akun Laravel harus sama dengan kolom `username` pada tabel
`user` SIAKAD lama. Pemetaan peran yang digunakan:

| Laravel | SIAKAD lama |
|---|---|
| `admin` | `admin` |
| `kaprodi` | `Jurusan/Prodi` |
| `dosen` | `dosen` |
| `mahasiswa` | `mhs` |

Bila `nim_nip` kosong, LMS memakai email sebagai identitas. Karena itu akun
admin/kaprodi juga perlu diisi `nim_nip` dengan username SIAKAD agar SSO berhasil.

## Cara kerja keamanan

LMS membuat tiket HMAC-SHA256 yang hanya berlaku 60 detik. SIAKAD memverifikasi
signature, waktu kedaluwarsa, identitas, dan peran, kemudian membentuk sesi lama
tanpa mengirim atau mengubah password SIAKAD. HTTPS wajib digunakan pada kedua
aplikasi.

## Pemeriksaan setelah deploy

1. Login melalui LMS, lalu pilih SIAKAD.
2. Pastikan pengguna langsung masuk ke dashboard SIAKAD sesuai perannya.
3. Uji mahasiswa dan dosen yang memiliki NIM/NIP lintas program studi.
4. Pilih LMS, buat kelas sebagai dosen, dan salin kode gabung.
5. Masuk sebagai mahasiswa dari prodi lain dan gabung memakai kode tersebut.
6. Pastikan menu KRS/KHS/transkrip hanya dikelola pada SIAKAD lama.
