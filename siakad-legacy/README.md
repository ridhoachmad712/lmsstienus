# SIAKAD Legacy STIE Nusantara Makassar

Folder ini berisi source runtime SIAKAD PHP lama yang tetap menjadi sistem resmi
untuk KRS, jadwal akademik, nilai, KHS, dan transkrip. Source disatukan ke dalam
repository yang sama dengan LMS agar keduanya dapat dirilis dengan satu proses
Git. SIAKAD tetap memakai login, sesi, konfigurasi, dan database tersendiri;
tidak ada SSO atau koneksi runtime ke LMS.

## Yang sengaja tidak masuk Git

- kredensial database produksi;
- dump database dan arsip ZIP;
- foto mahasiswa/dosen selain avatar bawaan;
- log server; dan
- spreadsheet impor/ekspor yang berpotensi memuat data pribadi.

Saat memperbarui hosting yang sudah aktif, file data tersebut tetap berada di
server karena tidak dikelola Git. Untuk instalasi baru, buat direktori foto yang
dapat ditulis PHP dan sediakan template impor secara terpisah bila fitur impor
lama masih digunakan.

## Konfigurasi server

Gunakan salah satu cara berikut:

1. pasang seluruh variabel pada `.env.example` sebagai environment variable
   PHP-FPM/panel hosting; atau
2. salin `config/local.example.php` menjadi `config/local.php`, lalu isi nilainya.

`config/local.php` diabaikan Git sehingga tidak ikut saat commit maupun push.
File `.env.example` sendiri hanya dokumentasi dan tidak dibaca otomatis oleh
aplikasi PHP lama.

Pada susunan repository gabungan, perintah `php artisan
lms:prepare-deployment --copy` menyalin folder ini ke `public/siakad`. Document
root domain tetap mengarah ke `public` Laravel, sedangkan web server melayani
folder SIAKAD secara langsung pada URL `/siakad`.
