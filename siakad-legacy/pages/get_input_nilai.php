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
$id_jadwal=$_GET['qwe'];

$d=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar WHERE id_jadwal='$id_jadwal'"));
$kode_prodi=$d['kode_prodi'];
$id_thn_akademik=$d['id_thn_akademik'];
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
  $mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa
    INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
    INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$username'");
  $tampil_mhs=mysqli_fetch_array($mhs);
}
if (isset($_POST['filter'])) {
  $id_thn_akademik=mysqli_real_escape_string($koneksi, $_POST['id_thn_akademik']);
}
if (isset($_POST['simpan_nilai'])) {
  $nim_npm=$_POST['nim_npm'];
  $nilai_uas=$_POST['nilai_uas'];
  $jumlah_dipilih = count($nim_npm); 
  for($j=0;$j<$jumlah_dipilih;$j++){
    // rumus
    $nilai_akhir[$j]=($nilai_uas[$j]*1);
    // 
    $grade=mysqli_query($koneksi,"SELECT * FROM tbl_grade ORDER BY  grade DESC");
    while ($t_grade=mysqli_fetch_array($grade)) {
      $nilai_grade=$t_grade['nilai_awal'];
      if ($nilai_akhir[$j] >= $nilai_grade) {
        $hasil_grade[$j]=$t_grade['grade'];
        $hasil_bobot[$j]=$t_grade['bobot'];
      }
    }
    mysqli_query($koneksi,"UPDATE khs_mhs SET nilai_uas='$nilai_uas[$j]', nilai_akhir='$nilai_akhir[$j]', bobot='$hasil_bobot[$j]', grade='$hasil_grade[$j]' WHERE nim_npm='$nim_npm[$j]' AND kode_prodi='$kode_prodi' AND id_jadwal='$id_jadwal' AND id_thn_akademik='$id_thn_akademik'");
    echo "<script>window.alert('Nilai berhasil disimpan. Terima Kasih.')
    window.location='get_input_nilai?qwe=$id_jadwal'</script>";
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
                Input Nilai Mahasiswa
              </h2>
              <p style="margin-top: 1em;">Halo Bapak/Ibu <strong><?= $tampil_dosen['nama_dosen']; ?></strong><br><br>Silakan menginput nilai mahasiswa dengan menggunakan nilai angka.<br>Pastikan anda menekan tombol <strong>Simpan Nilai</strong> untuk menyimpan nilai.</p>
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
                              <th>Tahun Akademik</th>
                              <td> 
                               <?php 
                               $th=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar INNER JOIN thn_akademik ON jadwal_mengajar.id_thn_akademik=thn_akademik.id_thn_akademik WHERE id_jadwal='$id_jadwal' AND kode_prodi='$kode_prodi'"));
                               $id_thn_akademik=$th['id_thn_akademik'];
                               ?>
                               : <?= $th['thn_akademik']; ?> - Semester <?= $th['ket']; ?>
                             </td>
                           </tr>
                            <tr>
                              <th>Program Studi</th>
                              <td>: <?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></td>
                            </tr>
                            <tr>
                              <th>Mata Kuliah</th>
                              <td>: 
                                <?php 
                                $mk=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar INNER JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE id_jadwal='$id_jadwal' AND kode_prodi='$kode_prodi'"));
                                ?>
                                <?= $mk['nama_matkul']; ?> -  <?= $mk['semester']; ?>
                              </td>
                            </tr>
                            <tr>
                              <th>Jam Kuliah</th>
                              <td>: 
                                <?php 
                                $jm=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar WHERE id_jadwal='$id_jadwal' AND kode_prodi='$kode_prodi'"));
                                ?>
                                <?= date('H:i', strtotime($jm['mulai_jam'])); ?> - <?= date('H:i', strtotime($jm['sampai_jam'])); ?>
                              </td>
                            </tr>
                            <tr>
                              <th>Dosen Mata Kuliah</th>
                              <td>: 
                                <?php 
                                $ds=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM jadwal_mengajar INNER JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE id_jadwal='$id_jadwal' AND kode_prodi='$kode_prodi'"));
                                ?>
                                <?= $ds['nama_dosen']; ?>
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
                     <!--  <td><a class="btn" href="input_nilai?qwe=<?= $id_thn_akademik; ?>" style="text-decoration: none;">Kembali</a></td> -->
                      <td><a class="btn btn-info" href="input_nilai_dosen" style="text-decoration: none;">Kembali</a></td>
                      <td><input type="submit" name="simpan_nilai" class="btn btn-green" value="Simpan Nilai"></td>
                    </tr>
                  </table>
                </div>
              <table class="table table-vcenter card-table">
                  <thead>
                      <tr>
                          <th style="text-align: center;">No</th>
                          <th style="text-align: center;">Nim</th>
                          <th style="text-align: center;">Nama mahasiswa</th>
                          <th style="text-align: center;">Input Nilai Angka</th>
                          <th style="text-align: center;">Nilai Huruf</th>
                      </tr>
                  </thead>
                  <?php
                  $no = 1;
                  $nilai = mysqli_query($koneksi, "SELECT * FROM khs_mhs INNER JOIN mahasiswa ON khs_mhs.nim_npm=mahasiswa.nim_npm WHERE id_thn_akademik='$id_thn_akademik' AND kode_prodi='$kode_prodi' AND id_jadwal='$id_jadwal'ORDER BY mahasiswa.nim_npm ASC");
                  while ($t_nilai = mysqli_fetch_array($nilai)) {
                      // Menghitung nilai huruf berdasarkan nilai angka
                      $nilai_angka = $t_nilai['nilai_uas'];
                      $nilai_huruf = '';

                      // Logika penentuan nilai huruf
                      if ($nilai_angka >= 86 && $nilai_angka <= 100) {
                          $nilai_huruf = 'A';
                      } elseif ($nilai_angka >= 81 && $nilai_angka <= 85) {
                          $nilai_huruf = 'A-';
                      } elseif ($nilai_angka >= 71 && $nilai_angka <= 80) {
                          $nilai_huruf = 'B';
                      } elseif ($nilai_angka >= 66 && $nilai_angka <= 70) {
                          $nilai_huruf = 'B-';
                      } elseif ($nilai_angka >= 56 && $nilai_angka <= 65) {
                          $nilai_huruf = 'C';
                      } elseif ($nilai_angka >= 46 && $nilai_angka <= 55) {
                          $nilai_huruf = 'D';
                      } elseif ($nilai_angka >= 1 && $nilai_angka <= 45) {
                          $nilai_huruf = 'E';
                      } else {
                          $nilai_huruf = '-';
                      }
                      ?>
                      <tr>
                          <td style="text-align: center;"><?= $no++; ?>.</td>
                          <td style="text-align: center;"><?= $t_nilai['nim_npm']; ?><input type="hidden" name="nim_npm[]" value="<?= $t_nilai['nim_npm']; ?>"></td>
                          <td style="text-transform: capitalize;"><?= $t_nilai['nama_mhs']; ?></td>
                          <td>
                            <input type="number" name="nilai_uas[]" value="<?= $nilai_angka == 0 ? '' : $nilai_angka; ?>" min="0" max="100" placeholder="Input Nilai Disini" class="form-control" oninput="updateNilaiHuruf(this)">
                          </td>
                          <td id="nilai_huruf_<?= $no; ?>" style="text-align: center;"><b><?= $nilai_huruf; ?></b></td>
                      </tr>
                  <?php } ?>
              </table>

              <script>
                  function updateNilaiHuruf(input) {
                      var nilaiAngka = parseFloat(input.value);
                      var nilaiHurufElement = input.closest('tr').querySelector('[id^="nilai_huruf_"]');
                      var nilaiHuruf = '';

                      // Logika penentuan nilai huruf
                      if (!isNaN(nilaiAngka)) {
    if (nilaiAngka >= 86 && nilaiAngka <= 100) {
        nilaiHuruf = 'A';
    } else if (nilaiAngka >= 81 && nilaiAngka <= 85) {
        nilaiHuruf = 'A-';
    } else if (nilaiAngka >= 71 && nilaiAngka <= 80) {
        nilaiHuruf = 'B';
    } else if (nilaiAngka >= 66 && nilaiAngka <= 70) {
        nilaiHuruf = 'B-';
    } else if (nilaiAngka >= 56 && nilaiAngka <= 65) {
        nilaiHuruf = 'C';
    } else if (nilaiAngka >= 46 && nilaiAngka <= 55) {
        nilaiHuruf = 'D';
    } else if (nilaiAngka >= 1 && nilaiAngka <= 45) {
        nilaiHuruf = 'E';
    } else {
        nilaiHuruf = '-';
    }
}

                      nilaiHurufElement.textContent = nilaiHuruf;
                  }
              </script>


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