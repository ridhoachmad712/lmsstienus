# Arsitektur LMS dan SIAKAD Terpisah

Repository ini memuat dua aplikasi yang berdiri sendiri:

- `https://akademik.stienus.ac.id/` adalah halaman depan publik;
- `https://akademik.stienus.ac.id/lms` adalah LMS Laravel;
- `https://akademik.stienus.ac.id/siakad` adalah SIAKAD PHP lama.

Keduanya berada dalam satu repository agar dapat dipasang dan diperbarui dengan
satu proses Git. Kesamaan repository tidak membuat kedua aplikasi saling
bergantung saat dijalankan.

## Batas sistem

LMS hanya menangani pembelajaran: kelas, peserta kelas, pertemuan, materi,
tugas, kuis, forum, presensi, dan nilai pembelajaran. Dosen membuat serta
mengelola kelas LMS. Mahasiswa dapat bergabung menggunakan kode kelas.

SIAKAD tetap menangani administrasi akademik, termasuk akun SIAKAD, KRS, KHS,
transkrip, dan proses administrasi lain yang sudah tersedia pada aplikasi lama.

Tidak ada SSO, pemetaan akun, sesi bersama, koneksi database silang, atau
sinkronisasi nilai antara kedua aplikasi. Pengguna login secara terpisah pada
sistem yang dipilih. Perubahan data di satu aplikasi tidak mengubah aplikasi
lain secara otomatis.

## Database dan konfigurasi

Database LMS ditentukan oleh variabel `DB_*` dalam `.env` Laravel. Laravel tidak
memiliki koneksi kedua menuju database SIAKAD.

Database SIAKAD ditentukan oleh `SIAKAD_DB_*` pada environment hosting atau
`siakad-legacy/config/local.php`. File lokal tersebut diabaikan Git dan tidak
boleh berisi kredensial yang dikomit.

Contoh konfigurasi lokal SIAKAD:

```php
<?php

return [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'nama_database_siakad',
    'db_user' => 'pengguna_database_siakad',
    'db_password' => 'kata_sandi_database_siakad',
    'timezone' => 'Asia/Makassar',
    'public_url' => 'https://akademik.stienus.ac.id/siakad',
    'home_url' => 'https://akademik.stienus.ac.id/',
];
```

## Alur pengguna

1. Pengguna membuka halaman depan.
2. Pilihan LMS membuka `/lms`; tamu diarahkan ke `/lms/login`.
3. Pilihan SIAKAD membuka `/siakad`; SIAKAD menampilkan login miliknya sendiri.
4. Logout hanya mengakhiri sesi aplikasi yang sedang digunakan.

Halaman depan hanya menjadi direktori dan tidak membuat sesi login.

## Pemeriksaan pemisahan

1. Pastikan root dapat dibuka tanpa login dan menampilkan dua pilihan.
2. Pastikan `/lms` mengarah ke login LMS bagi tamu.
3. Pastikan `/siakad` menampilkan login SIAKAD.
4. Login ke LMS tidak membuat pengguna login ke SIAKAD, dan sebaliknya.
5. Pastikan konfigurasi Laravel hanya berisi database LMS.
6. Pastikan backup dari menu LMS hanya mencadangkan database LMS.
