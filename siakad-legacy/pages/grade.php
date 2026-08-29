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
if (isset($_POST['tambah'])) {
  $grade=mysqli_real_escape_string($koneksi, $_POST['grade']);
  $bobot=mysqli_real_escape_string($koneksi, $_POST['bobot']);
  $nilai_awal=mysqli_real_escape_string($koneksi, $_POST['nilai_awal']);
  $nilai_akhir=mysqli_real_escape_string($koneksi, $_POST['nilai_akhir']);
  $ket=mysqli_real_escape_string($koneksi, $_POST['ket']);
  $cekdata=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM tbl_grade WHERE grade='$grade'"));
  if ($cekdata==1) {
    echo "<script>window.alert('Maaf Data Grade sudah ada !!!')
    window.location='grade'</script>";
  }else{
    $input=mysqli_query($koneksi,"INSERT INTO tbl_grade VALUES(NULL,'$grade','$bobot','$nilai_awal','$nilai_akhir','$ket')");
    echo "<script>window.alert('Grade $grade Berhasil di tambahkan !!!')
    window.location='grade'</script>";
  }
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
  $id=mysqli_real_escape_string($koneksi, $_GET['id']);
  $hapus=mysqli_query($koneksi,"DELETE FROM tbl_grade WHERE id_grade='$id'");
  if ($hapus==1) {
    echo "<script>window.alert('Berhasil dihapus !!!')
    window.location='grade'</script>";
  }
}
if (isset($_POST['set_jadwal'])) {
  $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
  $dari_tgl=mysqli_real_escape_string($koneksi, $_POST['dari_tgl']);
  $sampai_tgl=mysqli_real_escape_string($koneksi, $_POST['sampai_tgl']);
  $update=mysqli_query($koneksi,"UPDATE jadwal_penawaran SET dari_tgl='$dari_tgl', sampai_tgl='$sampai_tgl' WHERE id_thn_akademik='$id_thn_akademik'");
  echo "<script>window.alert('Jadwal Penawaran Berhasil di atur !!!')
  window.location='thn_akademik'</script>";
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
                Data Grade Nilai
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
                  <a class="btn" data-bs-toggle="offcanvas" href="#offcanvasStart" role="button" aria-controls="offcanvasStart">
                    Tambah Data
                  </a>
                </div>
              </div>

              <div class="table-responsive">
                <!-- tampil data -->
                <table class="table table-vcenter card-table">
                  <thead>
                    <th>NO</th>
                    <th>GRADE</th> 
                    <th>BOBOT</th>
                    <th>POIN</th>
                    <th>keterangan</th>
                    <th>Opsi</th>
                  </thead>
                  <tbody>
                    <?php 
                    $no=1;
                    $grade=mysqli_query($koneksi,"SELECT * FROM tbl_grade ORDER BY grade ASC");
                    while ($t_grade=mysqli_fetch_array($grade)) {
                      ?>
                      <tr>
                        <td><?= $no++ ?>.</td>
                        <td><?= $t_grade['grade']; ?></td>
                        <td><?= $t_grade['bobot']; ?></td>
                        <td>Poin <?= $t_grade['nilai_awal']; ?> - <?= $t_grade['nilai_akhir']; ?></td>
                        <td><?= $t_grade['ket']; ?></td>
                        <td>
                          <a onclick="return confirm('Hapus data ini ?')" href="grade?aksi=hapus&id=<?= $t_grade['id_grade']; ?>">
                            <!-- Download SVG icon from http://tabler-icons.io/i/trash -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                          </a>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
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

<div class="page-body">
  <div class="container-xl">



    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasStart" aria-labelledby="offcanvasStartLabel">
      <form action="" method="post">
        <div class="offcanvas-header">
          <h2 class="offcanvas-title" id="offcanvasStartLabel">Tambah Grade</h2>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
         <div>
          <div class="mb-3">
            <label>Grade</label> 
            <input type="text" name="grade" placeholder="Grade" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Bobot</label> 
            <input type="text" name="bobot" placeholder="Bobot" class="form-control" required>
          </div>
          <div class="mb-3">
            <div class="row">
              <label>Rentang Poin</label>
              <div class="col-lg-6">
                <input type="number" name="nilai_awal" class="form-control" required>
              </div>
              <div class="col-lg-6">
                <input type="number" name="nilai_akhir" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label>Keterangan</label> 
            <input type="text" name="ket" placeholder="LULUS / TIDAK LULUS" class="form-control" required>
          </div>

        </div>
        <div class="mt-3">
          <button class="btn" type="submit" name="tambah">
            Simpan
          </button>
          <button class="btn" type="button" data-bs-dismiss="offcanvas">
            Tutup
          </button>
        </div>
      </div>
    </form>
  </div>

</div>
</div>
<!-- Libs JS -->
<!-- Tabler Core -->
<script src="../dist/js/jquery.js"></script>
<script src="./dist/js/tabler.min.js"></script>
<!-- javascript search data fakultas -->

</body>
</html>