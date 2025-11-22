<?php
session_start();
include "../config/db.php";

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// AMBIL DATA USER
$user_id = $_SESSION['user_id'];
$userQuery = mysqli_query($conn, "SELECT nama_lengkap FROM `users` WHERE id = $user_id");
$userData = mysqli_fetch_assoc($userQuery);
$nama_lengkap = $userData['nama_lengkap'] ?? 'Admin';

// Ambil statistik (sesuai struktur tabel baru)
$total_prestasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `prestasi`"))['total'] ?? 0;
$total_kegiatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `kegiatan`"))['total'] ?? 0;
$total_saran     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `saran`"))['total'] ?? 0;
$total_pelayanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `users` WHERE role = 'admin'"))['total'] ?? 0;

// === Siapkan data grafik: saran per bulan (6 bulan terakhir) ===
$labels = [];
$data = [];

for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime($bulan));
    $labels[] = $label;

    // Hitung saran di bulan tersebut
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM `saran` WHERE DATE_FORMAT(tanggal_dikirim, '%Y-%m') = ?");
    mysqli_stmt_bind_param($stmt, "s", $bulan);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_array(mysqli_stmt_get_result($stmt))[0];
    $data[] = (int) $count;
}

// === Ambil 3 saran terbaru ===
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
    <!-- ICON -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* ========================= GLOBAL ========================= */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f7f8fa;
        }

        /* ========================= SIDEBAR HEADER ========================= */
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
            z-index: 1000;
        }

        .sidebar-header img {
            width: 42px;
            height: 42px;
        }

        .title { font-weight: 600; color: #333; }

        /* ========================= SIDEBAR ========================= */
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
            z-index: 999;
        }

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

        /* LOGOUT DI SIDEBAR */
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

        /* ========================= MAIN ========================= */
        .main {
            margin-left: 260px;
            padding: 30px 40px;
        }

        /* ========================= TOP BAR ========================= */
        .top-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
            padding: 10px 40px;
            background: #fff;
            height: 56px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .right-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-input-wrapper {
            background: #f3f4f8;
            border-radius: 999px;
            padding: 10px 22px;
            display: flex;
            align-items: center;
            width: 250px;
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

        .user-text {
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .user-name {
            font-size: 15px;
            font-weight: 600;
            color: #000;
        }

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

        /* ========================= CONTENT STYLE ========================= */
        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* Statistik Cards */
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            flex: 1;
            min-width: 220px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon-box {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5E63BB;
            font-size: 24px;
        }

        .stat-text {
            flex: 1;
        }

        .stat-label {
            font-size: 14px;
            color: #777;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-top: 4px;
        }

        /* Chart placeholder */
        .chart-box {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            height: 280px;
            margin-bottom: 30px;
        }

        /* Saran Box */
        .saran-box {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .saran-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }

        .saran-item:last-child {
            border-bottom: none;
        }

        .saran-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #555;
        }

        .saran-content h5 {
            margin: 0 0 6px 0;
            font-size: 16px;
            color: #333;
        }

        .saran-date {
            font-size: 12px;
            color: #888;
            margin-bottom: 6px;
        }

        .saran-desc {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }

        /* ========================= MODAL LOGOUT ========================= */
        .modal {
            position: fixed;
            z-index: 999999;
            inset: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.35);
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
        }

        .modal.show {
            opacity: 1;
            pointer-events: all;
        }

        .modal-content {
            width: 340px;
            padding: 26px 28px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            text-align: center;
            transform: scale(.85);
            opacity: 0;
            transition: all .28s cubic-bezier(.18,.89,.32,1.28);
            border: 1px solid rgba(255,255,255,0.4);
        }

        .modal-content.show {
            transform: scale(1);
            opacity: 1;
        }

        .logout-icon {
            font-size: 52px;
            color: #e63946;
            margin-bottom: 10px;
            animation: rotateIn .45s ease;
        }

        @keyframes rotateIn {
            0% { opacity: 0; transform: rotate(-30deg) scale(.4); }
            100% { opacity: 1; transform: rotate(0) scale(1); }
        }

        .modal-actions {
            margin-top: 22px;
            display: flex;
            justify-content: space-between;
            gap: 14px;
        }

        .btn-cancel {
            flex: 1;
            padding: 10px 0;
            border-radius: 10px;
            border: none;
            background: #dcdcdc;
            cursor: pointer;
            font-weight: 600;
            transition: .2s;
        }
        .btn-cancel:hover { background: #bfbfbf; }

        .btn-logout {
            flex: 1;
            padding: 10px 0;
            border-radius: 10px;
            border: none;
            background: #e63946;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: .2s;
            display: inline-block;
        }
        .btn-logout:hover { background: #c92c39; }
    </style>
</head>
<body>

<!-- SIDEBAR HEADER -->
<div class="sidebar-header">
    <img src="../assets/images/logo-nganjuk.png" alt="Logo">
    <div class="title">Desa Banjardowo</div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <ul class="menu">
        <li>
            <a href="dashboard.php" class="active">
                <img src="../assets/icons/dashboard1.png" alt="Dashboard" style="width:20px; height:20px; margin-right:8px;">
                Dashboard
            </a>
        </li>
        <li>
            <a href="kegiatan.php">
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
        <a href="../index.php" id="logoutBtn">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>

<!-- MODAL LOGOUT -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-circle-xmark logout-icon"></i>
        <h3 style="margin: 0; font-size: 20px; font-weight: 700;">Keluar Akun?</h3>
        <p style="margin-top: 6px; color: #444;">Anda yakin ingin keluar dari akun?</p>
        <div class="modal-actions">
            <button id="cancelLogout" class="btn-cancel">Batal</button>
            <a href="../logout.php" class="btn-logout">Keluar</a>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="right-group">
            <div class="search-input-wrapper">
                <input type="text" placeholder="Search...">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div class="user-text">
                <div class="user-name"><?= htmlspecialchars($nama_lengkap) ?></div>
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

    <!-- PAGE TITLE -->
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
                <div class="stat-value"><?= $total_pelayanan ?></div>
            </div>
        </div>
    </div>

    <!-- ✅ GRAFIK DENGAN CHART.JS -->
    <div class="chart-box">
        <h4>Grafik Kotak Saran Masuk (6 Bulan Terakhir)</h4>
        <canvas id="saranChart" height="200"></canvas>
    </div>

    <!-- DAFTAR SARAN -->
    <div class="saran-box">
        <h4>Daftar Saran Terbaru</h4>
        <?php if (empty($saran_list)): ?>
            <p class="text-center text-muted mt-3">Belum ada saran masuk.</p>
        <?php else: ?>
            <?php foreach ($saran_list as $saran): ?>
                <div class="saran-item">
                    <div class="saran-avatar"><?= strtoupper(substr($saran['email'] ?? 'U', 0, 1)) ?></div>
                    <div class="saran-content">
                        <h5><?= htmlspecialchars($saran['judul']) ?></h5>
                        <div class="saran-date">
                            dari <?= htmlspecialchars($saran['email']) ?> • 
                            <?= date('d F Y', strtotime($saran['tanggal_dikirim'])) ?>
                        </div>
                        <div class="saran-desc"><?= nl2br(htmlspecialchars(substr($saran['isi_saran'], 0, 100))) ?>...</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ✅ INISIALISASI CHART.JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('saranChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Jumlah Saran',
                <?= json_encode($data) ?>,
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

    <!-- DAFTAR SARAN -->
    <div class="saran-box">
        <h4>Daftar Saran Terbaru</h4>
        <?php if (empty($saran_list)): ?>
            <p class="text-center text-muted mt-3">Belum ada saran masuk.</p>
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
                        <div class="saran-desc"><?= nl2br(htmlspecialchars(substr($saran['isi_saran'], 0, 100))) ?>...</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPT MODAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutModal = document.getElementById("logoutModal");
    const cancelLogout = document.getElementById("cancelLogout");
    const modalContent = document.querySelector(".modal-content");

    logoutBtn.onclick = function(e) {
        e.preventDefault();
        logoutModal.classList.add("show");
        setTimeout(() => modalContent.classList.add("show"), 10);
    };

    cancelLogout.onclick = function() {
        modalContent.classList.remove("show");
        setTimeout(() => logoutModal.classList.remove("show"), 180);
    };
});
</script>

</body>
</html>