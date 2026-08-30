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
include '../config/koneksi.php';
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$level = $_SESSION['level'];
if (! isset($_SESSION['login'])) {
    header('location: login');
} else {
    $cek_user = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'"));
    if ($cek_user !== 1) {
        header('location: login');
    }
}
// --------------------------------------------------
// pengaturan aplikasi
$pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan = mysqli_fetch_array($pengaturan);
// tambah data fakultas
if (isset($_POST['tambah'])) {
    $kode_matkul = mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
    $nama_matkul = mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
    $sks = mysqli_real_escape_string($koneksi, $_POST['sks']);
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $id_jenis_mk = mysqli_real_escape_string($koneksi, $_POST['id_jenis_mk']);
    $input = mysqli_query($koneksi, "INSERT INTO mata_kuliah VALUES('$kode_matkul','$nama_matkul','$sks','$semester','$id_jenis_mk')");
    if ($input == 1) {
        echo "<script>window.alert('Mata Kuliah $nama_matkul Berhasil di tambahkan !!!')
    window.location='mata_kuliah'</script>";
    } else {
        echo "<script>window.alert('Tambah data gagal !!!')
    window.location='mata_kuliah'</script>";
    }
}
// Edit data fakultas
if (isset($_POST['update'])) {
    $kode_matkul = mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
    $nama_matkul = mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
    $sks = mysqli_real_escape_string($koneksi, $_POST['sks']);
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $id_jenis_mk = mysqli_real_escape_string($koneksi, $_POST['id_jenis_mk']);
    $update = mysqli_query($koneksi, "UPDATE mata_kuliah SET nama_matkul='$nama_matkul', sks='$sks', semester='$semester', id_jenis_mk='$id_jenis_mk' WHERE kode_matkul='$kode_matkul'");
    if ($update == 1) {
        echo "<script>window.alert('Berhasil diupdate menjadi $nama_matkul !!!')
    window.location='mata_kuliah'</script>";
    }
}
// Hapus data
if (isset($_GET['aksi']) == 'hapus') {
    $kode_matkul = mysqli_real_escape_string($koneksi, $_GET['kode_matkul']);
    $hapus = mysqli_query($koneksi, "DELETE FROM mata_kuliah WHERE kode_matkul='$kode_matkul'");
    if ($hapus == 1) {
        echo "<script>window.alert('Berhasil dihapus !!!')
    window.location='mata_kuliah'</script>";
    }
}

if (isset($_POST['import'])) {
    require 'php-excel-reader/excel_reader2.php';
    // upload file xls
    $target = basename($_FILES['mata_kuliah']['name']);
    move_uploaded_file($_FILES['mata_kuliah']['tmp_name'], $target);

    // mengambil isi file xls
    $data = new Spreadsheet_Excel_Reader($target, false);
    // menghitung jumlah baris data yang ada
    $jumlah_baris = $data->rowcount($sheet_index = 0);

    // jumlah default data yang berhasil di import
    $berhasil = 0;
    $stmt_import = mysqli_prepare($koneksi, 'INSERT INTO mata_kuliah VALUES(?,?,?,?,?)');
    for ($i = 2; $i <= $jumlah_baris; $i++) {

        // menangkap data dan memasukkan ke variabel sesuai dengan kolumnya masing-masing
        $kode_matkul = $data->val($i, 1);
        $nama_matkul = $data->val($i, 2);
        $sks = $data->val($i, 3);
        $semester = $data->val($i, 4);
        $id_jenis_mk = $data->val($i, 5);

        // input data ke database (table data_pegawai)
        // mysqli_query($koneksi,"INSERT INTO siswa values('$nim','$nama_mhs','$status','$waktu')");
        if ($kode_matkul != '' && $nama_matkul != '' && $sks != '' && $semester != '' && $id_jenis_mk != '') {
            mysqli_stmt_bind_param($stmt_import, 'sssss', $kode_matkul, $nama_matkul, $sks, $semester, $id_jenis_mk);
            if (mysqli_stmt_execute($stmt_import)) {
                $berhasil++;
            }
        }
    }
    mysqli_stmt_close($stmt_import);

    // hapus kembali file .xls yang di upload tadi
    @unlink($target);

    // alihkan halaman ke index.php
    echo "<script>window.alert('$berhasil Mata Kuliah berhasil diimport !!!')
  window.location='mata_kuliah'</script>";
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
    require_once '../template/header.php';
?>
    <div class="navbar-expand-md">
      <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar navbar-light">
          <div class="container-xl">
            <?php
        require_once '../template/menu.php';
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
               Master Data Mata Kuliah
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

             <div class="card">
              <div class="card-body">
                <a class="btn" data-bs-toggle="offcanvas" href="#offcanvasStart" role="button" aria-controls="offcanvasStart">
                  <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                  Tambah Data
                </a>
                <a class="btn" data-bs-toggle="modal" data-bs-target="#modal-simple">
                  <!-- Download SVG icon from http://tabler-icons.io/i/file-import -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 13v-8a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-5.5m-9.5 -2h7m-3 -3l3 3l-3 3" /></svg>
                  Import Data
                </a>
                <a href="template_file/mata_kuliah.xls" class="btn btn-success">
                  <!-- Download SVG icon from http://tabler-icons.io/i/file-download -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><line x1="12" y1="11" x2="12" y2="17" /><polyline points="9 14 12 17 15 14" /></svg>
                  Template File Mata kuliah
                </a>
                <a href="cetak/matakuliah" target="_blank" class="btn">
                  <!-- Download SVG icon from http://tabler-icons.io/i/printer -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><rect x="7" y="13" width="10" height="8" rx="2" /></svg>
                  Cetak Daftar Mata Kuliah
                </a>
              </div>
            </div>
            </br>
            <div class="table-responsive">
              <div class="my-2 my-md-0 flex-grow-1 flex-md-grow-0 order-first order-md-last">
                <form action="." method="get">
                  <div class="input-icon">
                    <span class="input-icon-addon">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7" /><line x1="21" y1="21" x2="15" y2="15" /></svg>
                    </span>
                    <input type="text" name="search_text" id="search_text" autofocus="on" class="form-control" placeholder="Cari Mata Kuliah" aria-label="Search in website">
                  </div>
                </form>
              </div>
              </br>
              <!-- tampil data -->
              <div id="data-matkul"></div>
              <!-- ------------ -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
  require_once '../template/footer.php';
?>
</div>
</div>

<div class="modal modal-blur fade" id="modal-simple" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form action="" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Import File Mata Kuliah</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
         <div class="form-group">
          <input type="file" name="mata_kuliah" class="form-control-file" required="required">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="import" class="btn btn-success">
          <!-- Download SVG icon from http://tabler-icons.io/i/file-import -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 13v-8a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-5.5m-9.5 -2h7m-3 -3l3 3l-3 3" /></svg>
          Import
        </button>
      </div>
    </div>
  </div>
</form>
</div>


<div class="page-body">
  <div class="container-xl">
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasStart" aria-labelledby="offcanvasStartLabel">
      <form action="" method="post">
        <div class="offcanvas-header">
          <h2 class="offcanvas-title" id="offcanvasStartLabel">Tambah Data Mata Kuliah</h2>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
         <div>
          <div class="mb-3">
            <label>Kode Mata Kuliah</label>
            <input type="text" name="kode_matkul" class="form-control" required="require">
          </div>
          <div class="mb-3">
            <label>Nama Mata Kuliah</label>
            <textarea class="form-control" name="nama_matkul" required="required"></textarea>
          </div>
          <div class="mb-3">
            <label>SKS</label>
            <input type="number" name="sks" class="form-control" required="required">
          </div>
          <div class="mb-3">
            <label>Semester</label>
            <input type="text" name="semester" class="form-control" required="required">
          </div>
          <div class="mb-3">
            <label>Jenis Mata Kuliah</label>
            <select class="form-control" name="id_jenis_mk" required>
              <option value="">Pilih Jenis Mata Kuliah</option>
              <?php
            $jenis_mk = mysqli_query($koneksi, 'SELECT * FROM tbl_jenis_mk');
while ($tampil_jenis_mk = mysqli_fetch_array($jenis_mk)) {
    ?>
               <option value="<?= $tampil_jenis_mk['id_jenis_mk']; ?>"><?= $tampil_jenis_mk['jenis_mk']; ?></option>
             <?php } ?>
           </select>
         </div>
       </div>
       <div class="mt-3">
        <button class="btn btn-green" type="submit" name="tambah">
          Simpan
        </button>
        <button class="btn btn-red" type="button" data-bs-dismiss="offcanvas">
          Batal
        </button>
      </div>
    </div>
  </form>
</div>

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
        url:"search_matkul.php",
        method:"post",
        data:{query:query},
        success:function(data)
        {
          $('#data-matkul').html(data);
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
