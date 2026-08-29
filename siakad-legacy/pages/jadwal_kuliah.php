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

if (isset($_GET['aksi'])=='hapus') {
  $id_krs=$_GET['id_krs'];
  $data_krs=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM krs_mhs WHERE id_krs='$id_krs'"));
  $id_jadwal=$data_krs['id_jadwal'];
  $id_thn_akademik=$_GET['id_thn_akademik'];
  $hapus=mysqli_query($koneksi,"DELETE FROM krs_mhs WHERE id_krs='$id_krs'");
  $hapus=mysqli_query($koneksi,"DELETE FROM khs_mhs WHERE nim_npm='$username' AND id_thn_akademik='$id_thn_akademik' AND id_jadwal='$id_jadwal' AND kode_prodi='$kode_prodi'");
  echo "<script>window.location='krs?qwe=$id_thn_akademik'</script>";
}
function tgl_indo($tanggal){
  $bulan = array (
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
  );
  $pecahkan = explode('-', $tanggal);
  return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
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
                Jadwal Kuliah
              </h2>
              <p style="margin-top: 1em;">Halo <strong><?= $tampil_mhs['nama_mhs']; ?></strong><br>Untuk Melihat Jadwal Kuliah, Silakan Pilih Tahun Akademik Terlebih Dahulu.</p>
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
                                <th>Nama Mahasiswa</th>
                                <td style="text-transform: capitalize;">: <?= $tampil_mhs['nama_mhs']; ?></td>
                              </tr>
                              <tr>
                                <th>NIM</th>
                                <td>: <?= $tampil_mhs['nim_npm']; ?></td>
                              </tr>
                              <tr>
                                <th>Tahun Angkatan</th>
                                <td>: <?= $tampil_mhs['thn_masuk']; ?></td>
                              </tr>
                              <tr>
                                <th>Program Studi</th>
                                <td>: <?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></td>
                              </tr>
                             <tr>
                              <th>Pilih Tahun Akademik</th>
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
                      <td><input type="submit" class="btn btn-info" value="Pilih Tahun Akademik" name="filter"></td>
                    </tr>
                  </thead>
                </table>
              </form>

            </div>
          </div>
        </div>

      </div>

      <?php 
      if (isset($_POST['filter']) OR (isset($_GET['qwe'])) ) {
        ?>
        <div class="card-body">
          <table>
            <tr>
              <td>
               <?php 
               $jadwal_menawar=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_penawaran WHERE id_thn_akademik='$id_thn_akademik'"));
               $tgl_sekarang=date('Y-m-d');
               $dari_tgl=$jadwal_menawar['dari_tgl'];
               $sampai_tgl=$jadwal_menawar['sampai_tgl'];
               if ($dari_tgl=="0000-00-00") {
                ?>
                <?php 
                if ($tgl_sekarang >= $dari_tgl AND $tgl_sekarang <= $sampai_tgl) {
                  ?>
                  <a class="btn btn-info" href="ambil_jadwal?qwe=<?= $id_thn_akademik; ?>" style="text-decoration: none;">Tambah KRS</a>
                <?php }else{ ?>
                <?php } ?>

              <?php } ?>
            </td>
             <td><a class="btn btn-outline-dark" target="_blank" href="cetak/jadwalkuliah?qwe=<?= $id_thn_akademik; ?>">
             <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                  <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                  <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
              </svg> Cetak Jadwal Kuliah
            </a>
          </td>
          </tr>
        </table>
      </div>
    <?php } ?>


    <?php 
    if (isset($_POST['filter']) OR (isset($_GET['qwe']))) {
      $jadwal_menawar=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_penawaran WHERE id_thn_akademik='$id_thn_akademik'"));
      $dari_tgl=$jadwal_menawar['dari_tgl'];
      if ($dari_tgl=="0000-00-00") {
        ?>
        <div class="alert btn-dark">
          <strong><p><i>Perhatian !!!</i><br>
          Jadwal Penawaran Mata kuliah Belum ada</p></strong>
        </div>
      <?php }else{ ?>
      <?php }} ?>
      <!-- tampil data -->
      <table
      class="table table-vcenter card-table">
      <thead>
        <tr>
         <?php 
         if (isset($_POST['filter']) OR (isset($_GET['qwe']))) {
          ?>
          <?php 
          if ($dari_tgl=="0000-00-00") {
            ?>
          <?php }else{ ?>
            <?php 
            if ($tgl_sekarang >= $dari_tgl AND $tgl_sekarang <= $sampai_tgl) {
              ?>
            <?php }}} ?>
            <th style="text-align: center;">No.</th>
            <th style="text-align: center;">Hari</th>
            <th style="text-align: center;">Waktu Perkuliahan</th>
            <th style="text-align: center;">Nama Mata Kuliah</th>
            <th style="text-align: center;">SKS</th>
            <th style="text-align: center;">Nama Dosen</th>
            <th style="text-align: center;">Ruangan</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (isset($_POST['filter'])) {
            $no=1;
            $id_thn_akademik=$_POST['id_thn_akademik'];
            $krs=mysqli_query($koneksi,"SELECT * FROM krs_mhs
              INNER JOIN jadwal_mengajar ON krs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
              LEFT JOIN  mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul
              LEFT JOIN tbl_ruangan ON jadwal_mengajar.kode_ruangan=tbl_ruangan.kode_ruangan
              LEFT JOIN tbl_hari ON jadwal_mengajar.id_hari=tbl_hari.id_hari
              LEFT JOIN dosen ON jadwal_mengajar.nip=dosen.nip
              WHERE krs_mhs.nim_npm='$username' AND krs_mhs.id_thn_akademik='$id_thn_akademik' AND krs_mhs.kode_prodi='$kode_prodi'
              ORDER BY tbl_hari.id_hari ASC, jadwal_mengajar.mulai_jam ASC");
            while ($t_krs=mysqli_fetch_array($krs)) { 
              $id_krs=$t_krs['id_krs'];
              ?>
              <tr>
                <?php 
                if (isset($_POST['filter']) OR (isset($_GET['qwe']))) {
                  ?>
                  <?php 
                  if ($dari_tgl=="0000-00-00") {
                    ?>
                  <?php }else{ ?>
                    <?php 
                    if ($tgl_sekarang >= $dari_tgl AND $tgl_sekarang <= $sampai_tgl) {
                      ?>
                    <?php }}} ?>
                    <td style="text-align: center;"><?= $no++; ?>.</td>
                    <td style="text-align: center;"><?= $t_krs['nama_hari']; ?></td>
                    <td style="text-align: center;"><?= date('H:i', strtotime($t_krs['mulai_jam'])); ?> - <?= date('H:i', strtotime($t_krs['sampai_jam'])); ?></td>
                    <td><?= $t_krs['nama_matkul']; ?></td>
                    <td style="text-align: center;"><?= $t_krs['sks']; ?> SKS</td>
                    <td><?= $t_krs['nama_dosen']; ?></td>
                    <td style="text-align: center;"><?= $t_krs['nama_ruangan']; ?></td>
                  </tr> 
                      <?php }} ?>
                    </tbody>
                  </table>
                  <!-- -------------------------------------------------- -->
                  <div class="card-body">
                   <?php 
                   if (isset($_POST['filter']) OR (isset($_GET['qwe']))) {
                    ?>
                  </div>
                  <div class="alert btn-light"><p><b>Jadwal Kuliah</b> ini adalah jadwal yang ditetapkan oleh Program Studi pada awal semester,
                  <br>Jadwal dan Ruangan dapat berubah mengikuti kesepakatan Dosen dan Mahasiswa yang telah disetujui oleh Program Studi.<br>
                    </div>
                <?php } ?>
              </div>

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