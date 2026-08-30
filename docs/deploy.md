# Deploy Akademik STIE Nusantara

Satu repository berisi dua aplikasi dan menggunakan dua database:

- `https://akademik.stienus.ac.id/lms` → LMS Laravel;
- `https://akademik.stienus.ac.id/siakad` → SIAKAD PHP lama;
- `https://akademik.stienus.ac.id/login` → satu halaman login dan portal pemilih.

Document root subdomain harus menunjuk ke folder `public` Laravel, bukan root
repository. Folder `siakad-legacy` tetap berada di root repository dan
ditautkan secara aman ke `public/siakad` pada tahap deploy.

## Deploy pertama

```bash
cd /home/u272545584/domains/stienus.ac.id/public_html
git clone https://github.com/ridhoachmad712/lmsstienus.git akademik
cd akademik
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan lms:prepare-deployment
php artisan storage:link
php artisan optimize
```

Arahkan document root `akademik.stienus.ac.id` ke
`/home/u272545584/domains/stienus.ac.id/public_html/akademik/public`.

Jika hosting menolak symlink, buat alias `/siakad` melalui panel hosting dengan
target folder `.../akademik/siakad-legacy`. Jangan menyalin folder secara manual,
karena salinan tidak akan ikut diperbarui oleh `git pull`.

## Konfigurasi

`DB_*` adalah database LMS. `SIAKAD_DB_*` adalah database SIAKAD lama. Isi juga:

```dotenv
APP_URL=https://akademik.stienus.ac.id
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
LEGACY_SIAKAD_ENABLED=true
LEGACY_SIAKAD_URL=https://akademik.stienus.ac.id/siakad
LEGACY_SIAKAD_SSO_URL=https://akademik.stienus.ac.id/siakad/pages/sso.php
LEGACY_SIAKAD_SSO_SECRET=secret-acak-minimal-32-karakter
SIAKAD_GRADE_SYNC_ENABLED=true
BACKUP_RETENTION_DAYS=30
BACKUP_COPY_PATH=/lokasi/backup/di-luar-public-html
BACKUP_ENCRYPTION_KEY=secret-backup-acak-dan-panjang
```

Salin `siakad-legacy/config/local.example.php` menjadi
`siakad-legacy/config/local.php`, isi koneksi database lama, secret yang sama,
`sso_issuer=https://akademik.stienus.ac.id`, dan
`public_url=https://akademik.stienus.ac.id/siakad`. File lokal ini diabaikan Git.

Jalankan sekali pada database SIAKAD lama sebelum login produksi agar hash kata
sandi modern tidak terpotong:

```sql
ALTER TABLE `user` MODIFY `password` VARCHAR(255) NOT NULL;
```

Login lama berformat plaintext/MD5 tetap diterima sementara dan otomatis diubah
menjadi hash modern setelah login berhasil.

## Update rutin

```bash
cd /home/u272545584/domains/stienus.ac.id/public_html/akademik
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan lms:prepare-deployment
php artisan lms:secure-files --delete-source
php artisan optimize:clear
php artisan optimize
```

Jalankan `lms:secure-files --delete-source` sekali setelah versi pengamanan file
dipasang. Perintah bersifat idempoten dan memverifikasi ukuran sebelum menghapus
salinan publik.

## Cron dan queue

Tambahkan cron berikut dari panel hosting (sesuaikan path PHP):

```cron
* * * * * cd /home/u272545584/domains/stienus.ac.id/public_html/akademik && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/u272545584/domains/stienus.ac.id/public_html/akademik && php artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

Scheduler mengirim pengingat dan membuat backup. Gunakan lokasi backup kedua di
luar `public_html`. Aktifkan `SIAKAD_BACKUP_ENABLED=true` hanya bila akun koneksi
SIAKAD punya izin membaca seluruh tabel. Akun sinkronisasi nilai dengan izin
minimum sebaiknya tidak dipakai untuk backup penuh.

Jika `BACKUP_ENCRYPTION_KEY` diisi, salinan pada `BACKUP_COPY_PATH` disimpan
sebagai `.enc`. Untuk pemulihan bencana, salin berkas itu ke server lalu jalankan
`php artisan lms:decrypt-backup sumber.enc tujuan.sql` sebelum mengunggahnya di
menu backup. Simpan key di pengelola kata sandi terpisah dari server.

## Pemeriksaan

```bash
php artisan about
php artisan migrate:status
php artisan test
composer audit
php artisan lms:backup-db
```

Uji login → pilih LMS, lalu logout/login → pilih SIAKAD. Verifikasi HTTPS,
penggantian kata sandi awal, upload/unduh materi, submit tugas, kecocokan peserta
dengan KRS, dan sinkronisasi satu kelas percobaan sebelum produksi penuh.
