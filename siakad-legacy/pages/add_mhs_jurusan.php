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
// tambah data 
if (isset($_POST['tambah'])) {
  $nim_npm=$_POST['pilih'];
  $jumlah_dipilih = count($nim_npm);
  for($x=0;$x<$jumlah_dipilih;$x++){
    $ok=mysqli_query($koneksi,"INSERT INTO prodi_has_mhs VALUES(NULL,'$kode_prodi','$nim_npm[$x]')");
    $update=mysqli_query($koneksi,"UPDATE user SET kode_prodi='$kode_prodi' WHERE username='$nim_npm[$x]'");
    echo "<script>window.alert('$jumlah_dipilih Data mahasiswa yang dipilih berhasil di tambah')
    window.location='jurusan_has_mhs'</script>";
  }
}
// Edit data fakultas
if (isset($_POST['update'])) {
  $nim_npm=mysqli_real_escape_string($koneksi, $_POST['nim_npm']);
  $thn_masuk=mysqli_real_escape_string($koneksi, $_POST['thn_masuk']);
  $nama_mhs=mysqli_real_escape_string($koneksi, $_POST['nama_mhs']);
  $id_jk=mysqli_real_escape_string($koneksi, $_POST['id_jk']);
  $tempat_lhr=mysqli_real_escape_string($koneksi, $_POST['tempat_lhr']);
  $tgl_lhr_mhs=mysqli_real_escape_string($koneksi, $_POST['tgl_lhr_mhs']);
  $id_agama=mysqli_real_escape_string($koneksi, $_POST['id_agama']);
  $email=mysqli_real_escape_string($koneksi, $_POST['email']);
  $alamat_mhs=mysqli_real_escape_string($koneksi, $_POST['alamat_mhs']);
  $no_telp_mhs=mysqli_real_escape_string($koneksi, $_POST['no_telp_mhs']);
  $status_mhs=mysqli_real_escape_string($koneksi, $_POST['status_mhs']);
  $update=mysqli_query($koneksi,"UPDATE mahasiswa SET nama_mhs='$nama_mhs', thn_masuk='$thn_masuk', id_jk='$id_jk', tempat_lhr='$tempat_lhr', tgl_lhr_mhs='$tgl_lhr_mhs', id_agama='$id_agama', email='$email', alamat_mhs='$alamat_mhs', no_telp_mhs='$no_telp_mhs', status_mhs='$status_mhs' WHERE nim_npm='$nim_npm'");
  if ($update == 1) {
    echo "<script>window.alert('Data Berhasil diupdate !!!')
    window.location='mhs'</script>";
  }
}
// Hapus data
if (isset($_GET['aksi'])=='hapus') {
  $nim_npm=mysqli_real_escape_string($koneksi, $_GET['nim_npm']);
  $hapus=mysqli_query($koneksi,"DELETE FROM mahasiswa WHERE nim_npm='$nim_npm'");
  if ($hapus==1) {
    echo "<script>window.alert('Berhasil dihapus !!!')
    window.location='mhs'</script>";
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
               Master Data Mahasiswa
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
                  <form action="." method="get">
                    <div class="input-icon">
                      <span class="input-icon-addon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7" /><line x1="21" y1="21" x2="15" y2="15" /></svg>
                      </span>
                      <input type="text" name="search_text" id="search_text" autofocus="on" class="form-control" placeholder="Pencarian…" aria-label="Search in website">
                    </div>
                  </form>
                </div>
                <!-- tampil data -->
                <div id="data-mhs"></div>
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
<script>
  $(document).ready(function(){
    load_data();
    function load_data(query)
    {
      $.ajax({
        url:"get_add_mhs.php",
        method:"post",
        data:{query:query},
        success:function(data)
        {
          $('#data-mhs').html(data);
        }
      });
    }
    $('#search_text').keyup(function(){
      var search = $(this).val();
      if(search != '')
      {
        load_data(search);
      }
      else
      {
        load_data();      
      }
    });
  });
</script>
</body>
</html>