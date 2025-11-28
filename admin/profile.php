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
    $coverBase64 = base64_encode($user['cover']);

    // untuk background cover (header)
    $coverStyle = "background-image: url('data:image/jpeg;base64," . $coverBase64 . "');";

    // untuk modal (full image)
    $coverSrc = "data:image/jpeg;base64," . $coverBase64;
} else {
    // fallback ke file biasa
    $coverStyle = "background-image: url('../uploads/cover-default.jpg');";
    $coverSrc   = "../uploads/cover-default.jpg";
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
    margin: 0;
    padding: 0;
    font-family: 'Inter', sans-serif;
    background: #f7f8fa;
    min-height: 100vh;
    overflow-x: hidden;  
}
body {
    width: 100%;
}

/* ===== SIDEBAR ===== */
.sidebar {
  position: fixed;
  left: 20px;
  top: 90px;
  width: 220px;
  height: calc(100vh - 152px);
  background: linear-gradient(200deg, #1c3f9f, #3B82F6);
  padding: 24px 20px;
  color: white;
  border-radius: 20px;
  display: flex;           /* <- tambah */
  flex-direction: column;  /* <- tambah */
}

/* Logo + teks "Desa Banjardowo" di paling atas */
.sidebar-header {
    position: fixed;
    top: 20px;
    left: 20px;
    background: transparent;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sidebar-header img { height: 48px; }
.sidebar-header div {
    color: #000000ff;
    font-weight: 600;
    font-size: 15px;
}

/* === GAYA MENU PILL (SAMA DENGAN PELAYANAN) === */
.menu {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    list-style: none;   /* <--- tambah */
    padding-left: 0;    /* <--- tambah, biar gak maju ke kanan */
}

/* bentuk pill item menu */
.menu-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border-radius:999px;         /* lonjong seperti di gambar */
    font-size:13px;
    opacity:.9;
    color:#e5e7ff;
    text-decoration: none;
}
.menu-item img{
    width:22px;
}

/* hover */
.menu-item:hover{
    background:rgba(255,255,255,.15);
    cursor:pointer;
    text-decoration: none;
}

/* item yang aktif (kalau mau dibold & biru muda) */
.menu-item.active{
    background:#38BDF8;
    opacity:1;
    font-weight:600;
    color:#fff;
    text-decoration: none; 
}


/* ===== MAIN FLEX WRAPPER ===== */
.main {
    margin-left: 230px;
    padding: 40px 20px;
    min-height: 100vh;
    height: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-sizing: border-box;
    justify-content: flex-start;
}

/* ===== PROFILE HEADER ===== */
.profile-header {
    width: calc(100% - 10px);   /* <--- lebar otomatis sisa dari sidebar */
    margin-left: 50px;          /* <--- geser ke kanan pas */
    height: 260px;
    position: relative;
    background-size: cover;
    background-position: center;
    border-radius: 10px 10px 0 0;
    margin-top: 50px;            /* <--- ganti dari margin:auto */
    box-shadow: 0 2px 8px rgba(60,60,60,0.10);
    display: block;
    cursor: pointer;
}


/* ===== PROFILE BOX ===== */
.profile-box {
    display: flex;
    flex-direction: column;
    align-items: flex-start;   /* tadinya center */
    margin-top: -90px;
    margin-left: -850px;        /* sejajar dengan tepi kiri konten (kanan sidebar) */
    margin-bottom: 10px;
    position: relative;
    z-index: 5;                /* biar di depan foto sampul */
}

.profile-photo {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    border: 4px solid #fff;
    object-fit: cover;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.13);
}

.profile-box h2 {
    margin: 6px 0 2px 0;
    font-size: 22px;
    font-weight: 700;
    text-align: center;  /* ← ini yang membuat teks tepat di bawah foto */
    width: 100%;
}



/* ===== EDIT PROFILE BUTTON ===== */
.btn-edit-profile {
    background: #fa9800;
    color: white;
    padding: 10px 32px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    margin: 16px auto 10px auto;
    margin-left: 35px;
    cursor: pointer;
    font-weight: bold;
    display: inline-block;
    box-shadow: 0 3px 15px rgba(230,160,24,0.06);
}
.btn-edit-profile:hover { background: #ffb743; }

/* ===== INFO GRID (FINAL, RAPI, 2 KOLOM) ===== */
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(260px, 1fr));
  column-gap: 120px;
  row-gap: 28px;
  width: 100%;
  max-width: 800px;
  margin: 48px auto 0 auto;
  justify-content: center;
}

.item-1,
.item-2,
.item-3,
.item-4,
.item-5 {
    margin: 0;
}

/* alamat ambil 2 kolom */
.item-5 {
    grid-column: 1 / -1;
    justify-self: center;
}

.info-item {
  display: flex;
  align-items: center;
  font-size: 17px;
  gap: 16px;
}
.info-item span { font-size: 17px; color: #333; }

/* ===== LOGOUT ===== */
.logout {
  margin-top: auto;
  padding: 0px 10px;
}
.logout a {
  display: flex;
  align-items: center;
  color: white !important;
  text-decoration: none !important;
  font-weight: 500;
  font-size: 15px;
  padding: 10px 13px;
  border-radius: 10px;
  gap: 10px;
  width: 100%;
}
.logout a:hover {
  background: linear-gradient(180deg, #1c3f9fff, #3B82F6);
  color: #ffe28f !important;
}
.logout img {
  width: 20px;
  height: 20px;
}

/* ===== MODAL ===== */
.cover-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    justify-content: center;
    align-items: center;
}

.cover-modal-content {
    max-width: 90%;
    max-height: 90%;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

.cover-modal-close {
    position: absolute;
    top: 20px; right: 30px;
    font-size: 32px;
    color: #fff;
    cursor: pointer;
}

/* ===== RESPONSIVE ===== */
@media (max-width:900px){
    .main {
        margin-left: 0;
        padding: 24px 12px;
    }
    .sidebar, .sidebar-header {
        display:none;
    }
}

@media (max-width:600px){
    .info-grid {
        grid-template-columns: 1fr;
        column-gap: 0;
    }
}

.swal2-rounded {
    border-radius: 20px !important;
}

.btn-red {
    background: linear-gradient(180deg, #1c3f9fff, #3B82F6) !important;
    color: white !important;
    padding: 8px 20px !important;
    border-radius: 10px !important;
    margin-right: 10px;
    border: none !important;
}

.btn-gray {
    background-color: #4a5568 !important;
    color: white !important;
    padding: 8px 20px !important;
    border-radius: 10px !important;
    border: none !important;
    outline: none !important;
}

.btn-red:hover, .btn-gray:hover {
    opacity: .9;
}

/* ======= BORDER ANIMASI UNTUK SWEETALERT ======= */
.swal2-popup {
    position: relative !important;
    overflow: visible !important;
    border-radius: 20px !important;
    box-shadow: 0 0 25px rgba(0, 234, 255, 0.6) !important;
    border: 2px solid #00eaff !important;
}

/* Titik kecil keliling border */
.swal-dot {
    position: absolute;
    width: 12px;
    height: 12px;
    background: #00eaff;
    border-radius: 50%;
    box-shadow: 0 0 10px #00eaff;
    animation: walkBorder 4s linear infinite;
    z-index: 9999;
}

@keyframes walkBorder {
    0%   { top: -6px; left: -6px; }                          /* pojok kiri atas */
    25%  { top: -6px; left: calc(100% - 6px); }              /* pojok kanan atas */
    50%  { top: calc(100% - 6px); left: calc(100% - 6px); }  /* pojok kanan bawah */
    75%  { top: calc(100% - 6px); left: -6px; }              /* pojok kiri bawah */
    100% { top: -6px; left: -6px; }                          /* kembali kiri atas */
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
    <a href="dashboard.php" class="menu-item">
      <img src="../assets/icons/dashboard1.png" alt="Dashboard">
      Dashboard
    </a>
  </li>
  <li> 
    <a href="kegiatan.php" class="menu-item">
      <img src="../assets/icons/kegiatandesa.png" alt="Kegiatan">
      Kegiatan Desa
    </a>
  </li>
  <li>
    <a href="prestasi.php" class="menu-item">
      <img src="../assets/icons/prestasi.png" alt="Prestasi">
      Prestasi
    </a>
  </li>
  <li>
    <a href="saran.php" class="menu-item">
      <img src="../assets/icons/kotaksaran1.png" alt="Kotak Saran">
      Kotak Saran
    </a>
  </li>
  <li>
    <a href="pelayanan.php" class="menu-item">
      <img src="../assets/icons/pelayanan1.png" alt="Pelayanan">
      Pelayanan
    </a>
  </li>
</ul>


  <div class="logout">
    <a href="#" class="logout" onclick="confirmLogout()">
      <img src="../assets/icons/logout1.png" alt="Keluar">
      Keluar
    </a>
  </div>
</aside>

<div class="main">
    <!-- Header Cover User (klik untuk lihat full) -->
<div class="profile-header"
     style="<?php echo $coverStyle; ?>"
     onclick="openCoverModal()"></div>

<!-- MODAL COVER FULL -->
<div id="coverModal" class="cover-modal" onclick="closeCoverModal()">
    <span class="cover-modal-close" onclick="closeCoverModal(event)">&times;</span>
    <img src="<?php echo $coverSrc; ?>" class="cover-modal-content" alt="Foto Sampul">
</div>

<!-- MODAL FOTO PROFIL FULL -->
<div id="fotoModal" class="cover-modal" onclick="closeFotoModal()">
    <span class="cover-modal-close" onclick="closeFotoModal(event)">&times;</span>
    <img src="<?php echo $fotoSrc; ?>" class="cover-modal-content" alt="Foto Profil">
</div>


    <div class="profile-box">
        <!-- Foto Profil (LONGBLOB / default file) -->
        <img class="profile-photo" src="<?php echo $fotoSrc; ?>" alt="Foto Profil" onclick="openFotoModal()" style="cursor:pointer;">
        <h2><?php echo htmlspecialchars($nama_lengkap); ?></h2>
        <button class="btn-edit-profile" onclick="window.location.href='edit_profile.php'">Edit Profil</button>
    </div>

    <?php if($msg) echo "<p class='alert'>".htmlspecialchars($msg)."</p>"; ?>

    <div class="info-grid">
        <div class="info-item item-1">
            <img src="../assets/icons/icon_user.png" width="28" />
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
        <div class="info-item item-2" style="margin-left: 150px;">
            <img src="../assets/icons/gmail.png" width="28" />
            <span><?php echo htmlspecialchars($email); ?></span>
        </div>
        <div class="info-item item-3" style="margin-top: 25px;">
        <img src="../assets/icons/jenis_kelamin.png" width="28" />
        <span><?php echo htmlspecialchars($jenis_kelamin); ?></span>
        </div>

        <div class="info-item item-4" style="margin-top: 25px; margin-left: 150px;">
        <img src="../assets/icons/telpon.png" width="28" />
        <span><?php echo htmlspecialchars($no_telp); ?></span>
        </div>

        <div class="info-item item-5" style="margin-top: 25px;">
            <img src="../assets/icons/google-maps.png" width="28" />
            <span><?php echo htmlspecialchars($alamat); ?></span>
        </div>
    </div>
</div>


<script>
function openCoverModal() {
    var modal = document.getElementById('coverModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// parameter event biar kita bisa stopPropagation saat klik tombol X
function closeCoverModal(e) {
    if (e) {
        e.stopPropagation(); // supaya klik tombol X tidak ikut baca klik background
    }
    var modal = document.getElementById('coverModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ==== FOTO PROFIL MODAL ====
function openFotoModal() {
    var modal = document.getElementById('fotoModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeFotoModal(e) {
    if (e) e.stopPropagation();
    var modal = document.getElementById('fotoModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
</script>


</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmLogout() {
    Swal.fire({
        title: "Logout?",
        text: "Anda yakin ingin keluar dari dashboard admin?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, logout",
        cancelButtonText: "Batal",
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-rounded',
            confirmButton: 'btn-red',
            cancelButton: 'btn-gray'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "../logout.php";
        }
    });
}
</script>

<!-- Opsional: Animasi titik di border SweetAlert -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const observer = new MutationObserver(() => {
        const popup = document.querySelector('.swal2-popup');
        if (popup && !document.querySelector('.swal-dot')) {
            const dot = document.createElement('div');
            dot.classList.add('swal-dot');
            popup.appendChild(dot);
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>
</body>
</html>
