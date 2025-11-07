<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['reset_user'])) {
    header("Location: lupa_password.php");
    exit;
}

$message = '';

if (isset($_POST['reset'])) {
    $new_pass = md5($_POST['password']);
    $user = $_SESSION['reset_user'];
    mysqli_query($conn, "UPDATE users SET password='{$new_pass}' WHERE username='{$user}'");
    session_unset();
    session_destroy();
    header("Location: login.php?pesan=reset_sukses");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password - E-Deslay</title>
<link rel="stylesheet" href="assets/css/style.css?v=1.0">
</head>
<body class="login-body">

<div class="login-container">
  <div class="glass-card">
    <h2>Atur Ulang Password</h2>
    <?= $message ?>
    <form method="POST" class="login-form">
      <div class="form-group">
        <label for="password">Password Baru</label>
        <input type="password" name="password" id="password" required placeholder="Masukkan Password Baru">
      </div>
      <button type="submit" name="reset" class="btn-login">Simpan Password</button>
    </form>
  </div>
</div>

</body>
</html>
