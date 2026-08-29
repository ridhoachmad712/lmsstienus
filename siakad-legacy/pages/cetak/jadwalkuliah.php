<?php 
session_start();
include"../../config/koneksi.php";
$username=$_SESSION['username'];
$password=$_SESSION['password'];
$level=$_SESSION['level'];
$kode_prodi=$_SESSION['kode_prodi'];
// 
if ($level=="Jurusan/Prodi") {
  $username=$_GET['nim_npm'];
}
// 
$prodi=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM prodi WHERE kode_prodi='$kode_prodi'"));

$fakultas=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM fakultas_has_jurusan 
  INNER JOIN tbl_fakultas ON fakultas_has_jurusan.kode_fakultas=tbl_fakultas.kode_fakultas WHERE kode_prodi='$kode_prodi'"));
$kode_fakultas=$fakultas['kode_fakultas'];

// if (!isset($_SESSION["login"]) ) {
//   header("location: login");
// }else{
//   $cek_user=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'"));
//   if ($cek_user !== 1) {
//     header("location: login");
//   }
// }
// --------------------------------------------------
// pengaturan aplikasi 
$pengaturan=mysqli_query($koneksi,"SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan=mysqli_fetch_array($pengaturan);
// MAHASISWA

$mhs=mysqli_query($koneksi,"SELECT * FROM mahasiswa
  INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
  INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$username'");
$tampil_mhs=mysqli_fetch_array($mhs);


if (isset($_GET['aksi'])=='hapus') {
  $id_krs=$_GET['id_krs'];
  $id_thn_akademik=$_GET['id_thn_akademik'];
  $hapus=mysqli_query($koneksi,"DELETE FROM krs_mhs WHERE id_krs='$id_krs'");
  echo "<script>window.location='krs?qwe=$id_thn_akademik'</script>";
}

?>
<?php 
error_reporting(0);
include "printer.css"; ?>
<html>
<head>
    <title>Jadwal Kuliah Mahasiswa <?= $tampil_mhs['nama_mhs']; ?></title>
    <style>
        body {
            font-family: Verdana;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #ffffff;
            color: #000;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .logo {
            width: 75px;
            height: 75px;
            border-radius: 0%;
        }
        .header h2 {
            font-size: 20px;
            text-transform: uppercase;
            margin: 0;
            margin-bottom: 0;
        }
        .address {
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body onload="javascript:window.print()">
    <div class="header">
        <img class="logo" src="../../img/<?= $r_pengaturan['logo_aplikasi']; ?>" alt="Logo">
        <h2><?= $r_pengaturan['nama_kampus']; ?></h2>
    </div>
    <div class="address" style="font-size: 12px;">
        <p>Jl. Ujung Pandang (Komp. Taman Bahari) No.25-26, Kec. Ujung Pandang, Kota Makassar <br>(0411) 3634463 – 3639368 | stienusantaramks@gmail.com | www.stienus.ac.id</p>
    </div>
    <hr>
  <table class="basic" width="750" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" class="style7">
        <h2 style="font-size: 17px;">JADWAL KULIAH MAHASISWA</h2>
        </td>
      </tr>
    </table>
    <table class="table_1">

       <tr>
          <td><strong>Semester</strong></td>
          <td>
             <?php 
             if (isset($_GET['qwe'])) {
              $id_thn_akademik=mysqli_real_escape_string($koneksi, $_GET['qwe']);
              $thn_akademik=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM thn_akademik WHERE id_thn_akademik='$id_thn_akademik'"));
            }
            ?>
            <?= $thn_akademik['ket']; ?> - <?= $thn_akademik['thn_akademik']; ?>
          </strong>
        </td>
      </tr>
      <tr>
        <td><strong>Nama Mahasiswa</strong></td>
        <td><?= $tampil_mhs['nama_mhs']; ?></td>
       </tr>
      <tr>
        <td><strong>NIM</strong></td>
        <td><?= $tampil_mhs['nim_npm']; ?></td>
        </tr>
        <tr>
            <td><strong>Program Studi</strong></td>
            <td><?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></strong></td>
         </tr>
    </table>
    <table class="table_2">
      <tr style="height: 30px;">
       <th>Hari</th>
       <th style="width: 100px;">Waktu</th>
       <th>Mata Kuliah</th>
       <th>SKS</th>
       <th>Nama Dosen</th>
       <th>Ruangan</th>
     </tr>

     <?php
     if (isset($_GET['qwe'])) {
      $no=1;
      $id_thn_akademik=$_GET['qwe'];
      $krs=mysqli_query($koneksi,"SELECT * FROM krs_mhs
        INNER JOIN jadwal_mengajar ON krs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
        LEFT JOIN  mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul
        LEFT JOIN tbl_ruangan ON jadwal_mengajar.kode_ruangan=tbl_ruangan.kode_ruangan
        LEFT JOIN tbl_hari ON jadwal_mengajar.id_hari=tbl_hari.id_hari
        LEFT JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE krs_mhs.nim_npm='$username' AND krs_mhs.id_thn_akademik='$id_thn_akademik' AND krs_mhs.kode_prodi='$kode_prodi'
        ORDER BY tbl_hari.id_hari ASC, jadwal_mengajar.mulai_jam ASC");
      while ($t_krs=mysqli_fetch_array($krs)) { 
        $id_krs=$t_krs['id_krs'];
        ?>
        <tr style="height: 40px;">
          <td align="center"><?= $t_krs['nama_hari']; ?></td>
          <td align="center"><?= date("H:i", strtotime($t_krs['mulai_jam'])); ?> - <?= date("H:i", strtotime($t_krs['sampai_jam'])); ?></td>
          <td><?= $t_krs['nama_matkul']; ?></td>
          <td style="text-align: center;"><?= $t_krs['sks']; ?></td>
          <td><?= $t_krs['nama_dosen']; ?></td>
          <td align="center"><?= $t_krs['nama_ruangan']; ?></td>
        </tr> 
      <?php }} ?>
      </table>
    <table class="table_1">
      <tr>
        <td valign="top">Total SKS Yang Diambil</td>
        <td valign="top">
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
          echo '<strong>' . $hasil_sks . ' SKS</strong>';
          ?>
        <?php } ?>
        </td>
  </tr>
</table>

<!-- Tambahkan teks berikut di bawah tabel Total SKS yang Diambil -->
</div>
<br>
    <div class="text" style="font-size: 11px;">
        <p><b>Catatan :</b>
        <br>Jadwal Kuliah ini adalah jadwal yang ditetapkan oleh Program Studi pada awal semester,
        <br>Jadwal dan Ruangan dapat berubah mengikuti kesepakatan Dosen dan Mahasiswa dan telah disetujui oleh Akademik.</p>
        <br>
        <br>
        Akademik STIE Nusantara Makassar
    </div>
      </td>
    </tr>
  </tr>
  </form>
</body>
</html>
