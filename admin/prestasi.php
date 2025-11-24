<?php
session_start();
include "../config/db.php";

// ================= CEK LOGIN =================
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// ================= PROFIL PENGGUNA (TOP BAR) =================
$user_id = (int)$_SESSION['user_id'];

$resUser = mysqli_query($conn, "
    SELECT nama_lengkap, username, role, foto
    FROM users
    WHERE id = $user_id
");
$userData = mysqli_fetch_assoc($resUser);

// Nama + role (pakai nama_lengkap, fallback username)
$namaAdmin = !empty($userData['nama_lengkap'])
    ? $userData['nama_lengkap']
    : ($_SESSION['username'] ?? 'Administrator');

$roleAdmin = !empty($userData['role']) ? $userData['role'] : 'admin';

// Inisial jika tidak ada foto
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1));

// Foto profil (longblob → base64)
$fotoProfilSrc = null;
if (!empty($userData['foto'])) {
    $fotoProfilSrc = "data:image/jpeg;base64," . base64_encode($userData['foto']);
}


// ================== 1. HAPUS DATA ==================
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM prestasi WHERE id={$id}");
    header("Location: prestasi.php");
    exit;
}


// ================== 2. MODE HALAMAN ==================
$mode = "list";
if (isset($_GET['tambah'])) {
    $mode = "tambah";
}
if (isset($_GET['edit'])) {
    $mode = "edit";
}

$edit = null;
if ($mode === 'edit') {
    $id   = intval($_GET['edit']);
    $res  = mysqli_query($conn, "SELECT * FROM prestasi WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
}

// Judul halaman
if ($mode == "tambah") {
    $page_title = "Tambah Prestasi";
} elseif ($mode == "edit") {
    $page_title = "Edit Prestasi";
} else {
    $page_title = "Daftar Prestasi";
}


// ================== 3. SIMPAN DATA (TAMBAH / EDIT) ==================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_prestasi'])) {
    $judul         = mysqli_real_escape_string($conn, $_POST['judul']);
    $bidang        = mysqli_real_escape_string($conn, $_POST['bidang']);
    $penyelenggara = mysqli_real_escape_string($conn, $_POST['penyelenggara']);
    $deskripsi     = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $tanggal       = $_POST['tanggal'];

    // Upload foto
    $foto  = null;
    $ftype = null;

    if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
        $foto  = addslashes(file_get_contents($_FILES['foto']['tmp_name']));
        $ftype = $_FILES['foto']['type'];
    }

    // EDIT
    if (!empty($_POST['id'])) {
        $id = intval($_POST['id']);

        if ($foto) {
            mysqli_query($conn, "UPDATE prestasi SET 
                judul         = '$judul',
                bidang        = '$bidang',
                penyelenggara = '$penyelenggara',
                deskripsi     = '$deskripsi',
                tanggal       = '$tanggal',
                foto          = '$foto',
                foto_type     = '$ftype'
            WHERE id = $id");
        } else {
            mysqli_query($conn, "UPDATE prestasi SET 
                judul         = '$judul',
                bidang        = '$bidang',
                penyelenggara = '$penyelenggara',
                deskripsi     = '$deskripsi',
                tanggal       = '$tanggal'
            WHERE id = $id");
        }

    } else {
        // TAMBAH
        mysqli_query($conn, "INSERT INTO prestasi 
            (judul, bidang, penyelenggara, deskripsi, tanggal, foto, foto_type) 
            VALUES (
                '$judul',
                '$bidang',
                '$penyelenggara',
                '$deskripsi',
                '$tanggal',
                " . ($foto ? "'$foto'" : "NULL") . ",
                " . ($ftype ? "'$ftype'" : "NULL") . "
            )");
    }

    header("Location: prestasi.php");
    exit;
}


// ================== 4. AMBIL DATA LIST (DENGAN SEARCH) ==================
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $like = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sqlList = "
        SELECT * FROM prestasi
        WHERE judul LIKE '$like'
           OR bidang LIKE '$like'
           OR penyelenggara LIKE '$like'
           OR deskripsi LIKE '$like'
        ORDER BY id DESC
    ";
} else {
    $sqlList = "SELECT * FROM prestasi ORDER BY id DESC";
}

$queryList = mysqli_query($conn, $sqlList);

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Prestasi</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

<style>
/* === GLOBAL === */
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;
  background:#f4f5fb;
  color:#333;
}
a{text-decoration:none;color:inherit}

/* ===== SIDEBAR ===== */
.sidebar {
  position: fixed;
  left: 20px;
  top: 90px;
  width: 260px;
  height: calc(100vh - 104px);
  background: linear-gradient(200deg, #1c3f9f, #3B82F6);
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
.sidebar-header img{
  height:48px;
  width:auto;
  display:block;
  object-fit:contain;
}

.sidebar-header .title{
  color:#000;
  font-weight:600;
  font-size:15px;
}

.menu { list-style:none;padding:0;margin-top:16px; }
.menu a{
  display:flex;
  align-items:center;
  gap:12px;
  color:white;
  padding:10px 12px;
  border-radius:999px;
  font-size:13px;
  opacity:.9;
}
.menu a.active{background:#38BDF8;opacity:1;font-weight:600;}
.menu a:hover{background:rgba(255,255,255,.15);}
.logout{
  position:absolute;bottom:24px;left:20px;right:20px;
}
.logout a{
  display:flex;
  gap:12px;
  padding:12px 18px;
  border-radius:8px;
  color:white;
}

/* ===== MAIN WRAPPER ===== */
.main{
  margin-top:-3px;
  margin-left:260px;
  padding:30px 40px;
  display:flex;
  flex-direction:column;
  flex:1;
  min-width:0;
}

 /* BAR ATAS: SEARCH DI TENGAH + PROFIL KANAN */
        .top-bar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:14px;
        }
        .search-input-wrapper{
            background:#ffffff;
            border-radius:999px;
            padding:10px 22px;
            display:flex;
            align-items:center;
            width:55%;
            max-width:580px;
            box-shadow:0 6px 16px rgba(15,23,42,.08);
            margin-left:auto;
            margin-right:20px;
        }
        .search-icon{
            font-size:18px;
            opacity:0.55;
            margin-right:10px;
            display:flex;
            align-items:center;
        }
        .search-input-wrapper input{
            border:none;
            outline:none;
            background:transparent;
            flex:1;
            font-size:13px;
        }
.profile-wrapper{
  display:flex;
  align-items:center;
  gap:10px;
  margin-left:20px;
}
.profile-text{
  text-align:right;
  font-size:12px;
}
.profile-text .name{font-weight:600}
.profile-text .role{font-size:11px;color:#9ca3af}
.profile-avatar{
  width:38px;height:38px;border-radius:999px;
  background:#f97316;color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-weight:600;font-size:16px;
  overflow:hidden;
}
.profile-avatar img{
  width:100%;height:100%;object-fit:cover;border-radius:999px;
}

/* ===== CONTENT CARD (sejajar dengan saran.php) ===== */
.content-card{
  background:#fff;
  border-radius:18px;
  padding:24px 28px;
  box-shadow:0 8px 20px rgba(15,23,42,.06);
  width:100%;
  max-width:none;
  margin:0;
  box-sizing:border-box;
  flex:1;
}
.header-row{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  margin-bottom:6px;
}
.page-title{font-size:20px;margin-bottom:4px;}
.breadcrumb{font-size:11px;color:#9ca3af;margin-top:2px;margin-bottom:4px}

/* ===== TOMBOL TAMBAH ===== */
.btn-tambah{
  background:#5E63BB;
  padding:10px 18px;
  color:#fff;
  border-radius:10px;
  border:none;
  cursor:pointer;
  display:flex;
  align-items:center;
  gap:6px;
}

/* ===== TABLE ===== */
table{
  width:100%;
  border-collapse:collapse;
  margin-top:20px;
  font-size:13px;
}
th,td{
  padding:8px 6px;
  text-align:left;
  vertical-align:top;
}
thead{ border-bottom:1px solid #e5e7eb; }
th{ color:#6b7280;font-weight:500; }
tbody tr:hover{ background:#f9fafb; }

.foto-prestasi{
  width:60px;
  height:60px;
  border-radius:16px;
  object-fit:cover;
  background:#e5e7eb;
}

/* Aksi */
.aksi-btn{
  display:flex;
  justify-content:space-between;
  align-items:center;
  width:100%;
  gap:10px;
  padding:0 !important;
}
.aksi-btn a{
  display:flex;
  align-items:center;
  justify-content:center;
  padding:6px 12px;
  border-radius:8px;
  color:#5E63BB;
  text-decoration:none;
  font-size:14px;
}
.aksi-btn a:hover{ background:#f0f0f8; }
.aksi-btn .fa-trash{ color:red !important; }

/* ===== FORM TAMBAH / EDIT ===== */
.form-container{
  display:flex;
  gap:30px;
  margin-top:20px;
  align-items:flex-start;
}
.form-left{
  width:55%;
  background:#fff;
  padding:28px;
  border-radius:14px;
  border:1px solid #e2e2e2;
}
.form-left input,
.form-left textarea{
  width:100%;
  padding:13px 14px;
  margin-top:14px;
  border-radius:10px;
  border:1px solid #c8c8c8;
  font-size:14px;
}
.form-right{
  width:40%;
  padding-top:10px;
  position:relative;
}
.upload-box{
  width:100%;height:230px;
  border:2px dashed #bfbfbf;
  border-radius:16px;
  display:flex;align-items:center;justify-content:center;
  position:relative;
  overflow:hidden;
  background:#fff;
  cursor:pointer;
}
.preview-img{
  position:absolute;top:0;left:0;
  width:100%;height:100%;
  object-fit:cover;border-radius:16px;z-index:1;
}
.upload-overlay{
  position:absolute;top:0;left:0;
  width:100%;height:100%;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  z-index:2;
}
.upload-overlay i{font-size:36px;color:#5E63BB;margin-bottom:10px;}
.upload-overlay span{font-size:16px;color:#000;font-weight:500;}
.upload-trigger{
  position:absolute;top:0;left:0;
  width:100%;height:100%;
  opacity:0;cursor:pointer;z-index:3;
}
.btn-simpan{
  margin-top:18px;
  background:#48C774;
  padding:12px 22px;
  border-radius:10px;
  border:none;
  color:#fff;
  font-size:15px;
  cursor:pointer;
  display:flex;
  align-items:center;
  gap:6px;
}
.back-btn{
  margin-left:10px;
  font-size:14px;
}

/* ===== MODAL LOGOUT ===== */
.modal{
  position:fixed;inset:0;display:flex;
  justify-content:center;align-items:center;
  background:rgba(0,0,0,.35);
  opacity:0;pointer-events:none;
  transition:.25s;
}
.modal.show{opacity:1;pointer-events:all;}
.modal-content{
  width:340px;padding:26px 28px;
  background:#fff;border-radius:18px;
  text-align:center;
}
.modal-actions{
  margin-top:22px;display:flex;gap:14px;
}
.btn-cancel,.btn-logout{
  flex:1;padding:10px 0;border-radius:10px;
  border:none;font-weight:600;cursor:pointer;
}
.btn-cancel{background:#dcdcdc;}
.btn-logout{background:#e63946;color:#fff;}

/* ===== MODAL HAPUS PRESTASI ===== */
.delete-modal-backdrop{
  position:fixed;
  inset:0;
  display:none;
  align-items:center;
  justify-content:center;
  background:rgba(15,23,42,0.45);
  z-index:9999;
}
.delete-modal-card{
  width:340px;
  padding:22px 24px;
  background:#ffffff;
  border-radius:18px;
  box-shadow:0 18px 40px rgba(15,23,42,0.35);
  border:1px solid rgba(148,163,184,0.5);
  animation:popIn .18s ease-out;
}
.delete-modal-title{
  font-size:16px;
  font-weight:600;
  margin-bottom:4px;
}
.delete-modal-text{
  font-size:13px;
  color:#4b5563;
  margin-bottom:16px;
}
.delete-modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:10px;
}
.btn-delete-cancel{
  background:#ffffff;
  border-radius:999px;
  padding:8px 16px;
  border:1px solid #e5e7eb;
  font-size:13px;
  cursor:pointer;
}
.btn-delete-confirm{
  background:#ef4444;
  color:#fff;
  border:none;
  border-radius:999px;
  padding:8px 16px;
  font-size:13px;
  cursor:pointer;
  font-weight:500;
}
@keyframes popIn{
  from{opacity:0;transform:translateY(6px) scale(.97);}
  to{opacity:1;transform:translateY(0) scale(1);}
}
</style>
</head>
<body>

<!-- SIDEBAR HEADER -->
<div class="sidebar-header">
  <img src="../assets/images/logo-nganjuk.png" alt="Logo">
  <div class="title">Desa Banjardowo</div>
</div>

<!-- SIDEBAR -->
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <ul class="menu">
    <li>
      <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <img src="../assets/icons/dashboard1.png" alt="" style="width:20px;height:20px;margin-right:8px;">Dashboard
      </a>
    </li>
    <li>
      <a href="kegiatan.php" class="<?= $current_page == 'kegiatan.php' ? 'active' : '' ?>">
        <img src="../assets/icons/kegiatandesa.png" alt="" style="width:20px;height:20px;margin-right:8px;">Kegiatan Desa
      </a>
    </li>
    <li>
      <a href="prestasi.php" class="<?= $current_page == 'prestasi.php' ? 'active' : '' ?>">
        <img src="../assets/icons/prestasi.png" alt="" style="width:20px;height:20px;margin-right:8px;">Prestasi
      </a>
    </li>
    <li>
      <a href="saran.php" class="<?= $current_page == 'saran.php' ? 'active' : '' ?>">
        <img src="../assets/icons/kotaksaran1.png" alt="" style="width:20px;height:20px;margin-right:8px;">Kotak Saran
      </a>
    </li>
    <li>
      <a href="pelayanan.php" class="<?= $current_page == 'pelayanan.php' ? 'active' : '' ?>">
        <img src="../assets/icons/pelayanan1.png" alt="" style="width:20px;height:20px;margin-right:8px;">Pelayanan
      </a>
    </li>
  </ul>

  <div class="logout">
    <a href="#" id="logoutBtn">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>Keluar
    </a>
  </div>
</aside>

<!-- MODAL LOGOUT -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Keluar Akun?</h3>
    <p>Anda yakin ingin keluar dari akun?</p>
    <div class="modal-actions">
      <button id="cancelLogout" class="btn-cancel">Batal</button>
      <a href="../logout.php" class="btn-logout">Keluar</a>
    </div>
  </div>
</div>

<div class="main">
    <!-- TOP BAR -->
    <div class="top-bar">
        <form method="get" class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search" value="<?= htmlspecialchars($search); ?>">
        </form>

        <div class="profile-wrapper">
            <div class="profile-text">
                <div class="name"><?= htmlspecialchars($namaAdmin); ?></div>
                <div class="role"><?= htmlspecialchars($roleAdmin); ?></div>
            </div>
            <a href="profile.php" class="profile-avatar">
                <?php if ($fotoProfilSrc): ?>
                    <img src="<?= $fotoProfilSrc; ?>" alt="Foto Profil">
                <?php else: ?>
                    <?= htmlspecialchars($inisialAdmin); ?>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- KONTEN UTAMA -->
    <div class="content-card">
        <div class="header-row">
            <div>
                <h2 class="page-title"><?= htmlspecialchars($page_title); ?></h2>
                <div class="breadcrumb">
                    Prestasi /
                    <?php if ($mode == "list"): ?>
                        Daftar Prestasi
                    <?php elseif ($mode == "tambah"): ?>
                        Tambah Prestasi
                    <?php else: ?>
                        Edit Prestasi
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($mode == "list"): ?>
                <a href="prestasi.php?tambah=1">
                    <button class="btn-tambah">
                        <i class="fa-solid fa-plus"></i> Tambah
                    </button>
                </a>
            <?php endif; ?>
        </div>

        <!-- FORM TAMBAH / EDIT -->
        <?php if ($mode != "list"): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-container">
                    <div class="form-left">
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

                        <label>Nama Prestasi</label>
                        <input type="text" name="judul" required
                               value="<?= htmlspecialchars($edit['judul'] ?? '') ?>">

                        <label>Bidang</label>
                        <input type="text" name="bidang" required
                               value="<?= htmlspecialchars($edit['bidang'] ?? '') ?>">

                        <label>Penyelenggara</label>
                        <input type="text" name="penyelenggara"
                               value="<?= htmlspecialchars($edit['penyelenggara'] ?? '') ?>">

                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="5"><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>

                        <label>Tanggal</label>
                        <input type="date" name="tanggal" required
                               value="<?= $edit['tanggal'] ?? '' ?>">
                    </div>

                    <div class="form-right">
                        <label>Upload Image</label>
                        <div class="upload-box" id="uploadBox">
                            <img
                                id="previewImg"
                                class="preview-img"
                                src="<?php
                                    if ($mode == 'edit' && !empty($edit['foto'])) {
                                        echo 'data:' . $edit['foto_type'] . ';base64,' . base64_encode($edit['foto']);
                                    }
                                ?> "
                                style="<?= ($mode == 'edit' && !empty($edit['foto'])) ? 'display:block;' : 'display:none;' ?>"
                                alt="Preview"
                            />
                            <div class="upload-overlay">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>
                                    <?= ($mode == "edit" && !empty($edit['foto']))
                                        ? 'Klik untuk Ganti Gambar'
                                        : 'Pilih Image'; ?>
                                </span>
                            </div>
                            <input
                                type="file"
                                id="uploadFoto"
                                name="foto"
                                class="upload-trigger"
                                accept="image/*"
                            >
                        </div>
                    </div>
                </div>

                <button class="btn-simpan" type="submit" name="save_prestasi">
                    <i class="fa-solid fa-plus"></i>
                    <?= ($mode == "edit" ? "Update Data" : "Simpan Data") ?>
                </button>
                <a href="prestasi.php" class="back-btn">&larr; Kembali</a>
            </form>
        <?php endif; ?>

        <!-- LIST DATA -->
        <?php if ($mode == "list"): ?>
            <table>
                <thead>
                <tr>
                    <th></th>
                    <th style="width:60px;">No</th>
                    <th>Nama Prestasi</th>
                    <th>Bidang</th>
                    <th>Penyelenggara</th>
                    <th>Deskripsi</th>
                    <th>Tanggal Perolehan</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                if (mysqli_num_rows($queryList) == 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:20px;color:#9ca3af;">
                            Belum ada data prestasi.
                        </td>
                    </tr>
                <?php else:
                    while ($row = mysqli_fetch_assoc($queryList)): ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['foto'])): ?>
                                    <img class="foto-prestasi"
                                         src="data:<?= $row['foto_type'] ?>;base64,<?= base64_encode($row['foto']) ?>">
                                <?php else: ?>
                                    <img class="foto-prestasi"
                                         src="../assets/icons/noimage.png" alt="No Image">
                                <?php endif; ?>
                            </td>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['bidang'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['penyelenggara'] ?? '-') ?></td>
                            <td>
                                <?php
                                $maxLength = 250;
                                $desc = strip_tags($row['deskripsi']);
                                if (strlen($desc) > $maxLength) {
                                    echo nl2br(htmlspecialchars(substr($desc, 0, $maxLength))) . "...";
                                } else {
                                    echo nl2br(htmlspecialchars($desc));
                                }
                                ?>
                            </td>
                            <td><?= date("d F Y", strtotime($row['tanggal'])) ?></td>
                            <td class="aksi-btn">
                                <a href="prestasi.php?edit=<?= $row['id'] ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="javascript:void(0);" onclick="openDeleteModal(<?= $row['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile;
                endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div> <!-- end .content-card -->
</div> <!-- end .main -->

<!-- MODAL HAPUS PRESTASI -->
<div id="deleteModal" class="delete-modal-backdrop">
  <div class="delete-modal-card">
    <div class="delete-modal-title">Hapus prestasi ini?</div>
    <div class="delete-modal-text">
      Data yang sudah dihapus tidak dapat dikembalikan.
      Apakah Anda yakin ingin melanjutkan?
    </div>
    <div class="delete-modal-actions">
      <button type="button" class="btn-delete-cancel" onclick="closeDeleteModal()">Batal</button>
      <button type="button" class="btn-delete-confirm" onclick="confirmDelete()">Ya, hapus</button>
    </div>
  </div>
</div>

<script>
// logout modal
const logoutBtn    = document.getElementById("logoutBtn");
const logoutModal  = document.getElementById("logoutModal");
const cancelLogout = document.getElementById("cancelLogout");

if (logoutBtn) {
  logoutBtn.onclick = function(e){
      e.preventDefault();
      logoutModal.classList.add("show");
  };
}
if (cancelLogout) {
  cancelLogout.onclick = function(){
      logoutModal.classList.remove("show");
  };
}

// preview foto upload
document.addEventListener('DOMContentLoaded', function () {
    const fileInput  = document.getElementById('uploadFoto');
    const previewImg = document.getElementById('previewImg');
    if (!fileInput || !previewImg) return;

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
});

// ===== MODAL HAPUS PRESTASI =====
let deleteId = null;

function openDeleteModal(id){
    deleteId = id;
    const m = document.getElementById('deleteModal');
    if (m) m.style.display = 'flex';
}
function closeDeleteModal(){
    deleteId = null;
    const m = document.getElementById('deleteModal');
    if (m) m.style.display = 'none';
}
function confirmDelete(){
    if (!deleteId) return;
    window.location.href = 'prestasi.php?del=' + deleteId;
}
</script>
</body>
</html>
