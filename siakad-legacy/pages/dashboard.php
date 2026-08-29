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
include "../config/koneksi.php";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$level = $_SESSION['level'];
$kode_prodi = $_SESSION['kode_prodi'];
// 
$prodi = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM prodi WHERE kode_prodi='$kode_prodi'"));
if (!isset($_SESSION["login"])) {
  header("location: login");
} else {
  $cek_user = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'"));
  if ($cek_user !== 1) {
    header("location: login");
  }
}
// --------------------------------------------------
// pengaturan aplikasi 
$pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan = mysqli_fetch_array($pengaturan);


// MAHASISWA
if ($level == 'mhs') {
  $mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa
    INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$username'");
  $tampil_mhs = mysqli_fetch_array($mhs);
  $foto_mhs = $tampil_mhs['foto_mhs'];
}
//  org tua
$orgtua = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM tbl_org_tua WHERE nim_npm='$username'"));
// 

// MAHASISWA
if ($level == 'dosen') {
  $dosen = mysqli_query($koneksi, "SELECT * FROM dosen
    INNER JOIN tbl_jk ON dosen.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON dosen.id_agama=tbl_agama.id_agama WHERE nip='$username'");
  $tampil_dosen = mysqli_fetch_array($dosen);
  $foto_dosen = $tampil_dosen['foto_dosen'];
}

if (isset($_POST['simpan_mhs'])) {
  // update mhs
  $nama_mhs = $_POST['nama_mhs'];
  $thn_masuk = $_POST['thn_masuk'];
  $id_jk = $_POST['id_jk'];
  $tempat_lhr = $_POST['tempat_lhr'];
  $tgl_lhr_mhs = $_POST['tgl_lhr_mhs'];
  $id_agama = $_POST['id_agama'];
  $email = $_POST['email'];
  $lulusan_jalur = $_POST['lulusan_jalur'];
  $sekolah_asal = $_POST['sekolah_asal'];
  $alamat_mhs = $_POST['alamat_mhs'];
  $no_telp_mhs = $_POST['no_telp_mhs'];

  // DATA ORG TUA

  $no_kk = $_POST['no_kk'];
  $nama_ayah = $_POST['nama_ayah'];
  $tmp_lhr_ayah = $_POST['tmp_lhr_ayah'];
  $tgl_lhr_ayah = $_POST['tgl_lhr_ayah'];
  $pekerjaan_ayah = $_POST['pekerjaan_ayah'];
  $penghasilan_ayah = $_POST['penghasilan_ayah'];
  $pend_ayah = $_POST['pend_ayah'];

  $nama_ibu = $_POST['nama_ibu'];
  $tmp_lhr_ibu = $_POST['tmp_lhr_ibu'];
  $tgl_lhr_ibu = $_POST['tgl_lhr_ibu'];
  $pekerjaan_ibu = $_POST['pekerjaan_ibu'];
  $penghasilan_ibu = $_POST['penghasilan_ibu'];
  $pend_ibu = $_POST['pend_ibu'];
  $alamat_org_tua = $_POST['alamat_org_tua'];
  $no_telp_orgtua = $_POST['no_telp_orgtua'];

  $update = mysqli_query($koneksi, "UPDATE mahasiswa SET nama_mhs='$nama_mhs', thn_masuk='$thn_masuk', id_jk='$id_jk', tempat_lhr='$tempat_lhr', tgl_lhr_mhs='$tgl_lhr_mhs', id_agama='$id_agama', email='$email', lulusan_jalur='$lulusan_jalur', sekolah_asal='$sekolah_asal', alamat_mhs='$alamat_mhs', no_telp_mhs='$no_telp_mhs' WHERE nim_npm='$username'");

  $cek_data = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM tbl_org_tua WHERE nim_npm='$username'"));

  if ($cek_data == 0) {
    $input = mysqli_query($koneksi, "INSERT INTO tbl_org_tua VALUES('$username','$no_kk','$nama_ayah','$tmp_lhr_ayah','$tgl_lhr_ayah','$pekerjaan_ayah','$penghasilan_ayah','$pend_ayah','$nama_ibu','$tmp_lhr_ibu','$tgl_lhr_ibu','$pekerjaan_ibu','$penghasilan_ibu','$pend_ibu','$alamat_org_tua','$no_telp_orgtua')");
  } else {
    $update = mysqli_query($koneksi, "UPDATE tbl_org_tua SET no_kk='$no_kk', nama_ayah='$nama_ayah', tmp_lhr_ayah='$tmp_lhr_ayah', tgl_lhr_ayah='$tgl_lhr_ayah', pekerjaan_ayah='$pekerjaan_ayah', penghasilan_ayah='$penghasilan_ayah', pend_ayah='$pend_ayah', nama_ibu='$nama_ibu', tmp_lhr_ibu='$tmp_lhr_ibu', tgl_lhr_ibu='$tgl_lhr_ibu', pekerjaan_ibu='$pekerjaan_ibu', penghasilan_ibu='$penghasilan_ibu', pend_ibu='$pend_ibu', alamat_org_tua='$alamat_org_tua', no_telp_orgtua='$no_telp_orgtua' WHERE nim_npm='$username'");
  }
  echo "<script>window.alert('Data anda berhasil di simpan')
window.location='dashboard'</script>";
}

if (isset($_POST['simpan_dosen'])) {
  $nama_dosen = $_POST['nama_dosen'];
  $id_jk = $_POST['id_jk'];
  $id_agama = $_POST['id_agama'];
  $alamat = $_POST['alamat'];
  $tmp_lhr_dosen = $_POST['tmp_lhr_dosen'];
  $tgl_lhr_dosen = $_POST['tgl_lhr_dosen'];
  $email = $_POST['email'];
  $no_telp = $_POST['no_telp'];
  $update = mysqli_query($koneksi, "UPDATE dosen SET nama_dosen='$nama_dosen', id_jk='$id_jk', id_agama='$id_agama', alamat='$alamat', tmp_lhr_dosen='$tmp_lhr_dosen', tgl_lhr_dosen='$tgl_lhr_dosen', email='$email', no_telp='$no_telp' WHERE nip='$username'");
  echo "<script>window.alert('Data anda berhasil di simpan')
  window.location='dashboard'</script>";
}

if (isset($_POST['ubahfotomhs'])) {
  $rand = $username;
  $ekstensi_diperbolehkan = array('png', 'jpg', 'JPG', 'PNG', 'jpeg', 'JPEG');
  $file_foto = $_FILES['file_foto']['name'];
  $x = explode('.', $file_foto);
  $ekstensi = strtolower(end($x));
  $ukuran = $_FILES['file_foto']['size'];
  $file_tmp = $_FILES['file_foto']['tmp_name'];
  if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
    if ($ukuran < 50044070) {
      if ($foto_mhs == "") {
        # code...
      } else {
        unlink("foto_mhs/$foto_mhs");
      }
      $xx_foto = $rand . '_' . $file_foto;
      move_uploaded_file($file_tmp, 'foto_mhs/' . $rand . '_' . $file_foto);
      $update_foto = mysqli_query($koneksi, "UPDATE mahasiswa SET foto_mhs='$xx_foto' WHERE nim_npm='$username'");
      echo "<script>window.alert('Foto Profile anda berhasil di ubah')
      window.location='dashboard'</script>";
    }
  }
}

if (isset($_POST['ubahfotodosen'])) {
  $rand = $username;
  $ekstensi_diperbolehkan = array('png', 'jpg', 'JPG', 'PNG', 'jpeg', 'JPEG');
  $file_foto = $_FILES['file_foto']['name'];
  $x = explode('.', $file_foto);
  $ekstensi = strtolower(end($x));
  $ukuran = $_FILES['file_foto']['size'];
  $file_tmp = $_FILES['file_foto']['tmp_name'];
  if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
    if ($ukuran < 50044070) {
      if ($foto_dosen == "") {
        # code...
      } else {
        unlink("foto_dosen/$foto_dosen");
      }
      $xx_foto = $rand . '_' . $file_foto;
      move_uploaded_file($file_tmp, 'foto_dosen/' . $rand . '_' . $file_foto);
      $update_foto = mysqli_query($koneksi, "UPDATE dosen SET foto_dosen='$xx_foto' WHERE nip='$username'");
      echo "<script>window.alert('Foto Profile anda berhasil di ubah')
      window.location='dashboard'</script>";
    }
  }
}

if (isset($_POST['ubahpass'])) {
  $password = $_POST['password'];
  $password2 = $_POST['password2'];
  if ($password !== $password2) {
    echo "<script>window.alert('Konfirmasi Password tidak sesuai !!!')
    window.location='dashboard'</script>";
  } else {
    $password2 = $_POST['password2'];
    $update = mysqli_query($koneksi, "UPDATE user SET password='$password2' WHERE username='$username'");
    echo "<script>window.alert('Password anda berhasil diubah !!!')
   window.location='dashboard'</script>";
  }
}
?>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title><?= $r_pengaturan['nama_aplikasi']; ?></title>
  <link rel="shortcut icon" href="../img/<?= $r_pengaturan['logo_aplikasi']; ?>" />
  <!-- CSS files -->
  <link href="../dist/css/tabler.min.css" rel="stylesheet" />
  <link href="../dist/css/tabler-flags.min.css" rel="stylesheet" />
  <link href="../dist/css/tabler-payments.min.css" rel="stylesheet" />
  <link href="../dist/css/tabler-vendors.min.css" rel="stylesheet" />
  <link href="../dist/css/demo.min.css" rel="stylesheet" />
</head>

<body class="antialiased">
  <div class="wrapper">
    <?php
    require_once "../template/header.php";
    ?>
    <div class="navbar-expand-md">
      <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar navbar-light">
          <div class="container-xl">
            <?php
            require_once "../template/menu.php";
            ?>

          </div>
        </div>
      </div>
    </div>
    <div class="page-wrapper">
      <br>
      <div class="page-body">
        <div class="container-xl">
          <div class="row row-deck row-cards">


            <?php
            if ($level == 'admin') {
            ?>

              <!-- Prodi -->
              <div class="col-sm-6 col-lg-3">
                <div class="card btn-primary">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader">

                      </div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $jurusan = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM prodi"));
                      echo "$jurusan";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/building-warehouse -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M3 21v-13l9 -4l9 4v13" />
                          <path d="M13 13h4v8h-10v-6h6" />
                          <path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" />
                        </svg>
                        PROGRAM STUDI
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- dosen -->
              <div class="col-sm-6 col-lg-3">
                <div class="card btn-warning">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader"></div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $dosen = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM dosen"));
                      echo "$dosen";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/users -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <circle cx="9" cy="7" r="4" />
                          <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                          <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                        </svg>
                        DOSEN
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-lg-3">
                <div class="card btn-info">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader"></div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $mahasiswa = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM mahasiswa"));
                      echo "$mahasiswa";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/users -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <circle cx="9" cy="7" r="4" />
                          <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                          <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                        </svg>
                        MAHASISWA
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-lg-3">
                <div class="card btn-dark">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader"></div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $mata_kuliah = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM mata_kuliah"));
                      echo "$mata_kuliah";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/book -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0" />
                          <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0" />
                          <line x1="3" y1="6" x2="3" y2="19" />
                          <line x1="12" y1="6" x2="12" y2="19" />
                          <line x1="21" y1="6" x2="21" y2="19" />
                        </svg>
                        MATA KULIAH
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Histori Login Pengguna</h3>
                  </div>
                  <div class="table-responsive">
                    <table class="table card-table table-vcenter" style="font-size: 10pt;">
                      <?php
                      $user = mysqli_query($koneksi, "SELECT * FROM user WHERE browser!='' ORDER BY tgl DESC, waktu DESC LIMIT 30");
                      while ($tampil = mysqli_fetch_array($user)) {
                        $tgl = $tampil['tgl'];
                        $waktu = $tampil['waktu'];
                      ?>
                        <tr>
                        <td>
                            <span class="avatar avatar-sm" style="background-image: url(foto_mhs/avatar-blank.png)"></span>
                          </td>
                          <td>
                            <?= $tampil['username']; ?>
                          </td>
                          <td>Akses : <?= $tampil['level']; ?></td>
                          <td>
                            IP : <?= $tampil['ip']; ?>
                          </td>
                          <td>
                            Browser : <?= $tampil['browser']; ?>
                          </td>
                          <td>
                            <!-- Download SVG icon from http://tabler-icons.io/i/clock -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <circle cx="12" cy="12" r="9" />
                              <polyline points="12 7 12 12 15 15" />
                            </svg> : <?php echo time_since(strtotime($tgl . $waktu)); ?>
                          </td>
                        </tr>
                      <?php } ?>
                    </table>
                  </div>
                </div>
              </div>


            <?php } elseif ($level == 'mhs') {
            ?>

              <div class="row">

                <div class="col-lg-4">
                  <div class="card">
                    <div class="card-body">

                      <div class="table-responsive">
                        <table class="table">
                          <tr>
                            <td colspan="2">


                              <div class="card">
                                <div class="card-body p-4 text-center">
                                  <?php
                                  if ($tampil_mhs['foto_mhs'] == '') {
                                  ?>
                                    <span class="avatar avatar-xl mb-3 avatar-rounded" style="background-image: url(foto_mhs/avatar-blank.png)"></span>
                                  <?php } else { ?>
                                    <img style="border-radius: 20%; width: 140px; height: 144px; overflow: hidden;" src="foto_mhs/<?= $tampil_mhs['foto_mhs']; ?>">
                                  <?php } ?>
                                  <div class="mt-3">
                                  <h3 class="m-0 mb-1"><a href="#"><?= $tampil_mhs['nama_mhs']; ?></a></h3>
                                    <span class="badge bg-success-lt" style="font-size: 16px;">IPK : <?php 
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
                                  </div>
                                </div>
                                <div class="d-flex">
                                  <a href="#" class="card-btn btn-btn-info" data-bs-toggle="modal" data-bs-target="#ubahfotomhs">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/photo -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                      <line x1="15" y1="8" x2="15.01" y2="8" />
                                      <rect x="4" y="4" width="16" height="16" rx="3" />
                                      <path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5" />
                                      <path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2" />
                                    </svg>
                                    Ubah Foto
                                  </a>
                                  <a href="#" class="card-btn" data-bs-toggle="modal" data-bs-target="#ubahpassmhs">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/key -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                      <circle cx="8" cy="15" r="4" />
                                      <line x1="10.85" y1="12.15" x2="19" y2="4" />
                                      <line x1="18" y1="5" x2="20" y2="7" />
                                      <line x1="15" y1="8" x2="17" y2="10" />
                                    </svg>
                                    Ubah Password
                                  </a>
                                </div>
                              </div>


                            </td>
                          </tr>
                          <tr>
                            <td>NIM</td>
                            <td>: <?= $tampil_mhs['nim_npm']; ?></td>
                          </tr>
                          <tr>
                            <td>Penasehat Akademik</td>
                            <td>:
                              <?php
                              $pa = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM mhs_has_pa INNER JOIN dosen ON mhs_has_pa.nip=dosen.nip WHERE nim_npm='$username'"));
                              ?>
                              <?= $pa['nama_dosen']; ?>
                            </td>
                          </tr>
                          <tr>
                            <td>Program Studi</td>
                            <td>: <?= $prodi['jenjang'] ?> - <?= $prodi['nama_prodi']; ?></td>
                          </tr>
                          <tr>
                            <td>Angkatan</td>
                            <td>: <?= $tampil_mhs['thn_masuk']; ?></td>
                          </tr>
                          <tr>
                            <td>Status</td>
                            <td>: <?= $tampil_mhs['status_mhs']; ?></td>
                          </tr>
                        </table>
                      </div>

                    </div>
                  </div>
                </div>

                <div class="col-lg-8">
                  <div class="card">
                    <div class="card-body">

                      <form action="" method="post">


                        <div class="col-md-12">
                          <div class="card">
                            <ul class="nav nav-tabs" data-bs-toggle="tabs">
                              <li class="nav-item">
                                <a href="#tabs-home-7" class="nav-link active" data-bs-toggle="tab">Data Mahasiswa</a>
                              </li>
                              <li class="nav-item">
                                <a href="#tabs-profile-7" class="nav-link" data-bs-toggle="tab">Data Orang Tua</a>
                              </li>

                              <li class="nav-item ms-auto">
                                <input type="submit" name="simpan_mhs" class="btn btn-info" value="Simpan Data">
                              </li>
                            </ul>
                            <div class="card-body">
                              <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-home-7">
                                  <div>

                                    <div class="row">
                                      <div class="col-lg-6">

                                        <div class="mb-3">
                                          <label>NIM</label>
                                          <input type="text" readonly name="nim_npm" value="<?= $tampil_mhs['nim_npm']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Nama Lengkap</label>
                                          <input type="text" name="nama_mhs" value="<?= $tampil_mhs['nama_mhs']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Angkatan</label>
                                          <input type="number" name="thn_masuk" minlength="4" maxlength="4" value="<?= $tampil_mhs['thn_masuk']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Jenis Kelamin</label>
                                          <select name="id_jk" class="form-control" id="id_jk">
                                            <?php
                                            $query_jk = "SELECT * FROM tbl_jk";
                                            $sql_jk = mysqli_query($koneksi, $query_jk);
                                            while ($data_jk = mysqli_fetch_array($sql_jk)) {
                                            ?>
                                              <option value="<?= $data_jk['id_jk'] ?>" <?= ($data_jk['jenis_kelamin'] == $tampil_mhs['jenis_kelamin']) ? "selected" : "" ?>>
                                                <?= $data_jk['jenis_kelamin'] ?>
                                              </option>

                                            <?php
                                            }
                                            ?>
                                          </select>
                                        </div>

                                        <div class="mb-3">
                                          <label>Tempat Lahir</label>
                                          <input type="text" name="tempat_lhr" value="<?= $tampil_mhs['tempat_lhr']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Tanggal Lahir</label>
                                          <input type="date" name="tgl_lhr_mhs" class="form-control" value="<?= $tampil_mhs['tgl_lhr_mhs']; ?>">
                                        </div>

                                      </div>

                                      <div class="col-lg-6">

                                        <div class="mb-3">
                                          <label>Agama</label>
                                          <select name="id_agama" class="form-control" id="id_agama">
                                            <?php
                                            $query_agama = "SELECT * FROM tbl_agama";
                                            $sql_agama = mysqli_query($koneksi, $query_agama);
                                            while ($data_agama = mysqli_fetch_array($sql_agama)) {
                                            ?>
                                              <option value="<?= $data_agama['id_agama'] ?>" <?= ($data_agama['agama'] == $tampil_mhs['agama']) ? "selected" : "" ?>>

                                                <?= $data_agama['agama'] ?>

                                              </option>

                                            <?php
                                            }
                                            ?>
                                          </select>
                                        </div>

                                        <div class="mb-3">
                                          <label>Email</label>
                                          <input type="email" name="email" value="<?= $tampil_mhs['email']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Jalur Masuk</label>
                                          <input type="text" name="lulusan_jalur" value="<?= $tampil_mhs['lulusan_jalur']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Sekolah Asal</label>
                                          <input type="text" name="sekolah_asal" value="<?= $tampil_mhs['sekolah_asal']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Alamat</label>
                                          <textarea class="form-control" name="alamat_mhs"><?= $tampil_mhs['alamat_mhs']; ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                          <label>No. HP / Whatsapp</label>
                                          <input type="text" name="no_telp_mhs" value="<?= $tampil_mhs['no_telp_mhs']; ?>" class="form-control">
                                        </div>

                                      </div>

                                    </div>


                                  </div>
                                </div>
                                <div class="tab-pane" id="tabs-profile-7">
                                  <div>


                                    <div class="row">
                                      <div class="col-lg-6">

                                        <div class="mb-3">
                                          <label>No. Kartu Keluarga</label>
                                          <input type="text" minlength="16" maxlength="16" name="no_kk" value="<?= $orgtua['no_kk']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Nama Ayah</label>
                                          <input type="text" name="nama_ayah" value="<?= $orgtua['nama_ayah']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Tempat Lahir Ayah</label>
                                          <input type="text" name="tmp_lhr_ayah" value="<?= $orgtua['tmp_lhr_ayah']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Tanggal Lahir Ayah</label>
                                          <input type="date" name="tgl_lhr_ayah" value="<?= $orgtua['tgl_lhr_ayah']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Pekerjaan Ayah</label>
                                          <input type="text" name="pekerjaan_ayah" value="<?= $orgtua['pekerjaan_ayah']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Penghasilan Ayah</label>
                                          <input type="text" name="penghasilan_ayah" value="<?= $orgtua['penghasilan_ayah']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Pendidikan Akhir Ayah</label>
                                          <input type="text" name="pend_ayah" value="<?= $orgtua['pend_ayah']; ?>" class="form-control">
                                        </div>


                                      </div>

                                      <div class="col-lg-6">

                                        <div class="mb-3">
                                          <label>Nama Ibu</label>
                                          <input type="text" name="nama_ibu" value="<?= $orgtua['nama_ibu']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Tempat Lahir Ibu</label>
                                          <input type="text" name="tmp_lhr_ibu" value="<?= $orgtua['tmp_lhr_ibu']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Tanggal Lahir Ibu</label>
                                          <input type="date" name="tgl_lhr_ibu" value="<?= $orgtua['tgl_lhr_ibu']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Pekerjaan Ibu</label>
                                          <input type="text" name="pekerjaan_ibu" value="<?= $orgtua['pekerjaan_ibu']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Penghasilan Ibu</label>
                                          <input type="text" name="penghasilan_ibu" value="<?= $orgtua['penghasilan_ibu']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Pendidikan Akhir Ibu</label>
                                          <input type="text" name="pend_ibu" value="<?= $orgtua['pend_ibu']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Alamat Orang Tua</label>
                                          <textarea class="form-control" name="alamat_org_tua"><?= $orgtua['alamat_org_tua']; ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                          <label>No. Telp Orang Tua</label>
                                          <input type="text" name="no_telp_orgtua" value="<?= $orgtua['no_telp_orgtua']; ?>" class="form-control">
                                        </div>

                                      </div>


                                    </div>


                                  </div>
                                </div>
                                <div class="tab-pane" id="tabs-settings-7">
                                  <div>

                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                      </form>


                    </div>
                  </div>
                </div>

              </div>


              <!-- modal ubah foto -->
              <form action="" method="post" enctype="multipart/form-data">
                <div class="modal modal-blur fade" id="ubahfotomhs" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="modal-status bg-secondary"></div>
                      <div class="modal-body text-center py-4">
                        <h3>Ubah Foto</h3>
                        <div class="mb-3">
                          <input type="file" name="file_foto" accept="image/*" class="form-control" required>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <div class="w-100">
                          <div class="row">

                            <div class="col">
                              <input type="submit" name="ubahfotomhs" class="btn btn-info w-100" value="Ubah">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
              <!--  -->

              <!-- modal ubah pass -->
              <form action="" method="post">
                <div class="modal modal-blur fade" id="ubahpassmhs" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="modal-status bg-secondary"></div>
                      <div class="modal-body text-center py-4">
                        <h3>Ubah Password</h3>
                        <div class="mb-3">
                          <input type="password" name="password" placeholder="Password baru" class="form-control" required>
                        </div>
                        <div class="mb-3">
                          <input type="password" name="password2" placeholder="Konfirmasi Password baru" class="form-control" required>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <div class="w-100">
                          <div class="row">

                            <div class="col">
                              <input type="submit" name="ubahpass" class="btn btn-info w-100" value="Ubah">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
              <!--  -->


            <?php } elseif ($level == "Jurusan/Prodi") {  ?>



              <div class="col-sm-6 col-lg-4">
                <div class="card btn-warning">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader"></div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $dosen = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM prodi_has_dosen WHERE kode_prodi='$kode_prodi'"));
                      echo "$dosen";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/users -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <circle cx="9" cy="7" r="4" />
                          <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                          <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                        </svg>
                        DOSEN
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-lg-4">
                <div class="card btn-info">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader"></div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $mahasiswa = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM prodi_has_mhs WHERE kode_prodi='$kode_prodi'"));
                      echo "$mahasiswa";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/users -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <circle cx="9" cy="7" r="4" />
                          <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                          <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                        </svg>
                        MAHASISWA
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-lg-4">
                <div class="card btn-dark">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader"></div>
                      <div class="ms-auto lh-1">
                      </div>
                    </div>
                    <div class="h1 mb-3">
                      <?php
                      $matkul = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM prodi_has_matkul WHERE kode_prodi='$kode_prodi'"));
                      echo "$matkul";
                      ?>
                    </div>
                    <div class="d-flex mb-2">
                      <div>
                        <!-- Download SVG icon from http://tabler-icons.io/i/book -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0" />
                          <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0" />
                          <line x1="3" y1="6" x2="3" y2="19" />
                          <line x1="12" y1="6" x2="12" y2="19" />
                          <line x1="21" y1="6" x2="21" y2="19" />
                        </svg>
                        MATA KULIAH
                      </div>
                    </div>
                  </div>
                </div>
              </div>




            <?php } elseif ($level == "dosen") { ?>
              
              <div class="row">
                <div class="col-lg-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table">
                          <tr>
                            <td colspan="2">
                              <div class="card">
                                <div class="card-body p-4 text-center">
                                  <?php
                                  if ($tampil_dosen['foto_dosen'] == '') {
                                  ?>
                                    <span class="avatar avatar-xl mb-3 avatar-rounded" style="background-image: url(foto_dosen/avatar-blank.png)"></span>
                                  <?php } else { ?>
                                    <img style="border-radius: 20%; width: 140px; height: 144px; overflow: hidden;" src="foto_dosen/<?= $tampil_dosen['foto_dosen']; ?>">
                                  <?php } ?>
                                  <div class="mt-3">
                                  <h3 class="m-0 mb-1"><a href="#"><?= $tampil_dosen['nama_dosen']; ?></a></h3>
                                    <span class="badge bg-success-lt" style="font-size: 14px;">NIDN : <?= $tampil_dosen['nip']; ?></span>
                                  </div>
                                </div>
                                <div class="d-flex">
                                  <a href="#" class="card-btn btn-btn-info" data-bs-toggle="modal" data-bs-target="#ubahfotodosen">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/photo -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                      <line x1="15" y1="8" x2="15.01" y2="8" />
                                      <rect x="4" y="4" width="16" height="16" rx="3" />
                                      <path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5" />
                                      <path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2" />
                                    </svg>
                                    Ubah Foto
                                  </a>
                                  <a href="#" class="card-btn" data-bs-toggle="modal" data-bs-target="#ubahpassdosen">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/key -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                      <circle cx="8" cy="15" r="4" />
                                      <line x1="10.85" y1="12.15" x2="19" y2="4" />
                                      <line x1="18" y1="5" x2="20" y2="7" />
                                      <line x1="15" y1="8" x2="17" y2="10" />
                                    </svg>
                                    Ubah Password</a>
                                </div>
                              </div>


                            </td>
                          </tr>
                        </table>
                      </div>

                    </div>
                  </div>
                </div>

                <div class="col-lg-8">
                  <div class="card">
                    <div class="card-body">

                      <form action="" method="post">


                        <div class="col-md-12">
                          <div class="card">
                            <ul class="nav nav-tabs" data-bs-toggle="tabs">
                              <li class="nav-item">
                                <a href="#tabs-home-7" class="nav-link active" data-bs-toggle="tab">Data Diri</a>
                              </li>
                              <!--  <li class="nav-item">
                <a href="#tabs-profile-7" class="nav-link" data-bs-toggle="tab">Data Orang Tua</a>
              </li> -->

                              <li class="nav-item ms-auto">
                                <input type="submit" name="simpan_dosen" class="btn btn-info" value="Simpan Data">
                              </li>
                            </ul>
                            <div class="card-body">
                              <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-home-7">
                                  <div>

                                    <div class="row">
                                      <div class="col-lg-6">

                                        <div class="mb-3">
                                          <label>NIDN</label>
                                          <input type="text" readonly name="nip" value="<?= $tampil_dosen['nip']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Nama Lengkap</label>
                                          <input type="text" name="nama_dosen" value="<?= $tampil_dosen['nama_dosen']; ?>" class="form-control">
                                        </div>



                                        <div class="mb-3">
                                          <label>Tempat Lahir</label>
                                          <input type="text" name="tmp_lhr_dosen" value="<?= $tampil_dosen['tmp_lhr_dosen']; ?>" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                          <label>Tanggal Lahir</label>
                                          <input type="date" name="tgl_lhr_dosen" class="form-control" value="<?= $tampil_dosen['tgl_lhr_dosen']; ?>">
                                        </div>

                                        <div class="mb-3">
                                          <label>Email</label>
                                          <input type="email" name="email" value="<?= $tampil_dosen['email']; ?>" class="form-control">
                                        </div>

                                      </div>

                                      <div class="col-lg-6">


                                        <div class="mb-3">
                                          <label>Jenis Kelamin</label>
                                          <select name="id_jk" class="form-control" id="id_jk">
                                            <?php
                                            $query_jk = "SELECT * FROM tbl_jk";
                                            $sql_jk = mysqli_query($koneksi, $query_jk);
                                            while ($data_jk = mysqli_fetch_array($sql_jk)) {
                                            ?>
                                              <option value="<?= $data_jk['id_jk'] ?>" <?= ($data_jk['jenis_kelamin'] == $tampil_dosen['jenis_kelamin']) ? "selected" : "" ?>>
                                                <?= $data_jk['jenis_kelamin'] ?>
                                              </option>

                                            <?php
                                            }
                                            ?>
                                          </select>
                                        </div>

                                        <div class="mb-3">
                                          <label>Agama</label>
                                          <select name="id_agama" class="form-control" id="id_agama">
                                            <?php
                                            $query_agama = "SELECT * FROM tbl_agama";
                                            $sql_agama = mysqli_query($koneksi, $query_agama);
                                            while ($data_agama = mysqli_fetch_array($sql_agama)) {
                                            ?>
                                              <option value="<?= $data_agama['id_agama'] ?>" <?= ($data_agama['agama'] == $tampil_dosen['agama']) ? "selected" : "" ?>>

                                                <?= $data_agama['agama'] ?>

                                              </option>

                                            <?php
                                            }
                                            ?>
                                          </select>
                                        </div>


                                        <div class="mb-3">
                                          <label>Alamat</label>
                                          <textarea class="form-control" name="alamat"><?= $tampil_dosen['alamat']; ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                          <label>No. HP / Whatsapp</label>
                                          <input type="text" minlength="12" name="no_telp" class="form-control" value="<?= $tampil_dosen['no_telp']; ?>">
                                        </div>

                                      </div>

                                    </div>


                                  </div>
                                </div>
                                <div class="tab-pane" id="tabs-profile-7">
                                  <div>

                                    <!--  <div class="row">
                    <div class="col-lg-6">

                      <div class="mb-3">
                        <label>No KK</label>
                        <input type="text" minlength="16" maxlength="16" name="no_kk" value="<?= $orgtua['no_kk']; ?>" class="form-control">
                      </div>

                      <div class="mb-3">
                        <label>Nama Ayah</label>
                        <input type="text" name="nama_ayah" value="<?= $orgtua['nama_ayah']; ?>" class="form-control">
                      </div>

                      <div class="mb-3">
                        <label>Tempat lahir ayah</label>
                        <input type="text" name="tmp_lhr_ayah" value="<?= $orgtua['tmp_lhr_ayah']; ?>" class="form-control">
                      </div>

                      <div class="mb-3">
                        <label>Tanggal lahir ayah</label>
                        <input type="date" name="tgl_lhr_ayah" value="<?= $orgtua['tgl_lhr_ayah']; ?>" class="form-control">
                      </div>

                      <div class="mb-3">
                        <label>Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" value="<?= $orgtua['pekerjaan_ayah']; ?>" class="form-control">
                      </div>

                      <div class="mb-3">
                        <label>Penghasilan Ayah</label>
                        <input type="text" name="penghasilan_ayah" value="<?= $orgtua['penghasilan_ayah']; ?>" class="form-control">
                      </div>

                      <div class="mb-3">
                        <label>Pendidikan Akhir Ayah</label>
                        <input type="text" name="pend_ayah" value="<?= $orgtua['pend_ayah']; ?>" class="form-control">
                      </div>


                    </div>

                    <div class="col-lg-6">

                     <div class="mb-3">
                      <label>Nama Ibu</label>
                      <input type="text" name="nama_ibu" value="<?= $orgtua['nama_ibu']; ?>" class="form-control">
                    </div>

                    <div class="mb-3">
                      <label>Tempat Lahir Ibu</label>
                      <input type="text" name="tmp_lhr_ibu" value="<?= $orgtua['tmp_lhr_ibu']; ?>" class="form-control">
                    </div>

                    <div class="mb-3">
                      <label>Tanggal Lahir ibu</label>
                      <input type="date" name="tgl_lhr_ibu" value="<?= $orgtua['tgl_lhr_ibu']; ?>" class="form-control">
                    </div>

                    <div class="mb-3">
                      <label>Pekerjaan Ibu</label>
                      <input type="text" name="pekerjaan_ibu" value="<?= $orgtua['pekerjaan_ibu']; ?>" class="form-control">
                    </div>

                    <div class="mb-3">
                      <label>Penghasilan Ibu</label>
                      <input type="text" name="penghasilan_ibu" value="<?= $orgtua['penghasilan_ibu']; ?>" class="form-control">
                    </div>

                    <div class="mb-3">
                      <label>Pendidikan Akhir Ibu</label>
                      <input type="text" name="pend_ibu" value="<?= $orgtua['pend_ibu']; ?>" class="form-control">
                    </div>

                    <div class="mb-3">
                      <label>Alamat Orang tua</label>
                      <textarea class="form-control" name="alamat_org_tua"><?= $orgtua['alamat_org_tua']; ?></textarea>
                    </div>

                    <div class="mb-3">
                      <label>No Telp Orang Tua</label>
                      <input type="text" name="no_telp_orgtua" value="<?= $orgtua['no_telp_orgtua']; ?>" class="form-control">
                    </div>

                  </div> -->


                                  </div>

                                </div>
                              </div>
                              <div class="tab-pane" id="tabs-settings-7">
                                <div>

                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>

                    </form>


                  </div>
                </div>
              </div>

          </div>



          <!-- modal ubah foto -->
          <form action="" method="post" enctype="multipart/form-data">
            <div class="modal modal-blur fade" id="ubahfotodosen" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  <div class="modal-status bg-secondary"></div>
                  <div class="modal-body text-center py-4">
                    <h3>Ubah Foto</h3>
                    <div class="mb-3">
                      <input type="file" name="file_foto" accept="image/*" class="form-control" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <div class="w-100">
                      <div class="row">

                        <div class="col">
                          <input type="submit" name="ubahfotodosen" class="btn btn-info w-100" value="Ubah">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
          <!--  -->

          <!-- modal ubah pass -->
          <form action="" method="post">
            <div class="modal modal-blur fade" id="ubahpassdosen" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  <div class="modal-status bg-secondary"></div>
                  <div class="modal-body text-center py-4">
                    <h3>Ubah Password</h3>
                    <div class="mb-3">
                      <input type="password" name="password" placeholder="Password baru" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <input type="password" name="password2" placeholder="Konfirmasi Password baru" class="form-control" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <div class="w-100">
                      <div class="row">

                        <div class="col">
                          <input type="submit" name="ubahpass" class="btn btn-info w-100" value="Ubah">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
          <!--  -->


        <?php } ?>


        </div>
      </div>
    </div>
    <?php
    require_once "../template/footer.php";
    ?>
  </div>
  </div>
  <!-- Libs JS -->
  <script src="../dist/libs/apexcharts/dist/apexcharts.min.js"></script>
  <!-- Tabler Core -->
  <script src="../dist/js/tabler.min.js"></script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('chart-revenue-bg'), {
        chart: {
          type: "area",
          fontFamily: 'inherit',
          height: 40.0,
          sparkline: {
            enabled: true
          },
          animations: {
            enabled: false
          },
        },
        dataLabels: {
          enabled: false,
        },
        fill: {
          opacity: .16,
          type: 'solid'
        },
        stroke: {
          width: 2,
          lineCap: "round",
          curve: "smooth",
        },
        series: [{
          name: "Profits",
          data: [37, 35, 44, 28, 36, 24, 65, 31, 37, 39, 62, 51, 35, 41, 35, 27, 93, 53, 61, 27, 54, 43, 19, 46, 39, 62, 51, 35, 41, 67]
        }],
        grid: {
          strokeDashArray: 4,
        },
        xaxis: {
          labels: {
            padding: 0,
          },
          tooltip: {
            enabled: false
          },
          axisBorder: {
            show: false,
          },
          type: 'datetime',
        },
        yaxis: {
          labels: {
            padding: 4
          },
        },
        labels: [
          '2020-06-20', '2020-06-21', '2020-06-22', '2020-06-23', '2020-06-24', '2020-06-25', '2020-06-26', '2020-06-27', '2020-06-28', '2020-06-29', '2020-06-30', '2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08', '2020-07-09', '2020-07-10', '2020-07-11', '2020-07-12', '2020-07-13', '2020-07-14', '2020-07-15', '2020-07-16', '2020-07-17', '2020-07-18', '2020-07-19'
        ],
        colors: ["#206bc4"],
        legend: {
          show: false,
        },
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('chart-new-clients'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 40.0,
          sparkline: {
            enabled: true
          },
          animations: {
            enabled: false
          },
        },
        fill: {
          opacity: 1,
        },
        stroke: {
          width: [2, 1],
          dashArray: [0, 3],
          lineCap: "round",
          curve: "smooth",
        },
        series: [{
          name: "May",
          data: [37, 35, 44, 28, 36, 24, 65, 31, 37, 39, 62, 51, 35, 41, 35, 27, 93, 53, 61, 27, 54, 43, 4, 46, 39, 62, 51, 35, 41, 67]
        }, {
          name: "April",
          data: [93, 54, 51, 24, 35, 35, 31, 67, 19, 43, 28, 36, 62, 61, 27, 39, 35, 41, 27, 35, 51, 46, 62, 37, 44, 53, 41, 65, 39, 37]
        }],
        grid: {
          strokeDashArray: 4,
        },
        xaxis: {
          labels: {
            padding: 0,
          },
          tooltip: {
            enabled: false
          },
          type: 'datetime',
        },
        yaxis: {
          labels: {
            padding: 4
          },
        },
        labels: [
          '2020-06-20', '2020-06-21', '2020-06-22', '2020-06-23', '2020-06-24', '2020-06-25', '2020-06-26', '2020-06-27', '2020-06-28', '2020-06-29', '2020-06-30', '2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08', '2020-07-09', '2020-07-10', '2020-07-11', '2020-07-12', '2020-07-13', '2020-07-14', '2020-07-15', '2020-07-16', '2020-07-17', '2020-07-18', '2020-07-19'
        ],
        colors: ["#206bc4", "#a8aeb7"],
        legend: {
          show: false,
        },
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('chart-active-users'), {
        chart: {
          type: "bar",
          fontFamily: 'inherit',
          height: 40.0,
          sparkline: {
            enabled: true
          },
          animations: {
            enabled: false
          },
        },
        plotOptions: {
          bar: {
            columnWidth: '50%',
          }
        },
        dataLabels: {
          enabled: false,
        },
        fill: {
          opacity: 1,
        },
        series: [{
          name: "Profits",
          data: [37, 35, 44, 28, 36, 24, 65, 31, 37, 39, 62, 51, 35, 41, 35, 27, 93, 53, 61, 27, 54, 43, 19, 46, 39, 62, 51, 35, 41, 67]
        }],
        grid: {
          strokeDashArray: 4,
        },
        xaxis: {
          labels: {
            padding: 0,
          },
          tooltip: {
            enabled: false
          },
          axisBorder: {
            show: false,
          },
          type: 'datetime',
        },
        yaxis: {
          labels: {
            padding: 4
          },
        },
        labels: [
          '2020-06-20', '2020-06-21', '2020-06-22', '2020-06-23', '2020-06-24', '2020-06-25', '2020-06-26', '2020-06-27', '2020-06-28', '2020-06-29', '2020-06-30', '2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08', '2020-07-09', '2020-07-10', '2020-07-11', '2020-07-12', '2020-07-13', '2020-07-14', '2020-07-15', '2020-07-16', '2020-07-17', '2020-07-18', '2020-07-19'
        ],
        colors: ["#206bc4"],
        legend: {
          show: false,
        },
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('chart-mentions'), {
        chart: {
          type: "bar",
          fontFamily: 'inherit',
          height: 240,
          parentHeightOffset: 0,
          toolbar: {
            show: false,
          },
          animations: {
            enabled: false
          },
          stacked: true,
        },
        plotOptions: {
          bar: {
            columnWidth: '50%',
          }
        },
        dataLabels: {
          enabled: false,
        },
        fill: {
          opacity: 1,
        },
        series: [{
          name: "Web",
          data: [1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 2, 12, 5, 8, 22, 6, 8, 6, 4, 1, 8, 24, 29, 51, 40, 47, 23, 26, 50, 26, 41, 22, 46, 47, 81, 46, 6]
        }, {
          name: "Social",
          data: [2, 5, 4, 3, 3, 1, 4, 7, 5, 1, 2, 5, 3, 2, 6, 7, 7, 1, 5, 5, 2, 12, 4, 6, 18, 3, 5, 2, 13, 15, 20, 47, 18, 15, 11, 10, 0]
        }, {
          name: "Other",
          data: [2, 9, 1, 7, 8, 3, 6, 5, 5, 4, 6, 4, 1, 9, 3, 6, 7, 5, 2, 8, 4, 9, 1, 2, 6, 7, 5, 1, 8, 3, 2, 3, 4, 9, 7, 1, 6]
        }],
        grid: {
          padding: {
            top: -20,
            right: 0,
            left: -4,
            bottom: -4
          },
          strokeDashArray: 4,
          xaxis: {
            lines: {
              show: true
            }
          },
        },
        xaxis: {
          labels: {
            padding: 0,
          },
          tooltip: {
            enabled: false
          },
          axisBorder: {
            show: false,
          },
          type: 'datetime',
        },
        yaxis: {
          labels: {
            padding: 4
          },
        },
        labels: [
          '2020-06-20', '2020-06-21', '2020-06-22', '2020-06-23', '2020-06-24', '2020-06-25', '2020-06-26', '2020-06-27', '2020-06-28', '2020-06-29', '2020-06-30', '2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08', '2020-07-09', '2020-07-10', '2020-07-11', '2020-07-12', '2020-07-13', '2020-07-14', '2020-07-15', '2020-07-16', '2020-07-17', '2020-07-18', '2020-07-19', '2020-07-20', '2020-07-21', '2020-07-22', '2020-07-23', '2020-07-24', '2020-07-25', '2020-07-26'
        ],
        colors: ["#206bc4", "#79a6dc", "#bfe399"],
        legend: {
          show: false,
        },
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-activity'), {
        chart: {
          type: "radialBar",
          fontFamily: 'inherit',
          height: 40,
          width: 40,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        plotOptions: {
          radialBar: {
            hollow: {
              margin: 0,
              size: '75%'
            },
            track: {
              margin: 0
            },
            dataLabels: {
              show: false
            }
          }
        },
        colors: ["#206bc4"],
        series: [35],
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('chart-development-activity'), {
        chart: {
          type: "area",
          fontFamily: 'inherit',
          height: 192,
          sparkline: {
            enabled: true
          },
          animations: {
            enabled: false
          },
        },
        dataLabels: {
          enabled: false,
        },
        fill: {
          opacity: .16,
          type: 'solid'
        },
        stroke: {
          width: 2,
          lineCap: "round",
          curve: "smooth",
        },
        series: [{
          name: "Purchases",
          data: [3, 5, 4, 6, 7, 5, 6, 8, 24, 7, 12, 5, 6, 3, 8, 4, 14, 30, 17, 19, 15, 14, 25, 32, 40, 55, 60, 48, 52, 70]
        }],
        grid: {
          strokeDashArray: 4,
        },
        xaxis: {
          labels: {
            padding: 0,
          },
          tooltip: {
            enabled: false
          },
          axisBorder: {
            show: false,
          },
          type: 'datetime',
        },
        yaxis: {
          labels: {
            padding: 4
          },
        },
        labels: [
          '2020-06-20', '2020-06-21', '2020-06-22', '2020-06-23', '2020-06-24', '2020-06-25', '2020-06-26', '2020-06-27', '2020-06-28', '2020-06-29', '2020-06-30', '2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08', '2020-07-09', '2020-07-10', '2020-07-11', '2020-07-12', '2020-07-13', '2020-07-14', '2020-07-15', '2020-07-16', '2020-07-17', '2020-07-18', '2020-07-19'
        ],
        colors: ["#206bc4"],
        legend: {
          show: false,
        },
        point: {
          show: false
        },
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-bounce-rate-1'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 24,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        stroke: {
          width: 2,
          lineCap: "round",
        },
        series: [{
          color: "#206bc4",
          data: [17, 24, 20, 10, 5, 1, 4, 18, 13]
        }],
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-bounce-rate-2'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 24,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        stroke: {
          width: 2,
          lineCap: "round",
        },
        series: [{
          color: "#206bc4",
          data: [13, 11, 19, 22, 12, 7, 14, 3, 21]
        }],
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-bounce-rate-3'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 24,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        stroke: {
          width: 2,
          lineCap: "round",
        },
        series: [{
          color: "#206bc4",
          data: [10, 13, 10, 4, 17, 3, 23, 22, 19]
        }],
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-bounce-rate-4'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 24,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        stroke: {
          width: 2,
          lineCap: "round",
        },
        series: [{
          color: "#206bc4",
          data: [6, 15, 13, 13, 5, 7, 17, 20, 19]
        }],
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-bounce-rate-5'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 24,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        stroke: {
          width: 2,
          lineCap: "round",
        },
        series: [{
          color: "#206bc4",
          data: [2, 11, 15, 14, 21, 20, 8, 23, 18, 14]
        }],
      })).render();
    });
    // @formatter:on
  </script>
  <script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function() {
      window.ApexCharts && (new ApexCharts(document.getElementById('sparkline-bounce-rate-6'), {
        chart: {
          type: "line",
          fontFamily: 'inherit',
          height: 24,
          animations: {
            enabled: false
          },
          sparkline: {
            enabled: true
          },
        },
        tooltip: {
          enabled: false,
        },
        stroke: {
          width: 2,
          lineCap: "round",
        },
        series: [{
          color: "#206bc4",
          data: [22, 12, 7, 14, 3, 21, 8, 23, 18, 14]
        }],
      })).render();
    });
    // @formatter:on
  </script>
</body>

</html>