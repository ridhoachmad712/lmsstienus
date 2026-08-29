<!doctype html>
<!--
* Tabler - Premium and Open Source dashboard template with responsive and high quality UI.
* @version 1.0.0-beta4
* @link https://tabler.io
* Copyright 2018-2021 The Tabler Authors
* Copyright 2018-2021 codecalm.net Paweł Kuna
* Licensed under MIT (https://github.com/tabler/tabler/blob/master/LICENSE)
-->
<?php 
session_start();
include"../config/koneksi.php";
$username=$_SESSION['username'];
$password=$_SESSION['password'];
$level=$_SESSION['level'];
if (!isset($_SESSION["login"]) ) {
  header("location: login");
}else{
  $cek_user=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'"));
  if ($cek_user !== 1) {
    header("location: login");
  }
}
// --------------------------------------------------
// pengaturan aplikasi 
$pengaturan=mysqli_query($koneksi,"SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan=mysqli_fetch_array($pengaturan);
// tambah data fakultas
if (isset($_POST['simpan'])) {
  $username=$_POST['pilih'];
  $jumlah_dipilih = count($username);
  for($x=0;$x<$jumlah_dipilih;$x++){
    $password=$username[$x];
    $nim_npm=$username[$x];
    $prodi=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM prodi_has_mhs INNER JOIN prodi ON prodi_has_mhs.kode_prodi=prodi.kode_prodi WHERE nim_npm='$nim_npm'"));
    $kode_prodi=$prodi['kode_prodi'];
    $input=mysqli_query($koneksi,"INSERT INTO user VALUES(NULL,'$username[$x]','$password','$kode_prodi','mhs','','','','0000-00-00','00:00:00')");
  }
  echo "<script>window.alert('Akun Berhasil ditambah !!!')
  window.location='akun_mhs'</script>";
}
// Edit data fakultas
if (isset($_POST['update'])) {
  $kode_matkul=mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
  $nama_matkul=mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
  $sks=mysqli_real_escape_string($koneksi, $_POST['sks']);
  $update=mysqli_query($koneksi,"UPDATE mata_kuliah SET nama_matkul='$nama_matkul', sks='$sks' WHERE kode_matkul='$kode_matkul'");
  if ($update == 1) {
    echo "<script>window.alert('Berhasil diupdate menjadi $nama_matkul !!!')
    window.location='mata_kuliah'</script>";
  }
}
// Hapus data
if (isset($_GET['aksi'])=='hapus') {
  $id=mysqli_real_escape_string($koneksi, $_GET['id_user']);
  $hapus=mysqli_query($koneksi,"DELETE FROM user WHERE id_user='$id'");
  if ($hapus==1) {
    echo "<script>window.alert('Akun Berhasil Dihapus!')
    window.location='akun_mhs'</script>";
  }
}
?>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title><?= $r_pengaturan['nama_aplikasi']; ?></title>
  <!-- CSS files -->
  <link href="./dist/css/tabler.min.css" rel="stylesheet"/>
  <link href="./dist/css/tabler-flags.min.css" rel="stylesheet"/>
  <link href="./dist/css/tabler-payments.min.css" rel="stylesheet"/>
  <link href="./dist/css/tabler-vendors.min.css" rel="stylesheet"/>
  <link href="./dist/css/demo.min.css" rel="stylesheet"/>
</head>
<body class="antialiased">
  <div class="wrapper">
    <?php 
    require_once"../template/header.php";
    ?>
    <div class="navbar-expand-md">
      <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar navbar-light">
          <div class="container-xl">
            <?php 
            require_once"../template/menu.php";
            ?>
          </div>
        </div>
      </div>
    </div>
    <div class="page-wrapper">
      <div class="container-xl">
        <!-- Page title -->
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title">
                Data Akun Mahasiswa
              </h2>
            </div>
          </div>
        </div>
      </div>
      <div class="page-body">
        <div class="container-xl">
          <div class="row row-cards">


            <div class="col-12">
              <div class="card">
               <div class="card">
                <div class="card-body">
                  <a class="btn" data-bs-toggle="modal" data-bs-target="#modal-scrollable">
                    Tambah Data
                  </a>
                </div>
              </div>
              <div class="table-responsive">
                <!-- tampil data -->
                <table class="table table-vcenter card-table">
                  <thead>
                    <th>NO</th>
                    <th>Nama Mahasiswa</th>
                    <th>Username</th>
                    <th>Password (Enkripsi)</th>
                    <th>Opsi</th>
                  </thead>
                  <?php 
                  $no=1;
                  $user=mysqli_query($koneksi,"SELECT * FROM user
                    INNER JOIN mahasiswa ON user.username=mahasiswa.nim_npm WHERE level='mhs'");
                  while ($t_user=mysqli_fetch_array($user)) {
                    ?>
                    <tr>
                      <td><?= $no++ ?>.</td>
                      <td><?= $t_user['nama_mhs']; ?></td>
                      <td><?= $t_user['username']; ?></td>
                      <td><value="<?= htmlspecialchars($t_user['password']); ?>"></td>

                      <td>
                        <a onclick="return confirm('Hapus data user ini ?')" href="akun_mhs?aksi=hapus&id_user=<?= $t_user['id_user']; ?>">
                          <!-- Download SVG icon from http://tabler-icons.io/i/trash -->
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                        </a>
                      </td>
                    </tr>
                  <?php } ?>
                </table>
                <!-- ------------ -->
              </div>
            </div>
          </div>


        </div>
      </div>
    </div>
    <?php 
    require_once"../template/footer.php";
    ?>
  </div>
</div>



<form action="" method="post">
  <div class="modal modal-blur fade" id="modal-scrollable" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Akun Mahasiswa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table class="table table-vcenter card-table table-striped">
           <thead>
            <tr>
              <th>Opsi</th>
              <th>Nim</th>
              <th>Nama mahasiswa</th>
            </tr>
          </thead>
          <?php 
          $mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa ORDER BY nama_mhs ASC");
          while ($t_mhs=mysqli_fetch_array($mhs)) {
            $nim_npm=$t_mhs['nim_npm'];
            ?>
            <?php 
            $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM user WHERE username='$nim_npm'"));
            if ($cek_data > 0) {
              ?>

            <?php }else{ ?>
              <tr>
                <td>
                  <input type="checkbox" name="pilih[]" value="<?= $t_mhs['nim_npm']; ?>">
                </td>
                <td><?= $t_mhs['nim_npm']; ?></td>
                <td><?= $t_mhs['nama_mhs']; ?></td>
              </tr>
            <?php }} ?>
          </table>
        </div>
        <div class="modal-footer">
          <button type="submit" name="simpan" class="btn btn-info">Tambah</button>
        </div>
      </div>
    </div>
  </div>
</form>
<!-- Libs JS -->
<!-- Tabler Core -->
<script src="../dist/js/jquery.js"></script>
<script src="./dist/js/tabler.min.js"></script>
<!-- javascript search data fakultas -->

</body>
</html>