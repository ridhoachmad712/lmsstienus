<?php
$lms_url = function_exists('siakad_config')
  ? siakad_config('LMS_URL', 'lms_url')
  : getenv('LMS_URL');
if ($level=='mhs') {
  $mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa
    INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$username'");
  $tampil_mhs=mysqli_fetch_array($mhs);
  $foto_mhs=$tampil_mhs['foto_mhs'];
}
if ($level=='dosen') {
  $dosen=mysqli_query($koneksi,"SELECT * FROM dosen
    INNER JOIN tbl_jk ON dosen.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON dosen.id_agama=tbl_agama.id_agama WHERE nip='$username'");
  $tampil_dosen=mysqli_fetch_array($dosen);
  $foto_dosen=$tampil_dosen['foto_dosen'];
}
?>
<link rel="stylesheet" href="../assets/siakad-ux.css">
<header class="navbar navbar-expand-md navbar-dark d-print-none">
  <div class="container-xl">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
      <a style="text-decoration: none;" href="#"s>
        <?= $r_pengaturan['nama_aplikasi']; ?>
        <!-- <img src="./static/logo.svg" width="110" height="32" alt="Tabler" class="navbar-brand-image"> -->
      </a>
    </h1>

    <div class="nav-item dropdown">
      <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
        <?php 
        if ($level=="mhs") {
          if ($foto_mhs=="") {
           ?>
           <span class="avatar avatar-sm" style="background-image: url(../pages/foto_mhs/avatar-blank.png)"></span>
         <?php }else{ ?>
           <img style="border-radius: 10%; width: 45px; height: 45px; overflow: hidden;" src="foto_mhs/<?= $tampil_mhs['foto_mhs']; ?>">
         <?php } ?>
       <?php }elseif ($level=="dosen") {
        if ($foto_dosen=="") {
         ?>
         <span class="avatar avatar-sm" style="background-image: url(../pages/foto_dosen/avatar-blank.png)"></span>
       <?php }else{ ?>
        <img style="border-radius: 10%; width: 45px; height: 45px; overflow: hidden;" src="foto_dosen/<?= $tampil_dosen['foto_dosen']; ?>">
      <?php }} ?>

      <?php 
      if ($level=="admin" OR $level=="Jurusan/Prodi") {
        ?>
        <span class="avatar avatar-sm" style="background-image: url(../pages/foto_mhs/avatar-blank.jpg)"></span>
      <?php } ?>


      <div class="d-none d-xl-block ps-2">
        <div><?= $_SESSION['username']; ?></div>
        <div class="mt-1 small text-muted"><?= $level; ?></div>
      </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
      <?php if ($lms_url) { ?>
        <a href="<?= htmlspecialchars($lms_url, ENT_QUOTES, 'UTF-8'); ?>" class="dropdown-item siakad-lms-link">
          <span aria-hidden="true">↗</span> Buka LMS
        </a>
        <div class="dropdown-divider"></div>
      <?php } ?>
      <a href="logout" onclick="return confirm('Anda Ingin Keluar dari aplikasi?')" class="dropdown-item">Keluar</a>
    </div>
  </div>
</div>
</div>
</header>
