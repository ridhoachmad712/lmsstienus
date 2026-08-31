<?php
session_start();
include '../config/koneksi.php';
// pengaturan aplikasi
$pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan = mysqli_fetch_array($pengaturan);
$portal_url = siakad_config('LMS_URL', 'lms_url', '');
// codingan masuk
if (isset($_POST['masuk'])) {
    siakad_login_rate_limit();
    $username = trim((string) $_POST['username']);
    $password = (string) $_POST['password'];
    $level = (string) $_POST['level'];
    $allowed_levels = ['admin', 'Jurusan/Prodi', 'dosen', 'mhs'];
    if ($username == '' or $password == '') {
        header('Location: login?login=gagal');
        exit;
    } elseif ($username !== '' and $password !== '' and in_array($level, $allowed_levels, true)) {
        $stmt = mysqli_prepare($koneksi, 'SELECT username, password, level, kode_prodi FROM user WHERE username = ? AND level = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $username, $level);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $db_username, $db_password, $db_level, $db_kode_prodi);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($found && siakad_upgrade_password($koneksi, $db_username, $db_level, $password, $db_password)) {
            $ip_address = get_client_ip();
            $os = getOS($user_agent);
            $browser = getBrowser($user_agent);
            $date = date('Y-m-d');
            $time = date('H:i:s');
            $update_stmt = mysqli_prepare($koneksi, 'UPDATE user SET ip = ?, os = ?, browser = ?, tgl = ?, waktu = ? WHERE username = ? AND level = ?');
            mysqli_stmt_bind_param($update_stmt, 'sssssss', $ip_address, $os, $browser, $date, $time, $db_username, $db_level);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            session_regenerate_id(true);
            $_SESSION['username'] = $db_username;
            $_SESSION['level'] = $db_level;
            $_SESSION['kode_prodi'] = $db_kode_prodi;
            $_SESSION['login'] = true;
            siakad_authenticate_session($koneksi);
            siakad_refresh_session_cookie();
            siakad_login_rate_limit(true);
            header('location:login?status=sukses');
            exit;
        } else {
            header('location:login?login=gagal');
            exit;

        }
    } else {
        header('location:login?login=gagal');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?= $r_pengaturan['nama_aplikasi']; ?></title>
	<link rel="shortcut icon" href="../img/<?= $r_pengaturan['logo_aplikasi']; ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
	<script src="../assets/sweetalert.js"></script>
	<link rel="stylesheet" href="../assets/sweetalert.css">
	<link href="../assets/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<script src="../assets/bootstrap.min.js"></script>
	<script src="../assets/jquery.min.js"></script>
	<!------ Include the above in your HEAD tag ---------->
	<link rel="stylesheet" type="text/css" href="../assets/style.css">

	<link href="../assets/css.css" rel="stylesheet" />
	<link href="../assets/all.css" rel="stylesheet">
</head>
<body class="login-page sidebar-collapse">


	<?php
    if (isset($_GET['login']) == 'gagal') {
        echo "<script type='text/javascript'>
		setTimeout(function () {
			swal({
				title: 'Gagal',
				text: 'Username dan password anda salah !!!',
				type: 'warning',
				timer: 3200,
				showConfirmButton: true
				});
				},10);
				window.setTimeout(function(){
					window.location.replace('login');
					},3000);
					</script>";
    }
if (isset($_GET['status']) == 'sukses') {
    echo "<script type='text/javascript'>
					setTimeout(function () {
						swal({
							title: 'Login Berhasil',
							text: 'Selamat Datang',
							type: 'success',
							timer: 3200,
							showConfirmButton: true
							});
							},10);
							window.setTimeout(function(){
								window.location.replace('dashboard');
								},3000);
								</script>";
}
?>
							<!-- Navbar -->

							<!-- End Navbar -->
							<div class="page-header clear-filter">
								<div class="page-header-image" style="background-image:url('../assets/bg-login.jpg')"></div>
								<div class="content">
									<div class="container">
										<div class="col-md-4 ml-auto mr-auto">
											<div class="card card-login card-plain">
												<form class="form" method="post" action="" autocomplete="off">
													<div class="card-header text-center">
														<img style="width: 100px;" src="../img/<?= $r_pengaturan['logo_aplikasi']; ?>" alt="">
														<p style="text-transform: uppercase;"><b>SISTEM INFORMASI AKADEMIK<br><?= $r_pengaturan['nama_kampus']; ?></b></p>
													</div>
													<center style="font-size: 16px;">
														Login Ke Akun Anda
													</center>
													<div class="card-body">
														<div class="input-group no-border input-lg">
															<div class="input-group-prepend">
																<span class="input-group-text">
																	<i class="now-ui-icons users_circle-08"></i>
																</span>
															</div>
															<input type="text" name="username" autocomplete="off" class="form-control" placeholder="Username" required>
														</div>
														<div class="input-group no-border input-lg">
															<div class="input-group-prepend">
																<span class="input-group-text">
																	<i class="now-ui-icons users_circle-08"></i>
																</span>
															</div>
															<input type="password" name="password" autocomplete="off" placeholder="Password" id="myInput" class="form-control" required>
														</div>
														<div class="form-group">
															<select class="form-control" name="level" required="required">
																<option style="color: black;" value="">-Pilih Hak Akses-</option>
																<option style="color: black;" value="admin">Admin Akademik</option>
																<option style="color: black;" value="Jurusan/Prodi">Program Studi</option>
																<option style="color: black;" value="dosen">Dosen</option>
																<option style="color: black;" value="mhs">Mahasiswa</option>
															</select>
														</div>
														<div class="input-group no-border input-lg">
															<input type="submit" name="masuk" value="Masuk" class="btn btn-primary btn-round btn-lg btn-block" style="font-size: 14px;">
														</div>
													</div>
													<div class="card-footer text-center">
														<?php if ($portal_url) { ?>
															<a href="<?= htmlspecialchars($portal_url, ENT_QUOTES, 'UTF-8'); ?>" style="color:#fff;">&larr; Kembali pilih sistem</a>
														<?php } ?>
													</div>
												</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!--   Core JS Files   -->
						<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>

						<script>
							function myFunction() {
								var x = document.getElementById("myInput");
								if (x.type === "password") {
									x.type = "text";
								} else {
									x.type = "password";
								}
							}
						</script>
					</body>
					</html>
