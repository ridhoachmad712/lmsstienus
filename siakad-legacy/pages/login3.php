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
// Endpoint login lama dinonaktifkan agar seluruh autentikasi memakai alur
// login yang telah dilindungi rate limit dan migrasi hash password.
header('Location: login', true, 302);
exit;
include '../config/koneksi.php';
// pengaturan aplikasi
$pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan = mysqli_fetch_array($pengaturan);
// codingan masuk
if (isset($_POST['masuk'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, md5($_POST['password']));
    $level = mysqli_real_escape_string($koneksi, $_POST['level']);
    if ($username == '' or $password == '') {
        header('location:login?kesalahan=login');
    } elseif ($username !== '' and $password !== '') {
        $user = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'");
        $r_user = mysqli_fetch_array($user);
        $data_user = mysqli_num_rows($user);
        if ($data_user == 1) {
            $_SESSION['password'] = $r_user['password'];
            $_SESSION['username'] = $r_user['username'];
            $_SESSION['level'] = $r_user['level'];
            $_SESSION['kode_prodi'] = $r_user['kode_prodi'];
            $_SESSION['login'] = true;
            header('location:login?Berhasil=login');
        } else {
            header('location:login?login=gagal');
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
  <link rel="shortcut icon" href="../img/<?= $r_pengaturan['logo_aplikasi']; ?>" />
  <!-- CSS files -->
  <link href="../dist/css/tabler.min.css" rel="stylesheet"/>
  <link href="../dist/css/tabler-flags.min.css" rel="stylesheet"/>
  <link href="../dist/css/tabler-payments.min.css" rel="stylesheet"/>
  <link href="../dist/css/tabler-vendors.min.css" rel="stylesheet"/>
  <link href="../dist/css/demo.min.css" rel="stylesheet"/>
  <style type="text/css">
    body{
      background-color: gray;
    }
  </style>
</head>
<body class="antialiased">
  <div class="page page-center">
    <div class="container-tight py-4">
      <div class="text-center mb-4">
        <!-- <a href="."><img src="./static/logo.svg" height="36" alt=""></a> -->
      </div>
      <form class="card card-md" action="" method="post" autocomplete="off">
        <div class="card-body">
          <img style="height: 120px; width: 100%;" src="../img/siakad.jpg"><br><br>
          <!-- <h2 class="card-title text-center mb-4">Masuk menggunakan akun anda</h2> -->
          <div class="mb-3">
           <?php
           if (isset($_GET['kesalahan']) == 'login') {
               ?>
            <div class="alert btn-warning" role="alert">
              <p style="font-size: 12pt;"><strong>Kesalahan Login !!!</strong></p>
            </div>
          <?php } ?>
          <?php
          if (isset($_GET['Berhasil']) == 'login') {
              ?>
            <div class="alert btn-success" role="alert">
              <p style="font-size: 12pt;"><strong>Login Anda Berhasil</strong></p>
            </div>

            <script type="text/javascript">
              window.setTimeout(function(){
                window.location.replace('dashboard');
              },3000);
            </script>

          <?php } ?>
          <?php
          if (isset($_GET['login']) == 'gagal') {
              ?>
            <div class="alert btn-danger" role="alert">
              <p style="font-size: 12pt;"><strong>Username atau password salah !!!</strong></p>
            </div>
          <?php } ?>
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" autofocus="on" placeholder="Username" autocomplete="off" required="required">
        </div>
        <div class="mb-2">
          <label class="form-label">
            Password
          </label>
          <div class="input-group input-group-flat">
            <input type="password" class="form-control form-password" name="password" placeholder="Password" autocomplete="off" required="required">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">
            Hak Akses
          </label>
          <div class="input-group input-group-flat">
            <select class="form-control" name="level" required="required">
              <option value="">--Hak Akses--</option>
              <option value="admin">Admin</option>
              <!--       <option value="akademik">Akademik</option> -->
              <option value="Jurusan/Prodi">Jurusan/Prodi</option>
              <option value="dosen">Dosen</option>
              <option value="mhs">Mahasiswa</option>
            </select>
          </div>
        </div>

        <div class="mb-2">
          <label class="form-check">
            <input type="checkbox" class="form-check-input form-checkbox"/>
            <span class="form-check-label">Tampil password</span>
          </label>
        </div>
        <div class="form-footer">
          <button type="submit" name="masuk" class="btn btn-info w-100">MASUK</button><br><br>
          <button type="reset" class="btn btn-danger w-100">RESET</button>
        </div>
      </div>
      <div class="hr-text">&copy;Copyright <?= $r_pengaturan['nama_aplikasi']; ?> 2021</div>
      <div class="card-body">
        <div class="row">


        </div>
      </div>
    </form>

  </div>
</div>
<!-- Libs JS -->
<!-- Tabler Core -->
<script src="../dist/js/jquery.js"></script>
<script src="../dist/js/tabler.min.js"></script>
<script>
  window.setTimeout (function(){
    $ (".alert").fadeTo(500, 0). slideUp(500, function(){
      $(this).remove();
    });
  }, 2000);
</script>
<script type="text/javascript">
  $(document).ready(function() {
    var cek = $('.form-checkbox').val();
    $('.form-checkbox').click(function() {
      if ($(this).is(':checked')) {
        $('.form-password').attr('type', 'text');
      } else {
        $('.form-password').attr('type', 'password');
      }
    });
  });
</script>
</body>
</html>
