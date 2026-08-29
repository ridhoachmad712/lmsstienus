# SIAKAD Legacy STIE Nusantara Makassar

Folder ini berisi source runtime SIAKAD PHP lama yang tetap menjadi sistem resmi
untuk KRS, jadwal akademik, nilai, KHS, dan transkrip. Source disatukan ke dalam
repository LMS agar versi integrasi SSO dan UI dapat dirilis bersama, tetapi
SIAKAD tetap memakai database dan document root tersendiri.

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

Jadikan folder ini sebagai document root SIAKAD (atau salin isinya ke document
root SIAKAD yang telah ada). Jangan arahkan document root SIAKAD ke folder
`public` milik Laravel.
