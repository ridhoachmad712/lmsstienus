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
include "../config/koneksi.php";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$level = $_SESSION['level'];
if (!isset($_SESSION["login"])) {
  header("location: login");
} else {
  $cek_user = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'"));
  if ($cek_user !== 1) {
    header("location: login");
  }
}
// --------------------------------------------------
// pengaturan aplikasi 
$pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan = mysqli_fetch_array($pengaturan);
// tambah data fakultas
if (isset($_POST['simpan'])) {
  $username = $_POST['pilih'];
  $kode_prodi = "";
  $ip = "";
  $os = "";
  $browser = "";
  $waktu = "00:00:00";
  $jumlah_dipilih = count($username);
  for ($x = 0; $x < $jumlah_dipilih; $x++) {
    $password = $username[$x];
    $input = mysqli_query($koneksi, "INSERT INTO user VALUES(NULL,'$username[$x]','$password','$kode_prodi','dosen','$ip','$os','$browser','0000-00-00','$waktu')");
  }
  echo "<script>window.alert('Akun Berhasil ditambah !!!')
  window.location='akun_dosen'</script>";
}
// Edit data fakultas
if (isset($_POST['update'])) {
  $kode_matkul = mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
  $nama_matkul = mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
  $sks = mysqli_real_escape_string($koneksi, $_POST['sks']);
  $update = mysqli_query($koneksi, "UPDATE mata_kuliah SET nama_matkul='$nama_matkul', sks='$sks' WHERE kode_matkul='$kode_matkul'");
  if ($update == 1) {
    echo "<script>window.alert('Berhasil diupdate menjadi $nama_matkul !!!')
    window.location='mata_kuliah'</script>";
  }
}
// Hapus data
if (isset($_GET['aksi']) == 'hapus') {
  $id = mysqli_real_escape_string($koneksi, $_GET['id_user']);
  $hapus = mysqli_query($koneksi, "DELETE FROM user WHERE id_user='$id'");
  if ($hapus == 1) {
    echo "<script>window.alert('Akun Berhasil dihapus !!!')
    window.location='akun_dosen'</script>";
  }
}
?>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title><?= $r_pengaturan['nama_aplikasi']; ?></title>
  <!-- CSS files -->
  <link href="./dist/css/tabler.min.css" rel="stylesheet" />
  <link href="./dist/css/tabler-flags.min.css" rel="stylesheet" />
  <link href="./dist/css/tabler-payments.min.css" rel="stylesheet" />
  <link href="./dist/css/tabler-vendors.min.css" rel="stylesheet" />
  <link href="./dist/css/demo.min.css" rel="stylesheet" />
</head>

<body class="antialiased">
  <div class="wrapper">
    <?php
    require_once "../template/header.php";
    ?>
    <div class="navbar-expand-md">
      <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar navbar-light">
          <div class="container-xl">
            <?php
            require_once "../template/menu.php";
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
                Data Akun Dosen
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
                      <th>Nama Dosen</th>
                      <th>Username</th>
                      <th>Password (Enkripsi)</th>
                      <th>Opsi</th>
                    </thead>
                    <?php
                    $no = 1;
                    $user = mysqli_query($koneksi, "SELECT * FROM user
                    INNER JOIN dosen ON user.username=dosen.nip WHERE level='dosen'");
                    while ($t_user = mysqli_fetch_array($user)) {
                    ?>
                      <tr>
                        <td><?= $no++ ?>.</td>
                        <td><?= $t_user['nama_dosen']; ?></td>
                        <td><?= $t_user['username']; ?></td>
                        <td><?= $t_user['password']; ?></td>
                        <td>
                          <a onclick="return confirm('Hapus data user ini ?')" href="akun_dosen?aksi=hapus&id_user=<?= $t_user['id_user']; ?>">
                            <!-- Download SVG icon from http://tabler-icons.io/i/trash -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <line x1="4" y1="7" x2="20" y2="7" />
                              <line x1="10" y1="11" x2="10" y2="17" />
                              <line x1="14" y1="11" x2="14" y2="17" />
                              <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                              <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                            </svg>
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
      require_once "../template/footer.php";
      ?>
    </div>
  </div>



  <form action="" method="post">
    <div class="modal modal-blur fade" id="modal-scrollable" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Akun Dosen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table class="table table-vcenter card-table table-striped">
              <thead>
                <tr>
                  <th>Opsi</th>
                  <th>NIDN</th>
                  <th>Nama Dosen</th>
                </tr>
              </thead>
              <?php
              $dosen = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
              while ($t_dosen = mysqli_fetch_array($dosen)) {
                $nip = $t_dosen['nip'];
              ?>
                <?php
                $cek_data = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM user WHERE username='$nip'"));
                if ($cek_data > 0) {
                ?>

                <?php } else { ?>
                  <tr>
                    <td><input type="checkbox" name="pilih[]" value="<?= $t_dosen['nip']; ?>"></td>
                    <td><?= $t_dosen['nip']; ?></td>
                    <td><?= $t_dosen['nama_dosen']; ?></td>
                  </tr>
              <?php }
              } ?>
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