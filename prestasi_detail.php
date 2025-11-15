<?php
include "config/db.php";

$id = intval($_GET['id'] ?? 0);
$res = mysqli_query($conn, "SELECT * FROM prestasi WHERE id={$id}");
$row = mysqli_fetch_assoc($res);

if(!$row){
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($row['judul']); ?></title>

<style>
    body{
        margin:0;
        background:#e5e7eb;
        font-family: Arial, sans-serif;
    }

    /* HEADER UNGU */
    .top-header{
        background:#4f46e5;
        padding:14px 22px;
        color:white;
        font-size:18px;
        font-weight:bold;
        display:flex;
        align-items:center;
        gap:10px;
    }
    .top-header img{
        height:45px;
    }

    /* WRAPPER PUTIH */
    .content-wrapper{
        max-width:900px;
        margin:25px auto;
        background:white;
        border-radius:12px;
        padding:25px 35px;
        box-shadow:0 4px 10px rgba(0,0,0,0.10);
    }

    /* TOMBOL KEMBALI */
    .back-btn{
        display:inline-flex;
        background:white;
        border-radius:50%;
        width:40px;
        height:40px;
        align-items:center;
        justify-content:center;
        font-size:22px;
        text-decoration:none;
        color:#111;
        box-shadow:0 3px 7px rgba(0,0,0,0.2);
        margin-bottom:10px;
    }

    /* BREADCRUMB */
    .breadcrumb-box{
        margin-bottom:10px;
        font-size:14px;
        color:#6b7280;
    }
    .breadcrumb-box a{
        text-decoration:none;
        color:#6b7280;
    }

    /* JUDUL */
    h1{
        font-size:30px;
        margin:10px 0;
        line-height:1.4;
        color:#111827;
    }

    /* INFO PENULIS */
    .author{
        display:flex;
        align-items:center;
        gap:12px;
        font-size:14px;
        color:#444;
        margin-bottom:20px;
    }
    .author img{
        width:40px;
        height:40px;
    }

    /* GAMBAR */
    .main-img{
        width:100%;
        max-height:480px;
        object-fit:contain;
        margin:20px 0;
    }

    .article p{
        text-align:justify;
        font-size:15px;
        line-height:1.7;
    }
</style>

</head>
<body>

<!-- HEADER UNGU -->
<div class="top-header">
    <img src="assets/images/logo-big.png" alt="Logo">
    Desa Banjardowo
</div>

<div class="content-wrapper">

    <!-- TOMBOL KEMBALI -->
    <a href="prestasi.php" class="back-btn">←</a>

    <!-- BREADCRUMB -->
    <div class="breadcrumb-box">
        <a href="index.php">Dashboard</a> /
        <a href="prestasi.php">Prestasi</a>
    </div>

    <!-- JUDUL -->
    <h1><?= htmlspecialchars($row['judul']); ?></h1>

    <!-- INFO PENULIS -->
    <div class="author">
        <img src="assets/images/logo-big.png" alt="avatar">
        <div>
            <b>E-Deslay</b><br>
            <?= date("d F Y", strtotime($row['tanggal'])); ?>
        </div>
    </div>

    <!-- FOTO -->
    <?php if ($row['foto']): ?>
        <img class="main-img" 
             src="data:<?= $row['foto_type']; ?>;base64,<?= base64_encode($row['foto']); ?>">
    <?php endif; ?>

    <!-- ARTIKEL -->
    <div class="article">
        <p><?= nl2br(htmlspecialchars($row['keterangan'])); ?></p>
    </div>

</div>

</body>
</html>
