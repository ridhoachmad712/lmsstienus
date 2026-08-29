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
$id_thn_akademik=$_GET['qwe'];
$thn_akademik=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM thn_akademik WHERE id_thn_akademik='$id_thn_akademik'"));
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
 $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
}
?>
<!-- batas -->
<?php 
$cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM krs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik'"));
if ($cek_data > 0) {
 ?>
 <?php 
 $t_sks = mysqli_query($koneksi,"SELECT * FROM krs_mhs INNER JOIN 
  jadwal_mengajar ON krs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
  LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND krs_mhs.kode_prodi='$kode_prodi' AND krs_mhs.id_thn_akademik='$id_thn_akademik'");
 while ($pilihan=mysqli_fetch_array($t_sks)) {
  $jum_sks [] = $pilihan['sks'];
}
$hasil_sks = array_sum($jum_sks);
?>
<?php }else{
  $hasil_sks=0;
} 
?>
<!--  -->
<?php 
$sks_diambil=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM pengaturan_sks_mhs WHERE id_thn_akademik='$id_thn_akademik' AND nim_npm='$username'"));
$sks_diambil['sks'];
?>
<!--  -->
<?php 
$sisa_sks=$sks_diambil['sks']-$hasil_sks;
?>
<!--  -->
<?php 
if (isset($_POST['simpan'])) {
  $id_jadwal=$_POST['pilih'];
  $jumlah_dipilih = count($id_jadwal);
  for($x=0;$x<$jumlah_dipilih;$x++){
    $sks=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar 
      INNER JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE id_jadwal='$id_jadwal[$x]'"));
    $sksdiambil [] =$sks['sks'];
  }
  $hasil_sks = array_sum($sksdiambil);
  if ($hasil_sks > $sisa_sks) {
    echo "<script>window.alert('Gagal !!! Jumlah Sks yang diambil melebihi batas Maximum Sks anda, kurangi mata kuliah yang ingin diambil')</script>";
  }else{
    for($j=0;$j<$jumlah_dipilih;$j++){
      $input=mysqli_query($koneksi,"INSERT INTO krs_mhs VALUES(NULL,'$kode_prodi','$id_jadwal[$j]','$username','$id_thn_akademik')");
      $input=mysqli_query($koneksi,"INSERT INTO khs_mhs VALUES('$kode_prodi','$username','$id_jadwal[$j]','$id_thn_akademik','0','0','0','-','-','-')");
      echo "<script>window.alert('$jumlah_dipilih Mata kuliah Berhasil diambil')
      window.location='krs?qwe=$id_thn_akademik'</script>";
    }
  } 
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
                Ambil Mata Kuliah
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
                              <th>Jurusan/Program Studi</th>
                              <td>: <?= $prodi['nama_prodi']; ?></td>
                            </tr>
                            <tr>
                              <th>Tahun Akademik</th>
                              <td> 
                                : <?= $thn_akademik['thn_akademik']; ?> - Semester <?= $thn_akademik['ket']; ?>
                              </td>
                            </tr>
                          </thead>
                        </table>

                      </div>
                    </div>
                  </div>

                </div>






                <form action="" method="post">

                  <div class="card-body">
                    <table>
                      <tr>
                        <td><a class="btn" href="krs?qwe=<?= $id_thn_akademik; ?>" style="text-decoration: none;">Kembali</a></td>
                        <td><input type="submit" name="simpan" class="btn btn-info" value="Ambil"></td>
                      </tr>
                    </table>
                  </div>

                  <table class="table table-vcenter card-table">
                    <thead>
                      <tr>
                        <th style="text-align: center;">Ambil</th>
                        <th>No</th>
                        <th>Kode Mk</th>
                        <th>Nama Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Semester</th>
                        <th>Dosen Pengajar</th>
                      </tr>
                    </thead>
                    <?php if(isset($_GET['qwe'])) { 
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

                        <?php 
                        $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM krs_mhs WHERE nim_npm='$username' AND id_jadwal='$id_jadwal'"));
                        if ($cek_data > 0) {
                          ?>

                        <?php }else{ ?>

                          <tr>
                            <td style="text-align: center;">
                              <input type="checkbox" name="pilih[]" value="<?= $id_jadwal; ?>">
                            </td>
                            <td><?= $no++; ?>.</td>
                            <td><?= $t_jadwal['kode_mk']; ?></td>
                            <td><?= $t_jadwal['nama_matkul']; ?></td>
                            <td><?= $t_jadwal['sks']; ?></td>
                            <td><?= $t_jadwal['semester']; ?></td>
                            <td><?= $t_jadwal['nama_dosen']; ?>
                          </td>
                        </tr> 
                      <?php } ?>
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