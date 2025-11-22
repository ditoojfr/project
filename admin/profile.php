<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$uid = intval($_SESSION['user_id']);
$msg = "";

$res = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($res);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil Admin</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<style>
html, body {
    height: 100vh;
    margin: 0;
    padding: 0;
    overflow: hidden;              /* Hilangkan semua scroll */
    font-family: 'Inter', sans-serif;
    background: #f7f8fa;
}
body {
    min-height: 100vh;
    width: 100vw;
}
/* ===== SIDEBAR ===== */
.sidebar {
  position: fixed;
  left: 20px;
  top: 90px;
  width: 220px;
  height: calc(100vh - 152px);
  background: #5E63BB;
  padding: 24px 20px;
  color: white;
  border-radius: 20px;
}
.sidebar-header {
    position: fixed;
    top: 20px; left: 20px;
    width: 170px;
    background: transparent;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index:20;
}
.sidebar-header img { width: 36px; height: 36px;}
.menu {
    list-style: none;
    padding: 0; margin: 0;
}
.menu a {
    display: flex;
    gap: 10px;
    color: white;
    padding: 9px 13px;
    text-decoration: none;
    border-radius: 10px;
    align-items: center;
    font-size: 15px;
}
.menu a.active { background: #38BDF8;}
.menu a:hover { background: #3047d3;}
.main {
    margin-left: 230px;         /* Sidebar + gap */
    padding: 0 20px;
    min-height: 100vh;
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-sizing: border-box;
    justify-content: center;
}
.profile-header {
    width: 98%;
    height: 110px;
    background: url('../assets/images/logo-nganjuk.png') center center no-repeat;
    background-size: cover;
    border-radius: 10px 10px 0 0;
    margin: 12px 0 0 0;
    box-shadow: 0 2px 8px rgba(60,60,60,0.10);
    display: block;
}
.profile-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -45px;
    margin-bottom: 10px;
}
.profile-photo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid #fff;
    margin-bottom: 3px;
    object-fit: cover;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.13);
}
.profile-box h2 {
    margin: 6px 0 2px 0;
    font-size: 22px;
    font-weight: 700;
    text-align: center;
}
.profile-box p {
    margin-bottom: 0;
    color: #666;
    font-size: 15px;
    text-align: center;
}
.btn-edit-profile {
    background: #fa9800;
    color: white;
    padding: 10px 32px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    margin: 16px auto 10px auto;
    cursor: pointer;
    font-weight: bold;
    display: block;
    box-shadow: 0 3px 15px rgba(230,160,24,0.06);
}
.btn-edit-profile:hover { background: #ffb743; }
.info-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    justify-items: center;
    gap: 8px 14px;
    margin: 10px 0 6px 0;
    width: 100%;
    max-width: 740px;
}
.info-item {
    text-align: center;
    font-size: 15px;
}
.info-item img { display:block; margin:0 auto 10px auto;}
.info-item p { margin: 0; font-size: 15px; color: #222; font-weight: 500; }
.alert {
    background: #f8ffe3;
    color: #7f9a4d;
    border: 1px solid #e5f5b2;
    padding: 6px 12px;
    border-radius: 8px;
    display: inline-block;
    margin: 10px 0 12px;
    font-size:14px;
}
form {
    margin: 0 auto;
    background: #fff;
    padding: 18px 25px;
    border-radius: 12px;
    box-shadow: 0 2px 18px rgba(80,80,80,.07);
    max-width: 340px;
    width: 100%;
}
form label { font-weight: 500; display: block; margin-bottom: 6px; margin-top: 12px; }
form input, form textarea, form select {
    width: 100%;
    border-radius: 7px;
    padding: 8px 10px;
    margin-bottom: 8px;
    border: 1px solid #ddd;
    font-size: 15px;
}
form textarea { min-height: 38px; }
form button[type="submit"] {
    background: #3e479e;
    color: #fff;
    border-radius: 7px;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 20px;
    margin: 17px auto 0 auto;
    border: none;
    display: block;
    cursor: pointer;
}

.logout {
  margin-top: 30px;
  padding: 0 0 0 6px;   /* sesuaikan padding kiri/gap biar rapi */
}
.logout a, .logout a:visited {
  display: flex;
  align-items: center;
  color: white !important;
  text-decoration: none !important;
  font-weight: 500;
  font-size: 15px;
  padding: 10px 13px;
  border-radius: 10px;
  gap: 10px;
}
.logout a:hover {
  background: #3047d3;
  color: #ffe28f !important;
}

.logout img {
  width: 20px;
  height: 20px;
  margin-right: 10px;
  vertical-align: middle;
  display: inline-block;
}


form button[type="submit"]:hover { background: #38BDF8; }
@media (max-width:900px){
    .info-grid { grid-template-columns: repeat(2, 1fr);}
    .main { padding: 8px 6px;}
}
@media (max-width:600px){
    .sidebar, .sidebar-header { display:none;}
    .main {margin-left:0;}
    .info-grid {grid-template-columns:1fr;}
}
</style>
</head>
<body>
<!-- SIDEBAR HEADER -->
<div class="sidebar-header">
    <img src="../assets/images/logo-nganjuk.png" alt="Logo">
    <span style="font-size:16px; font-weight:600;">Desa Banjardowo</span>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
  <ul class="menu">
    <li> 
      <a href="dashboard.php">
        <img src="../assets/icons/dashboard1.png" alt="Dashboard" style="width:20px; height:20px; margin-right:8px;">
        Dashboard
      </a>
    </li>
    <li> 
      <a href="kegiatan.php" class="active">
        <img src="../assets/icons/kegiatandesa.png" alt="Kegiatan" style="width:20px; height:20px; margin-right:8px;">
        Kegiatan Desa
      </a>
    </li>
    <li>
      <a href="prestasi.php">
        <img src="../assets/icons/prestasi.png" alt="Prestasi" style="width:20px; height:20px; margin-right:8px;">
        Prestasi
      </a>
    </li>
    <li>
      <a href="saran.php">
        <img src="../assets/icons/kotaksaran1.png" alt="Kotak Saran" style="width:20px; height:20px; margin-right:8px;">
        Kotak Saran
      </a>
    </li>
    <li>
      <a href="pelayanan.php">
        <img src="../assets/icons/pelayanan1.png" alt="Pelayanan" style="width:20px; height:20px; margin-right:8px;">
        Pelayanan
      </a>
    </li>
  </ul>

<div class="logout">
  <a href="../logout.php">
    <img src="../assets/icons/logout1.png" alt="Keluar">
    Keluar
  </a>
</div>
</aside>




<div class="main">
    <div class="profile-header"></div>
    <div class="profile-box">
        <img class="profile-photo" src="../uploads/<?php echo $user['foto'] ?: 'default.png'; ?>">
        <h2><?php echo $user['nama_lengkap']; ?></h2>
        <p>@<?php echo $user['username']; ?></p>
    </div>
    <?php if($msg) echo "<p class='alert'>$msg</p>"; ?>

    <button class="btn-edit-profile" onclick="window.location.href='edit_profile.php'">Edit Profil</button>

    <div class="info-grid">
        <div class="info-item"><img src="../assets/icons/icon_user.png" width="28" /><p><?php echo $user['username']; ?></p></div>
        <div class="info-item"><img src="../assets/icons/gmail.png" width="28" /><p><?php echo $user['email']; ?></p></div>
        <div class="info-item"><img src="../assets/icons/jenis_kelamin.png" width="28" /><p><?php echo $user['jenis_kelamin']; ?></p></div>
        <div class="info-item"><img src="../assets/icons/telpon.png" width="28" /><p><?php echo $user['no_telp']; ?></p></div>
        <div class="info-item"><img src="../assets/icons/google-maps.png" width="28" /><p><?php echo $user['alamat']; ?></p></div>
    </div>
</div>
</body>
</html>