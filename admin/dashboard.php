<?php
session_start();
include "../config/db.php";

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// =================== DATA PROFIL UNTUK TOP-BAR (profil.php style) ===================
$user_id = (int)$_SESSION['user_id'];
$resUser = mysqli_query($conn, "
    SELECT nama_lengkap, role, foto
    FROM users
    WHERE id = $user_id
");
$user = mysqli_fetch_assoc($resUser);

// Nama admin (dari nama_lengkap, fallback ke "Administrator")
$namaAdmin = !empty($user['nama_lengkap'])
    ? $user['nama_lengkap']
    : 'Administrator';

// Role
$roleAdmin = !empty($user['role']) ? $user['role'] : 'admin';

// Inisial jika tidak ada foto
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1));

// Foto profil
$fotoProfilSrc = null;
if (!empty($user['foto'])) {
    $fotoProfilSrc = "data:image/jpeg;base64," . base64_encode($user['foto']);
}

// ================================================================================

// Kode statistik, grafik, dan saran tetap pakai kode asli kamu di bawah ini, JANGAN DIUBAH!
$total_prestasi = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `prestasi`"))['total'];
$total_kegiatan = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `kegiatan`"))['total'];
$total_saran    = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `saran`"))['total'];
$total_panduan_surat = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `users` WHERE role = 'warga'"))['total'];
$total_panduan_surat    = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `panduan_surat`"))['total'];

$labels = [];
$data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M Y', strtotime($bulan));
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM `saran` WHERE DATE_FORMAT(tanggal_dikirim, '%Y-%m') = ?");
    mysqli_stmt_bind_param($stmt, "s", $bulan);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_array(mysqli_stmt_get_result($stmt))[0];
    $data[] = (int) $count;
}

$saran_list = [];
$result = mysqli_query($conn, "SELECT judul, email, isi_saran, tanggal_dikirim FROM `saran` ORDER BY tanggal_dikirim DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($result)) {
    $saran_list[] = $row;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Desa Banjardowo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f4f5fb;
            color: #333;
        }
        a { text-decoration: none; color: inherit; }

        .app { display: flex; min-height: 100vh; }

        /* ===== SIDEBAR (sama dengan saran.php) ===== */
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

        .menu {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 999px;
            font-size: 13px;
            opacity: .9;
            color: #e5e7ff;
        }
        .menu-item:hover {
            background: rgba(255,255,255,.15);
            cursor: pointer;
        }
        .menu-item.active {
            background: #38BDF8;
            opacity: 1;
            font-weight: 600;
            color: #fff;
        }
        .menu-item img { width: 22px; }

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
        .sidebar-footer .logout img { width: 20px; height: 20px; }

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

        /* ===== TOP BAR (tanpa search) ===== */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end; /* hanya profil di kanan */
            margin-bottom: 24px;
        }

        .profile-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-text {
            text-align: right;
            font-size: 12px;
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

        /* ===== KONTEN ===== */
        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        /* Statistik Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 6px 16px rgba(15,23,42,0.06);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon-box {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4c52ceff;
            font-size: 24px;
        }

        .stat-text {}
        .stat-label { font-size: 13px; color: #6b7280; }
        .stat-value { font-size: 26px; font-weight: 700; margin-top: 4px; color: #1c3f9f; }

        /* Chart */
        .chart-box {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 20px rgba(15,23,42,0.06);
            height: 300px;
            margin-bottom: 30px;
        }

        .chart-box h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }

        /* Saran */
        .saran-box {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 20px rgba(15,23,42,0.06);
        }

        .saran-box h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }

        .saran-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }
        .saran-item:last-child { border-bottom: none; }

        .saran-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: #6b7280;
        }

        .saran-content h5 {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 600;
            color: #1c3f9f;
        }

        .saran-date {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 6px;
        }

        .saran-desc {
            font-size: 13px;
            line-height: 1.5;
            color: #555;
        }

        /* Canvas Chart */
        #saranChart {
            width: 100% !important;
            height: 220px !important;
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
            <a href="dashboard.php" class="menu-item active">
                <img src="../assets/icons/dashboard1.png" alt="">Dashboard
            </a>
            <a href="kegiatan.php" class="menu-item">
                <img src="../assets/icons/kegiatandesa.png" alt="">Kegiatan Desa
            </a>
            <a href="prestasi.php" class="menu-item">
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
            <a href="../logout.php" class="logout">
                <img src="../assets/icons/logout1.png" alt="">
                <span>Keluar</span>
            </a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <!-- TOP BAR (tanpa search) -->
        <div class="top-bar">
            <div class="profile-wrapper">
    <div class="profile-text">
        <div class="name"><?= htmlspecialchars($namaAdmin); ?></div>
        <div class="role"><?= htmlspecialchars($roleAdmin); ?></div>
    </div>
    <!-- avatar bulat klik ke profile.php -->
    <a href="profile.php" class="profile-avatar">
        <?php if (!empty($fotoProfilSrc)) : ?>
            <img src="<?= $fotoProfilSrc; ?>" alt="Foto Profil">
        <?php else : ?>
            <?= $inisialAdmin; ?>
        <?php endif; ?>
    </a>
</div>

        </div>

        <h1 class="page-title">Dashboard</h1>

        <!-- STATISTIK -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon-box">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div class="stat-text">
                    <div class="stat-label">Jumlah Prestasi</div>
                    <div class="stat-value"><?= $total_prestasi ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div class="stat-text">
                    <div class="stat-label">Kegiatan Desa</div>
                    <div class="stat-value"><?= $total_kegiatan ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="stat-text">
                    <div class="stat-label">Kotak Saran Masuk</div>
                    <div class="stat-value"><?= $total_saran ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-text">
                    <div class="stat-label">Pelayanan Aktif</div>
                    <div class="stat-value"><?= $total_panduan_surat ?></div>
                </div>
            </div>
        </div>

        <!-- GRAFIK -->
        <div class="chart-box">
            <h4>Grafik Kotak Saran Masuk (6 Bulan Terakhir)</h4>
            <canvas id="saranChart"></canvas>
        </div>

        <!-- SARAN TERBARU -->
        <div class="saran-box">
            <h4>Daftar Saran Terbaru</h4>
            <?php if (empty($saran_list)): ?>
                <p style="text-align:center; color:#9ca3af; margin-top:20px;">Belum ada saran masuk.</p>
            <?php else: ?>
                <?php foreach ($saran_list as $saran): ?>
                    <div class="saran-item">
                        <div class="saran-avatar">
                            <?= strtoupper(substr($saran['email'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="saran-content">
                            <h5><?= htmlspecialchars($saran['judul']) ?></h5>
                            <div class="saran-date">
                                dari <?= htmlspecialchars($saran['email']) ?> • 
                                <?= date('d F Y', strtotime($saran['tanggal_dikirim'])) ?>
                            </div>
                            <div class="saran-desc">
                                <?= nl2br(htmlspecialchars(substr($saran['isi_saran'], 0, 100))) ?>...
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('saranChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Jumlah Saran',
                data: <?= json_encode($data) ?>,
                backgroundColor: '#5E63BB',
                borderColor: '#4a4ebf',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `Saran: ${ctx.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { stepSize: 1 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

</body>
</html>