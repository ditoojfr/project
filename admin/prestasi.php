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
    // redirect dengan pesan sukses hapus
    header("Location: prestasi.php?msg=Prestasi+berhasil+dihapus");
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
if (isset($_GET['detail'])) {
    $mode = "detail";
}

$edit = null;
$detail = null;

if ($mode === 'edit') {
    $id   = intval($_GET['edit']);
    $res  = mysqli_query($conn, "SELECT * FROM prestasi WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
} elseif ($mode === 'detail') {
    $id   = intval($_GET['detail']);
    $res  = mysqli_query($conn, "SELECT * FROM prestasi WHERE id=$id");
    $detail = mysqli_fetch_assoc($res);
    if (!$detail) {
        header("Location: prestasi.php?msg=Prestasi+tidak+ditemukan");
        exit;
    }
}

// Judul halaman
if ($mode == "tambah") {
    $page_title = "Tambah Prestasi";
} elseif ($mode == "edit") {
    $page_title = "Edit Prestasi";
} elseif ($mode == "detail") {
    $page_title = "Detail Prestasi";
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

        header("Location: prestasi.php?msg=Prestasi+berhasil+diupdate");
        exit;

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

        header("Location: prestasi.php?msg=Prestasi+baru+berhasil+ditambahkan");
        exit;
    }
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
        ORDER BY id DESC
    ";
} else {
    $sqlList = "SELECT * FROM prestasi ORDER BY id DESC";
}

$queryList = mysqli_query($conn, $sqlList);

// ================== 5. PESAN NOTIFIKASI ==================
$message = '';
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Prestasi - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;
            background:#f4f5fb;
            color:#333;
        }
        a{text-decoration:none;color:inherit}
        .app{display:flex;min-height:100vh}

        /* ====== SIDEBAR – COPY DARI PELAYANAN.PHP ====== */
        .sidebar {
            position: fixed;
            left: 20px;
            top: 90px;
            width: 260px;
            height: calc(100vh - 104px);
            background: linear-gradient(180deg, #1c3f9fff, #3B82F6);
            padding: 24px 20px;
            color: white;
            border-radius: 20px;
        }

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

        .sidebar-header div {
            color: #000000ff;
            font-weight: 600;
            font-size: 15px;
        }

        .sidebar-header img {
            height: 48px;
            width: auto;
            display: block;
            object-fit: contain;
        }

        .menu{
            margin-top:16px;
            display:flex;
            flex-direction:column;
            gap:6px;
        }
        .menu-item{
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px 12px;
            border-radius:999px;
            font-size:13px;
            opacity:.9;
            color:#e5e7ff;
        }
        .menu-item:hover{
            background:rgba(255,255,255,.15);
            cursor:pointer;
        }
        .menu-item.active{
            background:#38BDF8;
            opacity:1;
            font-weight:600;
            color:#fff;
        }
        .menu-item img{width:22px}

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        .sidebar-footer .logout {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 18px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .sidebar-footer .logout img {
            width: 20px;
            height: 20px;
        }

        /* ====== MAIN – COPY DARI PELAYANAN.PHP ====== */
        .main{
            margin-top: -3px;
            margin-left: 260px;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

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
            width:38px;
            height:38px;
            border-radius:999px;
            background:#f97316;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:600;
            font-size:16px;
            color:#fff;
            overflow:hidden;
        }
        .profile-avatar img{
            width:100%;
            height:100%;
            object-fit:cover;
            border-radius:999px;
        }

        .content-card {
            background: #fff;
            border-radius: 18px;
            padding: 24px 28px;
            box-shadow: 0 8px 20px rgba(15,23,42,.06);
            width: 100%;
            max-width: none;
            margin: 0;
            box-sizing: border-box;
            flex: 1;
        }

        .header-row{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            margin-bottom:6px;
        }

        .breadcrumb{font-size:11px;color:#9ca3af;margin-top:2px;margin-bottom:4px}
        h2.page-title{font-size:20px;margin-bottom:4px}

        .btn-primary{
            background: linear-gradient(200deg, #1c3f9f, #3B82F6);
            color:#fff;
            border-radius:10px;
            padding:10px 20px;
            font-size:13px;
            border:none;
            cursor:pointer;
            font-weight:500;
        }
        .btn-primary:hover {
    filter: brightness(1.05);
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.35);
}

               .alert{
            margin-top:10px;
            margin-bottom:10px;
            padding:8px 12px;
            border-radius:8px;
            font-size:12px;
        }
        /* sama seperti pelayanan & saran */
        .alert-success{
            background:#bbf7d0;      /* hijau muda */
            color:#166534;           /* hijau tua */
            border:1px solid #86efac;
        }


        /* ====== TABLE & FORM KHUSUS PRESTASI ====== */
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
        thead{
            border-bottom:1px solid #e5e7eb;
        }
        th{
            color:#6b7280;
            font-weight:500;
        }
        tbody tr:hover{
            background:#f9fafb;
        }

        .foto-prestasi{
            width:60px;
            height:60px;
            border-radius:16px;
            object-fit:cover;
            background:#e5e7eb;
        }

        /* Judul prestasi menjadi link */
        .prestasi-judul-link {
            color: #3B82F6;
            text-decoration: underline;
            cursor: pointer;
        }
        .prestasi-judul-link:hover {
            color: #1c3f9f;
        }

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

        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.35);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:9999;
        }

        .modal-card{
            background:#ffffff;
            border-radius:22px;
            padding:32px 40px;
            width:420px;
            max-width:90%;
            box-shadow:0 20px 40px rgba(15,23,42,0.25);
            text-align:center;
            animation:modalIn .18s ease-out;
        }

        .modal-icon{
            width:72px;
            height:72px;
            border-radius:999px;
            border:3px solid #fdba74;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 18px auto;
            color:#f97316;
            font-size:36px;
            font-weight:600;
        }

        .modal-title{
            font-size:22px;
            font-weight:600;
            margin-bottom:8px;
            color:#374151;
        }

        .modal-text{
            font-size:14px;
            color:#4b5563;
            margin-bottom:22px;
            line-height:1.5;
        }

        .modal-actions{
            display:flex;
            justify-content:center;
            gap:12px;
        }

        .btn-danger{
            background:#e11d48;
            color:#fff;
            border:none;
            border-radius:10px;
            padding:10px 22px;
            font-size:14px;
            cursor:pointer;
            font-weight:600;
        }

        .btn-outline{
            background:#4b5563;
            color:#fff;
            border:none;
            border-radius:10px;
            padding:10px 22px;
            font-size:14px;
            cursor:pointer;
            font-weight:600;
        }

        @keyframes modalIn{
            from{opacity:0;transform:translateY(10px) scale(.97);}
            to{opacity:1;transform:translateY(0) scale(1);}
        }

        /* ====== TAMPILAN DETAIL PRESTASI ====== */
        .detail-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .detail-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-foto {
            width: 200px;
            height: 200px;
            border-radius: 16px;
            object-fit: cover;
            background: #e5e7eb;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .detail-info {
            flex: 1;
        }

        .detail-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1c3f9f;
        }

        .detail-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            font-size: 14px;
            color: #6b7280;
        }

        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-deskripsi {
            font-size: 15px;
            line-height: 1.6;
            color: #374151;
            margin-top: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            border-left: 4px solid #3B82F6;
        }

        .detail-breadcrumb {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .detail-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-edit {
            background: #f59e0b;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            color: white;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-edit:hover {
            background: #e68a00;
        }

        .btn-back {
            background: linear-gradient(180deg, #1c3f9fff, #3B82F6);
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            color: white;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
        }

           .btn-back:hover {
    filter: brightness(1.05);
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.35);
}
        /* Batasi lebar kolom Deskripsi */
.table-prestasi td:nth-child(6), /* Kolom Deskripsi adalah kolom ke-6 */
.table-prestasi th:nth-child(6) {
    max-width: 200px; /* Atur lebar maksimum, bisa disesuaikan */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal; /* Biarkan teks bisa wrap jika perlu */
    word-wrap: break-word;
    padding: 8px 6px;
}

/* Pastikan kolom Tanggal dan Aksi tetap rapi */
.table-prestasi td:nth-child(7), /* Tanggal Perolehan */
.table-prestasi th:nth-child(7),
.table-prestasi td:nth-child(8), /* Aksi */
.table-prestasi th:nth-child(8) {
    white-space: nowrap;
    text-align: center;
}
/* card putih untuk tambah / edit / detail (tanpa top-bar) */
.content-card-form{
    margin-top: 63px;  /* boleh disesuaikan 50–80px sesuai selera */
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
    <div class="app">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo-nganjuk.png" alt="Logo Nganjuk">
                <div>Desa Banjardowo</div>
            </div>

            <div class="menu">
                <a href="dashboard.php" class="menu-item">
                    <img src="../assets/icons/dashboard1.png" alt="">Dashboard
                </a>
                <a href="kegiatan.php" class="menu-item">
                    <img src="../assets/icons/kegiatandesa.png" alt="">Kegiatan Desa
                </a>
                <a href="prestasi.php" class="menu-item active">
                    <img src="../assets/icons/prestasi.png" alt="">Prestasi
                </a>
                <a href="saran.php" class="menu-item">
                    <img src="../assets/icons/kotaksaran1.png" alt="">Kotak Saran
                </a>
                <a href="pelayanan.php" class="menu-item">
                    <img src="../assets/icons/pelayanan1.png" alt="">Pelayanan
                </a>
            </div>

            <div class="sidebar-footer">
    <a href="#" class="logout" onclick="confirmLogout()">
        <img src="../assets/icons/logout1.png" alt="">
        <span>Keluar</span>
    </a>
            </div>
        </div>

                <!-- MAIN -->
        <div class="main">

            <?php if ($mode == 'list'): ?>
            <!-- TOP BAR – hanya muncul di Daftar Prestasi -->
            <div class="top-bar">
                <form method="get" class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search Prestasi"
                           value="<?php echo htmlspecialchars($search); ?>">
                </form>

                <div class="profile-wrapper">
                    <div class="profile-text">
                        <div class="name"><?php echo htmlspecialchars($namaAdmin); ?></div>
                        <div class="role"><?php echo htmlspecialchars($roleAdmin); ?></div>
                    </div>

                    <a href="profile.php" class="profile-avatar">
                        <?php if (!empty($fotoProfilSrc)) : ?>
                            <img src="<?php echo $fotoProfilSrc; ?>" alt="Foto Profil">
                        <?php else : ?>
                            <?php echo htmlspecialchars($inisialAdmin); ?>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

           
            <!-- KONTEN UTAMA -->
<div class="content-card <?php echo ($mode != 'list') ? 'content-card-form' : ''; ?>">

                <div class="header-row">
                    <div>
                        <h2 class="page-title"><?php echo htmlspecialchars($page_title); ?></h2>
                        <div class="breadcrumb">
                            Prestasi /
                            <?php if ($mode == "list"): ?>
                                Daftar Prestasi
                            <?php elseif ($mode == "tambah"): ?>
                                Tambah Prestasi
                            <?php elseif ($mode == "edit"): ?>
                                Edit Prestasi
                            <?php else: ?>
                                Detail Prestasi
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($mode == "list"): ?>
                        <button class="btn-primary" onclick="window.location.href='prestasi.php?tambah=1'">+ Tambah</button>
                    <?php endif; ?>
                </div>

                <!-- NOTIFIKASI -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- FORM TAMBAH / EDIT -->
                <?php if ($mode != "list" && $mode != "detail"): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-container">
                            <div class="form-left">
                                <input type="hidden" name="id" value="<?php echo $edit['id'] ?? ''; ?>">

                                <label>Nama Prestasi</label>
                                <input type="text" name="judul" required
                                       value="<?php echo htmlspecialchars($edit['judul'] ?? ''); ?>">

                                <label>Bidang</label>
                                <input type="text" name="bidang" required
                                       value="<?php echo htmlspecialchars($edit['bidang'] ?? ''); ?>">

                                <label>Penyelenggara</label>
                                <input type="text" name="penyelenggara"
                                       value="<?php echo htmlspecialchars($edit['penyelenggara'] ?? ''); ?>">

                                <label>Deskripsi</label>
                                <textarea name="deskripsi" rows="5"><?php echo htmlspecialchars($edit['deskripsi'] ?? ''); ?></textarea>

                                <label>Tanggal</label>
                                <input type="date" name="tanggal" required
                                       value="<?php echo $edit['tanggal'] ?? ''; ?>">
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
                                        ?>"
                                        style="<?php echo ($mode == 'edit' && !empty($edit['foto'])) ? 'display:block;' : 'display:none;'; ?>"
                                        alt="Preview"
                                    />
                                    <div class="upload-overlay">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        <span>
                                            <?php echo ($mode == "edit" && !empty($edit['foto']))
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
                            <?php echo ($mode == "edit" ? "Update Data" : "Simpan Data"); ?>
                        </button>
                        <a href="prestasi.php" class="back-btn">&larr; Kembali</a>
                    </form>
                <?php endif; ?>

                <!-- TAMPILAN DETAIL PRESTASI -->
                <?php if ($mode == "detail"): ?>
                    <div class="detail-container">
                        <div class="detail-header">
                            <img src="<?php
                                if (!empty($detail['foto'])) {
                                    echo 'data:' . $detail['foto_type'] . ';base64,' . base64_encode($detail['foto']);
                                } else {
                                    echo '../assets/icons/noimage.png';
                                }
                            ?>" alt="Foto Prestasi" class="detail-foto">
                            <div class="detail-info">
                                <h1 class="detail-title"><?php echo htmlspecialchars($detail['judul']); ?></h1>
                                <div class="detail-meta">
                                    <div class="detail-meta-item">
                                        <i class="fa-solid fa-tag"></i>
                                        <span><?php echo htmlspecialchars($detail['bidang'] ?? '-'); ?></span>
                                    </div>
                                    <div class="detail-meta-item">
                                        <i class="fa-solid fa-calendar"></i>
                                        <span><?php echo date("d F Y", strtotime($detail['tanggal'])); ?></span>
                                    </div>
                                    <div class="detail-meta-item">
                                        <i class="fa-solid fa-building"></i>
                                        <span><?php echo htmlspecialchars($detail['penyelenggara'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-deskripsi">
                            <?php echo nl2br(htmlspecialchars($detail['deskripsi'])); ?>
                        </div>

                        <div class="detail-actions">
                            <button class="btn-edit" onclick="window.location.href='prestasi.php?edit=<?php echo $detail['id']; ?>'">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button class="btn-back" onclick="window.location.href='prestasi.php'">
                                <i class="fa-solid fa-arrow-left"></i> Kembali
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- LIST DATA -->
                <?php if ($mode == "list"): ?>
                    <table class="table-prestasi">
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
                                                 src="data:<?php echo $row['foto_type']; ?>;base64,<?php echo base64_encode($row['foto']); ?>">
                                        <?php else: ?>
                                            <img class="foto-prestasi"
                                                 src="../assets/icons/noimage.png" alt="No Image">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $no++; ?></td>
                                    <!-- Judul prestasi menjadi link ke detail -->
                                    <td>
                                        <a href="prestasi.php?detail=<?php echo $row['id']; ?>" class="prestasi-judul-link">
                                            <?php echo htmlspecialchars($row['judul']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['bidang'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['penyelenggara'] ?? '-'); ?></td>
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
                                    <td><?php echo date("d F Y", strtotime($row['tanggal'])); ?></td>
                                    <td class="aksi-btn">
                                        <!-- HANYA EDIT DAN HAPUS -->
                                        <a href="prestasi.php?edit=<?php echo $row['id']; ?>">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="openDeleteModal(<?php echo $row['id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div> <!-- .content-card -->
        </div> <!-- .main -->
    </div> <!-- .app -->

    <!-- MODAL HAPUS -->
    <div id="deleteModal" class="modal-backdrop">
        <div class="modal-card">
            <div class="modal-icon">!</div>
            <div class="modal-title">Hapus Prestasi?</div>
            <div class="modal-text">
                Data yang sudah dihapus tidak bisa dikembalikan.
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-danger" onclick="confirmDelete()">Ya, hapus</button>
                <button type="button" class="btn-outline" onclick="closeDeleteModal()">Batal</button>
            </div>
        </div>
    </div>

    <script>
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

        let deleteId = null;
        function openDeleteModal(id){
            deleteId = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        function closeDeleteModal(){
            deleteId = null;
            document.getElementById('deleteModal').style.display = 'none';
        }
        function confirmDelete(){
            if (deleteId) {
                window.location.href = 'prestasi.php?del=' + deleteId;
            }
        }
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