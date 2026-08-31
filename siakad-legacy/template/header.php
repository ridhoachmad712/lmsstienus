<?php
$home_url = function_exists('siakad_config')
  ? siakad_config('HOME_URL', 'home_url')
  : getenv('HOME_URL');
if ($level == 'mhs') {
    $stmt_header = mysqli_prepare($koneksi, 'SELECT * FROM mahasiswa
    INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm=?');
    mysqli_stmt_bind_param($stmt_header, 's', $username);
    mysqli_stmt_execute($stmt_header);
    $mhs = mysqli_stmt_get_result($stmt_header);
    $tampil_mhs = mysqli_fetch_array($mhs);
    $foto_mhs = $tampil_mhs['foto_mhs'];
}
if ($level == 'dosen') {
    $stmt_header = mysqli_prepare($koneksi, 'SELECT * FROM dosen
    INNER JOIN tbl_jk ON dosen.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON dosen.id_agama=tbl_agama.id_agama WHERE nip=?');
    mysqli_stmt_bind_param($stmt_header, 's', $username);
    mysqli_stmt_execute($stmt_header);
    $dosen = mysqli_stmt_get_result($stmt_header);
    $tampil_dosen = mysqli_fetch_array($dosen);
    $foto_dosen = $tampil_dosen['foto_dosen'];
}
?>
<link rel="stylesheet" href="../assets/siakad-ux.css">
<header class="navbar navbar-expand-md navbar-dark d-print-none">
  <div class="container-xl">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
      <a style="text-decoration: none;" href="dashboard">
        <?= htmlspecialchars($r_pengaturan['nama_aplikasi'], ENT_QUOTES, 'UTF-8'); ?>
        <!-- <img src="./static/logo.svg" width="110" height="32" alt="Tabler" class="navbar-brand-image"> -->
      </a>
    </h1>

    <div class="nav-item dropdown">
      <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
        <?php
        if ($level == 'mhs') {
            if ($foto_mhs == '') {
                ?>
           <span class="avatar avatar-sm" style="background-image: url(../pages/foto_mhs/avatar-blank.png)"></span>
         <?php } else { ?>
           <img style="border-radius: 10%; width: 45px; height: 45px; overflow: hidden;" src="foto_mhs/<?= $tampil_mhs['foto_mhs']; ?>">
         <?php } ?>
       <?php } elseif ($level == 'dosen') {
           if ($foto_dosen == '') {
               ?>
         <span class="avatar avatar-sm" style="background-image: url(../pages/foto_dosen/avatar-blank.png)"></span>
       <?php } else { ?>
        <img style="border-radius: 10%; width: 45px; height: 45px; overflow: hidden;" src="foto_dosen/<?= $tampil_dosen['foto_dosen']; ?>">
      <?php }
       } ?>

      <?php
      if ($level == 'admin' or $level == 'Jurusan/Prodi') {
          ?>
        <span class="avatar avatar-sm" style="background-image: url(../pages/foto_mhs/avatar-blank.jpg)"></span>
      <?php } ?>


      <div class="d-none d-xl-block ps-2">
        <div><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="mt-1 small text-muted"><?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
      <?php if ($home_url) { ?>
        <a href="<?= htmlspecialchars($home_url, ENT_QUOTES, 'UTF-8'); ?>" class="dropdown-item">
          <span aria-hidden="true">↗</span> Halaman Depan
        </a>
        <div class="dropdown-divider"></div>
      <?php } ?>
      <a href="logout" onclick="return confirm('Anda Ingin Keluar dari aplikasi?')" class="dropdown-item">Keluar</a>
    </div>
  </div>
</div>
</div>
</header>
