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
LEGACY_SIAKAD_URL=https://akademik.stienus.ac.id/siakad
LEGACY_SIAKAD_SSO_URL=https://akademik.stienus.ac.id/siakad/pages/sso.php
LEGACY_SIAKAD_SSO_SECRET=ganti-dengan-secret-acak-minimal-32-karakter

# Koneksi kedua khusus sinkronisasi nilai LMS -> SIAKAD
SIAKAD_GRADE_SYNC_ENABLED=true
SIAKAD_DB_CONNECTION=mysql
SIAKAD_DB_HOST=127.0.0.1
SIAKAD_DB_PORT=3306
SIAKAD_DB_DATABASE=nama_database_siakad
SIAKAD_DB_USERNAME=user_integrasi_siakad
SIAKAD_DB_PASSWORD=password-user-integrasi
```

Setelah mengubah `.env`:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
```

`DB_*` tetap menunjuk ke database LMS. `SIAKAD_DB_*` menunjuk ke database lama
yang berbeda. Jangan menjalankan migrasi Laravel dengan database SIAKAD sebagai
koneksi default.

## Konfigurasi SIAKAD PHP lama

Source runtime SIAKAD lama tersedia pada folder `siakad-legacy` di repository
yang sama. Deploy seluruh repository sekali, lalu jalankan `php artisan
lms:prepare-deployment`. Perintah itu membuat `public/siakad` sebagai symlink ke
`siakad-legacy`, sehingga kedua aplikasi ikut dalam satu `git pull` tanpa
menduplikasi source.

Git sengaja tidak membawa database dump, kredensial, foto pengguna, log, arsip,
serta spreadsheet impor/ekspor. Saat melakukan `git pull` pada instalasi lama,
data-data yang tidak dilacak Git tersebut tidak akan ditimpa.

Pasang environment variable pada hosting SIAKAD:

```text
SIAKAD_SSO_SECRET = nilai yang sama dengan LEGACY_SIAKAD_SSO_SECRET
SIAKAD_SSO_ISSUER = https://akademik.stienus.ac.id
SIAKAD_PUBLIC_URL = https://akademik.stienus.ac.id/siakad
LMS_URL = https://akademik.stienus.ac.id/portal
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

LMS membuat tiket HMAC-SHA256 yang hanya berlaku 60 detik dan mengirimkannya
melalui POST, bukan URL. SIAKAD memverifikasi signature, issuer, audience, waktu,
identitas, peran, serta nonce sekali pakai, kemudian membentuk sesi lama tanpa
mengirim password SIAKAD. HTTPS wajib digunakan.

## Sinkronisasi nilai akhir LMS ke SIAKAD

Sinkronisasi berjalan satu arah. Nilai tugas, kuis, kehadiran, dan komponen lain
tetap diolah dalam LMS. Setelah 16 pertemuan terpenuhi, bobot komponen tepat
100%, dan semua nilai lengkap, dosen menandai kelas selesai lalu membuka halaman
**Penilaian**.

Pada bagian **Sinkronisasi Nilai SIAKAD** dosen:

1. memilih jadwal resmi SIAKAD (`id_jadwal`) yang kode mata kuliah, periode, dan
   NIP dosennya cocok;
2. menekan **Finalisasi & Kirim ke SIAKAD**; dan
3. memeriksa status setiap mahasiswa atau memakai **Sinkronkan Ulang / Retry**
   untuk baris yang gagal.

Untuk setiap NIM, LMS memeriksa `krs_mhs` terlebih dahulu. Bila KRS resmi ada,
LMS mengambil skala resmi dari `tbl_grade`, lalu memperbarui kolom
`nilai_akhir`, `bobot`, dan `grade` pada baris `khs_mhs` yang sudah ada. LMS tidak
membuat KRS/KHS baru. Dengan demikian mahasiswa lintas prodi tetap dapat
diproses berdasarkan `kode_prodi` pada KRS-nya sendiri, sedangkan mahasiswa yang
hanya bergabung di LMS tanpa KRS resmi ditandai gagal.

Setiap percobaan disimpan di tabel LMS `siakad_grade_syncs`, termasuk snapshot
nilai, status, jumlah percobaan, pesan kegagalan, pelaku finalisasi, dan waktu
sinkron. Payload yang sama bersifat idempoten dan tidak ditulis ulang. Jika kelas
dibuka kembali atau pemetaan jadwal berubah, seluruh status ditandai perlu
sinkron ulang.

Gunakan akun MySQL integrasi dengan hak minimum:

- `SELECT`: `jadwal_mengajar`, `thn_akademik`, `krs_mhs`, `khs_mhs`, `tbl_grade`;
- `UPDATE`: hanya `nilai_akhir`, `bobot`, dan `grade` pada `khs_mhs`.

Jangan memberikan izin `DROP`, `ALTER`, `DELETE`, atau `INSERT` kepada akun
integrasi. Password hanya disimpan pada `.env` hosting dan tidak masuk Git.

## Pemeriksaan setelah deploy

1. Login melalui LMS, lalu pilih SIAKAD.
2. Pastikan pengguna langsung masuk ke dashboard SIAKAD sesuai perannya.
3. Uji mahasiswa dan dosen yang memiliki NIM/NIP lintas program studi.
4. Pilih LMS, buat kelas sebagai dosen, dan salin kode gabung.
5. Masuk sebagai mahasiswa dari prodi lain dan gabung memakai kode tersebut.
6. Pastikan menu KRS/KHS/transkrip hanya dikelola pada SIAKAD lama.
7. Selesaikan satu kelas uji, petakan jadwal SIAKAD, lalu sinkronkan nilai.
8. Pastikan mahasiswa ber-KRS berubah pada `khs_mhs`, sementara mahasiswa tanpa
   KRS mendapat status gagal dan tidak dibuatkan data akademik baru.
