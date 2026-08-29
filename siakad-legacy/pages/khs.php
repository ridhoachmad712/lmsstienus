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
                KHS (Kartu Hasil Studi)
              </h2>
              <p style="margin-top: 1em;">Halo <strong><?= $tampil_mhs['nama_mhs']; ?></strong>
              <br>Untuk Melihat Hasil Studi, Silakan Pilih Tahun Akademik Terlebih Dahulu.</p>
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
                                <th>Penasehat Akademik</th>
                                <td>:
                                 <?php 
                                 $pa=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM mhs_has_pa INNER JOIN dosen ON mhs_has_pa.nip=dosen.nip WHERE nim_npm='$username'"));
                                 ?>
                                 <?= $pa['nama_dosen']; ?>
                               </td>
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
      if (isset($_POST['filter'])) {
       ?>
       <div class="card-body">
        <table>
          <tr>
            <td><a class="btn btn-outline-dark" target="_blank" href="cetak/khs?qwe=<?= $id_thn_akademik; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                  <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                  <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                </svg> Cetak KHS
          </a>
        </td>
          </tr>
        </table>
      </div>
    <?php } ?>
    <!-- tampil data -->
    <table
    class="table table-vcenter card-table">
    <thead>
      <tr>
        <th style="text-align: center;">No.</th>
        <th style="text-align: center;">Kode Mk</th>
        <th style="text-align: center;">Nama Mata Kuliah</th>
        <th style="text-align: center;">SKS</th>
        <th style="text-align: center;">Semester</th>
        <th style="text-align: center;">Dosen</th>
        <th style="text-align: center;">Nilai</th>
        <th style="text-align: center;">Grade</th>
        <th style="text-align: center;">Bobot</th>
      </tr>
    </thead>
    <?php 
    if (isset($_POST['filter'])) {
      $no=1;
      $id_thn_akademik=$_POST['id_thn_akademik'];
      $khs=mysqli_query($koneksi,"SELECT * FROM khs_mhs
        INNER JOIN jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
        LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul
        LEFT JOIN tbl_ruangan ON jadwal_mengajar.kode_ruangan=tbl_ruangan.kode_ruangan
        LEFT JOIN tbl_hari ON jadwal_mengajar.id_hari=tbl_hari.id_hari
        LEFT JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.id_thn_akademik='$id_thn_akademik' AND khs_mhs.nim_npm='$username' ORDER BY jadwal_mengajar.id_hari ASC");
      while ($t_khs=mysqli_fetch_array($khs)) {
        ?>
        <tr>
          <td style="text-align: center;"><?= $no++; ?>.</td>
          <td style="text-align: center;"><?= $t_khs['kode_mk']; ?></td>
          <td><?= $t_khs['nama_matkul']; ?></td>
          <td style="text-align: center;"><?= $t_khs['sks']; ?></td>
          <td style="text-align: center;"><?= $t_khs['semester']; ?></td>
          <td><?= $t_khs['nama_dosen']; ?></td>
          <td style="text-align: center;"><?= $t_khs['nilai_akhir']; ?></td>
          <td style="text-align: center;"><?= $t_khs['grade']; ?></td>
          <td style="text-align: center;"><?= $t_khs['bobot']; ?></td>
        </tr> 
      <?php }} ?>
    </table>

    <div class="card-body">
     <?php 
     if (isset($_POST['filter'])) {
      ?>
      <table>
        <tr>
          <td>Total SKS Yang Diambil</td>
          <th>: 
                        <?php 
                        $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM krs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik'"));
                        if ($cek_data > 0) {
                          $t_sks = mysqli_query($koneksi,"SELECT * FROM krs_mhs INNER JOIN 
                          jadwal_mengajar ON krs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
                          LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND krs_mhs.kode_prodi='$kode_prodi' AND krs_mhs.id_thn_akademik='$id_thn_akademik'");
                          while ($pilihan=mysqli_fetch_array($t_sks)) {
                            $jum_sks [] = $pilihan['sks'];
                          }
                          $hasil_sks = array_sum($jum_sks);
                          echo $hasil_sks . " SKS"; // Menambahkan " SKS" setelah angka Total SKS
                        } else {
                          echo $hasil_sks = 0 . " SKS"; // Menambahkan " SKS" setelah angka 0
                        }
                        ?>
                      </th>
                    </tr>
                    <tr>
        <td>Indeks Prestasi Semester</td>
        <th>: 
          <?php 
          $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik'"));
          if ($cek_data > 0) {

            $ipk=mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
              LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.id_thn_akademik='$id_thn_akademik'");
            while ($row_ipk=mysqli_fetch_array($ipk)) {
              ?>
              <?php 
              $sks=$row_ipk['sks'];
              $bobot=$row_ipk['bobot'];
              if ($bobot=="-") {
                $bobot=0;
              }
              ?>
              <?php 
              $mutu [] =$sks*$bobot;
              $tsks [] =$sks;
              ?>
            <?php } ?>
            <?php 
            $hasil_sks=array_sum($tsks);
            $hasil_mutu=array_sum($mutu);
            $ip=$hasil_mutu/$hasil_sks;
            echo number_format($ip,2,',','.');
            ?>
          </th>
        </tr>
      </table>
      <?php } ?>
      </div>
      <div class="alert btn-light"><p>Jika merasa terdapat kekeliruan pada nilai yang telah terinput, hubungi Program Studi.<br>
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