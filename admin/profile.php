<?php
session_start();
include "../config/db.php";
if(!isset($_SESSION['user_id'])){ header('Location: ../login.php'); exit; }

$uid = intval($_SESSION['user_id']);
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['update_profile'])){
  $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $pw = $_POST['password'];
  if(!empty($pw)){
    $h = md5($pw);
    mysqli_query($conn, "UPDATE users SET nama_lengkap='{$nama}', email='{$email}', password='{$h}' WHERE id={$uid}");
  } else {
    mysqli_query($conn, "UPDATE users SET nama_lengkap='{$nama}', email='{$email}' WHERE id={$uid}");
  }
  $msg = 'Profil diperbarui.';
}
$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$uid}");
$user = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Profil</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
  <aside class="sidebar">
    <h2>E-DESLAY</h2><ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="kegiatan.php">Kegiatan</a></li>
      <li><a href="prestasi.php">Prestasi</a></li>
      <li><a href="saran.php">Kotak Saran</a></li>
      <li><a href="profile.php" class="active">Profil</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </aside>
  <div class="main">
    <h1>Profil Saya</h1>
    <?php if(isset($msg)) echo '<p class="x-profile-1">'.htmlspecialchars($msg).'</p>'; ?>
    <div class="form">
      <form method="post">
        <label>Nama Lengkap</label>
        <input type="text" name="nama_lengkap" required value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" class="x-profile-2">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>" class="x-profile-3">
        <label>Ganti Password (kosongkan jika tidak ingin mengubah)</label>
        <input type="password" name="password" class="x-profile-4">
        <button type="submit" name="update_profile" class="btn primary">Simpan</button>
      </form>
    </div>
  </div>
</body>
</html>
