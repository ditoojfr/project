<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$uid = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $jk = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $pw = $_POST['password'];

    if (!empty($pw)) {
        $h = md5($pw);
        $sql = "UPDATE users SET 
                nama_lengkap='$nama',
                email='$email',
                jenis_kelamin='$jk',
                no_telp='$telp',
                alamat='$alamat',
                password='$h'
                WHERE id=$uid";
    } else {
        $sql = "UPDATE users SET 
                nama_lengkap='$nama',
                email='$email',
                jenis_kelamin='$jk',
                no_telp='$telp',
                alamat='$alamat'
                WHERE id=$uid";
    }
    mysqli_query($conn, $sql);
    $msg = "Profil berhasil diperbarui.";
}

$res = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Kegiatan Desa</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
<style>
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: #f7f8fa;
}
/* Sidebar */
.sidebar {
    position: fixed;
    left: 20px;
    top: 100px;
    width: 220px;
    height: calc(100vh - 166px);
    background: #5E63BB;
    padding: 24px 20px;
    color: white;
    border-radius: 20px;
}
.sidebar-header {
    position: fixed;
    top: 20px;
    left: 20px;
    width: 220px;
    background: transparent;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sidebar-header img {
    width: 42px;
    height: 42px;
}
/* Menu */
.menu {
    list-style: none;
    padding: 0;
    margin: 0;
}
.menu a {
    display: flex;
    gap: 12px;
    color: white;
    padding: 12px 16px;
    text-decoration: none;
    border-radius: 10px;
}
.menu a.active {
    background: #38BDF8;
}
.menu a:hover {
    background: #3047d3;
}
/* Utama */
.main {
    margin-left: 260px;
    padding: 40px 60px;
}
.profile-header {
    width: 100%;
    height: 230px;
    background: url('../assets/images/logo-nganjuk.png') center/cover no-repeat;
    border-radius: 12px;
    margin-bottom: -70px;
    position: relative;
    z-index: 1;
}
/* Foto Profil Bulat */
.profile-box {
    position: relative;
}
.profile-photo {
    width: 140px;
    height: 140px;
    margin-left: 100px;
    border-radius: 50%;
    margin-top: 100px;
    border: 5px solid #fff;
    object-fit: cover;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.13);
}
.profile-box h2 {
    margin: 14px 0 2px 0;
    font-size: 25px;
    font-weight: 700;
}
.profile-box p {
    margin-bottom: 0;
    color: #666;
    font-size: 17px;
}
.btn-edit-profile {
    background: #ff8800;
    color: white;
    padding: 12px 32px;
    font-size: 17px;
    border: none;
    border-radius: 8px;
    margin: 18px auto 0 auto;
    cursor: pointer;
    font-weight: bold;
    display: block;
}
.btn-edit-profile:hover {
    background: #ff9900;
}
/* Biodata Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    justify-items: center;
    gap: 24px 40px;
    margin: 35px 0 40px 0;
}
.info-item {
    text-align: center;
}
.info-item i {
    font-size: 28px;
    margin-bottom: 12px;
    color: #3e479e;
    display: block;
}
.info-item p {
    margin: 0;
    font-size: 15px;
    color: #222;
    font-weight: 500;
}
.alert {
    background: #f8ffe3;
    color: #7f9a4d;
    border: 1px solid #e5f5b2;
    padding: 9px 16px;
    border-radius: 8px;
    display: inline-block;
    margin-bottom: 20px;
}
form {
    margin-top: 40px;
    background: #fff;
    padding: 24px 30px;
    border-radius: 12px;
    box-shadow: 0 3px 24px rgba(80,80,80,.07);
    max-width: 510px;
}
form label {
    font-weight: 500;
    display: block;
    margin-bottom: 6px;
    margin-top: 16px;
}
form input, form textarea, form select {
    width: 100%;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 12px;
    border: 1px solid #ddd;
    font-size: 15px;
}
form textarea {
    min-height: 48px;
}
form button[type="submit"] {
    background: #3e479e;
    color: #fff;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    padding: 13px 28px;
    margin: 22px auto 0 auto;
    border: none;
    display: block;
    cursor: pointer;
}
form button[type="submit"]:hover {
    background: #38BDF8;
}
</style>
</head>
<body>
<!-- SIDEBAR HEADER -->
<div class="sidebar-header">
    <img src="../assets/images/logo-nganjuk.png" alt="Logo">
    <span style="font-size:16px; font-weight:600;">Desa Banjardowo</span>
</div>
<!-- SIDEBAR MENU -->
<div class="sidebar">
    <ul class="menu">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':''; ?>">
                <img src="../assets/icons/dashboard1.png" alt="Dashboard" style="width:20px; height:20px; margin-right:8px;">
                Dashboard
            </a>
        </li>
        <li>
            <a href="kegiatan.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='kegiatan.php'?'active':''; ?>">
                <img src="../assets/icons/kegiatandesa.png" alt="Kegiatan" style="width:20px; height:20px; margin-right:8px;">
                Kegiatan Desa
            </a>
        </li>
        <li>
            <a href="prestasi.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='prestasi.php'?'active':''; ?>">
                <img src="../assets/icons/prestasi.png" alt="Prestasi" style="width:20px; height:20px; margin-right:8px;">
                Prestasi
            </a>
        </li>
        <li>
            <a href="saran.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='saran.php'?'active':''; ?>">
                <img src="../assets/icons/kotaksaran1.png" alt="Saran" style="width:20px; height:20px; margin-right:8px;">
                Saran
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <img src="../assets/icons/logout1.png" alt="Logout" style="width:20px; height:20px; margin-right:8px;">
                Logout
            </a>
        </li>
    </ul>
</div>
<div class="main">
    <!-- Foto sampul di bagian atas! -->
    <div class="profile-header"></div>
    <!-- Foto profil bulat & biodata -->
    <div class="profile-box">
        <img class="profile-photo" src="../uploads/<?php echo $user['foto'] ?: 'default.png'; ?>">
        <h2><?php echo $user['nama_lengkap']; ?></h2>
        <p>@<?php echo $user['username']; ?></p>
    </div>
    <?php if(isset($msg)) echo "<p class='alert'>$msg</p>"; ?>
    <!-- Biodata Grid -->
    <div class="info-grid">
        <div class="info-item"> 
            <i class="fa fa-envelope"></i>
            <p><?php echo $user['email']; ?></p>
        </div>
        <div class="info-item">
            <i class="fa fa-venus-mars"></i>
            <p><?php echo $user['jenis_kelamin']; ?></p>
        </div>
        <div class="info-item">
            <i class="fa fa-phone"></i>
            <p><?php echo $user['no_telp']; ?></p>
        </div>
        <div class="info-item">
            <i class="fa fa-map-marker"></i>
            <p><?php echo $user['alamat']; ?></p>
        </div>
    </div>
    <button class="btn-edit-profile" onclick="location.href='edit_profile.php'">Edit Profil</button>
    <form method="post">
        <label>Nama Lengkap</label>
        <input type="text" name="nama_lengkap" required value="<?php echo $user['nama_lengkap']; ?>">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo $user['email']; ?>">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin">
            <option value="Laki-Laki" <?php if($user['jenis_kelamin']=='Laki-Laki') echo 'selected'; ?>>Laki-Laki</option>
            <option value="Perempuan" <?php if($user['jenis_kelamin']=='Perempuan') echo 'selected'; ?>>Perempuan</option>
        </select>
        <label>No Telpon</label>
        <input type="text" name="no_telp" value="<?php echo $user['no_telp']; ?>">
        <label>Alamat</label>
        <textarea name="alamat"><?php echo $user['alamat']; ?></textarea>
        <label>Password Baru (opsional)</label>
        <input type="password" name="password">
        <button type="submit" name="update_profile" class="btn primary">Simpan Perubahan</button>
    </form>
</div>
</body>
</html>
