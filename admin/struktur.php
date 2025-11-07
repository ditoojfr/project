<?php
session_start();
include "../config/db.php";
if(!isset($_SESSION['user_id'])){ header('Location: ../login.php'); exit; }

/* handle delete */
if(isset($_GET['del'])){
  $id = intval($_GET['del']);
  mysqli_query($conn, "DELETE FROM struktur_desa WHERE id={$id}");
  header('Location: struktur.php'); exit;
}

/* handle save (add/edit) */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_struktur'])){
  $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  if(!empty($_POST['id'])){
    $id = intval($_POST['id']);
    mysqli_query($conn, "UPDATE struktur_desa SET jabatan='{$jabatan}', nama='{$nama}' WHERE id={$id}");
  } else {
    mysqli_query($conn, "INSERT INTO struktur_desa (jabatan, nama) VALUES ('{$jabatan}','{$nama}')");
  }
  header('Location: struktur.php'); exit;
}

/* edit fetch */
$edit = null;
if(isset($_GET['edit'])){
  $id = intval($_GET['edit']);
  $res = mysqli_query($conn, "SELECT * FROM struktur_desa WHERE id={$id}");
  $edit = mysqli_fetch_assoc($res);
}

/* list */
$list = mysqli_query($conn, "SELECT * FROM struktur_desa ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Kelola Struktur Desa</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
  <aside class="sidebar">
    <h2>E-DESLAY</h2>
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="kegiatan.php">Kegiatan</a></li>
      <li><a href="prestasi.php">Prestasi</a></li>
      <li><a href="saran.php">Kotak Saran</a></li>
      <li><a href="struktur.php" class="active">Struktur Desa</a></li>
      <li><a href="profile.php">Profil</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </aside>

  <div class="main">
    <h1>Kelola Struktur Perangkat Desa</h1>

    <div class="form">
      <h3><?php echo $edit ? 'Edit' : 'Tambah'; ?> Struktur</h3>
      <form method="post">
        <input type="hidden" name="id" value="<?php echo $edit['id'] ?? ''; ?>">
        <label>Jabatan</label>
        <input type="text" name="jabatan" required value="<?php echo htmlspecialchars($edit['jabatan'] ?? ''); ?>" style="width:100%;padding:8px;margin:8px 0;border-radius:8px;border:1px solid #e5e7eb">
        <label>Nama</label>
        <input type="text" name="nama" required value="<?php echo htmlspecialchars($edit['nama'] ?? ''); ?>" style="width:100%;padding:8px;margin:8px 0;border-radius:8px;border:1px solid #e5e7eb">
        <button type="submit" name="save_struktur" class="btn primary"><?php echo $edit ? 'Update' : 'Simpan'; ?></button>
        <?php if($edit): ?>
          <a href="struktur.php" class="btn" style="margin-left:8px;">Batal</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="table" style="margin-top:12px;">
      <h3>Daftar Struktur</h3>
      <table width="100%">
        <tr><th>Jabatan</th><th>Nama</th><th>Aksi</th></tr>
        <?php while($r = mysqli_fetch_assoc($list)): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['jabatan']); ?></td>
            <td><?php echo htmlspecialchars($r['nama']); ?></td>
            <td>
              <a href="struktur.php?edit=<?php echo $r['id']; ?>">Edit</a> |
              <a href="struktur.php?del=<?php echo $r['id']; ?>" onclick="return confirm('Hapus?')">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>

  </div>
</body>
</html>
