<?php
include "config/db.php";

$id = intval($_GET['id'] ?? 0);
$res = mysqli_query($conn, "SELECT * FROM kegiatan WHERE id={$id}");
$row = mysqli_fetch_assoc($res);

if (!$row) { 
    header('Location: index.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($row['judul']); ?></title>

    <style>
        body {
            margin: 0;
            background: #e5e7eb;
            font-family: Arial, sans-serif;
            overflow-y: auto;
        }

        /* HEADER ATAS */
        .top-header {
            background: #4f46e5;
            padding: 14px 22px;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        /* WRAPPER KONTEN */
        .content-wrapper {
            max-width: 900px;
            margin: 25px auto;
            background: white;
            border-radius: 10px;
            padding: 25px 35px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        /* BREADCRUMB */
        .breadcrumb {
            font-size: 13px;
            color: #555;
            margin-bottom: 10px;
        }

        /* TOMBOL KEMBALI BULAT */
        .back-btn {
            display: inline-flex;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            text-decoration: none;
            color: black;
            font-size: 22px;
        }

        /* JUDUL */
        h1 {
            font-size: 26px;
            margin: 10px 0 5px;
            line-height: 1.4;
            color: #111827;
        }

        /* INFO PENULIS */
        .author {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #444;
            margin-bottom: 20px;
        }
        .author img {
            width: 40px;
            height: 40px;
        }

        /* GAMBAR UTAMA */
        .main-img {
            width: 100%;
            max-height: 450px;
            object-fit: contain;
            margin: 20px 0;
        }

        /* TEKS ARTIKEL */
        .article p {
            line-height: 1.7;
            font-size: 15px;
            margin-bottom: 14px;
            text-align: justify;
        }

        /* BREADCRUMB STYLE (Breadcrumb Navigation */
        .breadcrumb-box {
            margin-bottom: 20px;
            font-size: 14px;
            color: #6b7280;
        }

        /* Breadcrumb Link */
        .breadcrumb {
            text-decoration: none;
            color: #6b7280;
            transition: 0.2s;
        }

        /*Logo */
        .top-header {
            background: #4f46e5;
            padding: 14px 22px;
            color: white;
            font-size: 18px;
            font-weight: bold;

            display: flex;          /* ⬅ supaya logo dan tulisan sejajar */
            align-items: center;    /* ⬅ posisi vertikal tengah */
            gap: 10px;              /* ⬅ jarak antara logo & teks */
        }

          .top-header img {
                height: 45px;           /* ⬅ atur tinggi logo */
        }


    </style>
</head>

<body>
<!-- HEADER Logo -->
<div class="top-header">
    <img src="assets/images/logo-nganjuk.png" alt="Logo Desa">
    Desa Banjardowo
</div>

<!-- KONTEN UTAMA -->
<div class="content-wrapper">

<a href="index.php" class="back-btn">←</a>  <div class="breadcrumb-box">
    <a href="index.php" class="breadcrumb">Dashboard</a> /
    <a href="kegiatan.php" class="breadcrumb">Kegiatan Desa</a>

    

    <!-- JUDUL  -->
    <h1><?php echo htmlspecialchars($row['judul']); ?></h1>

    <!-- PENULIS -->
    <div class="author">
        <img src="assets/images/logo-big.png" alt="avatar">
        <div>
            <b>E-Deslay</b><br>
            <?php echo date("d F Y", strtotime($row['tanggal'])); ?>
        </div>
    </div>

    <!-- GAMBAR -->
    <?php if ($row['foto']): ?>
        <img class="main-img" 
             src="data:<?php echo $row['foto_type']; ?>;base64,<?php echo base64_encode($row['foto']); ?>">
    <?php endif; ?>

    <!-- ISI ARTIKEL -->
    <div class="article">
        <p><?php echo nl2br(htmlspecialchars($row['deskripsi'])); ?></p>
    </div>
    
</div>

</div>

</body>
</html>
