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
$logo_apk=$r_pengaturan['logo_aplikasi'];
// tambah data fakultas
if (isset($_POST['tambah'])) {
  $thn_akademik=mysqli_real_escape_string($koneksi, $_POST['thn_akademik']);
  $input=mysqli_query($koneksi,"INSERT INTO thn_akademik VALUES(NULL,'$thn_akademik')");
  if ($input == 1) {
    echo "<script>window.alert('Tahun Akademik $thn_akademik Berhasil di tambahkan !!!')
    window.location='thn_akademik'</script>";
  }else{
    echo "<script>window.alert('Tambah data gagal !!!')
    window.location='thn_akademik'</script>";
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
  $hapus=mysqli_query($koneksi,"DELETE FROM thn_akademik WHERE id_thn_akademik='$id'");
  if ($hapus==1) {
    echo "<script>window.alert('Berhasil dihapus !!!')
    window.location='thn_akademik'</script>";
  }
}
if (isset($_POST['pengaturan'])) {
  $nama_aplikasi=mysqli_real_escape_string($koneksi, $_POST['nama_aplikasi']);
  $copyright=mysqli_real_escape_string($koneksi, $_POST['copyright']);
  $alamat=mysqli_real_escape_string($koneksi, $_POST['alamat']);
  $nama_kampus=mysqli_real_escape_string($koneksi, $_POST['nama_kampus']);
  $email=mysqli_real_escape_string($koneksi, $_POST['email']);
  $no_telp=mysqli_real_escape_string($koneksi, $_POST['no_telp']);
  $kota=mysqli_real_escape_string($koneksi, $_POST['kota']);

  $ekstensi_diperbolehkan = array('png','jpg','JPG','PNG','jpeg','JPEG');
  $logo = $_FILES['logo']['name'];
  $x = explode('.', $logo);
  $ekstensi = strtolower(end($x));
  $ukuran = $_FILES['logo']['size'];
  $file_tmp = $_FILES['logo']['tmp_name'];     
  if(in_array($ekstensi, $ekstensi_diperbolehkan) === true){
    if($ukuran <= 4000000){  
      $x_logo = $logo; 
      move_uploaded_file($file_tmp, '../img/'.$x_logo);
    }else{
      echo "<script>window.alert('Ukuran File logo melebihi batas !!!')
      window.location='pengaturan'</script>";
    }
  }else{
    $x_logo=$logo_apk;
  }
  $update_pengaturan=mysqli_query($koneksi,"UPDATE pengaturan SET nama_kampus='$nama_kampus', nama_aplikasi='$nama_aplikasi', logo_aplikasi='$x_logo', copyright='$copyright', alamat='$alamat', email='$email', no_telp='$no_telp', kota='$kota' WHERE id_pengaturan=1");
  if ($update_pengaturan) {
    echo "<script>window.alert('Pengaturan berhasil disimpan')
    window.location='pengaturan'</script>";
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
                Pengaturan
              </h2>
            </div>
          </div>
        </div>
      </div>
      <div class="page-body">
        <div class="container-xl">
          <div class="row row-cards">

            <div class="page-body">
              <div class="container-xl">

                <div class="card">
                  <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                      <div class="offcanvas-body">
                       <div>
                        <div class="form-group">
                          <div class="form-group">
                            <img style="width: 85px;" src="../img/<?= $r_pengaturan['logo_aplikasi']; ?>"><br>
                            <label>Logo Kampus</label> 
                            <input type="file" name="logo" class="form-control">
                          </div><br>
                          <label>Nama Aplikasi</label> 
                          <input type="text" name="nama_aplikasi" value="<?= $r_pengaturan['nama_aplikasi']; ?>" class="form-control" required="require">
                        </div><br>
                        <div class="form-group">
                          <label>Copyright</label> 
                          <input type="text" name="copyright" value="<?= $r_pengaturan['copyright']; ?>" class="form-control" required="require">
                        </div>
                        <br>
                        <div class="form-group">
                          <label>Nama Kampus</label> 
                          <textarea class="form-control" name="nama_kampus"><?= $r_pengaturan['nama_kampus']; ?></textarea>
                        </div>
                        <br>
                        <div class="form-group">
                          <label>Kota Kampus</label> 
                          <input class="form-control" autocomplete="off" type="text" name="kota" value="<?= $r_pengaturan['kota']; ?>">
                        </div>
                        <br>
                        <div class="form-group">
                          <label>Alamat Kampus</label> 
                          <textarea class="form-control" name="alamat"><?= $r_pengaturan['alamat']; ?></textarea>
                        </div>
                        <br>
                        <div class="form-group">
                          <label>Email</label>
                          <input type="email" name="email" value="<?= $r_pengaturan['email']; ?>" placeholder="emailkampus@gmail.com" class="form-control">
                        </div>
                        <br>
                        <div class="form-group">
                          <label>No Telp</label>
                          <input type="text" placeholder="No Telp" name="no_telp" value="<?= $r_pengaturan['no_telp']; ?>" class="form-control">
                        </div>
                      </div>
                      <div class="mt-3">
                        <button class="btn btn-info" type="submit" name="pengaturan">
                          <!-- Download SVG icon from http://tabler-icons.io/i/file-like -->
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="16" width="3" height="5" rx="1" /><path d="M6 20a1 1 0 0 0 1 1h3.756a1 1 0 0 0 .958 -.713l1.2 -3c.09 -.303 .133 -.63 -.056 -.884c-.188 -.254 -.542 -.403 -.858 -.403h-2v-2.467a1.1 1.1 0 0 0 -2.015 -.61l-1.985 3.077v4z" /><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 12.1v-7.1a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-2.3" /></svg>
                          Simpan
                        </button>
                      </div>
                    </div>
                  </form>
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