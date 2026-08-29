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
// data fakultas
$kode_fakultas=mysqli_real_escape_string($koneksi, $_GET['kode_fakultas']);
$fakultas=mysqli_query($koneksi,"SELECT * FROM tbl_fakultas WHERE kode_fakultas='$kode_fakultas'");
$t_fakultas=mysqli_fetch_array($fakultas);
// -------------------------
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
if (isset($_POST['tambah'])) {
  $kode_fakultas = $_POST['kode_fakultas'];
  $kode_prodi = $_POST['kode_prodi'];
  $jumlah_dipilih = count($kode_prodi);
  for($x=0;$x<$jumlah_dipilih;$x++){
    $input=mysqli_query($koneksi, "INSERT INTO fakultas_has_jurusan values(NULL,'$kode_fakultas','$kode_prodi[$x]')");
  }
  if ($input > 0) {
    echo "<script>window.alert('Berhasil di tambahkan !!!')
    window.location='fak-has-jur?kode_fakultas=$kode_fakultas'</script>";
  }else{
    echo "<script>window.alert('Tambah data gagal !!!')
    window.location='fak-has-jur?kode_fakultas=$kode_fakultas'</script>";
  }
}
// Hapus data
if (isset($_GET['aksi'])=='hapus') {
  $kode_fakultas=mysqli_real_escape_string($koneksi, $_GET['kode_fakultas']);
  $id=mysqli_real_escape_string($koneksi, $_GET['id']);
  $hapus=mysqli_query($koneksi,"DELETE FROM fakultas_has_jurusan WHERE id='$id'");
  if ($hapus==1) {
    echo "<script>window.alert('Berhasil dihapus !!!')
    window.location='fak-has-jur?kode_fakultas=$kode_fakultas'</script>";
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
              <h4>Data Program Studi</h4>
              <h5>
                FAKULTAS <?= $t_fakultas['nama_fakultas']; ?>
              </h5>
            </div>
          </div>
        </div>
      </div>
      <div class="page-body">
        <div class="container-xl">
          <div class="row row-cards">



            <div class="col-12">
              <div class="card">
               <div class="card">
                <div class="card-body">
                  <a href="fakultas" class="btn btn-secondary">
                    Kembali
                  </a>
                  <a href="#" class="btn" data-bs-toggle="modal" data-bs-target="#modal-report">
                    <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                    Tambah Data
                  </a>
                </div>
              </div>
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
                <div id="data-jurusan"></div>
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

<!-- input data  -->
<div class="modal modal-blur fade" id="modal-report" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <form action="" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Data Program Studi pada Institusi <?= $t_fakultas['nama_fakultas']; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th>PILIH</th>
                <th>KODE PROGRAM STUDI</th>
                <th>NAMA PROGRAM STUDI</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no=1;
              $jur=mysqli_query($koneksi,"SELECT * FROM prodi ORDER BY nama_prodi ASC");
              while ($t_jur=mysqli_fetch_array($jur)) {
                $kode_prodi=$t_jur['kode_prodi'];
                ?>
                <?php 
                $cek_prodi=mysqli_query($koneksi,"SELECT * FROM fakultas_has_jurusan WHERE kode_prodi='$kode_prodi'");
                $sum_prodi=mysqli_num_rows($cek_prodi);
                if ($sum_prodi > 0) {
                  ?>

                <?php }else{ ?>

                  <tr>
                    <td>
                      <input type="hidden" name="kode_fakultas" value="<?= $kode_fakultas; ?>">
                      <input type="checkbox" name="kode_prodi[]" value="<?= $kode_prodi; ?>">
                    </td>
                    <td><?= $kode_prodi; ?></td>
                    <td><?= $t_jur['nama_prodi']; ?></td>
                  </tr>
                <?php }} ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <a href="#" class="btn" data-bs-dismiss="modal">
              Tutup
            </a>
            <button type="submit" name="tambah" class="btn btn-info">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!--  -->

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
          url:"search_fak_has_jur.php?kode_fakultas=<?= $kode_fakultas; ?>",
          method:"post",
          data:{query:query},
          success:function(data)
          {
            $('#data-jurusan').html(data);
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