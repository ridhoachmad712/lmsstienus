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
$kode_prodi=$_SESSION['kode_prodi'];
// 
$prodi=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM prodi WHERE kode_prodi='$kode_prodi'"));
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
if (isset($_POST['filter'])) {
  $angkatan=$_POST['angkatan'];
}else{
  $angkatan='';
}
?>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title><?= $r_pengaturan['nama_aplikasi']; ?></title>
  <link rel="shortcut icon" href="../img/<?= $r_pengaturan['logo_aplikasi']; ?>" />
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
                Kartu Hasil Studi Mahasiswa
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

                          <form action="" method="post">
                            <table
                            class="table table-vcenter card-table">
                            <thead>
                              <tr>
                                <th>Program Studi</th>
                                <td>: <?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></td>
                              </tr>
                              <tr>
                                <th>Angkatan</th>
                                <td><input type="text" maxlength="4" value="<?= $angkatan; ?>" placeholder="Angkatan" minlength="4" name="angkatan" class="form-control" required></td>
                              </tr>
                              <tr>
                                <th>Tahun Akademik</th>
                                <td>
                                 <?php 
                                 if (isset($_POST['filter'])) {
                                   $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
                                   ?>
                                   <select name="id_thn_akademik" class="form-select" required>
                                    <?php
                                    $thn_akademik="SELECT * FROM thn_akademik ORDER BY thn_akademik DESC, ket DESC";
                                    $sql_thn_akademik=mysqli_query($koneksi, $thn_akademik);
                                    while ($data_thn_akademik=mysqli_fetch_array($sql_thn_akademik)) {
                                      ?>
                                      <option value="<?= $data_thn_akademik['id_thn_akademik'] ?>" <?= ($data_thn_akademik['id_thn_akademik'] == $id_thn_akademik)? "selected": "" ?>> 
                                        <?= $data_thn_akademik['thn_akademik']?> - Semester <?= $data_thn_akademik['ket']?>
                                      </option>
                                      <?php                     
                                    }
                                    ?>      
                                  </select>
                                  <?php 
                                }elseif (isset($_GET['qwe'])) {
                                  $id_thn_akademik=mysqli_real_escape_string($koneksi, $_GET['qwe']);
                                  ?>
                                  <select name="id_thn_akademik" class="form-select" required>
                                    <?php
                                    $thn_akademik="SELECT * FROM thn_akademik ORDER BY thn_akademik DESC, ket DESC";
                                    $sql_thn_akademik=mysqli_query($koneksi, $thn_akademik);
                                    while ($data_thn_akademik=mysqli_fetch_array($sql_thn_akademik)) {
                                      ?>
                                      <option value="<?= $data_thn_akademik['id_thn_akademik'] ?>" <?= ($data_thn_akademik['id_thn_akademik'] == $id_thn_akademik)? "selected": "" ?>> 
                                        <?= $data_thn_akademik['thn_akademik']?> - Semester <?= $data_thn_akademik['ket']?>
                                      </option>
                                      <?php                     
                                    }
                                    ?>      
                                  </select>

                                <?php }else{ ?>

                                  <select class="form-select" name="id_thn_akademik" required="required">
                                    <option value="">Tahun Akademik</option>
                                    <?php 
                                    $thn_akademik=mysqli_query($koneksi,"SELECT * FROM thn_akademik ORDER BY thn_akademik DESC, ket DESC");
                                    while ($t_akademik=mysqli_fetch_array($thn_akademik)) {
                                      ?>
                                      <option value="<?= $t_akademik['id_thn_akademik']; ?>"><?= $t_akademik['thn_akademik']; ?> - Semester <?= $t_akademik['ket']; ?></option>
                                    <?php } ?>
                                  </select>

                                <?php } ?>
                              </td>
                            </tr>
                            <tr>
                              <th></th>
                              <td><input type="submit" class="btn btn-info" value="Refresh" name="filter"></td>
                            </tr>
                          </thead>
                        </table>
                      </form>

                    </div>
                  </div>
                </div>

              </div>
              <!-- tampil data -->
              <?php 
              if (isset($_POST['filter'])) {
                ?>
                <div class="alert btn-dark">
                  <p>Kartu Hasil Studi Mahasiswa Angkatan <b style="color: yellow;"><?= $angkatan; ?></b></p>
                </div>
              <?php } ?>
              <table class="table table-vcenter card-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>KHS</th>
                  </tr>
                </thead>
                <?php 
                if (isset($_POST['filter'])) {
                  $no=1;
                  $dmhs=mysqli_query($koneksi,"SELECT * FROM prodi_has_mhs INNER JOIN mahasiswa ON prodi_has_mhs.nim_npm=mahasiswa.nim_npm WHERE kode_prodi='$kode_prodi' AND mahasiswa.thn_masuk='$angkatan'");
                  while ($row=mysqli_fetch_array($dmhs)) {
                    ?>
                    <tr>
                      <td><?= $no++; ?>.</td>
                      <td><?= $row['nim_npm']; ?></td>
                      <td style="text-transform: capitalize;"><?= $row['nama_mhs']; ?></td>
                      <td><a href="cetak/khs?qwe=<?= $id_thn_akademik; ?>&nim_npm=<?= $row['nim_npm']; ?>" target="_blank" class="btn">Lihat KHS</a></td>
                    </tr>
                    <?php 
                  }
                }
                ?>
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


<!-- Libs JS -->
<!-- Tabler Core -->
<script src="../dist/js/jquery.js"></script>
<script src="./dist/js/tabler.min.js"></script>
<!-- javascript search data fakultas -->

</body>
</html>