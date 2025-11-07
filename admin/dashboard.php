<?php
session_start();
include "../config/db.php";
if(!isset($_SESSION['user_id'])){ header('Location: ../login.php'); exit; }
$kegiatan = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM kegiatan"))[0];
$prestasi = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM prestasi"))[0];
$saran = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM saran"))[0];
$latest_keg = mysqli_query($conn, "SELECT id, judul, tanggal FROM kegiatan ORDER BY id DESC LIMIT 5");
$latest_pre = mysqli_query($conn, "SELECT id, judul, tanggal FROM prestasi ORDER BY id DESC LIMIT 5");
$latest_sar = mysqli_query($conn, "SELECT id, nama, tanggal FROM saran ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
  <aside class="sidebar">
    <h2>E-DESLAY</h2>
    <ul>
      <li><a href="dashboard.php" class="active">Dashboard</a></li>
      <li><a href="kegiatan.php">Kegiatan</a></li>
      <li><a href="prestasi.php">Prestasi</a></li>
      <li><a href="saran.php">Kotak Saran</a></li>
      <li><a href="profile.php">Profil</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </aside>
  <div class="main">
    <header style="display:flex;justify-content:space-between;align-items:center">
      <h1>Dashboard</h1>
      <div>Halo, <?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </header>

    <div class="card-stat">
      <div class="stat"><h2><?php echo $kegiatan; ?></h2><p>Total Kegiatan</p></div>
      <div class="stat"><h2><?php echo $prestasi; ?></h2><p>Total Prestasi</p></div>
      <div class="stat"><h2><?php echo $saran; ?></h2><p>Total Saran</p></div>
    </div>

    <div style="display:flex;gap:18px;margin-top:18px" class="tables">
      <div style="flex:1" class="table">
        <h3>Terbaru - Kegiatan</h3>
        <table width="100%">
          <tr><th>Judul</th><th>Tanggal</th><th></th></tr>
          <?php while($r = mysqli_fetch_assoc($latest_keg)): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['judul']); ?></td>
              <td><?php echo $r['tanggal']; ?></td>
              <td><a href="kegiatan.php?edit=<?php echo $r['id']; ?>">Edit</a></td>
            </tr>
          <?php endwhile; ?>
        </table>
      </div>

      <div style="flex:1" class="table">
        <h3>Terbaru - Prestasi</h3>
        <table width="100%">
          <tr><th>Judul</th><th>Tanggal</th><th></th></tr>
          <?php while($r = mysqli_fetch_assoc($latest_pre)): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['judul']); ?></td>
              <td><?php echo $r['tanggal']; ?></td>
              <td><a href="prestasi.php?edit=<?php echo $r['id']; ?>">Edit</a></td>
            </tr>
          <?php endwhile; ?>
        </table>
      </div>

      <div style="flex:1" class="table">
        <h3>Terbaru - Saran</h3>
        <table width="100%">
          <tr><th>Nama</th><th>Tanggal</th><th></th></tr>
          <?php while($r = mysqli_fetch_assoc($latest_sar)): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['nama']); ?></td>
              <td><?php echo $r['tanggal']; ?></td>
              <td><a href="saran.php?del=<?php echo $r['id']; ?>">Hapus</a></td>
            </tr>
          <?php endwhile; ?>
        </table>
      </div>
    </div>

  </div>
</body>
</html>
