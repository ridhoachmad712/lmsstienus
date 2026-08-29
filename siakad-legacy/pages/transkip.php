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
                Transkip Nilai
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



                  </div>

                  <div class="card-body">
                    <table>
                      <tr>
                        <td><a class="btn btn-green" href="cetak/transkip" target="_blank" style="text-decoration: none;">Cetak Transkip Nilai</a></td>          
                      </tr>
                    </table>
                  </div>

                  <table class="basic"  border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="65" rowspan="6"><img style="width: 70px;" src="../img/<?= $r_pengaturan['logo_aplikasi']; ?>"></td>
                      <td width="550" align="center">&nbsp;</td>
                    </tr>

                    <tr>
                      <td align="center" class=fs><strong style="text-transform: uppercase;"><?= $r_pengaturan['nama_kampus']; ?></strong></td>
                    </tr>
                    <tr>
                      <td align="center"><p><?= $r_pengaturan['alamat']; ?> <br />Email: <?= $r_pengaturan['email']; ?>, Tlp./Wa: <?= $r_pengaturan['no_telp']; ?></p></td>
                    </tr>
                  </table>

                  <div class="col-lg-5">
                    <div class="card">
                      <div class="card-body">
                        <table
                        class="table table-vcenter card-table">
                        <thead>

                          <tr>
                            <th>NIM</th>
                            <td>: <?= $tampil_mhs['nim_npm']; ?></td>
                          </tr>
                          <tr>
                            <th>Nama</th>
                            <td style="text-transform: capitalize;">: <?= $tampil_mhs['nama_mhs']; ?></td>
                          </tr>
                          <tr>
                            <th>Tahun/Angkatan</th>
                            <td>: <?= $tampil_mhs['thn_masuk']; ?></td>
                          </tr>
                          <tr>
                            <th>Jurusan/Program Studi</th>
                            <td>: <?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></td>
                          </tr>
                        </thead>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- tampil data -->
                <table class="table table-vcenter card-table">
                  <thead>
                    <tr>
                      <th style="text-align: center;">No</th>
                      <th style="text-align: center;">Kode MK</th>
                      <th style="text-align: center;">Nama Mata Kuliah</th>
                      <th style="text-align: center;">Semester</th>
                      <th style="text-align: center;">SKS</th>
                      <th style="text-align: center;">Nilai Angka</th>
                      <th style="text-align: center;">Grade</th>
                    </tr>
                  </thead>
                  <?php 
                  $no=1;
                  $transkip=mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
                    LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND khs_mhs.grade!='-' ORDER BY mata_kuliah.semester ASC");
                  while ($t_transkip=mysqli_fetch_array($transkip)) {
                    ?>
                    <tr>
                      <td style="text-align: center;"><?= $no++; ?>.</td>
                      <td style="text-align: center;"><?= $t_transkip['kode_mk']; ?></td>
                      <td><?= $t_transkip['nama_matkul']; ?></td>
                      <td style="text-align: center;"><?= $t_transkip['semester']; ?></td>
                      <td style="text-align: center;"><?= $t_transkip['sks']; ?></td>
                      <td style="text-align: center;"><?= $t_transkip['nilai_akhir']; ?></td>
                      <td style="text-align: center;"><?= $t_transkip['grade']; ?></td>
                    </tr> 
                  <?php } ?>
                </table>

                <div class="card-body">
                  <table>
                    <tr>
                      <td>Total SKS</td>
                      <th>: 
                       <?php 
                       $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND grade!='-'"));
                       if ($cek_data > 0) {
                         ?>
                         <?php 
                         $t_sks = mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN 
                          jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
                          LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.grade!='-'");
                         while ($pilihan=mysqli_fetch_array($t_sks)) {
                          $jum_sks [] = $pilihan['sks'];
                        }
                        $hasil_sks = array_sum($jum_sks);
                        echo $hasil_sks . " SKS";
                        ?>
                      <?php } ?>
                    </th>
                  </tr>
                  <tr>
                    <td>Indeks Prestasi Kumulatif</td>
                    <th>: 
                      <?php 
                      $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND grade!='-'"));
                      if ($cek_data > 0) {
                        $ipk=mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
                          LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.grade!='-'");
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
                      <?php } ?>
                    </th>
                  </tr>
                </table>
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