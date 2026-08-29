<ul class="navbar-nav">
 <?php 
 if ($_SESSION['level']=='admin') {
  ?>
  <li class="nav-item active">
    <a class="nav-link" href="dashboard" >
      <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
      </span>
      <span class="nav-link-title">
        Beranda
      </span>
    </a>
  </li>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><ellipse cx="12" cy="6" rx="8" ry="3"></ellipse><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>
      </span>
      <span class="nav-link-title">
        Master Data
      </span>
    </a>
    <div class="dropdown-menu">
      <a class="dropdown-item" href="fakultas" >
        Data Institusi
      </a>
      <a class="dropdown-item" href="jurusan" >
       Data Program Studi
     </a>
     <a class="dropdown-item" href="dosen" >
      Data Dosen
    </a>
    <a class="dropdown-item" href="mhs" >
      Data Mahasiswa
    </a>
    <a class="dropdown-item" href="mata_kuliah" >
      Data Mata Kuliah
    </a>
  </div>
</li>
<li class="nav-item">
  <a class="nav-link" href="thn_akademik" >
    <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/box-padding -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M8 16v.01" /><path d="M8 12v.01" /><path d="M8 8v.01" /><path d="M16 16v.01" /><path d="M16 12v.01" /><path d="M16 8v.01" /><path d="M12 8v.01" /><path d="M12 16v.01" /></svg>
    </span>
    <span class="nav-link-title">
      Tahun Akademik
    </span>
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" href="grade" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/stars -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17.8 19.817l-2.172 1.138a0.392 .392 0 0 1 -.568 -.41l.415 -2.411l-1.757 -1.707a0.389 .389 0 0 1 .217 -.665l2.428 -.352l1.086 -2.193a0.392 .392 0 0 1 .702 0l1.086 2.193l2.428 .352a0.39 .39 0 0 1 .217 .665l-1.757 1.707l.414 2.41a0.39 .39 0 0 1 -.567 .411l-2.172 -1.138z" /><path d="M6.2 19.817l-2.172 1.138a0.392 .392 0 0 1 -.568 -.41l.415 -2.411l-1.757 -1.707a0.389 .389 0 0 1 .217 -.665l2.428 -.352l1.086 -2.193a0.392 .392 0 0 1 .702 0l1.086 2.193l2.428 .352a0.39 .39 0 0 1 .217 .665l-1.757 1.707l.414 2.41a0.39 .39 0 0 1 -.567 .411l-2.172 -1.138z" /><path d="M12 9.817l-2.172 1.138a0.392 .392 0 0 1 -.568 -.41l.415 -2.411l-1.757 -1.707a0.389 .389 0 0 1 .217 -.665l2.428 -.352l1.086 -2.193a0.392 .392 0 0 1 .702 0l1.086 2.193l2.428 .352a0.39 .39 0 0 1 .217 .665l-1.757 1.707l.414 2.41a0.39 .39 0 0 1 -.567 .411l-2.172 -1.138z" /></svg>
    </span>
    <span class="nav-link-title">
      Grade Nilai
    </span>
  </a>
</li>

<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/user-plus -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 11h6m-3 -3v6" /></svg>
    </span>
    <span class="nav-link-title">
      User
    </span>
  </a>
  <div class="dropdown-menu">
    <a class="dropdown-item" href="akun_admin">
      Admin Akademik
    </a>
    <a class="dropdown-item" href="akun_jurusan">
      Program Studi
    </a>
    <a class="dropdown-item" href="akun_dosen">
      Dosen
    </a>
    <a class="dropdown-item" href="akun_mhs">
      Mahasiswa
    </a>
  </div>
</li>
<li class="nav-item">
  <a class="nav-link" href="pengaturan" >
    <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/settings -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><circle cx="12" cy="12" r="3" /></svg>
    </span>
    <span class="nav-link-title">
      Pengaturan Aplikasi
    </span>
  </a>
</li>

<?php }elseif ($_SESSION['level']=='mhs') { ?>

  <li class="nav-item active">
    <a class="nav-link" href="dashboard" >
      <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
      </span>
      <span class="nav-link-title">
        Beranda
      </span>
    </a>
  </li>


  <li class="nav-item">
    <a class="nav-link" href="jadwal_kuliah" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
        <!-- Download SVG icon from http://tabler-icons.io/i/list-numbers -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="6" x2="9.5" y2="6" /><line x1="4" y1="10" x2="9.5" y2="10" /><line x1="4" y1="14" x2="9.5" y2="14" /><line x1="4" y1="18" x2="9.5" y2="18" /><line x1="14.5" y1="6" x2="20" y2="6" /><line x1="14.5" y1="10" x2="20" y2="10" /><line x1="14.5" y1="14" x2="20" y2="14" /><line x1="14.5" y1="18" x2="20" y2="18" /></svg>
      </span>
      <span class="nav-link-title">
        Jadwal Kuliah
      </span>
    </a>
  </li>

  
  <li class="nav-item">
    <a class="nav-link" href="krs" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
        <!-- Download SVG icon from http://tabler-icons.io/i/columns -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="6" x2="9.5" y2="6" /><line x1="4" y1="10" x2="9.5" y2="10" /><line x1="4" y1="14" x2="9.5" y2="14" /><line x1="4" y1="18" x2="9.5" y2="18" /><line x1="14.5" y1="6" x2="20" y2="6" /><line x1="14.5" y1="10" x2="20" y2="10" /><line x1="14.5" y1="14" x2="20" y2="14" /><line x1="14.5" y1="18" x2="20" y2="18" /></svg>
      </span>
      <span class="nav-link-title">
        KRS (Kartu Rencana Studi)
      </span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="khs" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
       <!-- Download SVG icon from http://tabler-icons.io/i/notebook -->
       <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18" /><line x1="13" y1="8" x2="15" y2="8" /><line x1="13" y1="12" x2="15" y2="12" /></svg>
     </span>
     <span class="nav-link-title">
      KHS (Kartu Hasil Studi)
    </span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="transkip" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/news -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 6h3a1 1 0 0 1 1 1v11a2 2 0 0 1 -4 0v-13a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1v12a3 3 0 0 0 3 3h11" /><line x1="8" y1="8" x2="12" y2="8" /><line x1="8" y1="12" x2="12" y2="12" /><line x1="8" y1="16" x2="12" y2="16" /></svg>
    </span>
    <span class="nav-link-title">
      Transkrip Nilai
    </span>
  </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="https://stienus.ac.id/pengumuman/" target="_blank" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
       <!-- Download SVG icon from http://tabler-icons.io/i/notebook -->
       <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-square-rounded" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
  <path d="M12 9h.01" />
  <path d="M11 12h1v4h1" />
  <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
</svg>
     </span>
     <span class="nav-link-title">
      Pengumuman & Informasi Kampus
    </span>
  </a>
</li>





<?php }elseif ($level=='Jurusan/Prodi') { ?>

 <li class="nav-item active">
  <a class="nav-link" href="dashboard" >
    <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
    </span>
    <span class="nav-link-title">
      Beranda
    </span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="jurusan_has_matkul" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/book -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0" /><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0" /><line x1="3" y1="6" x2="3" y2="19" /><line x1="12" y1="6" x2="12" y2="19" /><line x1="21" y1="6" x2="21" y2="19" /></svg>
    </span>
    <span class="nav-link-title">
      Mata Kuliah
    </span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="jurusan_has_dosen" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/users -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
    </span>
    <span class="nav-link-title">
      Dosen
    </span>
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" href="jurusan_has_mhs" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/users -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
    </span>
    <span class="nav-link-title">
      Mahasiswa
    </span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="rekap_jadwal" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <!-- Download SVG icon from http://tabler-icons.io/i/users -->
      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-script" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
  <path d="M17 20h-11a3 3 0 0 1 0 -6h11a3 3 0 0 0 0 6h1a3 3 0 0 0 3 -3v-11a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v8" />
</svg>
    </span>
    <span class="nav-link-title">
      Jadwal Perkuliahan
    </span>
  </a>
</li>

<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><ellipse cx="12" cy="6" rx="8" ry="3"></ellipse><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>
    </span>
    <span class="nav-link-title">
      Akademik
    </span>
  </a>
  <div class="dropdown-menu">
    <a class="dropdown-item" href="buat_jadwal" >
      Penjadwalan Kuliah
    </a>
    <a class="dropdown-item" href="sks_mhs" >
      Pengaturan SKS Mahasiswa
    </a>
    <a class="dropdown-item" href="krs_mhs" >
     KRS Mahasiswa
   <a class="dropdown-item" href="khs_mhs" >
    KHS Mahasiswa
  </a>
  <a class="dropdown-item" href="input_nilai" >
    Input Nilai Mahasiswa
  </a>
  <a class="dropdown-item" href="transkip_mhs" >
    Transkip Nilai Mahasiswa
  </a>
</div>
</li>


<?php }elseif ($level=='dosen') { ?>

  <li class="nav-item active">
    <a class="nav-link" href="dashboard" >
      <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
      </span>
      <span class="nav-link-title">
        Home
      </span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="dosen_has_mhs" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
        <!-- Download SVG icon from http://tabler-icons.io/i/users -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
      </span>
      <span class="nav-link-title">
        Mahasiswa Perwalian
      </span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="jadwal_mengajar" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
        <!-- Download SVG icon from http://tabler-icons.io/i/calendar-event -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="5" width="16" height="16" rx="2" /><line x1="16" y1="3" x2="16" y2="7" /><line x1="8" y1="3" x2="8" y2="7" /><line x1="4" y1="11" x2="20" y2="11" /><rect x="8" y="15" width="2" height="2" /></svg>
      </span>
      <span class="nav-link-title">
        Jadwal Mengajar
      </span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="input_nilai_dosen" >
      <span class="nav-link-icon d-md-none d-lg-inline-block">
        <!-- Download SVG icon from http://tabler-icons.io/i/news -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
  <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
  <path d="M13.5 6.5l4 4" />
</svg>
      </span>
      <span class="nav-link-title">
        Input Nilai Mahasiswa
      </span>
    </a>
  </li>

  <li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false" >
    <span class="nav-link-icon d-md-none d-lg-inline-block">
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-square-rounded" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
  <path d="M12 9h.01" />
  <path d="M11 12h1v4h1" />
  <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
</svg>
    </span>
    <span class="nav-link-title">
      Layanan Dosen
    </span>
  </a>
  <div class="dropdown-menu">
    <a class="dropdown-item" href="https://sister.kemdikbud.go.id/beranda" target="_blank">
    SISTER
    </a>
    <a class="dropdown-item" href="https://sipinter.lldikti9.id/" target="_blank">
      SiPinter
    </a>
    <a class="dropdown-item" href="https://jafa.lldikti9.id/access" target="_blank">
     Sijafung
   <a class="dropdown-item" href="https://sinta.kemdikbud.go.id/" target="_blank">
    SINTA
  </a>
  <a class="dropdown-item" href="https://bima.kemdikbud.go.id/" target="_blank">
    BIMA
  </a>
</div>
</li>

<?php } ?>

</ul>