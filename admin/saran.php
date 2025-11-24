<?php
session_start();
include "../config/db.php"; // koneksi MySQL

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
// =================== DATA PROFIL PENGGUNA ===================
$uid = (int)$_SESSION['user_id'];

$resUser = mysqli_query($conn, "
    SELECT nama_lengkap, username, role, foto 
    FROM users 
    WHERE id = $uid
");
$user = mysqli_fetch_assoc($resUser);

$namaAdmin = !empty($user['nama_lengkap'])
    ? $user['nama_lengkap']
    : ($_SESSION['username'] ?? 'Administrator');

$roleAdmin = !empty($user['role']) ? $user['role'] : 'admin';

$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1));

$fotoProfilSrc = null;
if (!empty($user['foto'])) {
    $fotoProfilSrc = "data:image/jpeg;base64," . base64_encode($user['foto']);
}



/*
   =====================================
   MODE HALAMAN
   =====================================
   action = list  -> daftar saran
   action = view  -> detail satu saran
   action = delete-> hapus saran
*/
$action  = $_GET['action'] ?? 'list';
$message = "";

/*
   =====================================
   HAPUS SARAN
   =====================================
*/
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // kalau nanti ingin hapus file fisik foto, bisa tambahkan SELECT foto_sampul di sini
    mysqli_query($conn, "DELETE FROM saran WHERE id = {$id}");
    header("Location: saran.php?msg=Saran+berhasil+dihapus");
    exit;
}

/*
   =====================================
   DATA UNTUK LIST & DETAIL
   =====================================
*/
$search    = trim($_GET['search'] ?? '');
$saranList = [];
$detail    = null;

/* ----- LIST SARAN ----- */
if ($action === 'list') {
    if ($search !== '') {
        $like = '%' . mysqli_real_escape_string($conn, $search) . '%';
        $sql  = "
            SELECT * FROM saran
            WHERE judul     LIKE '{$like}'
               OR isi_saran LIKE '{$like}'
            ORDER BY tanggal_dikirim DESC, id DESC
        ";
    } else {
        $sql = "SELECT * FROM saran ORDER BY tanggal_dikirim DESC, id DESC";
    }

    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $saranList[] = $row;
    }
}

/* ----- DETAIL SARAN ----- */
if ($action === 'view' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM saran WHERE id = {$id} LIMIT 1");
    $detail = mysqli_fetch_assoc($res);
    if (!$detail) {
        $message = "Data saran tidak ditemukan.";
        $action  = 'list'; // fallback ke list kalau id tidak ada
    }
}

/* ----- PESAN (NOTIF) ----- */
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kotak Saran - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- SweetAlert2 untuk popup hapus -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* --- Reset kecil & font --- */
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;
            background:#f4f5fb;
            color:#333;
        }
        a{text-decoration:none;color:inherit}
        .app{display:flex;min-height:100vh}

       .sidebar {
    position: fixed;
    left: 20px;
    top: 90px;
    width: 260px;
    height: calc(100vh - 104px);

    /* 🎨 GRADIENT BARU */
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

        /* ===== MAIN ===== */
        .main {
            margin-top: -3px;
            margin-left: 260px;
            padding: 30px 40px;
            display: flex;
            flex-direction: column; 
            flex: 1;
            min-width: 0;           
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
.profile-text .name{
    font-weight:600;
}
.profile-text .role{
    font-size:11px;
    color:#9ca3af;
}

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
    text-decoration:none;
    cursor:pointer;
}

.profile-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:999px;
    display:block;
}

        .content-card {
            background: #fff;
            border-radius: 18px;
            padding: 24px 28px;
            box-shadow: 0 8px 20px rgba(15,23,42,.06);
            width: 100%;            /* lebar maksimum area parent (main) */
            max-width: none;        /* nonaktifkan batas lebar, boleh dihapus atau set none */
            margin: 0;              /* hilangkan margin auto supaya tidak di tengah dan kecil */
            flex: 1;                /* jika parent flex, card akan meluas otomatis */
            box-sizing: border-box; /* padding tetap dihitung agar desain tetap rapi */
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

        .text-judul{font-weight:500}
        .text-tanggal{font-size:12px;color:#6b7280}

        /* foto kotak membulat di tabel */
        .foto-bulat{
            width:60px;
            height:60px;
            border-radius:16px;
            object-fit:cover;
            background:#e5e7eb;
            display:block;
        }

        /* ===== DETAIL SARAN ===== */
        .detail-container{
            background:#fff;
            border-radius:18px;
            padding:30px 40px;
            box-shadow:0 8px 20px rgba(15,23,42,.06);
            display:flex;
            flex-direction:column;
            align-items:center;
        }
        .detail-inner{
            max-width:700px;
            width:100%;
        }
        .detail-back{
            font-size:24px;
            cursor:pointer;
            margin-bottom:10px;
        }
        .detail-image{
            width:100%;
            max-height:420px;
            object-fit:cover;
            border-radius:18px;
            margin-bottom:18px;
            background:#e5e7eb;
        }
        .detail-date{
            font-size:11px;
            color:#6b7280;
            margin-bottom:4px;
        }
        .detail-title{
            font-size:16px;
            font-weight:600;
            margin-bottom:12px;
        }
        .detail-text{
            font-size:13px;
            line-height:1.6;
        }

        /* ===== SIMPLE SWEETALERT FIXES (no animations) =====
           keep minimal styling so SweetAlert works normally and
           doesn't lock the page when cancelled */
        .swal2-container {
            z-index: 100000 !important; /* normal high z-index but not insane */
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
                <input type="hidden" name="action" value="list">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" placeholder="Search"
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>

        <div class="profile-wrapper">
    <div class="profile-text">
        <div class="name"><?= htmlspecialchars($namaAdmin); ?></div>
        <div class="role"><?= htmlspecialchars($roleAdmin); ?></div>
    </div>

    <a href="profile.php" class="profile-avatar">
        <?php if (!empty($fotoProfilSrc)): ?>
            <img src="<?= $fotoProfilSrc; ?>" alt="Foto Profil">
        <?php else: ?>
            <?= htmlspecialchars($inisialAdmin); ?>
        <?php endif; ?>
    </a>
</div>


        </div>

        <?php if ($action === 'list'): ?>
            <!-- ================= LIST SARAN ================= -->
            <div class="content-card">
                <div class="header-row">
                    <div>
                        <h2 class="page-title">Daftar Saran</h2>
                        <div class="breadcrumb">Dashboard / Kotak Saran / Daftar Saran</div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-info">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <table>
                    <thead>
                    <tr>
                        <th style="width:10%;"></th>
                        <th style="width:6%;">No</th>
                        <th style="width:22%;">Judul</th>
                        <th style="width:18%;">Tanggal Dikirim</th>
                        <th>Saran atau Kritik</th>
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
                    <?php else:
                        $no = 1;
                        foreach ($saranList as $row): ?>
                            <tr>
                                <!-- FOTO -->
                                <td>
                                    <?php if (!empty($row['foto_sampul'])): ?>
                                        <img src="../<?php echo htmlspecialchars($row['foto_sampul']); ?>"
                                             alt="foto"
                                             class="foto-bulat">
                                    <?php else: ?>
                                        <span class="foto-bulat"></span>
                                    <?php endif; ?>
                                </td>

                                <!-- NO -->
                                <td><?php echo $no++; ?></td>

                                <!-- JUDUL (bisa di klik ke detail) -->
                                <td class="text-judul">
                                    <a href="saran.php?action=view&id=<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['judul']); ?>
                                    </a>
                                </td>

                                <!-- TANGGAL -->
                                <td class="text-tanggal">
                                    <?php
                                    $tgl = $row['tanggal_dikirim'] ?? '';
                                    if ($tgl) {
                                        echo date('d F Y', strtotime($tgl));
                                    }
                                    ?>
                                </td>

                                <!-- ISI SARAN (singkat) -->
                                <td>
                                    <?php echo nl2br(htmlspecialchars($row['isi_saran'])); ?>
                                </td>

                                <!-- AKSI HAPUS -->
                                <td class="aksi-col">
                                    <button class="icon-btn delete"
                                            title="Hapus"
                                            onclick="confirmDelete('saran.php?action=delete&id=<?php echo $row['id']; ?>')">
                                        🗑
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'view' && $detail): ?>
            <!-- ================= DETAIL SARAN ================= -->
            <div class="detail-container">
                <div class="detail-inner">
                    <!-- tombol kembali seperti ikon panah di desain -->
                    <div class="detail-back" onclick="window.location.href='saran.php'">⟵</div>

                    <?php if (!empty($detail['foto_sampul'])): ?>
                        <img src="../<?php echo htmlspecialchars($detail['foto_sampul']); ?>"
                             alt="Foto Saran"
                             class="detail-image">
                    <?php else: ?>
                        <div class="detail-image"></div>
                    <?php endif; ?>

                    <div class="detail-date">
                        <?php
                        $tgl = $detail['tanggal_dikirim'] ?? '';
                        if ($tgl) {
                            echo date('d F Y', strtotime($tgl));
                        }
                        ?>
                    </div>

                    <div class="detail-title">
                        <?php echo htmlspecialchars($detail['judul']); ?>
                    </div>

                    <div class="detail-text">
                        <?php echo nl2br(htmlspecialchars($detail['isi_saran'])); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Popup hapus pakai SweetAlert2 (versi normal, tanpa animasi api)
    function confirmDelete(url) {
        Swal.fire({
            title: 'Hapus Saran?',
            text: 'Data yang sudah dihapus tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            allowOutsideClick: true,
            allowEscapeKey: true,
            backdrop: true
        }).then((result) => {
            // Jika dikonfirmasi -> arahkan ke URL hapus
            if (result.isConfirmed) {
                window.location.href = url;
            }
            // Jika batal atau klik luar/esc -> SweetAlert otomatis menutup
            // (tidak perlu panggil Swal.close(); karena SweetAlert menutup sendiri)
        });
    }

    // Toast sukses (menggunakan SweetAlert standar)
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
