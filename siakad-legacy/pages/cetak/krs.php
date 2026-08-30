<?php
session_start();
include '../../config/koneksi.php';
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$level = $_SESSION['level'];
$kode_prodi = $_SESSION['kode_prodi'];
//
$username = siakad_scoped_student_username($koneksi, isset($_GET['nim_npm']) ? $_GET['nim_npm'] : $username);
//
$prodi = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM prodi WHERE kode_prodi='$kode_prodi'"));

$fakultas = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM fakultas_has_jurusan
  INNER JOIN tbl_fakultas ON fakultas_has_jurusan.kode_fakultas=tbl_fakultas.kode_fakultas WHERE kode_prodi='$kode_prodi'"));
$kode_fakultas = $fakultas['kode_fakultas'];

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
$pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan='1'");
$r_pengaturan = mysqli_fetch_array($pengaturan);
// MAHASISWA

$mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa
  INNER JOIN tbl_jk ON mahasiswa.id_jk=tbl_jk.id_jk
  INNER JOIN tbl_agama ON mahasiswa.id_agama=tbl_agama.id_agama WHERE nim_npm='$username'");
$tampil_mhs = mysqli_fetch_array($mhs);

?>
<?php
function tanggal_indonesia($tanggal)
{
    $bulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];

    $split = explode('-', $tanggal);

    return $split[2].' '.$bulan[$split[1]].' '.$split[0];
}

?>
<?php
error_reporting(0);
include 'printer.css'; ?>
<html>
<head>
    <title>Kartu Rencana Studi <?= $tampil_mhs['nama_mhs']; ?></title>
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
        <h2 style="font-size: 17px;">KARTU RENCANA STUDI (KRS)</h2>
        </td>
      </tr>
    </table>
    <table class="table_1">

      <tr>
      <td><strong>Tahun Akademik</strong></td>
          <td>
             <?php
             if (isset($_GET['qwe'])) {
                 $id_thn_akademik = mysqli_real_escape_string($koneksi, $_GET['qwe']);
                 $thn_akademik = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM thn_akademik WHERE id_thn_akademik='$id_thn_akademik'"));
             }
?>
            <?= $thn_akademik['thn_akademik']; ?> - Semester <?= $thn_akademik['ket']; ?>
          </strong>
        </td>
          <td rowspan="6">
            <?php
if ($tampil_mhs['foto_mhs'] == '') {
    ?>
             <img style="border-radius: 0%; width: 140px; height: 144px; overflow: hidden;" src="../foto_mhs/avatar-blank.png">
           <?php } else { ?>
            <img style="border-radius: 0%; width: 140px; height: 144px; overflow: hidden;" src="../foto_mhs/<?= $tampil_mhs['foto_mhs']; ?>">
          <?php } ?>
        </td>
      </tr>
      <tr>
            <td><strong>Angkatan</strong></td>
            <td><?= $tampil_mhs['thn_masuk']; ?></td>
           </tr>
      <tr>
            <td><strong>Program Studi</strong></td>
            <td><?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></strong></td>
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
            <td><strong>Penasehat Akademik</strong></td>
            <td>
             <?php
    $pa = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM mhs_has_pa INNER JOIN dosen ON mhs_has_pa.nip=dosen.nip WHERE nim_npm='$username'"));
?>
             <?= $pa['nama_dosen']; ?>
           </strong></td>
         </tr>
    </table>
    <table class="table_2">
      <tr>
       <th>No.</th>
       <th>Kode</th>
       <th>Mata Kuliah</th>
       <th>SKS</th>
       <th>Kelas</th>
       <th>Nama Dosen</th>
     </tr>

     <?php
     if (isset($_GET['qwe'])) {
         $no = 1;
         $id_thn_akademik = $_GET['qwe'];
         $krs = mysqli_query($koneksi, "SELECT * FROM krs_mhs
        INNER JOIN jadwal_mengajar ON krs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
        LEFT JOIN  mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul
        LEFT JOIN tbl_ruangan ON jadwal_mengajar.kode_ruangan=tbl_ruangan.kode_ruangan
        LEFT JOIN tbl_hari ON jadwal_mengajar.id_hari=tbl_hari.id_hari
        LEFT JOIN dosen ON jadwal_mengajar.nip=dosen.nip WHERE krs_mhs.nim_npm='$username' AND krs_mhs.id_thn_akademik='$id_thn_akademik' AND krs_mhs.kode_prodi='$kode_prodi' ORDER BY jadwal_mengajar.id_hari ASC");
         while ($t_krs = mysqli_fetch_array($krs)) {
             $id_krs = $t_krs['id_krs'];
             ?>
        <tr>
          <td align="center"><?= $no++; ?>.</td>
          <td align="center"><?= $t_krs['kode_mk']; ?></td>
          <td><?= $t_krs['nama_matkul']; ?></td>
          <td align="center"><?= $t_krs['sks']; ?> SKS</td>
          <td align="center"><?= $t_krs['semester']; ?></td>
          <td><?= $t_krs['nama_dosen']; ?></td>
        </tr>
      <?php }
         } ?>
    </table>
    <table class="table_1">

      <tr>
        <td valign="top">Total SKS Yang Diambil</td>
        <td valign="top">
         <?php
             $cek_data = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM krs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND id_thn_akademik='$id_thn_akademik'"));
if ($cek_data > 0) {
    ?>
           <?php
    $t_sks = mysqli_query($koneksi, "SELECT * FROM krs_mhs INNER JOIN
            jadwal_mengajar ON krs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
            LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND krs_mhs.kode_prodi='$kode_prodi' AND krs_mhs.id_thn_akademik='$id_thn_akademik'");
    while ($pilihan = mysqli_fetch_array($t_sks)) {
        $jum_sks[] = $pilihan['sks'];
    }
    $hasil_sks = array_sum($jum_sks);
    echo '<strong>'.$hasil_sks.' SKS</strong>';
    ?>
        <?php } ?>
      </td>
    </tr>
  </tr>

<table class="hk">
    <tr>
      <td colspan="2"></td>
      <td width="600"></td>
    </tr>
    <tr>
      <td width="430" align="center" style="font-size: 12px;">Mengetahui &amp; Menyetujui</td>
      <td width="350"></td>
      <td align="center" style="font-size: 12px;"><?= $r_pengaturan['kota']; ?>, <?= tanggal_indonesia(date('Y-m-d')); ?></td>
    </tr>
    <tr>
      <td width="530" align="center" style="font-size: 12px;">Dosen Penasehat Akademik,<br /><br /><br /><br /><br />
        <?php
         $pa = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM mhs_has_pa INNER JOIN dosen ON mhs_has_pa.nip=dosen.nip WHERE nim_npm='$username'"));
?>
         <strong><?= $pa['nama_dosen']; ?></strong><br />
         <td>&nbsp;</td>
         <td align="center" valign="top" style="font-size: 12px;">Mahasiswa,<br /><br /><br /><br /><br>
           <strong><?= $tampil_mhs['nama_mhs']; ?></strong></td>
     </tr>
     <tr>
       <td colspan="2" style="font-size: 14px;">&nbsp;</td>
       <td style="font-size: 14px;">&nbsp;</td>
     </tr>
</table>
<dl><dd><div align="center"></div>
      </dd>
    </dl>
  </form>
</body>
</html>
