<?php
require_once 'config/db.php';

// Fungsi untuk mengambil semua kegiatan
function getAllKegiatan($conn) {
    $sql = "SELECT id, judul, deskripsi, tanggal, foto, foto_type FROM kegiatan ORDER BY tanggal DESC";
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

// Fungsi untuk mengambil semua prestasi
function getAllPrestasi($conn) {
    $sql = "SELECT id, judul, keterangan, tanggal, foto, foto_type FROM prestasi ORDER BY tanggal DESC";
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

// Fungsi untuk mengambil struktur perangkat desa
function getStrukturDesa($conn) {
    $sql = "SELECT jabatan, nama FROM struktur_desa ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

// Ambil data
$kegiatanList = getAllKegiatan($conn);
$prestasiList = getAllPrestasi($conn);
$strukturDesa = getStrukturDesa($conn);

// Fungsi untuk membuat URL gambar dari BLOB
function displayImageFromBlob($foto, $foto_type) {
    if ($foto && $foto_type) {
        return 'data:' . $foto_type . ';base64,' . base64_encode($foto);
    }
    return 'https://via.placeholder.com/400x300?text=No+Image';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desa Banjardowo - E-Deslay</title>
    <style>
        /* Reset & Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
        }

        /* Header Styles */
        .header {
            background: linear-gradient( 90deg, #002a9e, #0062ff);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header-title {
            font-size: 0.9rem;
            text-align: left;
        }

        .header-title h1 {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 0.2rem;
        }

        .header-title p {
            font-size: 0.85rem;
            margin: 0;
        }

        .header-nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: background-color 0.3s ease;
        }

        .header-nav a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .login-btn {
            background-color: #0624d3;
            color: #5c6bc0;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            background-color: #f0f0f0;
        }

        /* Mobile Menu Hamburger */
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 24px;
            height: 18px;
            cursor: pointer;
        }

        .mobile-menu-btn span {
            display: block;
            height: 2px;
            width: 100%;
            background-color: white;
            transition: all 0.3s ease;
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100vh;
            background: linear-gradient(90deg, #002a9e, #0062ff);
            z-index: 1000;
            transition: right 0.3s ease;
            padding: 2rem 1rem;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-menu-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: white;
            cursor: pointer;
        }

        .mobile-menu ul {
            list-style: none;
            margin-top: 2rem;
        }

        .mobile-menu li {
            margin-bottom: 1rem;
        }

        .mobile-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            display: block;
            transition: background-color 0.3s ease;
        }

        .mobile-menu a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        /* Main Content Styles */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .hero {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .hero-img {
            flex: 0 0 300px;
        }

        .hero-img img {
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .hero-text {
            flex: 1;
            min-width: 300px;
        }

        .hero-text h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .hero-text p {
            margin-bottom: 1rem;
            text-align: justify;
        }

        /* Section Styles */
        .section {
            margin: 3rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: #333;
        }

        /* Kegiatan Desa */
        .kegiatan-slider-container {
            position: relative;
            margin: 0 auto;
            max-width: 1200px;
        }

        .kegiatan-slider {
            display: flex;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .kegiatan-slide {
            min-width: 100%;
            padding: 1rem;
            box-sizing: border-box;
            display: flex;
            gap: 2rem;
            justify-content: center;
            align-items: stretch;
        }

        .kegiatan-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
        }

        .kegiatan-card:hover {
            transform: translateY(-5px);
        }

        .kegiatan-img {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .kegiatan-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .kegiatan-card:hover .kegiatan-img img {
            transform: scale(1.05);
        }

        .kegiatan-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kegiatan-content h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .kegiatan-content h3:hover {
            color: #5c6bc0;
            text-decoration: underline;
        }

        .kegiatan-content p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            text-align: justify;
            flex: 1;
        }

        .kegiatan-date {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 1rem;
        }

        .kegiatan-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-prev {
            background-color: #3B82F6;
            color: white;
        }

        .btn-next {
            background-color: #3B82F6;
            color: white;
        }

        .btn-prev:hover, .btn-next:hover {
            background-color: #1E3A8A;
        }

        /* Prestasi */
        .prestasi-slider-container {
            position: relative;
            margin: 0 auto;
            max-width: 1200px;
        }

        .prestasi-slider {
            display: flex;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .prestasi-slide {
            min-width: 100%;
            padding: 1rem;
            box-sizing: border-box;
            display: flex;
            gap: 2rem;
            justify-content: center;
            align-items: stretch;
        }

        .prestasi-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
        }

        .prestasi-card:hover {
            transform: translateY(-5px);
        }

        .prestasi-img {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .prestasi-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .prestasi-card:hover .prestasi-img img {
            transform: scale(1.05);
        }

        .prestasi-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .prestasi-content h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .prestasi-content h3:hover {
            color: #5c6bc0;
            text-decoration: underline;
        }

        .prestasi-content p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            text-align: justify;
            flex: 1;
        }

        .prestasi-date {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 1rem;
        }

        /* Struktur Perangkat Desa - Versi Modern */
        .struktur-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .struktur-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .struktur-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            width: 100%;
        }

        .struktur-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .struktur-card:hover {
            transform: translateY(-5px);
            border-color: #5c6bc0;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .struktur-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #5c6bc0, #7e57c2);
            transition: all 0.3s ease;
        }

        .struktur-card:hover::before {
            height: 6px;
        }

        .struktur-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }

        .struktur-card p {
            font-size: 1rem;
            color: #555;
            font-weight: 500;
        }

        /* Download Aplikasi */
        .download-section {
            text-align: center;
            padding: 3rem 1rem;
            background-color: #f5f5f5;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .download-section h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .download-section p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: #555;
        }

        .download-btn {
            background-color: #0062ff;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .download-btn:hover {
            background-color: #3f51b5;
            transform: translateY(-2px);
        }

        /* Footer Styles - New Design */
        .footer {
            width: 100%;
            background: linear-gradient(90deg, #002a9e, #0062ff);
            color: #fff;
            padding: 60px 8% 30px;
            margin-top: 60px;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.1);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 40px;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 280px;
        }

        .footer-left img {
            height: 120px;
            width: auto;
        }

        .footer-left .desa-info h3 {
            font-size: 1.4rem;
            margin: 0;
            font-weight: 700;
        }

        .footer-left .desa-info p {
            font-size: 0.95rem;
            margin: 6px 0 0;
            color: #dbeafe;
        }

        .footer-right {
            flex: 1;
            min-width: 280px;
        }

        .footer-right h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #bfdbfe;
        }

        .footer-right p {
            font-size: 0.95rem;
            margin: 4px 0;
            line-height: 1.6;
        }

        .footer-right a {
            color: #fff;
            text-decoration: none;
        }

        .footer-right a:hover {
            text-decoration: underline;
        }

        .footer-bottom {
            margin-top: 40px;
            text-align: center;
            font-size: 0.9rem;
            color: #dbeafe;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
        }
        
        @keyframes fadeUp { 
          from { 
            opacity: 0; 
            transform: translateY(30px); 
          } to { 
            opacity: 1; 
            transform: translateY(0); 
          } 
        }
        @keyframes fadeDown { 
          from { 
            opacity: 0; 
            transform: translateY(-30px); 
          } to { 
            opacity: 1; 
            transform: translateY(0); 
          } 
        }
        
        html {
          scroll-behavior: smooth;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: row;
                justify-content: space-between;
            }

            .header-left {
                width: auto;
                margin-right: 1rem;
            }

            .header-nav {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .mobile-menu {
                right: -300px;
            }

            .mobile-menu.active {
                right: 0;
            }

            .hero {
                flex-direction: column;
                gap: 1rem;
            }

            .hero-img {
                flex: 0 0 100%;
                max-width: 300px;
            }

            .hero-text {
                flex: 1;
                min-width: auto;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .kegiatan-slider,
            .prestasi-slider {
                flex-direction: column;
                align-items: stretch;
            }

            .kegiatan-slide,
            .prestasi-slide {
                flex-direction: column;
                gap: 1rem;
            }

            .kegiatan-card,
            .prestasi-card {
                min-width: 100%;
            }

            .struktur-grid {
                grid-template-columns: 1fr;
            }

            .download-section {
                padding: 2rem 1rem;
            }

            .download-btn {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }

            .footer {
                padding: 40px 6% 20px;
                text-align: center;
            }

            .footer-content {
                flex-direction: column;
                align-items: center;
            }

            .footer-left, .footer-right {
                text-align: center;
            }

            .footer-left img {
                height: 70px;
            }
        }

        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 0.9rem;
            }

            .header-title p {
                font-size: 0.8rem;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .hero-text h1 {
                font-size: 1.2rem;
            }

            .kegiatan-content h3,
            .prestasi-content h3 {
                font-size: 1rem;
            }

            .struktur-card h3 {
                font-size: 1rem;
            }

            .struktur-card p {
                font-size: 0.9rem;
            }

            .download-section h2 {
                font-size: 1.5rem;
            }

            .download-section p {
                font-size: 1rem;
            }

            .footer-column h3 {
                font-size: 1rem;
            }

            .footer-logo img {
                width: 120px;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="header-logo">
                <img src="assets/images/logo-nganjuk.png" alt="Logo Desa">
            </div>
            <div class="header-title">
                <h1>Desa Banjardowo</h1>
                <p>Kec.Lengkong, Kab.Nganjuk</p>
            </div>
        </div>
        <nav class="header-nav">
            <a href="#visimisi">Visi-Misi</a>
            <a href="#kegiatan">Kegiatan Desa</a>
            <a href="#prestasi">Prestasi</a>
            <a href="#struktur">Struktur Organisasi</a>
            <a href="login.php" class="login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.1c1.11.35 2 1.22 2 2.22v1H2v-1c0-1.01.89-1.88 2-2.22.51-.14 1.02-.25 1.53-.33C7.04 9.28 8 9.18 9 9.18c.6.01 1.2.12 1.73.33zM6 12.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/>
                </svg>
                LOGIN ADMIN
            </a>
        </nav>
        <button class="mobile-menu-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <button class="mobile-menu-close">&times;</button>
        <ul>
            <li><a href="#visimisi">Visi-Misi</a></li>
            <li><a href="#kegiatan">Kegiatan Desa</a></li>
            <li><a href="#prestasi">Prestasi</a></li>
            <li><a href="#struktur">Struktur Organisasi</a></li>
            <li><a href="login.php">LOGIN ADMIN</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="container">

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-img">
                <img src="assets/images/logo-big.png" alt="E-Deslay Logo">
            </div>
            <div class="hero-text">
                <h1>Selamat datang di E-Deslay, Layanan Digital Desa yang Lebih Mudah dan Cepat.</h1>
                <p>E-Deslay hadir sebagai wujud inovasi pelayanan masyarakat di desa. Melalui platform ini, warga dapat memperoleh panduan pembuatan surat, menyampaikan saran, serta mengakses informasi terkini mengenai kegiatan desa. Mari bersama mewujudkan pelayanan publik yang transparan, efisien, dan berorientasi pada kemudahan masyarakat.</p>
            </div>
        </section>

        <!-- Visi Misi -->
        <section id="visimisi" class="section">
            <h2 class="section-title">Visi-Misi</h2>
            <div class="hero-text">
                <p><strong>Pemerintah Desa berkomitmen mewujudkan pelayanan yang transparan, efisien, dan berbasis digital untuk meningkatkan kesejahteraan masyarakat, dengan menyediakan akses informasi yang cepat dan jelas, panduan tata cara pengurusan administrasi yang terstruktur, transparansi kegiatan serta pengumuman resmi, serta mendorong partisipasi masyarakat dalam pembangunan desa melalui keterbukaan informasi.</strong></p>
            </div>
        </section>

        <!-- Kegiatan Desa -->
        <section id="kegiatan" class="section">
            <h2 class="section-title">Kegiatan Desa</h2>
            <div class="kegiatan-slider-container">
                <div class="kegiatan-slider" id="kegiatanSlider">
                    <?php
                    // Buat slider dengan semua kegiatan
                    $slideCount = ceil(count($kegiatanList) / 2); // Jumlah slide (2 item per slide)
                    for ($i = 0; $i < $slideCount; $i++):
                        $startIndex = $i * 2;
                    ?>
                        <div class="kegiatan-slide">
                            <?php for ($j = $startIndex; $j < $startIndex + 2 && $j < count($kegiatanList); $j++): ?>
                                <div class="kegiatan-card">
                                    <div class="kegiatan-img">
                                        <img src="<?= displayImageFromBlob($kegiatanList[$j]['foto'], $kegiatanList[$j]['foto_type']) ?>" alt="<?= htmlspecialchars($kegiatanList[$j]['judul']) ?>">
                                    </div>
                                    <div class="kegiatan-content">
                                        <h3 onclick="window.location.href='detail_kegiatan.php?id=<?= $kegiatanList[$j]['id'] ?>'"><?= htmlspecialchars($kegiatanList[$j]['judul']) ?></h3>
                                        <p><?= htmlspecialchars(substr($kegiatanList[$j]['deskripsi'], 0, 150)) ?>...</p>
                                        <div class="kegiatan-date"><?= date('d F Y', strtotime($kegiatanList[$j]['tanggal'])) ?></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="kegiatan-actions">
                    <button class="btn btn-prev" onclick="moveSlide('kegiatan', -1)">PREV</button>
                    <button class="btn btn-next" onclick="moveSlide('kegiatan', 1)">NEXT</button>
                </div>
            </div>
        </section>

        <!-- Prestasi -->
        <section id="prestasi" class="section">
            <h2 class="section-title">Prestasi</h2>
            <div class="prestasi-slider-container">
                <div class="prestasi-slider" id="prestasiSlider">
                    <?php
                    // Buat slider dengan semua prestasi
                    $slideCount = ceil(count($prestasiList) / 2); // Jumlah slide (2 item per slide)
                    for ($i = 0; $i < $slideCount; $i++):
                        $startIndex = $i * 2;
                    ?>
                        <div class="prestasi-slide">
                            <?php for ($j = $startIndex; $j < $startIndex + 2 && $j < count($prestasiList); $j++): ?>
                                <div class="prestasi-card">
                                    <div class="prestasi-img">
                                        <img src="<?= displayImageFromBlob($prestasiList[$j]['foto'], $prestasiList[$j]['foto_type']) ?>" alt="<?= htmlspecialchars($prestasiList[$j]['judul']) ?>">
                                    </div>
                                    <div class="prestasi-content">
                                        <h3 onclick="window.location.href='detail_prestasi.php?id=<?= $prestasiList[$j]['id'] ?>'"><?= htmlspecialchars($prestasiList[$j]['judul']) ?></h3>
                                        <p><?= htmlspecialchars(substr($prestasiList[$j]['keterangan'], 0, 150)) ?>...</p>
                                        <div class="prestasi-date"><?= date('d F Y', strtotime($prestasiList[$j]['tanggal'])) ?></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="kegiatan-actions">
                    <button class="btn btn-prev" onclick="moveSlide('prestasi', -1)">PREV</button>
                    <button class="btn btn-next" onclick="moveSlide('prestasi', 1)">NEXT</button>
                </div>
            </div>
        </section>

        <!-- Struktur Perangkat Desa -->
        <section id="struktur" class="section">
            <h2 class="section-title">Struktur Perangkat Desa</h2>
            <div class="struktur-container">
                <div class="struktur-grid">
                    <?php foreach ($strukturDesa as $item): ?>
                        <div class="struktur-card">
                            <h3><?= htmlspecialchars($item['jabatan']) ?></h3>
                            <p><?= htmlspecialchars($item['nama']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Download Aplikasi -->
        <section class="download-section">
            <h2>Download Aplikasi</h2>
            <p>Nikmati kemudahan layanan desa dari aplikasi resmi kami.</p>
            <a href="#" class="download-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h5zM0 5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V5zm8 0a.5.5 0 0 1 .5.5V7H10a.5.5 0 0 1 .5.5.5.5 0 0 1-.5.5H8.5v1.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V8H5a.5.5 0 0 1-.5-.5.5.5 0 0 1 .5-.5h1.5V5.5A.5.5 0 0 1 7 5h1z"/>
                </svg>
                DOWNLOAD APK
            </a>
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <img src="assets/images/logo-big.png" alt="Logo Team">
                <div class="desa-info">
                    <h3>Desa Banjardowo</h3>
                    <p>Kecamatan Lengkong, Kabupaten Nganjuk</p>
                </div>
            </div>

            <div class="footer-right">
                <h4>Kontak Kami</h4>
                <p>📍 JL. Gondang Timur No.41A Banjardowo, Kec. Lengkong, Kab. Nganjuk</p>
                <p>📧 <a href="mailto:banjardowolengkong@gmail.com">banjardowolengkong@gmail.com</a></p>
                <p>📞 0857-4664-1970</p>
            </div>
        </div>

        <div class="footer-bottom">
            © <?= date("Y"); ?> Desa Banjardowo. All Rights Reserved.  
            | Website Resmi E-DESLAY — Layanan Digital Desa.
        </div>
    </footer>

    <script>
        // Fungsi untuk menggerakkan slider
        function moveSlide(sectionId, direction) {
            const slider = document.getElementById(sectionId + 'Slider');
            const slideWidth = slider.parentElement.offsetWidth; // Lebar container
            const currentScroll = slider.scrollLeft;
            const newScroll = currentScroll + (direction * slideWidth);

            // Batasi agar tidak bisa melewati batas
            if (newScroll >= 0 && newScroll <= slider.scrollWidth - slider.clientWidth) {
                slider.scrollTo({
                    left: newScroll,
                    behavior: 'smooth'
                });
            }
        }

        // Fungsi untuk membuka menu mobile
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('active');
        });

        // Fungsi untuk menutup menu mobile saat klik tombol close
        document.querySelector('.mobile-menu-close').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.remove('active');
        });

        // Fungsi untuk menutup menu mobile saat klik di luar menu
        document.addEventListener('click', function(event) {
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target) && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
            }
        });
    </script>

</body>
</html>