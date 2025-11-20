<?php
session_start();
include "../config/db.php"; // koneksi MySQL

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// =================== LOGIKA AKSI ===================
$action  = $_GET['action'] ?? 'list';
$message = "";

// ---------- HAPUS ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // hapus file gambar kalau ada
    $res = mysqli_query($conn, "SELECT gambar FROM saran WHERE id = {$id}");
    if ($row = mysqli_fetch_assoc($res)) {
        if (!empty($row['gambar'])) {
            $file = __DIR__ . "/../" . $row['gambar']; // sesuaikan root path
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    mysqli_query($conn, "DELETE FROM saran WHERE id = {$id}");
    header("Location: saran.php?msg=Saran+berhasil+dihapus");
    exit;
}

// =================== AMBIL DATA UNTUK TAMPILAN ===================
$search    = trim($_GET['search'] ?? '');
$saranList = [];

if ($search !== '') {
    $like = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql  = "
        SELECT * FROM saran
        WHERE nama  LIKE '{$like}'
           OR judul LIKE '{$like}'
           OR isi_saran LIKE '{$like}'
        ORDER BY tanggal DESC, id DESC
    ";
} else {
    $sql = "SELECT * FROM saran ORDER BY tanggal DESC, id DESC";
}

$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $saranList[] = $row;
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
    <title>Kotak Saran - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- SweetAlert2 untuk popup hapus yang keren -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;
            background:#f4f5fb;
            color:#333;
        }
        a{text-decoration:none;color:inherit}
        .app{display:flex;min-height:100vh}

        /* ===== SIDEBAR ===== */
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

        /* ===== MAIN AREA ===== */
        .main{
            flex:1;
            padding:18px 32px;
            display:flex;
            flex-direction:column;
            margin-left:260px; /* karena sidebar fixed */
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

        .content-card{
            background:#fff;
            border-radius:18px;
            padding:24px 28px;
            box-shadow:0 8px 20px rgba(15,23,42,.06);
            flex:1;
        }

        .header-row{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            margin-bottom:6px;
        }

        .breadcrumb{
            font-size:11px;
            color:#9ca3af;
            margin-top:2px;
            margin-bottom:4px;
        }
        h2.page-title{
            font-size:20px;
            margin-bottom:4px;
        }

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

        .thumb-col{width:70px}
        .thumb-img{
            width:46px;
            height:46px;
            border-radius:999px;
            object-fit:cover;
            background:#e5e7eb;
        }

        .aksi-col{
            width:70px;
            text-align:center;
        }
        .icon-btn{
            border:none;
            background:transparent;
            cursor:pointer;
            font-size:18px;
            margin:0 2px;
        }
        .icon-btn.delete{color:#ef4444}

        .alert{
            margin-top:10px;
            padding:8px 12px;
            border-radius:8px;
            font-size:12px;
        }
        .alert-info{
            background:#e0f2fe;
            color:#1d4ed8;
        }
        .alert-error{
            background:#fee2e2;
            color:#991b1b;
        }

        .text-judul{font-weight:500}
        .text-nama{font-size:12px;color:#4b5563}
        .text-tanggal{font-size:12px;color:#6b7280}
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
            <a href="prestasi.php" class="menu-item">
                <img src="../assets/icons/prestasi.png" alt="">Prestasi
            </a>
            <!-- Halaman ini -->
            <a href="saran.php" class="menu-item active">
                <img src="../assets/icons/kotaksaran1.png" alt="">Kotak Saran
            </a>
            <a href="pelayanan.php" class="menu-item">
                <img src="../assets/icons/pelayanan1.png" alt="">Pelayanan
            </a>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="logout">
                <img src="../assets/icons/logout1.png" alt="">
                <span>Keluar</span>
            </a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <!-- BAR ATAS -->
        <div class="top-bar">
            <form method="get" class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" placeholder="Search"
                       value="<?php echo htmlspecialchars($search); ?>">
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
            <div class="header-row">
                <div>
                    <h2 class="page-title">Daftar Saran</h2>
                    <div class="breadcrumb">Dashboard / Kotak Saran / Daftar Saran</div>
                </div>
            </div>

            <?php if ($message) : ?>
                <div class="alert alert-info">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                <tr>
                    <th class="thumb-col"></th>
                    <th style="width:18%;">Nama</th>
                    <th style="width:20%;">Judul</th>
                    <th style="width:14%;">Tanggal Dikirim</th>
                    <th>Isi Saran</th>
                    <th class="aksi-col">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$saranList): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:20px;color:#9ca3af;">
                            Belum ada data saran.
                        </td>
                    </tr>
                <?php else: foreach ($saranList as $row): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['gambar'])): ?>
                                <img src="../<?php echo htmlspecialchars($row['gambar']); ?>"
                                     class="thumb-img" alt="Foto">
                            <?php else: ?>
                                <div class="thumb-img"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="text-nama">
                                <?php echo htmlspecialchars($row['nama']); ?>
                            </div>
                        </td>
                        <td class="text-judul">
                            <?php echo htmlspecialchars($row['judul']); ?>
                        </td>
                        <td class="text-tanggal">
                            <?php
                            $tgl = $row['tanggal'] ?? '';
                            if ($tgl) {
                                echo date('d F Y', strtotime($tgl));
                            }
                            ?>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($row['isi_saran'])); ?></td>
                        <td class="aksi-col">
                            <button class="icon-btn delete"
                                    title="Hapus"
                                    onclick="confirmDelete('saran.php?action=delete&id=<?php echo $row['id']; ?>')">
                                🗑
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Popup hapus pakai SweetAlert2
    function confirmDelete(url) {
        Swal.fire({
            title: 'Hapus Saran?',
            text: 'Data yang sudah dihapus tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    // toast kalau ada pesan
    <?php if (!empty($message)): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '<?php echo $message; ?>',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });
    <?php endif; ?>
</script>
</body>
</html>
