  <?php 
  session_start();
  include"../../config/koneksi.php";
  $username=$_SESSION['username'];
  $password=$_SESSION['password'];
  $level=$_SESSION['level'];
  $kode_prodi=$_SESSION['kode_prodi'];
  $prodi=mysqli_fetch_array(mysqli_query($koneksi,"SELECT * FROM prodi WHERE kode_prodi='$kode_prodi'"));

  if ($level=="Jurusan/Prodi") {
    $username=$_GET['nim_npm'];
  }

  // if (!isset($_SESSION["login"]) ) {
  //   header("location: ../login");
  // }else{
  //   $cek_user=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM user WHERE username='$username' AND password='$password' AND level='$level'"));
  //   if ($cek_user !== 1) {
  //     header("location: ../login");
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
  

  ?>
  <?php 
  error_reporting(0);
  include "printer.css"; ?>
<html>
<head>
    <title>Transkrip Nilai <?= $tampil_mhs['nama_mhs']; ?></title>
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
        <h2 style="font-size: 17px;">TRANSKRIP NILAI</h2>
        </td>
      </tr>
    </table>
    <table class="table_1">
      <tr>
        <td rowspan="4">
          <div style="width: 100px; height: 104px;">
            <?php if ($tampil_mhs['foto_mhs'] == '') { ?>
              <img style="border-radius: 0%; width: 100px; height: 104px; overflow: hidden;" src="../foto_mhs/avatar-blank.png">
            <?php } else { ?>
              <img style="border-radius: 0%; width: 100px; height: 104px; overflow: hidden;" src="../foto_mhs/<?= $tampil_mhs['foto_mhs']; ?>">
            <?php } ?>
          </div>
        </td>
        <td>
          <strong>Tahun Angkatan</strong>
        </td>
        <td><?= $tampil_mhs['thn_masuk']; ?></td>
      </tr>
      <tr>
        <td>
          <strong>NIM</strong>
        </td>
        <td><?= $tampil_mhs['nim_npm']; ?></td>
      </tr>
      <tr>
        <td>
          <strong>Nama Mahasiswa</strong>
        </td>
        <td><?= $tampil_mhs['nama_mhs']; ?></td>
      </tr>
      <tr>
        <td>
          <strong>Program Studi</strong>
        </td>
        <td><?= $prodi['jenjang']; ?> - <?= $prodi['nama_prodi']; ?></td>
      </tr>
    </table>

            <table class="table_2">
              <tr>
               <th><center>No.</center></th>
               <th><center>Kode</center></th>
               <th><center>Nama Mata Kuliah</center></th>
               <th><center>SKS</center></th>
               <th><center> Nilai </center></th>
               <th><center> Grade </center></th>
             </tr>
             <?php 
             $no=1;
             $transkip=mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
              LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND khs_mhs.grade!='-' ORDER BY mata_kuliah.semester ASC");
             while ($t_transkip=mysqli_fetch_array($transkip)) {
              ?>
              <tr valign="top">
                <td align=center>
                  <?php echo $no++; ?>.
                </td>
                <td align=center>
                  <?php echo $t_transkip['kode_mk']; ?>
                </td>
                <td>
                  <?php echo $t_transkip['nama_matkul']; ?>
               </td>
               <td align=center>
                 <?php echo $t_transkip['sks']; ?>
               </td>
               <td align=center>
                <?php echo $t_transkip['nilai_akhir']; ?>
              </td>
              <td align=center>
               <?php echo $t_transkip['grade']; ?>
             </td>
           </tr>
         <?php } ?>

       </table>
       <table class="table_1">

        <tr>
          <td valign="top">Total SKS</td>
          <td valign="top">
           <?php 
           $cek_data=mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM khs_mhs WHERE nim_npm='$username' AND kode_prodi='$kode_prodi' AND grade!='-'"));
           if ($cek_data > 0) {
             ?>
             <?php 
             $t_sks = mysqli_query($koneksi,"SELECT * FROM khs_mhs INNER JOIN 
              jadwal_mengajar ON khs_mhs.id_jadwal=jadwal_mengajar.id_jadwal
              LEFT JOIN mata_kuliah ON jadwal_mengajar.kode_mk=mata_kuliah.kode_matkul WHERE nim_npm='$username' AND khs_mhs.kode_prodi='$kode_prodi' AND khs_mhs.grade!='-'");
             while ($pilihan=mysqli_fetch_array($t_sks)) {
              $jum_sks [] = $pilihan['sks'];
            }
            $hasil_sks = array_sum($jum_sks);
            echo '<strong>' . $hasil_sks . ' SKS</strong>';
            ?>
          <?php } ?>
        </td>
      </tr>
      <tr>
        <td valign="top">Indeks Prestasi Kumulatif (IPK)</td>
        <td valign="top">
         <?php 
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
          echo '<strong>' . number_format($ip,2,',','.') . '</strong>';
          ?>
        <?php } ?>
      </td>
    </tr>

<table class="hk">
    <tr>
      <td colspan="2"></td>
      <td width="600"></td>
    </tr>
    <tr>
      <td width="430" align="center" style="font-size: 12px;">Mengetahui</td>
      <td width="350"></td>
      <td align="center" style="font-size: 12px;"><?= $r_pengaturan['kota']; ?>, <?= date("d F Y"); ?></td>
    </tr>
    <tr>
      <td width="530" align="center" style="font-size: 12px;">Wakil Ketua Bidang Akademik<br /><br /><br /><br /><br />
        ................................................<br />
         <td>&nbsp;</td>
         <td align="center" valign="top" style="font-size: 12px;">Mahasiswa Bersangkutan<br /><br /><br /><br /><br>
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