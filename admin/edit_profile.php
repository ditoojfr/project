<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$uid = intval($_SESSION['user_id']);
$msg = "";

// ============= PROSES POST =============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) FORM KECIL: HANYA GANTI COVER (tombol Edit Cover)
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
        $allowed = ['png', 'jpg', 'jpeg'];
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // baca file sebagai binary untuk disimpan ke LONGBLOB
            $imgData = file_get_contents($_FILES['cover']['tmp_name']);
            $imgDataEscaped = mysqli_real_escape_string($conn, $imgData);

            mysqli_query($conn, "UPDATE users SET cover='$imgDataEscaped' WHERE id=$uid");

            header("Location: edit_profile.php");
            exit;
        } else {
            $msg = 'Format file cover tidak didukung. Pilih PNG atau JPG.';
        }
    }

    // 2) FORM UTAMA: UPDATE DATA + (OPSIONAL) GANTI FOTO PROFIL
    if (isset($_POST['update_profile'])) {
        $nama     = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email    = mysqli_real_escape_string($conn, $_POST['email']);
        $jk       = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
        $telp     = mysqli_real_escape_string($conn, $_POST['no_telp']);
        $alamat   = mysqli_real_escape_string($conn, $_POST['alamat']);

        $fotoSql = "";

        // Jika user upload foto baru
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0 && $_FILES['foto']['size'] > 0) {
            $allowedFoto = ['png', 'jpg', 'jpeg'];
            $extFoto = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

            if (in_array($extFoto, $allowedFoto)) {
                $fotoData = file_get_contents($_FILES['foto']['tmp_name']);
                $fotoDataEscaped = mysqli_real_escape_string($conn, $fotoData);
                $fotoSql = ", foto='$fotoDataEscaped'";
            } else {
                $msg = 'Format foto profil tidak didukung. Pilih PNG atau JPG.';
            }
        }

        if ($msg === "") {
            $sql = "UPDATE users SET 
                        nama_lengkap='$nama',
                        username='$username',
                        email='$email',
                        jenis_kelamin='$jk',
                        no_telp='$telp',
                        alamat='$alamat'
                        $fotoSql
                    WHERE id=$uid";
            mysqli_query($conn, $sql);

            $msg = "Profil berhasil diperbarui.";
            header("Location: profile.php");
            exit;
        }
    }
}

// ============= AMBIL DATA USER =============
$res  = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($res);

if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

// === Siapkan background cover (LONGBLOB -> base64) ===
if (!empty($user['cover'])) {
    $coverStyle = "background-image: url('data:image/jpeg;base64," . base64_encode($user['cover']) . "');";
} else {
    $coverStyle = "background-image: url('../uploads/cover-default.jpg')";
}

// === Siapkan foto profil (LONGBLOB -> base64) ===
if (!empty($user['foto'])) {
    $fotoSrc = "data:image/jpeg;base64," . base64_encode($user['foto']);
} else {
    $fotoSrc = "../uploads/default.png";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Profil</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<style>
body{
    margin:0;
    padding:0;
    background:#ffffff;
    font-family:'Inter',sans-serif;
}

/* WRAPPER HALAMAN */
.container-profile-edit {
    max-width: 100%;              /* FULL WIDTH */
    margin: 0 0 40px 0;           /* tidak center lagi */
    padding-bottom: 38px;
}

/* Logo dan desa banjardowo (pojok kiri atas) */
.sidebar-header {
    position: relative;
    padding: 20px 30px 15px 30px;  /* atas, kanan, bawah, kiri */
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: flex-start;   /* tetap kiri */
    margin-bottom: 0;              /* cover langsung di bawahnya */
}
.sidebar-header img {
    width: 42px;
    height: 42px;
}
.sidebar-header .title {
    font-size: 20px;
    font-weight: 600;
}

/* COVER DI BAWAH HEADER */
.cover-edit {
    width: 100%;
    height: 260px;
    background-size: cover;
    background-position: center;
    margin: 0;
    border-radius: 0;
    position: relative;
}

.cover-btn-edit {
    position: absolute;
    bottom: 18px;
    right: 26px;
    background: rgba(0,0,0,0.55);
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 20px;
    font-size: 13px;
    padding: 7px 16px;
    cursor: pointer;
}
.cover-btn-edit i{font-size:13px;}

/* HEADER: AVATAR + TEKS */
.profile-edit-head-row {
    display: flex;
    align-items: center;
    gap: 26px;
    margin-left: 60px;
    margin-top: -28px;
}

/* Bungkus avatar untuk ikon pensil */
.avatar-box{
    position:relative;
    width:140px;
    height:140px;
    cursor:pointer;
}
.avatar-edit-img {
    width: 140px; height: 140px;
    border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 2px 16px rgba(0,0,0,0.15);
    object-fit: cover;
    background: #fff;
}
.avatar-edit-icon{
    position:absolute;
    right:8px;
    bottom:12px;
    width:30px;height:30px;
    border-radius:50%;
    background:#ffffff;
    border:1px solid #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    color:#111827;
}

.edit-main-info h2 {
    margin: 0 0 4px 0;
    font-size: 22px;
    font-weight: 700;
}
.edit-main-info small {
    color: #9ca3af;
    font-size: 13px;
}
.edit-nama {
    margin-top: 8px;
    font-size: 18px;
    font-weight: 500;
    color: #111827;
}

/* SECTION FORM */
.profile-edit-form-section { 
    margin-top: 26px; 
    padding: 0 60px;
}
.form-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 10px;
}

/* GRID FORM 2 KOLOM */
.profile-edit-form-flex {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 60px;
}
.profile-edit-form-flex label { 
    font-weight: 500; 
    font-size: 13px; 
    color: #374151; 
    display: block; 
    margin-bottom: 4px;
}
.profile-edit-form-flex input,
.profile-edit-form-flex textarea,
.profile-edit-form-flex select {
    width: 100%; 
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 14px;
    padding: 8px 11px;
    margin-top: 1px;
}
.profile-edit-form-flex textarea { min-height: 60px; }

.profile-edit-form-flex input:focus,
.profile-edit-form-flex textarea:focus,
.profile-edit-form-flex select:focus{
    outline:none;
    border-color:#fb923c;
    box-shadow:0 0 0 1px rgba(251,146,60,0.2);
}

/* baris full 2 kolom */
.form-full{
    grid-column:1/3;
}

/* teks bantuan kecil */
.help-text{
    font-size:12px;
    color:#9ca3af;
    margin-top:4px;
}

/* BUTTONS */
.form-btn-row{
    margin-top:34px; 
    display: flex; 
    gap:26px;
    grid-column:1/3;
    justify-content:flex-start;
}
.btn-edit-cancel{
    background: #fff; 
    color: #fa9800; 
    padding: 10px 32px; 
    border:1.6px solid #fa9800; 
    border-radius: 8px; 
    font-size: 15px; 
    font-weight: 500; 
    cursor: pointer;
    text-decoration:none;
    display:inline-block;
}
.btn-edit-cancel:hover{background: #fff7ed;}
.btn-edit-simpan{
    background: #fa9800; 
    color: #fff; 
    padding: 10px 36px; 
    border:none; 
    border-radius: 8px;
    font-size:15px; 
    font-weight:600; 
    cursor:pointer;
}
.btn-edit-simpan:hover{background: #ffa820;}

/* ALERT */
.alert{
    padding:10px 60px;
    color:#92400e;
    font-size:13px;
}

/* RESPONSIVE */
@media(max-width:900px){
    .container-profile-edit{margin:0 0 30px 0;}
    .profile-edit-head-row{margin-left:24px;}
    .profile-edit-form-section{padding:0 24px;}
}
@media(max-width:700px){
    .profile-edit-form-flex{
        grid-template-columns:1fr;
    }
    .form-full{grid-column:1/2;}
    .profile-edit-head-row{
        flex-direction:column;
        align-items:flex-start;
        margin-top:-40px;
    }
}
</style>
</head>
<body>

<div class="container-profile-edit">

    <!-- BARIS 1: LOGO -->
    <div class="sidebar-header">
        <img src="../assets/images/logo-nganjuk.png" alt="Logo">
        <span class="title">Desa Banjardowo</span>
    </div>

    <!-- BARIS 2: FOTO SAMPUL -->
    <div class="cover-edit" style="<?php echo $coverStyle; ?>">
        <form method="post" enctype="multipart/form-data" style="position:absolute;bottom:13px;right:20px;">
            <input type="file" name="cover" id="cover" accept=".png,.jpg,.jpeg"
                   style="display:none;" onchange="this.form.submit()"/>
            <button type="button" class="cover-btn-edit"
                    onclick="document.getElementById('cover').click();">
                <i class="fa fa-camera"></i> Edit Cover
            </button>
        </form>
    </div>

    <!-- AVATAR + TEKS -->
    <div class="profile-edit-head-row">
        <div class="avatar-box" onclick="document.getElementById('foto').click();">
            <img src="<?php echo $fotoSrc; ?>" class="avatar-edit-img" id="avatarPreview" alt="Foto Profil">
            <div class="avatar-edit-icon">
                <i class="fa-solid fa-pen"></i>
            </div>
        </div>
        <div class="edit-main-info">
            <h2>Edit Profil</h2>
            <small>Profil / Edit Profil</small>
            <div class="edit-nama"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
        </div>
    </div>

    <?php if($msg) echo "<p class='alert'>$msg</p>"; ?>

    <!-- FORM -->
    <div class="profile-edit-form-section">
        <div class="form-section-title">Informasi Pribadi</div>

        <form method="post" enctype="multipart/form-data" class="profile-edit-form-flex">
            <div>
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required
                       value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>">
            </div>
            <div>
                <label>Username</label>
                <input type="text" name="username" required
                       value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required
                       value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div>
                <label>No Telpon</label>
                <input type="text" name="no_telp"
                       value="<?php echo htmlspecialchars($user['no_telp']); ?>">
            </div>
            <div>
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="Laki-Laki" <?php if($user['jenis_kelamin']=='Laki-Laki') echo 'selected'; ?>>Laki-Laki</option>
                    <option value="Perempuan" <?php if($user['jenis_kelamin']=='Perempuan') echo 'selected'; ?>>Perempuan</option>
                </select>
            </div>
            <div>
                <label>Alamat</label>
                <textarea name="alamat"><?php echo htmlspecialchars($user['alamat']); ?></textarea>
            </div>

            <!-- FOTO PROFIL (hidden input, trigger dari avatar) -->
            <div class="form-full">
                <input type="file" name="foto" id="foto" accept=".png,.jpg,.jpeg" style="display:none;">
            </div>

            <div class="form-btn-row">
                <a href="profile.php" class="btn-edit-cancel">Batal</a>
                <button type="submit" name="update_profile" class="btn-edit-simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
// preview avatar ketika pilih file
const fotoInput = document.getElementById('foto');
if (fotoInput) {
    fotoInput.addEventListener('change', function(e){
        const file = e.target.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = function(ev){
            const img = document.getElementById('avatarPreview');
            if (img) img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
}
</script>

</body>
</html>
