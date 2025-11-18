<?php
session_start();
include "../config/db.php";

// CEK LOGIN
if(!isset($_SESSION['user_id'])){ 
    header('Location: ../login.php'); 
    exit; 
}

// AMBIL DATA USER UNTUK PROFILE DI TOP-BAR
$user_id = $_SESSION['user_id'];
$userQuery = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = $user_id");
$userData = mysqli_fetch_assoc($userQuery);


/* ======================================================
   1. HAPUS DATA
   ====================================================== */
if(isset($_GET['del'])){
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM kegiatan WHERE id={$id}");
    header("Location: kegiatan.php"); 
    exit;
}


/* ======================================================
   2. MODE TAMPILAN (LIST / TAMBAH / EDIT)
   ====================================================== */
$mode = "list"; 

// tombol tambah
if(isset($_GET['tambah'])) {
    $mode = "tambah";
}

// tombol edit
$edit = null;
if(isset($_GET['edit'])){
    $mode = "edit";
    $id = intval($_GET['edit']);
    $res = mysqli_query($conn, "SELECT * FROM kegiatan WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
}


/* ======================================================
   3. SIMPAN DATA (TAMBAH / EDIT)
   ====================================================== */
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['save_kegiatan'])){

    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $tanggal = $_POST['tanggal'];

    // upload foto
    if(isset($_FILES['foto']) && $_FILES['foto']['size'] > 0){
        $foto = addslashes(file_get_contents($_FILES['foto']['tmp_name']));
        $ftype = $_FILES['foto']['type'];
    } else {
        $foto = null;
        $ftype = null;
    }

    // MODE EDIT
    if(!empty($_POST['id'])){
        $id = intval($_POST['id']);

        if($foto){
            mysqli_query($conn, "UPDATE kegiatan SET 
                judul='$judul',
                deskripsi='$deskripsi',
                tanggal='$tanggal',
                foto='$foto',
                foto_type='$ftype'
            WHERE id=$id");
        } else {
            mysqli_query($conn, "UPDATE kegiatan SET 
                judul='$judul',
                deskripsi='$deskripsi',
                tanggal='$tanggal'
            WHERE id=$id");
        }
    }

    // MODE TAMBAH
    else {
        mysqli_query($conn, "INSERT INTO kegiatan 
            (judul, deskripsi, tanggal, foto, foto_type) 
            VALUES (
                '$judul', 
                '$deskripsi', 
                '$tanggal',
                ".($foto ? "'$foto'" : "NULL").",
                ".($ftype ? "'$ftype'" : "NULL")."
            )");
    }

    header("Location: kegiatan.php");
    exit;
}


/* ======================================================
   4. AMBIL DATA LIST KEGIATAN
   ====================================================== */
$queryList = mysqli_query($conn, "SELECT * FROM kegiatan ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Kegiatan Desa</title>

<!-- ICON -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

<style>
/* ======================================================
   ================ START OF INLINE CSS =================
   ====================================================== */

/* DAFTAR KEGIATAN */
body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background: #f7f8fa;
}

.title-row {
  margin-top: 10px;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* ===== SIDEBAR ===== */
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

/* MENU */
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

/* LOGOUT */
.logout {
  position: absolute;
  bottom: 24px;
  left: 20px;
  right: 20px;
}

.logout a {
  display: flex;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  text-decoration: none;
  color: white;
}

/* ===== MAIN ===== */
.main {
  margin-left: 260px;
  padding: 30px 40px;
}

/* ===== TOP BAR (DIPERBAIKI) ===== */
.top-bar {
    display: flex;
    justify-content: flex-end;  /* semua elemen di kanan */
    align-items: center;
    gap: 16px;
    padding: 10px 40px; /* sesuaikan padding agar tidak terlalu nempel */
    background: #fff;
    height: 56px; /* tinggi bar */
    box-sizing: border-box;
}

/* Container untuk grup kanan */
.right-group {
    display: flex;
    align-items: center;
    gap: 15px; /* jarak antar search, nama, foto */
}

/* Search input */
.search-input-wrapper {
    background: #f3f4f8;
    border-radius: 999px;
    padding: 10px 22px;
    display: flex;
    align-items: center;
    width: 250px;
    box-shadow: none;
    border: 1px solid #ccc;
}

.search-input-wrapper input {
    border: none;
    outline: none;
    background: transparent;
    flex: 1;
    font-size: 13px;
    color: #717bbc;
}

.search-input-wrapper i {
    color: #717bbc;
}

/* Nama user */
.user-text {
    display: flex;
    align-items: center;
    white-space: nowrap; /* supaya nama tidak pecah */
}

.user-name {
    font-size: 15px;
    font-weight: 600;
    color: #000;
}

/* Foto profil */
.user-photo {
  width: 42px;
  height: 42px;
  border-radius: 100%;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* BUTTON TAMBAH */
.btn-tambah {
  background: #5E63BB;
  padding: 10px 18px;
  color: white;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* FORM TAMBAH / EDIT */
.form-box {
  background: white;
  padding: 20px;
  border-radius: 12px;
  width: 500px;
}

.form-box input, 
.form-box textarea {
  width: 100%;
  padding: 12px;
  margin-top: 10px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

.form-box button {
  margin-top: 15px;
  padding: 10px 16px;
  background: #5E63BB;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

/* TABLE */
table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: #f0f0f8;
  padding: 14px;
}

td {
  padding: 14px;
}

.foto-kegiatan {
  width: 70px;
  height: 70px;
  border-radius: 10px;
  object-fit: cover;
}

.breadcrumb a {
    color: #5E63BB;
    text-decoration: none;
    font-weight: 600;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.page-title {
    font-size: 28px;   /* lebih besar */
    font-weight: 700;  /* bold */
    color: #000;        /* opsional */
}


/* ====== FORM TAMBAH / EDIT (DESAIN BARU) ====== */
.form-container {
    display: flex;
    gap: 30px;
    margin-top: 20px;
    align-items: flex-start;
}

/* Box kiri (form input) */
.form-left {
    width: 55%;
    background: white;
    padding: 28px;
    border-radius: 14px;
    border: 1px solid #e2e2e2;
}

.form-left h3 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 6px;
}

.breadcrumb {
    margin-bottom: 18px;
    font-size: 14px;
    color: #777;
}

/* Input style */
.form-left input,
.form-left textarea {
    width: 100%;
    padding: 13px 14px;
    margin-top: 14px;
    border-radius: 10px;
    border: 1px solid #c8c8c8;
    font-size: 14px;
}

/* Tombol kembali */
.back-btn {
    margin-top: 10px;
    display: inline-block;
    color: #444;
    text-decoration: none;
    font-size: 14px;
}

/* Tombol simpan */
.btn-simpan {
    margin-top: 18px;
    background: #48C774;
    padding: 12px 22px;
    border-radius: 10px;
    border: none;
    color: white;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ====== Upload Image (kanan) ====== */

.form-right {
    width: 40%;
    padding-top: 10px;
}

.upload-box {
    width: 100%;
    height: 230px;
    border: 2px dashed #bfbfbf;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #777;
    font-size: 15px;
}

.upload-box i {
    font-size: 36px;
    color: #5E63BB;
    margin-bottom: 10px;
}

.upload-box:hover {
    background: #f5f6ff;
}

/* Preview image */
.preview-img {
    width: 100%;
    max-height: 230px;
    object-fit: cover;
    border-radius: 16px;
    margin-top: 14px;
}




/* ======================================================
   ================= END OF INLINE CSS ==================
   ====================================================== */
</style>
</head>

<body>

<!-- SIDEBAR HEADER -->
<div class="sidebar-header">
  <img src="../assets/images/logo-nganjuk.png">
  <div class="title">Desa Banjardowo</div>
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
      <i class="fa-solid fa-arrow-right-from-bracket"></i>Keluar
    </a>
  </div>
</aside>



<div class="main">
<div class="top-bar">
  <div class="right-group">
    <div class="search-input-wrapper">
        <input type="text" placeholder="Search...">
        <i class="fa-solid fa-magnifying-glass"></i>
    </div>

    <div class="user-text">
        <div class="user-name"><?= htmlspecialchars($userData['nama_lengkap']); ?></div>
    </div>

    <a href="profile.php" class="user-photo">
      <svg viewBox="0 0 24 24" fill="#ffffff" width="42" height="42">
          <circle cx="12" cy="12" r="12" fill="#b4b4b4"></circle>
          <circle cx="12" cy="10" r="4" fill="#ffffff"></circle>
          <path d="M4 20c1.5-4 6.5-4 8-4s6.5 0 8 4" fill="#ffffff"></path>
      </svg>
    </a>
  </div>
</div>





<!-- TITLE ROW -->
<div class="title-row">
  <div class="page-title">Daftar Kegiatan Desa</div>

  <?php if($mode == "list"): ?>
    <a href="kegiatan.php?tambah=1">
      <button class="btn-tambah"><i class="fa-solid fa-plus"></i> Tambah</button>
    </a>
  <?php endif; ?>
</div>

<div class="breadcrumb">
    <a href="dashboard.php">Dashboard</a> / 
    <a href="kegiatan.php">Kegiatan Desa</a>
</div>

<!-- ===================================================
     5. FORM TAMBAH / EDIT
     =================================================== -->
<?php if($mode != "list"): ?>

<div class="form-container">

    <!-- ================= LEFT FORM ================= -->
    <div class="form-left">

        <h3><?= ($mode == "edit" ? "Edit Kegiatan Desa" : "Tambah Kegiatan Desa") ?></h3>

        <div class="breadcrumb">
            <a href="kegiatan.php">Kegiatan Desa</a> /
            <?= ($mode == "edit" ? "Edit" : "Tambah Kegiatan Desa") ?>
        </div>

        <a href="kegiatan.php" class="back-btn">&larr; Kembali</a>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

            <label>No</label>
            <input type="text" placeholder="No otomatis / opsional">

            <label>Nama Kegiatan Desa</label>
            <input type="text" name="judul" required
                   value="<?= htmlspecialchars($edit['judul'] ?? '') ?>">

            <label>Lokasi</label>
            <input type="text" placeholder="Lokasi kegiatan">

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="5"><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>

            <label>Tanggal</label>
            <input type="date" name="tanggal" required
                   value="<?= $edit['tanggal'] ?? '' ?>">

            <button class="btn-simpan" type="submit" name="save_kegiatan">
                <i class="fa-solid fa-plus"></i>
                <?= ($mode == "edit" ? "Update Data" : "Simpan Data") ?>
            </button>

        </form>

    </div>

    <!-- ================= RIGHT UPLOAD IMAGE ================= -->
    <div class="form-right">

        <label>Upload Image</label>

        <label class="upload-box" for="uploadFoto">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            Pilih Image
        </label>

        <input type="file" id="uploadFoto" name="foto" accept="image/*" style="display:none">

        <?php if ($mode == "edit" && $edit['foto']): ?>
            <img class="preview-img"
                 src="data:<?= $edit['foto_type'] ?>;base64,<?= base64_encode($edit['foto']) ?>">
        <?php endif; ?>
</div>


<?php endif; ?>


<!-- ===================================================
     6. LIST DATA
     =================================================== -->
<?php if($mode == "list"): ?>

<table>
<tr>
  <th></th>
  <th>No</th>
  <th>Nama Kegiatan Desa</th>
  <th>Lokasi</th>
  <th>Deskripsi</th>
  <th>Tanggal</th>
  <th>Aksi</th>
</tr>

<?php 
$no = 1;
while($row = mysqli_fetch_assoc($queryList)): 
?>
<tr>

  <td>
    <?php if(!empty($row['foto'])): ?>
      <img class="foto-kegiatan" src="data:<?= $row['foto_type'] ?>;base64,<?= base64_encode($row['foto']) ?>">
    <?php endif; ?>
  </td>

  <td><?= $no++; ?></td>
  <td><?= htmlspecialchars($row['judul']) ?></td>
  <td>-</td>
  <!-- Pembatas Deskripsi -->
  <td>
  <?php 
    $maxLength = 250; // batas maksimal karakter
    $desc = strip_tags($row['deskripsi']); // hapus tag HTML supaya aman
    if(strlen($desc) > $maxLength){
      echo nl2br(htmlspecialchars(substr($desc, 0, $maxLength))) . "...";
    } else {
      echo nl2br(htmlspecialchars($desc));
    }
  ?>
</td>

  <td><?= date("d F Y", strtotime($row['tanggal'])) ?></td>

  <td class="aksi-btn">
    <a href="kegiatan.php?edit=<?= $row['id'] ?>"><i class="fa-solid fa-pen"></i></a>
    <a href="kegiatan.php?del=<?= $row['id'] ?>" onclick="return confirm('Hapus kegiatan ini?')">
      <i class="fa-solid fa-trash" style="color:red;"></i>
    </a>
  </td>

</tr>
<?php endwhile; ?>

</table>

<?php endif; ?>


</div> <!-- END MAIN -->
</body>
</html>
