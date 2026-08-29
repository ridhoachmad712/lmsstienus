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
//
$fakultas=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM fakultas_has_jurusan WHERE kode_prodi='$kode_prodi'"));
$kode_fakultas=$fakultas['kode_fakultas'];
// 

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
if (isset($_GET['aksi'])=='hapus') {
  $id_jadwal=$_GET['id'];
  $id_thn_akademik=$_GET['id_thn_akademik'];
  $hapus=mysqli_query($koneksi,"DELETE FROM jadwal_mengajar WHERE id_jadwal='$id_jadwal'");
  echo "<script>window.location='buat_jadwal?qwe=$id_thn_akademik'</script>";
}
if (isset($_POST['filter'])) {
  $thn_masuk=mysqli_real_escape_string($koneksi, $_POST['thn_masuk']);
  $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
}else{
  $thn_masuk="";
}
if (isset($_POST['simpan_sks'])) {
  $nim_npm=$_POST['Pilih'];
  $sks=$_POST['sks'];
  $id_thn_akademik=$_POST['id_thn_akademik'];
  $jumlah_dipilih = count($nim_npm);
  for($x=0;$x<$jumlah_dipilih;$x++){
    $cek=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM pengaturan_sks_mhs WHERE id_thn_akademik='$id_thn_akademik' AND nim_npm='$nim_npm[$x]'"));
    if ($cek > 0) {
      $update=mysqli_query($koneksi,"UPDATE pengaturan_sks_mhs SET sks='$sks[$x]' WHERE id_thn_akademik='$id_thn_akademik' AND nim_npm='$nim_npm[$x]'");
    }else{
      $input=mysqli_query($koneksi,"INSERT INTO pengaturan_sks_mhs VALUES(NULL,'$id_thn_akademik','$nim_npm[$x]','$sks[$x]')");
    }
  }
  echo "<script>window.alert('Sks mahasiswa berhasil disimpan')
  window.location='sks_mhs'</script>";
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
                Pengaturan SKS Mahasiswa
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
                                <td><input type="text" maxlength="4" minlength="4" value="<?= $thn_masuk; ?>" placeholder="Angkatan" name="thn_masuk" class="form-control" required></td>
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


                <form action="" method="post">
                  <?php 
                  if (isset($_POST['filter'])) {
                    ?>
                    <div class="card-body">
                      <table>
                        <input type="hidden" name="id_thn_akademik" value="<?= $id_thn_akademik; ?>">
                        <tr>
                          <td><input type="submit" name="simpan_sks" class="btn btn-info" value="Simpan"></td>
                        </tr>
                      </table>
                    </div>
                  <?php } ?>

                  <?php 
                  if (isset($_POST['filter'])) {
                    ?>
                    <div class="alert btn-dark">
                      <p>Pengaturan SKS Mahasiswa <b style="color: yellow;">Angkatan <?= $thn_masuk; ?> Tahun Akademik 
                        <?php
                        $akademik=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM thn_akademik WHERE id_thn_akademik='$id_thn_akademik'"));
                        ?>
                        <?= $akademik['thn_akademik']; ?> - Semester <?= $akademik['ket']; ?>
                      </b></p>
                    </div>
                  <?php } ?>

                  <!-- tampil data -->
                  <table
                  class="table table-vcenter card-table">
                  <thead>
                    <tr>
                      <th style="text-align: center;">No</th>
                      <th style="text-align: center;">NIM</th>
                      <th style="text-align: center;">Nama Mahasiswa</th>
                      <th>Jumlah SKS yang dapat diambil</th>
                    </tr>
                  </thead>
                  <?php 
                  if (isset($_POST['filter'])) {
                    ?>
                    <?php 
                    $no=1;
                    $mhs=mysqli_query($koneksi,"SELECT * FROM prodi_has_mhs INNER JOIN mahasiswa ON prodi_has_mhs.nim_npm=mahasiswa.nim_npm WHERE kode_prodi='$kode_prodi' AND mahasiswa.thn_masuk='$thn_masuk' ORDER BY mahasiswa.nama_mhs ASC");
                    while ($t_mhs=mysqli_fetch_array($mhs)) {
                      $nim_npm=$t_mhs['nim_npm'];
                      ?>
                      <tr>
                        <td><?= $no++; ?>.</td>
                        <td><?= $t_mhs['nim_npm']; ?><input type="hidden" name="Pilih[]" value="<?= $t_mhs['nim_npm']; ?>"></td>
                        <td><?= $t_mhs['nama_mhs']; ?></td>
                        <td>
                          <?php 
                          $sks=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM pengaturan_sks_mhs WHERE id_thn_akademik='$id_thn_akademik' AND nim_npm='$nim_npm'"));
                          ?>
                          <input type="number" name="sks[]" value="<?= $sks['sks']; ?>" max="24" min="0" style="width: 80px;" autofocus="" class="form-control" placeholder="SKS" required>
                        </td>
                      </tr>
                    <?php }} ?>
                  </table>
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



<!-- tambah jadwal -->
<form action="" method="post">
  <div class="modal modal-blur fade" id="modal-scrollable" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Penjadwalan Kuliah</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <input type="hidden" name="id_thn_akademik" value="<?= $id_thn_akademik; ?>">

          <div class="mb-3">
            <label>Mata Kuliah</label>
            <select class="form-select" name="kode_mk" required>
              <option value="">--Pilih--</option>
              <?php 
              $mata_kuliah=mysqli_query($koneksi,"SELECT * FROM prodi_has_matkul
                INNER JOIN mata_kuliah ON prodi_has_matkul.kode_matkul=mata_kuliah.kode_matkul
                LEFT JOIN tbl_jenis_mk ON mata_kuliah.id_jenis_mk=tbl_jenis_mk.id_jenis_mk WHERE kode_prodi='$kode_prodi'");
              while ($t_matkul=mysqli_fetch_array($mata_kuliah)) {
                ?>
                <option value="<?= $t_matkul['kode_matkul']; ?>"><?= $t_matkul['kode_matkul']; ?> | <?= $t_matkul['nama_matkul']; ?> | Sks <?= $t_matkul['sks']; ?> | Semester <?= $t_matkul['semester']; ?> | <?= $t_matkul['jenis_mk']; ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="mb-3">
            <label>Dosen Pengajar</label>
            <select class="form-select" name="nip" required>
              <option value="">--Pilih--</option>
              <?php 
              $dosen=mysqli_query($koneksi,"SELECT * FROM prodi_has_dosen
                INNER JOIN dosen ON prodi_has_dosen.nip=dosen.nip WHERE kode_prodi='$kode_prodi'");
              while ($t_dosen=mysqli_fetch_array($dosen)) {
                ?>
                <option value="<?= $t_dosen['nip']; ?>"><?= $t_dosen['nama_dosen']; ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="mb-3">
            <label>Ruangan</label>
            <select class="form-select" name="kode_ruangan" required>
              <option value="">--Pilih--</option>
              <?php 
              $ruangan=mysqli_query($koneksi,"SELECT * FROM tbl_ruangan WHERE kode_fakultas='$kode_fakultas' ORDER BY kode_ruangan ASC");
              while ($t_ruangan=mysqli_fetch_array($ruangan)) {
                ?>
                <option value="<?= $t_ruangan['kode_ruangan']; ?>"><?= $t_ruangan['nama_ruangan']; ?> - Lantai <?= $t_ruangan['lantai']; ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="mb-3">
            <label>Hari</label>
            <select class="form-select" name="id_hari" required>
              <option value="">--Pilih--</option>
              <?php 
              $hari=mysqli_query($koneksi,"SELECT * FROM tbl_hari");
              while ($t_hari=mysqli_fetch_array($hari)) {
                ?>
                <option value="<?= $t_hari['id_hari']; ?>"><?= $t_hari['nama_hari']; ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="mb-3">
            <div class="row">
              <div class="col-lg-6">
                <label>Mulai jam</label>
                <input type="time" name="mulai_jam" class="form-control" required>
              </div>
              <div class="col-lg-6">
                <label>Sampai jam</label>
                <input type="time" name="sampai_jam" class="form-control" required>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Tutup</button>
          <input type="submit" name="simpan" class="btn btn-info" value="Tambah">
        </div>
      </div>
    </div>
  </div>
</form>
<!--  -->


<!-- Libs JS -->
<!-- Tabler Core -->
<script src="../dist/js/jquery.js"></script>
<script src="./dist/js/tabler.min.js"></script>
<!-- javascript search data fakultas -->

</body>
</html>