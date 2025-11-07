<?php
session_start();
include "../config/db.php";
if(!isset($_SESSION['user_id'])){ header('Location: ../login.php'); exit; }

if(isset($_GET['del'])){
  $id = intval($_GET['del']);
  mysqli_query($conn, "DELETE FROM saran WHERE id={$id}");
  header('Location: saran.php'); exit;
}
$list = mysqli_query($conn, "SELECT * FROM saran ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Kotak Saran</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
  <aside class="sidebar">
    <h2>E-DESLAY</h2><ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="kegiatan.php">Kegiatan</a></li>
      <li><a href="prestasi.php">Prestasi</a></li>
      <li><a href="saran.php" class="active">Kotak Saran</a></li>
      <li><a href="profile.php">Profil</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </aside>
  <div class="main">
    <h1>Kotak Saran</h1>
    <div class="table">
      <table width="100%">
        <tr><th>Nama</th><th>Email</th><th>Isi</th><th>Tanggal</th><th>Aksi</th></tr>
        <?php while($r = mysqli_fetch_assoc($list)): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['nama']); ?></td>
            <td><?php echo htmlspecialchars($r['email']); ?></td>
            <td style="max-width:360px"><?php echo nl2br(htmlspecialchars($r['isi_saran'])); ?></td>
            <td><?php echo $r['tanggal']; ?></td>
            <td><a href="saran.php?del=<?php echo $r['id']; ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
</body>
</html>
