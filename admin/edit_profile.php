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
    $nama   = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $jk     = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $telp   = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    $sql = "UPDATE users SET nama_lengkap='$nama', username='$username', email='$email', jenis_kelamin='$jk', no_telp='$telp', alamat='$alamat' WHERE id=$uid";
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
.container-profile-edit{
    max-width:900px;margin:38px auto;background:#fff;border-radius:10px;box-shadow:0 4px 22px rgba(30,24,51,.07);
    border:1.2px solid #e2e1e1;padding-bottom:38px;
}
.cover-edit{
    width:100%;height:150px;background:url('../assets/images/cover.jpg') center center no-repeat;background-size:cover;
    border-radius:10px 10px 0 0;position:relative;display:flex;align-items:flex-end;justify-content: flex-end;
}
.cover-btn-edit{
    position:absolute;bottom:13px;right:20px;background:rgba(0,0,0,0.13);color:#fff;border:none;display:flex;align-items:center;gap:6px;
    border-radius:8px;font-size:15px;padding:6px 15px;cursor:pointer;
}
.profile-edit-row{display:flex;align-items:end;gap:28px;margin-top:-68px;margin-left:30px;}
.avatar-edit-img{width:120px;height:120px;border-radius:50%;border:4px solid #fff;box-shadow:0 2px 16px rgba(0,0,0,0.09);}
.profile-main-info h2{margin:0 0 2px 0;font-size:23px;font-weight:700;}
.profile-main-info .desc{margin:5px 0 5px 0;color:#555;}
.profile-main-info small{color:#8c8c8c;font-size:15px;}
.profile-edit-form-section{margin-top:24px;padding:0 26px;}
.form-section-title{font-size:17px;font-weight:600;color:#755d2c;margin-bottom:18px;}
.profile-edit-form-flex{display:grid;grid-template-columns:1fr 1fr;gap:18px 32px;max-width:99vw;}
.profile-edit-form-flex label{font-weight:500;font-size:15px;color:#444;display:block;margin-bottom:5.5px;}
.profile-edit-form-flex input,.profile-edit-form-flex textarea,.profile-edit-form-flex select{
    width:100%;border-radius:8px;border:1.2px solid #bbb;background:#f7fafc;font-size:15px;
    padding:10px 13px;margin-top:1.5px;margin-bottom:0;
}
.profile-edit-form-flex textarea{min-height:40px;}
.form-btn-row{margin-top:31px;display:flex;gap:18px;}
.btn-edit-cancel{background:#fff;color:#fa9800;padding:10px 30px;border:1.6px solid #fa9800;border-radius:8px;font-size:15px;font-weight:500;cursor:pointer;}
.btn-edit-cancel:hover{background:#ffe5c8;}
.btn-edit-simpan{background:#fa9800;color:#fff;padding:10px 30px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;}
.btn-edit-simpan:hover{background:#ffa820;}
.alert{background:#e8fff2;color:#39a27d;border:1.2px solid #baf9c2;padding:7px 14px;border-radius:7px;margin:9px 0 15px 36px;display:inline-block;}
@media(max-width:750px){
    .container-profile-edit{max-width:99vw;}
    .profile-edit-row{flex-direction:column;gap:0;margin-left:0;}
    .avatar-edit-img{margin:-60px auto 0 auto;}
    .profile-edit-form-section{padding:0 6px;}
    .profile-edit-form-flex{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="container-profile-edit">
    <div class="cover-edit">
        <button class="cover-btn-edit">
            <i class="fa fa-camera"></i> Edit Cover
        </button>
    </div>
    <div class="profile-edit-row">
        <img src="../uploads/<?php echo $user['foto'] ?: 'default.png'; ?>" class="avatar-edit-img">
        <div class="profile-main-info">
            <h2>Edit Profil</h2>
            <small>Profil / Edit Profil</small>
            <div class="desc"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
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
