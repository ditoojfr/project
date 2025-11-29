<?php
session_start();
include "../config/db.php"; // koneksi MySQL

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// =================== DATA PROFIL PENGGUNA ===================
$uid = (int)$_SESSION['user_id'];
$resUser = mysqli_query($conn, "SELECT nama_lengkap, username, role, foto FROM users WHERE id = $uid");
$user = mysqli_fetch_assoc($resUser);

$namaAdmin = !empty($user['nama_lengkap']) ? $user['nama_lengkap'] : 'Administrator';
$roleAdmin = !empty($user['role']) ? $user['role'] : 'admin';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1));
$fotoProfilSrc = null;
if (!empty($user['foto'])) {
    $fotoProfilSrc = "data:image/jpeg;base64," . base64_encode($user['foto']);
}

// =================== LOGIKA AKSI ===================
$action = $_GET['action'] ?? 'list';
$message = "";

// ---------- HAPUS ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM panduan_surat WHERE id={$id}");
    header("Location: pelayanan.php?msg=Panduan+surat+berhasil+dihapus");
    exit;
}

// ---------- TAMBAH ----------
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul'] ?? ''));
    $deskripsi_singkat = mysqli_real_escape_string($conn, trim($_POST['deskripsi_singkat'] ?? ''));
    $isi_panduan = mysqli_real_escape_string($conn, trim($_POST['isi_panduan'] ?? ''));

    $fotoData = null;
    $fotoType = 'image/jpeg'; // Default

    if (isset($_FILES['foto_pendukung']) && $_FILES['foto_pendukung']['error'] === UPLOAD_ERR_OK) {
        $fotoData = file_get_contents($_FILES['foto_pendukung']['tmp_name']);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fotoType = finfo_file($finfo, $_FILES['foto_pendukung']['tmp_name']);
        finfo_close($finfo);
    }

    if ($judul && $deskripsi_singkat && $isi_panduan) {
        $stmt = mysqli_prepare($conn, "INSERT INTO panduan_surat (judul, deskripsi_singkat, isi_panduan, foto_pendukung, foto_type) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sssss", $judul, $deskripsi_singkat, $isi_panduan, $fotoData, $fotoType);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: pelayanan.php?msg=Panduan+surat+berhasil+ditambahkan");
        exit;
    } else {
        $message = "Semua field (Judul, Deskripsi, Panduan) wajib diisi.";
    }
}

// ---------- UPDATE ----------
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul'] ?? ''));
    $deskripsi_singkat = mysqli_real_escape_string($conn, trim($_POST['deskripsi_singkat'] ?? ''));
    $isi_panduan = mysqli_real_escape_string($conn, trim($_POST['isi_panduan'] ?? ''));

    if ($id && $judul && $deskripsi_singkat && $isi_panduan) {
        $fotoData = null;
        $fotoType = 'image/jpeg';

        if (isset($_FILES['foto_pendukung']) && $_FILES['foto_pendukung']['error'] === UPLOAD_ERR_OK) {
            $fotoData = file_get_contents($_FILES['foto_pendukung']['tmp_name']);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fotoType = finfo_file($finfo, $_FILES['foto_pendukung']['tmp_name']);
            finfo_close($finfo);
        }

        $stmt = mysqli_prepare($conn, "UPDATE panduan_surat SET judul = ?, deskripsi_singkat = ?, isi_panduan = ?, foto_pendukung = ?, foto_type = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssssi", $judul, $deskripsi_singkat, $isi_panduan, $fotoData, $fotoType, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: pelayanan.php?msg=Panduan+surat+berhasil+diupdate");
        exit;
    } else {
        $message = "Semua field wajib diisi.";
    }
}

// =================== AMBIL DATA UNTUK TAMPILAN ===================
$search = trim($_GET['search'] ?? '');
$pelayananList = [];
$detail = null;

if ($action === 'list') {
    if ($search !== '') {
        $like = '%' . mysqli_real_escape_string($conn, $search) . '%';
        $sql = "SELECT * FROM panduan_surat WHERE LOWER(judul) LIKE LOWER('{$like}') ORDER BY id ASC";
    } else {
        $sql = "SELECT * FROM panduan_surat ORDER BY id ASC";
    }
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $pelayananList[] = $row;
    }
}

if (($action === 'edit_form' || $action === 'view') && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM panduan_surat WHERE id={$id}");
    $detail = mysqli_fetch_assoc($res);
    if (!$detail) {
        $message = "Data tidak ditemukan.";
    }
}

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pelayanan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;background:#f4f5fb;color:#333}
        a{text-decoration:none;color:inherit}
        .app{display:flex;min-height:100vh}

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

        .menu{margin-top:16px;display:flex;flex-direction:column;gap:6px}
        .menu-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:999px;font-size:13px;opacity:.9;color:#e5e7ff}
        .menu-item:hover{background:rgba(255,255,255,.15);cursor:pointer}
        .menu-item.active{background:#38BDF8;opacity:1;font-weight:600;color:#fff}
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

       .main{
            margin-top: -3px;
            margin-left: 260px;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        /* BAR ATAS: SEARCH DI TENGAH + PROFIL DI KANAN (HANYA LIST) */
        .top-bar{
            display:flex;
            align-items:center;
            justify-content:flex-end;
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

        .header-row{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            margin-bottom:6px;
        }

        .btn-primary{
            background: linear-gradient(200deg, #1c3f9f, #3B82F6);
            color:#fff;
            border-radius:8px;
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

        .content-card {
            background: #fff;
            border-radius: 18px;
            padding: 24px 28px;
            box-shadow: 0 8px 20px rgba(15,23,42,.06);
            width: 100%;
            max-width: none;
            margin:0;
            flex: 1;
            box-sizing: border-box;
        }
        
        .breadcrumb{font-size:11px;color:#9ca3af;margin-top:2px;margin-bottom:4px}
        h2.page-title{font-size:20px;margin-bottom:4px}

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
        .aksi-col{text-align:center;width:80px}
        .icon-btn{border:none;background:transparent;cursor:pointer;font-size:18px;margin:0 2px}
        .icon-btn.edit{color:#f97316}
        .icon-btn.delete{color:#ef4444}

        .thumb-col{width:90px}
        .thumb-img{
            width:60px;
            height:60px;
            border-radius:16px;
            object-fit:cover;
            background:#e5e7eb;
        }

        .link-judul{
            color:#1d4ed8;
            font-weight:500;
        }
        .link-judul:hover{
            text-decoration:underline;
        }

        .form-wrapper{
            margin-top:20px;
            max-width:900px
        }
        .form-grid{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:24px
        }
        .card-form{
            border-radius:18px;
            border:1px solid #e5e7eb;padding:18px
        }
        .form-group{
            margin-bottom:14px
        }
        .form-group label{
            display:block;
            font-size:13px;
            margin-bottom:4px;
            font-weight:500}
        .form-group input[type=text], .form-group textarea{
            width:100%;
            padding:8px 10px;border-radius:10px;
            border:1px solid #d1d5db;
            font-size:13px;
            outline:none;
            resize:vertical
        }
        .form-group textarea{
            min-height:90px
        }
        .form-group input:focus, .form-group textarea:focus{
            border-color:#5E63BB;
            box-shadow:0 0 0 1px rgba(79,70,229,.1)
        }

        /* UPLOAD BOX STYLE */
        .upload-box {
            width: 100%;
            min-height: 200px;
            border: 2px dashed #d1d5db;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b7280;
            font-size: 14px;
            position: relative;
            overflow: hidden;
            background: #fff;
        }

        .upload-box:hover {
            background: #f9fafb;
        }

        .preview-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 16px;
            z-index: 1;
        }

        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .upload-overlay i {
            font-size: 36px;
            color: #5E63BB;
            margin-bottom: 10px;
        }

        .upload-overlay span {
            font-size: 16px;
            color: #000000ff;
            font-weight: 500;
        }

        .upload-trigger {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            opacity: 0;
            z-index: 3;
        }

        .form-actions{
            margin-top:16px;
            display:flex;gap:10px
        }
        .btn-secondary{
            border-radius:10px;
            padding:9px 18px;
            background:#e5e7eb;
            border:none;
            font-size:13px;
            cursor:pointer
        }
        .alert{
            margin-top:10px;
            padding:8px 12px;
            border-radius:8px;
            font-size:12px
        }
        .alert-info{
            background:#bbf7d0;
            color:#166534;
        }

        .alert-error{background:#fee2e2;
            color:#991b1b
        }
        pre{
            white-space:pre-wrap;
            font-family:inherit;
            font-size:13px
        }

        /* DETAIL PELAYANAN */
        .detail-wrapper{
            margin-top:20px;
            display:flex;
            flex-direction:column;
            align-items:center;
        }
        .detail-inner{
            max-width:700px;
            width:100%;
        }
        .detail-img{
            max-width:100%;
            border-radius:18px;
            display:block;
            margin:0 auto 16px auto;
        }
        .detail-title{
            font-size:18px;
            font-weight:600;
            margin-bottom:8px;
        }
        .detail-label{
            font-size:13px;
            color:#6b7280;
            margin-top:10px;
            margin-bottom:4px;
        }
        .detail-text{
            font-size:13px;
        }

        /* MODAL KONFIRMASI HAPUS */
        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.35);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:999;
        }

        .modal-card{
            background:#ffffff;
            border-radius:18px;
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
            color:#ffffff;
            border:none;
            border-radius:10px;
            padding:10px 22px;
            font-size:14px;
            cursor:pointer;
            font-weight:600;
        }

        .btn-outline{
            background:#4b5563;
            color:#ffffff;
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
.content-card-form {
    margin-top: 63px;  /* ini paling mirip tinggi top-bar sebelumnya */
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
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo-nganjuk.png" alt="Logo Nganjuk">
                <div>Desa Banjardowo</div>
            </div>
            <div class="menu">
                <a href="dashboard.php" class="menu-item"><img src="../assets/icons/dashboard1.png" alt="">Dashboard</a>
                <a href="kegiatan.php" class="menu-item"><img src="../assets/icons/kegiatandesa.png" alt="">Kegiatan Desa</a>
                <a href="prestasi.php" class="menu-item"><img src="../assets/icons/prestasi.png" alt="">Prestasi</a>
                <a href="saran.php" class="menu-item"><img src="../assets/icons/kotaksaran1.png" alt="">Kotak Saran</a>
                <a href="pelayanan.php" class="menu-item active"><img src="../assets/icons/pelayanan1.png" alt="">Pelayanan</a>
            </div>
           <div class="sidebar-footer">
    <a href="#" class="logout" onclick="confirmLogout()">
        <img src="../assets/icons/logout1.png" alt="">
        <span>Keluar</span>
    </a>
            </div>
        </div>

        <div class="main">

            <!-- TOP BAR HANYA UNTUK LIST -->
            <?php if ($action === 'list'): ?>
            <div class="top-bar">
                <form method="get" class="search-input-wrapper">
                    <input type="hidden" name="action" value="list">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search Pelayanan" value="<?php echo htmlspecialchars($search); ?>">
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
                            <?php echo $inisialAdmin; ?>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-card <?php echo ($action !== 'list') ? 'content-card-form' : ''; ?>">

                <!-- TITLE + BREADCRUMB + TOMBOL TAMBAH -->
                <div class="header-row">
                    <div>
                        <h2 class="page-title">
                            <?php
                            if ($action === 'list') echo 'Daftar Pelayanan';
                            elseif ($action === 'add_form') echo 'Tambah Pelayanan';
                            elseif ($action === 'edit_form') echo 'Edit Pelayanan';
                            elseif ($action === 'view') echo 'Detail Pelayanan';
                            ?>
                        </h2>
                        <div class="breadcrumb">
                            Pelayanan /
                            <?php
                            echo ($action === 'list'
                                ? 'Daftar Pelayanan'
                                : ($action === 'add_form'
                                    ? 'Tambah Pelayanan'
                                    : ($action === 'edit_form' ? 'Edit Pelayanan' : 'Detail Pelayanan')));
                            ?>
                        </div>
                    </div>
                    <?php if ($action === 'list') : ?>
                        <button class="btn-primary" onclick="window.location.href='pelayanan.php?action=add_form'">+ Tambah</button>
                    <?php endif; ?>
                </div>

                <?php if ($message) : ?>
                    <div class="alert <?php echo (stripos($message, 'wajib') !== false || stripos($message, 'tidak') !== false) ? 'alert-error' : 'alert-info'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list') : ?>
                    <table>
                        <thead>
                            <tr>
                                <th class="thumb-col"></th>
                                <th style="width:60px;">No</th>
                                <th style="width:18%;">Judul</th>
                                <th style="width:27%;">Deskripsi Singkat</th>
                                <th>Isi Panduan</th>
                                <th class="aksi-col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$pelayananList) : ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:20px;color:#9ca3af;">
                                    Belum ada data pelayanan.
                                </td>
                            </tr>
                        <?php else : foreach ($pelayananList as $i => $row) : ?>
                            <tr>
                                <td>
                                    <a href="pelayanan.php?action=view&id=<?php echo $row['id']; ?>">
                                        <?php if (!empty($row['foto_pendukung'])) : ?>
                                            <img src="data:<?php echo htmlspecialchars($row['foto_type']); ?>;base64,<?php echo base64_encode($row['foto_pendukung']); ?>" class="thumb-img" alt="Gambar">
                                        <?php else : ?>
                                            <div class="thumb-img"></div>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <a href="pelayanan.php?action=view&id=<?php echo $row['id']; ?>" class="link-judul">
                                        <?php echo htmlspecialchars($row['judul']); ?>
                                    </a>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($row['deskripsi_singkat'])); ?></td>
                                <td>
                                <?php 
                                    $maxLength = 250;
                                    $desc = $row['isi_panduan'];

                                    $desc = str_replace('\\r\\n', "\r\n", $desc);
                                    $desc = str_replace('\\n', "\n", $desc);
                                    $desc = str_replace('\\r', "\r", $desc);

                                    if (strlen($desc) > $maxLength) {
                                        echo nl2br(htmlspecialchars(substr($desc, 0, $maxLength), ENT_QUOTES, 'UTF-8')) . "...";
                                    } else {
                                        echo nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'));
                                    }
                                ?>
                                </td>
                                <td class="aksi-col">
                                    <button class="icon-btn edit" title="Edit" onclick="window.location.href='pelayanan.php?action=edit_form&id=<?php echo $row['id']; ?>'">✏️</button>
                                    <button class="icon-btn delete" title="Hapus" onclick="openDeleteModal(<?php echo $row['id']; ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($action === 'add_form' || ($action === 'edit_form' && $detail)) : ?>
                    <div class="form-wrapper">
                        <div class="form-grid">
                            <div class="card-form">
                                <div class="form-group">
                                    <label>No</label>
                                    <input type="text" name="no_pelayanan_form_disabled" disabled value="<?php echo ($action === 'edit_form' && $detail) ? htmlspecialchars($detail['id']) : ''; ?>">
                                    <small style="font-size:11px;color:#9ca3af;">No akan mengikuti ID data atau urutan.</small>
                                </div>
                            </div>
                        </div>
                        <form method="post" action="pelayanan.php?action=<?php echo $action === 'add_form' ? 'add' : 'edit'; ?>" enctype="multipart/form-data">
                            <?php if ($action === 'edit_form') : ?>
                                <input type="hidden" name="id" value="<?php echo $detail['id']; ?>">
                            <?php endif; ?>

                            <div class="form-grid">
                                <div class="card-form">
                                    <div class="form-group">
                                        <label>Judul</label>
                                        <input type="text" name="judul" required value="<?php echo htmlspecialchars($detail['judul'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi Singkat</label>
                                        <input type="text" name="deskripsi_singkat" required value="<?php echo htmlspecialchars($detail['deskripsi_singkat'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Isi Panduan</label>
                                        <textarea name="isi_panduan" required><?php 
                                            $isi_panduan = $detail['isi_panduan'] ?? '';
                                            $isi_panduan = str_replace('\\r\\n', "\r\n", $isi_panduan);
                                            $isi_panduan = str_replace('\\n', "\n", $isi_panduan);
                                            $isi_panduan = str_replace('\\r', "\r", $isi_panduan);
                                            echo $isi_panduan;
                                        ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="card-form">
                                    <div class="form-group">
                                        <label>Upload Image</label>
                                        <div class="upload-box" id="uploadBox">
                                            <img id="previewImg" class="preview-img"
                                                 src="<?php
                                                     if ($action === 'edit_form' && !empty($detail['foto_pendukung'])) {
                                                         echo 'data:' . htmlspecialchars($detail['foto_type']) . ';base64,' . base64_encode($detail['foto_pendukung']);
                                                     }
                                                 ?>"
                                                 style="<?php echo ($action === 'edit_form' && !empty($detail['foto_pendukung'])) ? 'display:block;' : 'display:none;'; ?>"
                                                 alt="Preview">

                                            <div class="upload-overlay">
                                                <?php if ($action === 'edit_form' && !empty($detail['foto_pendukung'])): ?>
                                                    <span>Klik untuk Ganti Gambar</span>
                                                <?php else: ?>
                                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                                    <span>Pilih Image</span>
                                                <?php endif; ?>
                                            </div>

                                            <input type="file" id="uploadFoto" name="foto_pendukung" class="upload-trigger" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-secondary" onclick="window.location.href='pelayanan.php'">← Kembali</button>
                                <button type="submit" class="btn-primary">
                                    <?php echo $action === 'add_form' ? 'Simpan Data' : 'Update Data'; ?>
                                </button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action === 'view' && $detail) : ?>
                    <div class="detail-wrapper">
                        <div class="detail-inner">
                            <?php if (!empty($detail['foto_pendukung'])) : ?>
                                <img src="data:<?php echo htmlspecialchars($detail['foto_type']); ?>;base64,<?php echo base64_encode($detail['foto_pendukung']); ?>" class="detail-img" alt="Gambar Pelayanan">
                            <?php endif; ?>

                            <div class="detail-title"><?php echo htmlspecialchars($detail['judul']); ?></div>

                            <div class="detail-label">Deskripsi Singkat</div>
                            <div class="detail-text">
                                <?php echo nl2br(htmlspecialchars($detail['deskripsi_singkat'])); ?>
                            </div>

                            <div class="detail-label">Isi Panduan</div>
                            <div class="detail-text">
                                <?php 
                                    $desc = $detail['isi_panduan'];
                                    $desc = str_replace('\\r\\n', "\r\n", $desc);
                                    $desc = str_replace('\\n', "\n", $desc);
                                    $desc = str_replace('\\r', "\r", $desc);
                                    echo nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'));
                                ?>
                            </div>

                            <div class="form-actions" style="margin-top:18px;">
                                <button type="button" class="btn-secondary" onclick="window.location.href='pelayanan.php'">← Kembali</button>
                                <button type="button" class="btn-primary" onclick="window.location.href='pelayanan.php?action=edit_form&id=<?php echo $detail['id']; ?>'">Edit</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div id="deleteModal" class="modal-backdrop">
        <div class="modal-card">
            <div class="modal-icon">!</div>
            <div class="modal-title">Hapus Pelayanan?</div>
            <div class="modal-text">
                Data yang sudah dihapus tidak bisa dikembalikan.
            </div>
            <div class="modal-actions">
                <button class="btn-danger" type="button" onclick="confirmDelete()">Ya, hapus</button>
                <button class="btn-outline" type="button" onclick="closeDeleteModal()">Batal</button>
            </div>
        </div>
    </div>


    <script>
    let deleteId = null;

    function openDeleteModal(id){
        deleteId = id;
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'flex';
    }

    function closeDeleteModal(){
        deleteId = null;
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'none';
    }

    function confirmDelete(){
        if(deleteId){
            window.location.href = 'pelayanan.php?action=delete&id=' + deleteId;
        }
    }

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