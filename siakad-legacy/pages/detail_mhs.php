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
$nim_npm=$_GET['nim_npm'];
// data mhs
$mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa
  INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
  INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$nim_npm'");
$row_mhs=mysqli_fetch_array($mhs);
$foto_mhs=$row_mhs['foto_mhs'];
// 
// data orgtua
$orgtua=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM tbl_org_tua WHERE nim_npm='$nim_npm'"));
// 
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
  $hapus=mysqli_query($koneksi,"DELETE FROM thn_akademik WHERE id_thn_akademik='$id'");
  $hapus2=mysqli_query($koneksi,"DELETE FROM jadwal_penawaran WHERE id_thn_akademik='$id'");
  if ($hapus==1) {
    echo "<script>window.alert('Berhasil dihapus !!!')
    window.location='thn_akademik'</script>";
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
                Detail data mahasiswa
              </h2>
            </div>
          </div>
        </div>
      </div>


      <div class="page-body">
        <div class="container-xl">
          <div class="card">
            <div class="card-body">
              <a href="mhs" class="btn btn-secondary">Kembali</a>
            </div>
          </div>
          <div class="row row-cards">
            <div class="col-lg-12">
              <div class="card">
                <ul class="nav nav-tabs" data-bs-toggle="tabs">
                  <li class="nav-item">
                    <a href="#tabs-home-7" class="nav-link active" data-bs-toggle="tab">Data Diri</a>
                  </li>
                  <li class="nav-item">
                    <a href="#tabs-profile-7" class="nav-link" data-bs-toggle="tab">Data Orang Tua</a>
                  </li>
                 <!--  <li class="nav-item ms-auto">
                    <a href="#tabs-settings-7" class="nav-link" title="Settings" data-bs-toggle="tab">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><circle cx="12" cy="12" r="3" /></svg>
                    </a>
                  </li> -->
                </ul>
                <div class="card-body">
                  <div class="tab-content">
                    <div class="tab-pane active show" id="tabs-home-7">
                      <div>
                        <table class="table table-vcenter card-table">
                          <tr>
                            <td>Nim / Npm</td>
                            <td>: <?= $row_mhs['nim_npm']; ?></td>
                            <td rowspan="4">
                              <?php 
                              if ($foto_mhs=='') {
                                ?>
                                <img style="width: 85pt;" src="foto_mhs/avatar-blank.png"></td>
                              <?php }else{ ?>
                                <img style="width: 85pt;" src="foto_mhs/<?= $row_mhs['foto_mhs']; ?>"></td>
                              <?php } ?>
                            </td>
                          </tr>
                          <tr>
                            <td>Nama </td>
                            <td>: <?= $row_mhs['nama_mhs']; ?></td>
                          </tr>
                          <tr>
                            <td>Tahun Masuk </td>
                            <td>: <?= $row_mhs['thn_masuk']; ?></td>
                          </tr>
                          <tr>
                            <td>Tempat Tanggal lahir</td>
                            <td>: <?= $row_mhs['tempat_lhr']; ?>, <?= tgl_indo($row_mhs['tgl_lhr_mhs']); ?></td>
                          </tr>
                          <tr>
                            <td>Jenis Kelamin</td>
                            <td>: <?= $row_mhs['jenis_kelamin']; ?></td>
                          </tr>
                          <tr>
                            <td>Agama</td>
                            <td>: <?= $row_mhs['agama']; ?></td>
                          </tr>
                          <tr>
                            <td>Email</td>
                            <td>: <?= $row_mhs['email']; ?></td>
                          </tr>
                          <tr>
                            <td>Alamat mahasiswa</td>
                            <td>: <?= $row_mhs['alamat_mhs']; ?></td>
                          </tr>
                          <tr>
                            <td>No Telp mahasiswa</td>
                            <td>: <?= $row_mhs['no_telp_mhs']; ?></td>
                          </tr>
                          <tr>
                            <td>Status Mahasiswa</td>
                            <td>: <?= $row_mhs['status_mhs']; ?></td>
                          </tr>
                        </table>
                      </div>
                    </div>
                    <div class="tab-pane" id="tabs-profile-7">
                      <div>
                       <table class="table table-vcenter card-table">
                        <tr>
                          <td>No KK</td>
                          <td>: <?= $orgtua['no_kk']; ?></td>
                        </tr>
                        <tr>
                          <td>Nama Ayah </td>
                          <td>: <?= $orgtua['nama_ayah']; ?></td>
                        </tr>
                        <tr>
                          <td>Tempat Tanggal lahir ayah </td>
                          <td>: <?= $orgtua['tmp_lhr_ayah']; ?>, <?= $orgtua['tgl_lhr_ayah']; ?></td>
                        </tr>
                        <tr>
                          <td>Pekerjaan Ayah</td>
                          <td>: <?= $orgtua['pekerjaan_ayah']; ?></td>
                        </tr>
                        <tr>
                          <td>Penghasilan ayah</td>
                          <td>: <?= $orgtua['penghasilan_ayah']; ?></td>
                        </tr>
                        <tr>
                          <td>Pendidikan Ayah</td>
                          <td>: <?= $orgtua['pend_ayah']; ?></td>
                        </tr>
                        <tr>
                          <td>Nama Ibu</td>
                          <td>: <?= $orgtua['nama_ibu']; ?></td>
                        </tr>
                        <tr>
                          <td>Tenpat tanggal lahir ibu</td>
                          <td>: <?= $orgtua['tmp_lhr_ibu']; ?>, <?= $orgtua['tgl_lhr_ibu']; ?></td>
                        </tr>
                        <tr>
                          <td>Pekerjaan Ibu</td>
                          <td>: <?= $orgtua['pekerjaan_ibu']; ?></td>
                        </tr>
                        <tr>
                          <td>Pendidikan Ibu</td>
                          <td>: <?= $orgtua['pend_ibu']; ?></td>
                        </tr>
                        <tr>
                          <td>Alamat Org tua</td>
                          <td>: <?= $orgtua['alamat_org_tua']; ?></td>
                        </tr>
                        <tr>
                          <td>No Telp Orgtua</td>
                          <td>: <?= $orgtua['no_telp_orgtua']; ?></td>
                        </tr>
                      </table>
                    </div>
                  </div>
                  <div class="tab-pane" id="tabs-settings-7">
                    <div>Donec ac vitae diam amet vel leo egestas consequat rhoncus in luctus amet, facilisi sit mauris accumsan nibh habitant senectus</div>
                  </div>
                </div>
              </div>
            </div>
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