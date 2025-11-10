<?php
session_start();
include "../config/db.php";
if(!isset($_SESSION['user_id'])){ header('Location: ../login.php'); exit; }

// delete
if(isset($_GET['del'])){
  $id = intval($_GET['del']);
  mysqli_query($conn, "DELETE FROM prestasi WHERE id={$id}");
  header('Location: prestasi.php'); exit;
}

// save
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['save_prestasi'])){
  $judul = mysqli_real_escape_string($conn, $_POST['judul']);
  $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
  $tanggal = $_POST['tanggal'];
  if(isset($_FILES['foto']) && $_FILES['foto']['size']>0){
    $foto = addslashes(file_get_contents($_FILES['foto']['tmp_name']));
    $ftype = $_FILES['foto']['type'];
  } else { $foto = null; $ftype = null; }

  if(!empty($_POST['id'])) {
    $id = intval($_POST['id']);
    if($foto){
      mysqli_query($conn, "UPDATE prestasi SET judul='{$judul}', keterangan='{$keterangan}', tanggal='{$tanggal}', foto='{$foto}', foto_type='{$ftype}' WHERE id={$id}");
    } else {
      mysqli_query($conn, "UPDATE prestasi SET judul='{$judul}', keterangan='{$keterangan}', tanggal='{$tanggal}' WHERE id={$id}");
    }
  } else {
    mysqli_query($conn, "INSERT INTO prestasi (judul, keterangan, tanggal, foto, foto_type) VALUES ('{$judul}','{$keterangan}','{$tanggal}',".($foto?"'{$foto}'":"NULL").",".($ftype?"'{$ftype}'":"NULL").")");
  }
  header('Location: prestasi.php'); exit;
}

// edit fetch
$edit = null;
if(isset($_GET['edit'])){
  $id = intval($_GET['edit']);
  $res = mysqli_query($conn, "SELECT * FROM prestasi WHERE id={$id}");
  $edit = mysqli_fetch_assoc($res);
}
$list = mysqli_query($conn, "SELECT id, judul, tanggal FROM prestasi ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Kelola Prestasi</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
  <aside class="sidebar">
    <h2>E-DESLAY</h2><ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="kegiatan.php">Kegiatan</a></li>
      <li><a href="prestasi.php" class="active">Prestasi</a></li>
      <li><a href="saran.php">Kotak Saran</a></li>
      <li><a href="profile.php">Profil</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </aside>
  <div class="main">
    <h1>Kelola Prestasi</h1>
    <div class="form">
      <h3><?php echo $edit? 'Edit Prestasi':'Tambah Prestasi'; ?></h3>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $edit['id']??''; ?>">
        <input type="text" name="judul" placeholder="Judul" required value="<?php echo htmlspecialchars($edit['judul']??''); ?>" class="x-prestasi-1">
        <input type="date" name="tanggal" required value="<?php echo $edit['tanggal']??''; ?>" class="x-prestasi-2">
        <textarea name="keterangan" rows="6" placeholder="Keterangan" class="x-prestasi-3"><?php echo htmlspecialchars($edit['keterangan']??''); ?></textarea>
        <input type="file" name="foto" accept="image/*">
        <?php if($edit && $edit['foto']): ?>
          <?php echo '<div class="x-prestasi-4"><img src="data:' . $edit['foto_type'] . ';base64,' . base64_encode($edit['foto']) . '" class="x-prestasi-5"></div>'; ?>
        <?php endif; ?>
        <button type="submit" name="save_prestasi" class="btn primary xgin-top:12px"><?php echo $edit? 'Update':'Simpan'; ?></button>
      </form>
    </div>

    <div class="table">
      <h3>Daftar Prestasi</h3>
      <table width="100%">
        <tr><th>Judul</th><th>Tanggal</th><th>Aksi</th></tr>
        <?php while($r = mysqli_fetch_assoc($list)): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['judul']); ?></td>
            <td><?php echo $r['tanggal']; ?></td>
            <td>
              <a href="prestasi.php?edit=<?php echo $r['id']; ?>">Edit</a> |
              <a href="prestasi.php?del=<?php echo $r['id']; ?>" onclick="return confirm('Hapus?')">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>

  </div>
</body>
</html>
