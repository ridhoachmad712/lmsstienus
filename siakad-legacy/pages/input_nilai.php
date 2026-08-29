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
    header("location: dashboard.php"); // Gantilah 'unauthorized.php' dengan halaman yang sesuai
    exit();
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
  $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
}
if (isset($_POST['simpan'])) {
  $nip=mysqli_real_escape_string($koneksi, $_POST['nip']);
  $kode_mk=mysqli_real_escape_string($koneksi, $_POST['kode_mk']);
  $kode_ruangan=mysqli_real_escape_string($koneksi, $_POST['kode_ruangan']);
  $id_hari=mysqli_real_escape_string($koneksi, $_POST['id_hari']);
  $mulai_jam=mysqli_real_escape_string($koneksi, $_POST['mulai_jam']);
  $sampai_jam=mysqli_real_escape_string($koneksi, $_POST['sampai_jam']);
  $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);

// $cek_jadwal=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar WHERE (id_thn_akademik='$id_thn_akademik' AND id_hari='$id_hari' AND kode_ruangan='$kode_ruangan' AND nip='$nip' AND mulai_jam>='$mulai_jam' AND mulai_jam<='$sampai_jam') OR (id_thn_akademik='$id_thn_akademik' AND id_hari='$id_hari' AND kode_ruangan='$kode_ruangan' AND nip='$nip' AND sampai_jam>='$mulai_jam' AND sampai_jam<='$sampai_jam')"));

  $cek_jadwal=mysqli_num_rows(mysqli_query($koneksi,"
    SELECT * FROM jadwal_mengajar
    WHERE 
    id_thn_akademik='$id_thn_akademik' AND
    (((mulai_jam<='$mulai_jam') AND ('$mulai_jam'<=sampai_jam)) OR ((mulai_jam<='$sampai_jam') AND ('$sampai_jam'<=sampai_jam))) AND
    id_hari='$id_hari' AND
    (
    nip='$nip' OR (nip='$nip' AND kode_ruangan='$kode_ruangan') OR kode_ruangan='$kode_ruangan'
    )
    "));
  if ($cek_jadwal > 0) {
    echo "<script>window.alert('Jadwal Gagal ditambah karna bentrok dengan jadwal lain !!!')
    window.location='buat_jadwal?qwe=$id_thn_akademik'</script>";
  }else{
    mysqli_query($koneksi,"INSERT INTO jadwal_mengajar VALUES(NULL,'$kode_prodi','$nip','$id_thn_akademik','$kode_mk','$kode_ruangan','$id_hari','$mulai_jam','$sampai_jam')");
    echo "<script>window.alert('Jadwal Berhasil ditambah !!!')
    window.location='buat_jadwal?qwe=$id_thn_akademik'</script>";
  }
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
                Input Nilai Mahasiswa
              </h2>
              Nilai mahasiswa dapat diinput langsung oleh Dosen Mata Kuliah.<br>
              Admin Program Studi dapat menginput nilai mahasiswa apabila belum diisi oleh Dosen Mata Kuliah.
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

                <?php 
                if (isset($_POST['filter']) OR (isset($_GET['qwe']))) {
                  $jadwal_input_nilai=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_input_nilai WHERE id_thn_akademik='$id_thn_akademik'"));
                  $dari_tgl=$jadwal_input_nilai['dari_tgl'];
                  if ($dari_tgl=="0000-00-00") {
                    ?>
                    <div class="alert btn-dark">
                      <strong><p>Perhatian!<br>
                      Jadwal Penginputan Nilai Mata kuliah Belum ada</p></strong>
                    </div>
                  <?php }else{ ?>
                   <div class="alert btn-dark">
                    <p>Perhatian!<br>
                      Jadwal Penginputan Nilai Mata kuliah dibuka <b style="color: yellow;"><?= tgl_indo($dari_tgl); ?> Sampai <?= tgl_indo($jadwal_input_nilai['sampai_tgl']); ?></b> 
                      <br>Jadwal pengimputan nilai hanya dapat diatur oleh Admin Akademik.</p>
                    </div>
                  <?php }} ?>
                  <!-- tampil data -->
                  <table
                  class="table table-vcenter card-table">
                  <thead>
                    <tr>
                      <th style="text-align: center;">No</th>
                      <th style="text-align: center;">Kode</th>
                      <th style="text-align: center;">Mata Kuliah</th>
                      <th style="text-align: center;">SKS</th>
                      <th style="text-align: center;">Semester</th>
                      <th style="text-align: center;">Peserta</th>
                      <th style="text-align: center;">Dosen Pengajar</th>
                      <th style="text-align: center;">Input Nilai</th>
                    </tr>
                  </thead>
                  <?php 
                  if (isset($_POST['filter'])) {
                    $no=1;
                    $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
                    $jadwal=mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar
                      INNER JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul
                      INNER JOIN tbl_ruangan ON jadwal_mengajar.kode_ruangan=tbl_ruangan.kode_ruangan
                      INNER JOIN tbl_hari ON jadwal_mengajar.id_hari=tbl_hari.id_hari
                      INNER JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE id_thn_akademik='$id_thn_akademik' AND kode_prodi='$kode_prodi' ORDER BY mata_kuliah.semester ASC");
                    while ($t_jadwal=mysqli_fetch_array($jadwal)) {
                      $id_jadwal=$t_jadwal['id_jadwal'];
                      ?>
                      <tr>
                        <td style="text-align: center;"><?= $no++; ?>.</td>
                        <td style="text-align: center;"><?= $t_jadwal['kode_mk']; ?></td>
                        <td><?= $t_jadwal['nama_matkul']; ?></td>
                        <td style="text-align: center;"><?= $t_jadwal['sks']; ?> SKS</td>
                        <td style="text-align: center;"><?= $t_jadwal['semester']; ?></td>
                        <td style="text-align: center;">
                          <span class="btn btn-green">
                            <?php 
                            $jml_mhs=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik' AND id_jadwal='$id_jadwal'"));
                            echo $jml_mhs;
                            ?> Mahasiswa
                          </span>
                        </td>
                        <td><?= $t_jadwal['nama_dosen']; ?></td>
                        <td style="text-align: center;">
                          <?php 
                          $jadwal_input_nilai=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_input_nilai WHERE id_thn_akademik='$id_thn_akademik'"));
                          $tgl_sekarang=date('Y-m-d');
                          $dari_tgl=$jadwal_input_nilai['dari_tgl'];
                          $sampai_tgl=$jadwal_input_nilai['sampai_tgl'];
                          if ($dari_tgl=="0000-00-00") {
                            ?>
                            <button class="btn btn-secondary disabled">Input Nilai</button>
                          <?php }else{ ?>

                            <?php 
                            if ($tgl_sekarang >= $dari_tgl AND $tgl_sekarang <= $sampai_tgl) {
                              ?>
                              <a href="get_input_nilai?qwe=<?= $id_jadwal; ?>" class="btn btn-info">Input Nilai</a>
                            <?php }else{ ?>
                              <button class="btn btn-secondary disabled">Input Nilai</button>
                            <?php } ?>

                          <?php } ?>
                        </td>
                    </tr> 
                  <?php }}elseif(isset($_GET['qwe'])) { 
                    $no=1;
                    $id_thn_akademik=mysqli_real_escape_string($koneksi, $_GET['qwe']);
                    $jadwal=mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar
                      INNER JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul
                      INNER JOIN tbl_ruangan ON jadwal_mengajar.kode_ruangan=tbl_ruangan.kode_ruangan
                      INNER JOIN tbl_hari ON jadwal_mengajar.id_hari=tbl_hari.id_hari
                      INNER JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE id_thn_akademik='$id_thn_akademik' AND kode_prodi='$kode_prodi' ORDER BY mata_kuliah.semester ASC");
                    while ($t_jadwal=mysqli_fetch_array($jadwal)) {
                      $id_jadwal=$t_jadwal['id_jadwal'];
                      ?>

                      <tr>
                        <td>
                         <?php 
                         $jadwal_input_nilai=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_input_nilai WHERE id_thn_akademik='$id_thn_akademik'"));
                         $tgl_sekarang=date('Y-m-d');
                         $dari_tgl=$jadwal_input_nilai['dari_tgl'];
                         $sampai_tgl=$jadwal_input_nilai['sampai_tgl'];
                         if ($dari_tgl=="0000-00-00") {
                          ?>
                          <button class="btn btn-secondary disabled">Input Nilai</button>
                        <?php }else{ ?>
                          <?php 
                          if ($tgl_sekarang >= $dari_tgl AND $tgl_sekarang <= $sampai_tgl) {
                            ?>
                            <a href="get_input_nilai?qwe=<?= $id_jadwal; ?>" class="btn btn-info">Input Nilai</a>
                          <?php }else{ ?>
                            <button class="btn btn-secondary disabled">Input Nilai</button>
                          <?php } ?>
                        <?php } ?>
                      </td>
                      <td><?= $no++; ?>.</td>
                      <td><?= $t_jadwal['kode_mk']; ?></td>
                      <td><?= $t_jadwal['nama_matkul']; ?></td>
                      <td>
                       <span class="btn btn-yellow">
                        <?php 
                        $jml_mhs=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik' AND id_jadwal='$id_jadwal'"));
                        echo $jml_mhs;
                        ?>
                      </span>
                    </td>
                    <td><?= $t_jadwal['sks']; ?></td>
                    <td><?= $t_jadwal['nama_ruangan']; ?> lantai <?= $t_jadwal['lantai']; ?></td>
                    <td><?= $t_jadwal['semester']; ?></td>
                    <td><?= $t_jadwal['nama_hari']; ?> - <?= $t_jadwal['mulai_jam']; ?> - <?= $t_jadwal['sampai_jam']; ?></td>
                    <td><?= $t_jadwal['nama_dosen']; ?>
                  </td>
                </tr> 
              <?php }} ?>
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