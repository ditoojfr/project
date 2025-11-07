<?php
session_start();
include "config/db.php";
$error = '';
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='{$username}' AND password='{$password}'");
    if(mysqli_num_rows($q)){
        $user = mysqli_fetch_assoc($q);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: admin/dashboard.php'); exit;
    } else { $error = 'Username atau password salah'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="veiwport" content="width=device-width, initial-scale=1">
  <title>Login Admin - Desa Banjar Dowo</title>
  <link rel="stylesheet" href="assets/css/style.css?v=1.0">
</head>
<body class="login-body"">
  <header class="login-header">
    <div class="logo-container">
      <img src="assets/images/logo-nganjuk.png" alt="Logo Kabupaten Nganjuk" class="logo-kabupaten">
      <div class="desa-info">
        <h1>Desa Banjardowo</h1>
        <p>Kecamatan Lengkong, Kabupaten Nganjuk</p>
      </div>
    </div>
  </header>

  <div class="login-container">
  <div class="glass-card">
    <h2>Login Admin</h2>

    <?php if (!empty($error)): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <button type="submit" name="login" class="btn-login">Login</button>
      <a href="lupa_password.php" class="forgot-password">Forgot Password?</a>
    </form>
  </div>
  </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
