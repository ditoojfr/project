<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$uid = intval($_SESSION['user_id']);
$msg = "";

// Ambil data user
$res  = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid");
$user = mysqli_fetch_assoc($res);

// Antisipasi jika data user tidak ada
if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

// ====== HANDLE DATA TEKS ======
$nama_lengkap  = !empty($user['nama_lengkap']) ? $user['nama_lengkap'] : 'Administrator';
$username      = !empty($user['username']) ? $user['username'] : '-';
$email         = !empty($user['email']) ? $user['email'] : '-';
$jenis_kelamin = !empty($user['jenis_kelamin']) ? $user['jenis_kelamin'] : '-';
$no_telp       = !empty($user['no_telp']) ? $user['no_telp'] : '-';
$alamat        = !empty($user['alamat']) ? $user['alamat'] : '-';

// ====== HANDLE COVER (LONGBLOB) ======
if (!empty($user['cover'])) {
    // asumsikan jpeg, kalau kamu pakai png tinggal ganti image/png
    $coverStyle = "background-image: url('data:image/jpeg;base64," . base64_encode($user['cover']) . "');";
} else {
    // fallback ke file biasa
    $coverStyle = "background-image: url('../uploads/cover-default.jpg');";
}

// ====== HANDLE FOTO PROFIL (LONGBLOB) ======
if (!empty($user['foto'])) {
    $fotoSrc = "data:image/jpeg;base64," . base64_encode($user['foto']);
} else {
    $fotoSrc = "../uploads/default.png";   // fallback gambar default
}
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
    overflow: hidden;
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

/* Logo dan desa banjardowo */
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
.sidebar-header img { width: 36px; height: 36px; }

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
.menu a.active { background: #38BDF8; }
.menu a:hover { background: #3047d3; }

.main {
    margin-left: 230px;
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
    width: 1427px;
    height: 250px;
    left: 25px;
    top: -23px;
    position: relative;
    background-size: cover;
    background-position: center;
    border-radius: 10px 10px 0 0;
    margin: 12px 0 0 0;
    box-shadow: 0 2px 8px rgba(60,60,60,0.10);
    display: block;
}

.profile-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -120px;
    margin-bottom: 10px;
    margin-left: -900px;
}
.profile-photo {
    width: 180px;
    height: 180px;
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
    margin-left: 2000px;
    margin-top: -80px;
}
.btn-edit-profile:hover { background: #ffb743; }

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: auto auto auto;
  gap: 28px 100px;
  width: 700px;
  margin: 48px auto 0 auto;
  position: relative;
}

.item-1 { grid-row: 1; grid-column: 1; margin-left: -50px; }
.item-2 { grid-row: 1; grid-column: 2; margin-left: 100px; }
.item-3 { grid-row: 2; grid-column: 1; margin-left: -50px; margin-top: 40px; }
.item-4 { grid-row: 2; grid-column: 2; margin-left: 100px; margin-top: 40px; }
.item-5 { grid-row: 3; grid-column: 1 / span 2; margin-left: 220px; margin-top: 40px; }

.info-item {
  display: flex;
  align-items: center;
  font-size: 17px;
  gap: 16px;
}
.info-item img { display: inline-block; }
.info-item span { font-size: 17px; color: #333; }

.logout {
  margin-top: 30px;
  padding: 0 0 0 6px;
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
    <!-- Header Cover User (LONGBLOB / default file) -->
    <div class="profile-header" style="<?php echo $coverStyle; ?>"></div>

    <div class="profile-box">
        <!-- Foto Profil (LONGBLOB / default file) -->
        <img class="profile-photo" src="<?php echo $fotoSrc; ?>" alt="Foto Profil">
        <h2><?php echo htmlspecialchars($nama_lengkap); ?></h2>
        <button class="btn-edit-profile" onclick="window.location.href='edit_profile.php'">Edit Profil</button>
    </div>

    <?php if($msg) echo "<p class='alert'>".htmlspecialchars($msg)."</p>"; ?>

    <div class="info-grid">
        <div class="info-item item-1">
            <img src="../assets/icons/icon_user.png" width="28" />
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
        <div class="info-item item-2">
            <img src="../assets/icons/gmail.png" width="28" />
            <span><?php echo htmlspecialchars($email); ?></span>
        </div>
        <div class="info-item item-3">
            <img src="../assets/icons/jenis_kelamin.png" width="28" />
            <span><?php echo htmlspecialchars($jenis_kelamin); ?></span>
        </div>
        <div class="info-item item-4">
            <img src="../assets/icons/telpon.png" width="28" />
            <span><?php echo htmlspecialchars($no_telp); ?></span>
        </div>
        <div class="info-item item-5">
            <img src="../assets/icons/google-maps.png" width="28" />
            <span><?php echo htmlspecialchars($alamat); ?></span>
        </div>
    </div>
</div>

</body>
</html>
