# Deploy Akademik STIE Nusantara

Pemetaan aplikasi:

- `https://akademik.stienus.ac.id/` → halaman depan publik;
- `https://akademik.stienus.ac.id/lms` → LMS Laravel;
- `https://akademik.stienus.ac.id/siakad` → SIAKAD PHP lama.

Satu repository membawa kedua source code, tetapi masing-masing aplikasi
memiliki login, sesi, konfigurasi, dan database sendiri. Tidak ada SSO atau
sinkronisasi database.

Document root subdomain harus menunjuk ke folder `public` Laravel:

```text
/home/u272545584/domains/stienus.ac.id/public_html/akademik/public
```

Folder source SIAKAD berada di `siakad-legacy` dan dipublikasikan ke
`public/siakad` saat deployment.

## Deploy pertama

```bash
cd /home/u272545584/domains/stienus.ac.id/public_html
git clone https://github.com/ridhoachmad712/lmsstienus.git akademik
cd akademik
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan lms:prepare-deployment --copy
php artisan storage:link
php artisan optimize
```

Opsi `--copy` aman digunakan pada hosting yang menonaktifkan fungsi PHP
`symlink()`. Jalankan perintah yang sama sesudah setiap `git pull` agar perubahan
source SIAKAD disalin ke folder publik.

## Konfigurasi LMS

Isi `.env` Laravel hanya dengan koneksi database LMS:

```dotenv
APP_NAME="Akademik STIE Nusantara"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://akademik.stienus.ac.id

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_lms
DB_USERNAME=pengguna_database_lms
DB_PASSWORD=kata_sandi_database_lms

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

BACKUP_RETENTION_DAYS=30
BACKUP_COPY_PATH=/lokasi/backup/di-luar-public-html
BACKUP_ENCRYPTION_KEY=secret-backup-acak-dan-panjang
```

Jangan menambahkan koneksi database SIAKAD ke `.env` Laravel.

## Konfigurasi SIAKAD

Salin contoh konfigurasi, kemudian isi database SIAKAD lama:

```bash
cp siakad-legacy/config/local.example.php siakad-legacy/config/local.php
```

Nilai penting di `siakad-legacy/config/local.php`:

```php
'public_url' => 'https://akademik.stienus.ac.id/siakad',
'home_url' => 'https://akademik.stienus.ac.id/',
```

Koneksi `db` di file ini harus menunjuk ke database SIAKAD, bukan database LMS.
File `local.php` diabaikan Git sehingga tidak ditimpa saat pembaruan.

Jika kolom password SIAKAD lama masih terlalu pendek, jalankan sekali pada
database SIAKAD:

```sql
ALTER TABLE `user` MODIFY `password` VARCHAR(255) NOT NULL;
```

## Update rutin

```bash
cd /home/u272545584/domains/stienus.ac.id/public_html/akademik
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan lms:prepare-deployment --copy
php artisan lms:secure-files --delete-source
php artisan optimize:clear
php artisan optimize
```

Perintah `lms:secure-files --delete-source` memindahkan berkas unggahan lama
yang masih publik ke penyimpanan privat dan aman dijalankan ulang.

## Cron dan queue LMS

```cron
* * * * * cd /home/u272545584/domains/stienus.ac.id/public_html/akademik && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/u272545584/domains/stienus.ac.id/public_html/akademik && php artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

Scheduler dan menu backup hanya bekerja untuk LMS. Backup SIAKAD harus diatur
secara terpisah melalui hosting atau mekanisme milik SIAKAD.

## Pemeriksaan setelah deploy

```bash
php artisan about
php artisan migrate:status
php artisan route:list --path=lms
php artisan test
composer audit
```

Buka ketiga URL utama dan uji login LMS serta SIAKAD secara terpisah. Pastikan
logout dari salah satu aplikasi tidak memengaruhi sesi aplikasi lain.
