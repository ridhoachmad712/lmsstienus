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
}else{
  $nim_npm=$_GET['qaz'];
  $mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa
    INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$nim_npm'");
  $tampil_mhs=mysqli_fetch_array($mhs);
  $nim_npm=$tampil_mhs['nim_npm'];
  $prodi=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM prodi_has_mhs INNER JOIN prodi ON prodi_has_mhs.kode_prodi=prodi.kode_prodi WHERE nim_npm='$nim_npm'"));
  $kode_prodi=$prodi['kode_prodi'];
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
                Kartu Hasil Studi
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

                    <div class="col-lg-8">
                      <div class="card">
                        <div class="card-body">

                          <form action="" method="post">
                            <table
                            class="table table-vcenter card-table">
                            <thead>
                              <tr>
                                <th>Tahun/Angkatan</th>
                                <td>: <?= $tampil_mhs['thn_masuk']; ?></td>
                                <td rowspan="6">
                                  <?php 
                                  if ($tampil_mhs['foto_mhs']=='') {
                                    ?>
                                    <img style="border-radius: 50%; width: 100px; height: 104px; overflow: hidden;" src="foto_mhs/avatar-blank.png">
                                  <?php }else{ ?>
                                    <img style="border-radius: 50%; width: 100px; height: 104px; overflow: hidden;" src="foto_mhs/<?= $tampil_mhs['foto_mhs']; ?>">
                                  <?php } ?>
                                </td>
                              </tr>
                              <tr>
                                <th>NIM</th>
                                <td>: <?= $tampil_mhs['nim_npm']; ?></td>
                              </tr>
                              <tr>
                                <th>Nama</th>
                                <td style="text-transform: capitalize;">: <?= $tampil_mhs['nama_mhs']; ?></td>
                              </tr>
                              <tr>
                                <th>Jurusan/Program Studi</th>
                                <td>: <?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></td>
                              </tr>
                              <tr>
                                <th>Penasehat Akademik</th>
                                <td>: 
                                 <?php 
                                 $pa=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM mhs_has_pa INNER JOIN dosen ON mhs_has_pa.nip=dosen.nip WHERE nim_npm='$nim_npm'"));
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
                                  $thn_akademik="SELECT * FROM thn_akademik ORDER BY thn_akademik DESC";
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
                                $thn_akademik="SELECT * FROM thn_akademik ORDER BY thn_akademik DESC";
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
                              $thn_akademik=mysqli_query($koneksi,"SELECT * FROM thn_akademik ORDER BY thn_akademik DESC");
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

      <?php 
      if (isset($_POST['filter'])) {
       ?>
       <!-- <div class="card-body">
        <table>
          <tr>
            <td>
              <a class="btn btn-yellow" target="_blank" href="cetak/khs?qwe=<?= $id_thn_akademik; ?>" style="text-decoration: none;">Cetak KHS</a>
            </td>
          </tr>
        </table>
      </div> -->
    <?php } ?>
    <!-- tampil data -->
    <table
    class="table table-vcenter card-table">
    <thead>
      <tr>
        <th>No</th>
        <th>Kode Mk</th>
        <th>Nama Mata Kuliah</th>
        <th>sks</th>
        <th>Ruang</th>
        <th>Semester</th>
        <th>Jam Kuliah</th>
        <th>Dosen</th>
        <th>Nilai</th>
        <th>Grade</th>
        <th>Bobot</th>
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
        LEFT JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.id_thn_akademik='$id_thn_akademik' AND khs_mhs.nim_npm='$nim_npm' ORDER BY jadwal_mengajar.id_hari ASC");
      while ($t_khs=mysqli_fetch_array($khs)) {
        ?>
        <tr>
          <td><?= $no++; ?>.</td>
          <td><?= $t_khs['kode_mk']; ?></td>
          <td><?= $t_khs['nama_matkul']; ?></td>
          <td><?= $t_khs['sks']; ?></td>
          <td><?= $t_khs['nama_ruangan']; ?> - Lantai <?= $t_khs['lantai']; ?></td>
          <td><?= $t_khs['semester']; ?></td>
          <td><?= $t_khs['nama_hari']; ?> - <?= $t_khs['mulai_jam']; ?> - <?= $t_khs['sampai_jam']; ?></td>
          <td><?= $t_khs['nama_dosen']; ?></td>
          <td><?= $t_khs['nilai_akhir']; ?></td>
          <td><?= $t_khs['grade']; ?></td>
          <td><?= $t_khs['bobot']; ?></td>
        </tr> 
      <?php }} ?>
    </table>

    <div class="card-body">
     <?php 
     if (isset($_POST['filter'])) {
      ?>
      <table>
        <tr>
          <td>Total SKS yang diambil</td>
          <th>: 
           <?php 
           $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE nim_npm='$nim_npm' AND kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik'"));
           if ($cek_data > 0) {
             ?>
             <?php 
             $t_sks = mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN 
              jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
              LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$nim_npm' AND khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.id_thn_akademik='$id_thn_akademik'");
             while ($pilihan=mysqli_fetch_array($t_sks)) {
              $jum_sks [] = $pilihan['sks'];
            }
            $hasil_sks = array_sum($jum_sks);
            echo $hasil_sks;
            ?>
          <?php } ?>
        </th>
      </tr>
      <tr>
        <td>Indeks Prestasi Semester</td>
        <th>: 
          <?php 
          $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE nim_npm='$nim_npm' AND kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik'"));
          if ($cek_data > 0) {

            $ipk=mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
              LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$nim_npm' AND khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.id_thn_akademik='$id_thn_akademik'");
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
      <?php } ?>
    </table>
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