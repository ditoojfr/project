<?php
include "config/db.php";
$id = intval($_GET['id'] ?? 0);
$res = mysqli_query($conn, "SELECT * FROM kegiatan WHERE id={$id}");
$row = mysqli_fetch_assoc($res);
if(!$row){ header('Location: kegiatan_detail.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title><?php echo htmlspecialchars($row['judul']); ?></title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="container">
  <a href="index.php">← Kembali</a>
  <div class="detail">
    <h1><?php echo htmlspecialchars($row['judul']); ?></h1>
    <p><small><?php echo $row['tanggal']; ?></small></p>
    <?php if($row['foto']): ?>
      <?php echo '<img src="data:' . $row['foto_type'] . ';base64,' . base64_encode($row['foto']) . '" style="width:100%;max-height:400px;object-fit:cover;border-radius:8px">'; ?>
    <?php endif; ?>
    <p style="margin-top:12px"><?php echo nl2br(htmlspecialchars($row['deskripsi'])); ?></p>
  </div>
</div>
</body>
</html>
