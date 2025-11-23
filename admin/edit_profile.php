<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
$uid = intval($_SESSION['user_id']);
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama_file = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] == 0) {
        $allowed = ['png', 'jpg', 'jpeg'];
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $nama_file = 'cover_' . $uid . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['cover']['tmp_name'], '../uploads/' . $nama_file);
            mysqli_query($conn, "UPDATE users SET cover='$nama_file' WHERE id=$uid");
            header("Location: edit_profile.php"); // atau profile.php jika ingin lihat perubahan
            exit;
        } else {
            $msg = 'Format file tidak didukung. Pilih PNG atau JPG.';
        }
    }
    // Lanjutan proses update user
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $jk = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    if ($nama_file) {
        $sql = "UPDATE users SET nama_lengkap='$nama', username='$username', email='$email', jenis_kelamin='$jk', no_telp='$telp', alamat='$alamat', cover='$nama_file' WHERE id=$uid";
    } else {
        $sql = "UPDATE users SET nama_lengkap='$nama', username='$username', email='$email', jenis_kelamin='$jk', no_telp='$telp', alamat='$alamat' WHERE id=$uid";
    }
    mysqli_query($conn, $sql);
    $msg = "Profil berhasil diperbarui.";
    header("Location: profile.php");
    exit;
}


$res  = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Profil</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<!-- CSS EDIT PROFIL MODERN/FLEXIBLE -->
<style>
body{margin:0;padding:0;background:#f7f8fa;font-family:'Inter',sans-serif;}
.container-profile-edit {
    max-width: 1100px;
    margin: 38px auto;
    background: #fff;
    border-radius: 10px;
    padding-bottom: 38px;
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
.sidebar-header img { width: 36px; height: 36px;}

.cover-edit {
    width: 100%;
    height: 190px;
    background-size: cover;
    border-radius: 10px 10px 0 0;
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
}
.cover-btn-edit {
    position: absolute; bottom: 18px; right: 26px;
    background: rgba(0,0,0,0.16); color: #fff; border: none;
    display: flex; align-items: center; gap: 8px;
    border-radius: 8px; font-size: 15px; padding: 7px 15px; cursor: pointer;
}

.profile-edit-head-row {
    display: flex;
    align-items: center;
    gap: 22px;
    margin-left: 38px;
    margin-top: -60px; /* avatar naik ke header */
}
.avatar-edit-img {
    width: 126px; height: 126px;
    border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 2px 16px rgba(0,0,0,0.11);
    object-fit: cover;
    background: #fff;
}
.edit-main-info h2 {
    margin: 0 0 4px 0;
    font-size: 25px;
    font-weight: 700;
    text-align: left;
}
.edit-main-info small {
    color: #888;
    font-size: 16px;
}
.edit-nama {
    margin-top: 9px;
    font-size: 18px;
    font-weight: 500;
    color: #222;
}

.profile-edit-form-section { margin-top: 24px; padding: 0 40px; }
.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #755d2c;
    margin-bottom: 12px;
}
.profile-edit-form-flex {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 30px;
    max-width: 900px;
}
.profile-edit-form-flex label { font-weight: 500; font-size: 15px; color: #444; display: block; margin-bottom: 5px; margin-top: 12px;}
.profile-edit-form-flex input,
.profile-edit-form-flex textarea,
.profile-edit-form-flex select {
    width: 100%; border-radius: 8px;
    border: 1.2px solid #bbb;
    background: #f7fafc;
    font-size: 15px;
    padding: 10px 13px;
    margin-top: 1.5px;
}
.profile-edit-form-flex textarea { min-height: 40px; }
.form-btn-row{margin-top:35px; display: flex; gap:24px;}
.btn-edit-cancel{background: #fff; color: #fa9800; padding: 10px 31px; border:1.6px solid #fa9800; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer;}
.btn-edit-cancel:hover{background: #ffe5c8;}
.btn-edit-simpan{background: #fa9800; color: #fff; padding: 10px 34px; border:none; border-radius: 8px;font-size:16px; font-weight:600; cursor:pointer;}
.btn-edit-simpan:hover{background: #ffa820;}



</style>
</head>
<body>
<!-- SIDEBAR HEADER -->
<div class="sidebar-header">
  <img src="../assets/images/logo-nganjuk.png">
  <div class="title">Desa Banjardowo</div>
</div>

<!-- Bagian dalam cover-edit -->
<div class="container-profile-edit">
    <!-- Bagian Cover Sampul -->
    <div class="cover-edit" style="background-image: url('../uploads/<?php echo $user['cover'] ?: 'cover.jpg'; ?>')">
        <form method="post" enctype="multipart/form-data" style="position:absolute;bottom:13px;right:20px;">
            <input type="file" name="cover" id="cover" accept=".png,.jpg,.jpeg" style="display:none;" onchange="this.form.submit()"/>
            <button type="button" class="cover-btn-edit" onclick="document.getElementById('cover').click();">
                <i class="fa fa-camera"></i> Edit Cover
            </button>
        </form>
    </div>

    <!-- Avatar + Judul + Info User -->
    <div class="profile-edit-head-row">
        <img src="../uploads/<?php echo $user['foto'] ?: 'default.png'; ?>" class="avatar-edit-img">
        <div class="edit-main-info">
            <h2>Edit Profil</h2>
            <small>Profil / Edit Profil</small>
            <div class="edit-nama"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
        </div>
    </div>

    <?php if($msg) echo "<p class='alert'>$msg</p>"; ?>
    <div class="profile-edit-form-section">
        <div class="form-section-title">Informasi Pribadi</div>
        <form method="post" class="profile-edit-form-flex">
            <div>
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required value="<?php echo $user['nama_lengkap']; ?>">
            </div>
            <div>
                <label>Username</label>
                <input type="text" name="username" required value="<?php echo $user['username']; ?>">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo $user['email']; ?>">
            </div>
            <div>
                <label>No Telpon</label>
                <input type="text" name="no_telp" value="<?php echo $user['no_telp']; ?>">
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
                <textarea name="alamat"><?php echo $user['alamat']; ?></textarea>
            </div>
            <div class="form-btn-row" style="grid-column:1/3">
                <a href="profile.php" class="btn-edit-cancel">Batal</a>
                <button type="submit" name="update_profile" class="btn-edit-simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>




</body>
</html>
