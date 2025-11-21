<?php
session_start();
include "../config/db.php"; // koneksi MySQL


if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


// =================== KONFIGURASI UPLOAD ===================
$uploadDir       = __DIR__ . "/../assets/img/panduan_surat/"; // folder fisik BARU
$uploadUrlPrefix = "assets/img/panduan_surat/";               // path yang disimpan di DB BARU

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}
 
// =================== LOGIKA AKSI ===================
$action  = $_GET['action'] ?? 'list';
$message = "";


// ---------- HAPUS ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // hapus file foto_pendukung kalau ada
    $res = mysqli_query($conn, "SELECT foto_pendukung FROM panduan_surat WHERE id={$id}");
    if ($row = mysqli_fetch_assoc($res)) {
        if (!empty($row['foto_pendukung'])) {
            $file = __DIR__ . "/../" . $row['foto_pendukung'];
            if (is_file($file)) @unlink($file);
        }
    }

    mysqli_query($conn, "DELETE FROM panduan_surat WHERE id={$id}");
    header("Location: pelayanan.php?msg=Panduan+surat+berhasil+dihapus");
    exit;
}


// ---------- TAMBAH ----------
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // no_pelayanan DIHAPUS → tidak dipakai lagi
    $judul             = mysqli_real_escape_string($conn, trim($_POST['judul'] ?? ''));
    $deskripsi_singkat = mysqli_real_escape_string($conn, trim($_POST['deskripsi_singkat'] ?? ''));
    $isi_panduan       = mysqli_real_escape_string($conn, trim($_POST['isi_panduan'] ?? ''));

    // proses upload foto_pendukung (opsional)
    $fotoPath = null;
    if (isset($_FILES['foto_pendukung']) && $_FILES['foto_pendukung']['error'] === UPLOAD_ERR_OK) {
        $ext     = pathinfo($_FILES['foto_pendukung']['name'], PATHINFO_EXTENSION);
        $newName = 'panduan_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest    = $uploadDir . $newName;

        if (move_uploaded_file($_FILES['foto_pendukung']['tmp_name'], $dest)) {
            $fotoPath = $uploadUrlPrefix . $newName; // disimpan relatif dari root project
        }
    }

    if ($judul && $deskripsi_singkat && $isi_panduan) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO panduan_surat (judul, deskripsi_singkat, isi_panduan, foto_pendukung) VALUES (?,?,?,?)"
        );
        mysqli_stmt_bind_param($stmt, "ssss", $judul, $deskripsi_singkat, $isi_panduan, $fotoPath);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // setelah tambah, kembali ke daftar
        header("Location: pelayanan.php?msg=Panduan+surat+berhasil+ditambahkan");
        exit;
    } else {
        $message = "Semua field (Judul, Deskripsi, Panduan) wajib diisi.";
    }
}


// ---------- UPDATE ----------
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = (int)($_POST['id'] ?? 0);
    // no_pelayanan DIHAPUS → tidak dipakai lagi
    $judul             = mysqli_real_escape_string($conn, trim($_POST['judul'] ?? ''));
    $deskripsi_singkat = mysqli_real_escape_string($conn, trim($_POST['deskripsi_singkat'] ?? ''));
    $isi_panduan       = mysqli_real_escape_string($conn, trim($_POST['isi_panduan'] ?? ''));

    if ($id && $judul && $deskripsi_singkat && $isi_panduan) {
        // ambil data lama (untuk foto_pendukung)
        $resOld = mysqli_query($conn, "SELECT foto_pendukung FROM panduan_surat WHERE id={$id}");
        $old    = mysqli_fetch_assoc($resOld);
        $fotoPath = $old['foto_pendukung'] ?? null;

        // jika ada upload baru
        if (isset($_FILES['foto_pendukung']) && $_FILES['foto_pendukung']['error'] === UPLOAD_ERR_OK) {
            // hapus file lama
            if (!empty($fotoPath)) {
                $file = __DIR__ . "/../" . $fotoPath;
                if (is_file($file)) @unlink($file);
            }

            $ext     = pathinfo($_FILES['foto_pendukung']['name'], PATHINFO_EXTENSION);
            $newName = 'panduan_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $dest    = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['foto_pendukung']['tmp_name'], $dest)) {
                $fotoPath = $uploadUrlPrefix . $newName;
            }
        }

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE panduan_surat 
             SET judul = ?, deskripsi_singkat = ?, isi_panduan = ?, foto_pendukung = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssssi", $judul, $deskripsi_singkat, $isi_panduan, $fotoPath, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // setelah update, kembali ke daftar
        header("Location: pelayanan.php?msg=Panduan+surat+berhasil+diupdate");
        exit;
    } else {
        $message = "Semua field wajib diisi.";
    }
}


// =================== AMBIL DATA UNTUK TAMPILAN ===================
$search         = trim($_GET['search'] ?? '');
$pelayananList  = [];
$detail         = null;

if ($action === 'list') {
  if ($search !== '') {
    $like = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql  = "
        SELECT * FROM panduan_surat
        WHERE LOWER(judul) LIKE LOWER('{$like}')
        ORDER BY id ASC";
} else {
    $sql = "SELECT * FROM panduan_surat ORDER BY id ASC";
}


    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $pelayananList[] = $row;
    }
}

if (($action === 'edit_form' || $action === 'view') && isset($_GET['id'])) {
    $id    = (int)$_GET['id'];
    $res   = mysqli_query($conn, "SELECT * FROM panduan_surat WHERE id={$id}");
    $detail = mysqli_fetch_assoc($res);

    if (!$detail) {
        $message = "Data tidak ditemukan.";
    }
}

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}


// =================== DATA PROFIL (ATAS KANAN) ===================
$namaAdmin    = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Administrator Utama';
$roleAdmin    = $_SESSION['role'] ?? 'Admin';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pelayanan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;background:#f4f5fb;color:#333}
        a{text-decoration:none;color:inherit}
        .app{display:flex;min-height:100vh}

        /* SIDEBAR (jangan diubah) */
        .sidebar {
          position: fixed;
          left: 20px;
          top: 90px;
          width: 220px;
          height: calc(110vh - 175px);
          background: linear-gradient(180deg, #1c3f9fff, #3B82F6);
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

        .main{flex:1;padding:18px 32px;display:flex;flex-direction:column}

        /* BAR ATAS: SEARCH DI TENGAH + PROFIL DI KANAN */
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

        /* BAR TITLE + TOMBOL TAMBAH */
        .header-row{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            margin-bottom:6px;
        }

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

        .content-card{background:#fff;border-radius:18px;padding:24px 28px;box-shadow:0 8px 20px rgba(15,23,42,.06);flex:1}
        .breadcrumb{font-size:11px;color:#9ca3af;margin-top:2px;margin-bottom:4px}
        h2.page-title{font-size:20px;margin-bottom:4px}

        table{width:100%;border-collapse:collapse;margin-top:20px;font-size:13px}
        th,td{padding:8px 6px;text-align:left;vertical-align:top}
        thead{border-bottom:1px solid #e5e7eb}
        th{color:#6b7280;font-weight:500}
        tbody tr:hover{background:#f9fafb}
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

        .form-wrapper{margin-top:20px;max-width:900px}
        .form-grid{display:grid;grid-template-columns:2fr 1fr;gap:24px}
        .card-form{border-radius:18px;border:1px solid #e5e7eb;padding:18px}
        .form-group{margin-bottom:14px}
        .form-group label{display:block;font-size:13px;margin-bottom:4px;font-weight:500}
        .form-group input[type=text], .form-group textarea{width:100%;padding:8px 10px;border-radius:10px;border:1px solid #d1d5db;font-size:13px;outline:none;resize:vertical}
        .form-group textarea{min-height:90px}
        .form-group input:focus, .form-group textarea:focus{border-color:#5E63BB;box-shadow:0 0 0 1px rgba(79,70,229,.1)}
        .upload-box{border-radius:18px;border:1px dashed #d1d5db;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:13px;color:#6b7280}
        .upload-box input{margin-top:8px}
        .upload-preview{margin-top:10px}
        .upload-preview img{max-width:100%;border-radius:12px}
        .form-actions{margin-top:16px;display:flex;gap:10px}
        .btn-secondary{border-radius:10px;padding:9px 18px;background:#e5e7eb;border:none;font-size:13px;cursor:pointer}
        .alert{margin-top:10px;padding:8px 12px;border-radius:8px;font-size:12px}
        .alert-info{background:#e0f2fe;color:#5E63BB}
        .alert-error{background:#fee2e2;color:#991b1b}
        pre{white-space:pre-wrap;font-family:inherit;font-size:13px}

        /* DETAIL PELAYANAN (gambar ke-4) */
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
            background:rgba(15,23,42,.45);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:999;
        }
        .modal-card{
            background:#ffffff;
            border-radius:18px;
            padding:20px 24px;
            width:320px;
            box-shadow:0 20px 40px rgba(15,23,42,.25);
            animation:modalIn .18s ease-out;
        }
        .modal-title{
            font-size:16px;
            font-weight:600;
            margin-bottom:6px;
        }
        .modal-text{
            font-size:13px;
            color:#4b5563;
            margin-bottom:16px;
        }
        .modal-actions{
            display:flex;
            justify-content:flex-end;
            gap:8px;
        }
        .btn-danger{
            background:#ef4444;
            color:#fff;
            border:none;
            border-radius:999px;
            padding:8px 16px;
            font-size:13px;
            cursor:pointer;
            font-weight:500;
        }
        .btn-outline{
            background:#ffffff;
            color:#4b5563;
            border-radius:999px;
            padding:8px 16px;
            font-size:13px;
            border:1px solid #e5e7eb;
            cursor:pointer;
        }
        @keyframes modalIn{
            from{opacity:0;transform:translateY(8px) scale(.98);}
            to{opacity:1;transform:translateY(0) scale(1);}
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
                <a href="../logout.php" class="logout">
                    <img src="../assets/icons/logout1.png" alt="">
                    <span>Keluar</span>
                </a>
            </div>
        </div>

        <div class="main">
            <!-- BAR ATAS: SEARCH TENGAH + PROFIL KANAN -->
            <div class="top-bar">
                <form method="get" class="search-input-wrapper">
                    <input type="hidden" name="action" value="list">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                </form>

                <div class="profile-wrapper">
                    <div class="profile-text">
                        <div class="name"><?php echo htmlspecialchars($namaAdmin); ?></div>
                        <div class="role"><?php echo htmlspecialchars($roleAdmin); ?></div>
                    </div>
                    <div class="profile-avatar">
                        <?php echo $inisialAdmin; ?>
                    </div>
                </div>
            </div>

            <div class="content-card">
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
                        <img src="../<?php echo htmlspecialchars($row['foto_pendukung']); ?>" class="thumb-img" alt="Gambar">
                    <?php else : ?>
                        <div class="thumb-img"></div>
                    <?php endif; ?>
                </a>
            </td>
            <!-- No pakai urutan looping, bukan id -->
            <td><?php echo $i + 1; ?></td>
            <td>
                <a href="pelayanan.php?action=view&id=<?php echo $row['id']; ?>" class="link-judul">
                    <?php echo htmlspecialchars($row['judul']); ?>
                </a>
            </td>
            <td><?php echo nl2br(htmlspecialchars($row['deskripsi_singkat'])); ?></td>
            <td><?php echo nl2br(htmlspecialchars($row['isi_panduan'])); ?></td>
            <td class="aksi-col">
                <button class="icon-btn edit" title="Edit" onclick="window.location.href='pelayanan.php?action=edit_form&id=<?php echo $row['id']; ?>'">✏</button>
                <!-- PANGGIL MODAL, BUKAN confirm() -->
                <button class="icon-btn delete" title="Hapus" onclick="openDeleteModal(<?php echo $row['id']; ?>)">🗑</button>
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
                                    <!-- hanya tampilan, tidak dari DB lagi -->
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
                                    <!-- field no_pelayanan DIHAPUS dari form input -->
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
                                        <textarea name="isi_panduan" required><?php echo htmlspecialchars($detail['isi_panduan'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="card-form">
                                    <div class="form-group">
                                        <label>Upload Image</label>
                                        <div class="upload-box">
                                            <div>Upload Image</div>
                                            <input type="file" name="foto_pendukung" accept="image/*">
                                            <?php if ($action === 'edit_form' && !empty($detail['foto_pendukung'])) : ?>
                                                <div class="upload-preview">
                                                    <img src="../<?php echo htmlspecialchars($detail['foto_pendukung']); ?>" alt="Preview">
                                                </div>
                                            <?php endif; ?>
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
                                <img src="../<?php echo htmlspecialchars($detail['foto_pendukung']); ?>" class="detail-img" alt="Gambar Pelayanan">
                            <?php endif; ?>

                            <div class="detail-title"><?php echo htmlspecialchars($detail['judul']); ?></div>

                            <div class="detail-label">Deskripsi Singkat</div>
                            <div class="detail-text">
                                <?php echo nl2br(htmlspecialchars($detail['deskripsi_singkat'])); ?>
                            </div>

                            <div class="detail-label">Isi Panduan</div>
                            <div class="detail-text">
                                <?php echo nl2br(htmlspecialchars($detail['isi_panduan'])); ?>
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
            <div class="modal-title">Hapus data pelayanan?</div>
            <div class="modal-text">
                Data yang sudah dihapus tidak dapat dikembalikan.<br>
                Apakah Anda yakin ingin melanjutkan?
            </div>
            <div class="modal-actions">
                <button class="btn-outline" type="button" onclick="closeDeleteModal()">Batal</button>
                <button class="btn-danger" type="button" onclick="confirmDelete()">Ya, hapus</button>
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

        // Tutup modal kalau klik area gelap di luar card
        document.getElementById('deleteModal').addEventListener('click', function(e){
            if(e.target === this){
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
