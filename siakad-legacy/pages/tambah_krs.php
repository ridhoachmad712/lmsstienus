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
// MAHASISWA
if ($level=='mhs') {
  $mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa
    INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$username'");
  $tampil_mhs=mysqli_fetch_array($mhs);
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
                Penawaran Mata Kuliah
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
                <div class="table-responsive">
                  <div class="my-2 my-md-0 flex-grow-1 flex-md-grow-0 order-first order-md-last">

                    <div class="col-lg-6">
                      <div class="card">
                        <div class="card-body">

                          <table
                          class="table table-vcenter card-table">
                          <thead>
                            <tr>
                              <th>Tahun/Angkatan</th>
                              <td>: <?= $tampil_mhs['thn_masuk']; ?></td>
                            </tr>
                            <tr>
                              <th>NIM</th>
                              <td>: <?= $tampil_mhs['nim_npm']; ?></td>
                            </tr>
                            <tr>
                              <th>Nama</th>
                              <td>: <?= $tampil_mhs['nama_mhs']; ?></td>
                            </tr>
                            <tr>
                              <th>Jurusan/Program Studi</th>
                              <td>:</td>
                            </tr>
                            <tr>
                              <th>Penasehat Akademik</th>
                              <td>:</td>
                            </tr>
                          </thead>
                        </table>

                      </div>
                    </div>
                  </div>

                </div>

               <!--  <div class="card-body">
                  <table>
                    <tr>
                      <td><a class="btn btn-yellow" href="" style="text-decoration: none;">Cetak KRS</a></td>
                      <td><a class="btn btn-info" href="tambah_krs" style="text-decoration: none;">Tambah KRS</a></td>
                    </tr>
                  </table>
                </div> -->
                <!-- tampil data -->
                <form action="" method="post">
                  <table class="table table-vcenter card-table">
                    <thead>
                      <tr>
                        <th style="text-align: center;">Ambil</th>
                        <th>Kode MK</th>
                        <th>Nama Mata Kuliah</th>
                        <th>sks</th>
                        <th>Dosen</th>
                        <th>Semester</th>
                        <th>Ruang</th>
                        <th>Jam Kuliah</th>
                        <th>Status</th>

                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td style="text-align: center;"><input type="checkbox" name="id_jadwal[]"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                      </tr> 
                    </tbody>
                  </table>

                  <div class="card-body">
                    <table>
                      <tr>
                        <td><input type="submit" name="ambil" value="Ambil" class="btn btn-info"></td>
                        <td><button type="reset" class="btn btn-yellow">Reset</button></td>
                        <td><a href="krs" class="btn btn-danger">Batal</a></td>
                      </tr>
                    </table>
                  </div>

                </form>
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


<!-- Libs JS -->
<!-- Tabler Core -->
<script src="../dist/js/jquery.js"></script>
<script src="./dist/js/tabler.min.js"></script>
<!-- javascript search data fakultas -->

</body>
</html>