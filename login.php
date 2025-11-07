<?php
session_start();
include "config/db.php";
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
<head><meta charset="utf-8"><title>Login</title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body style="display:flex;align-items:center;justify-content:center;height:100vh;background:linear-gradient(135deg,#1E3A8A,#3B82F6)">
  <div style="width:340px;background:#fff;padding:22px;border-radius:10px;box-shadow:0 8px 30px rgba(2,6,23,0.2)">
    <h2 style="color:#1E3A8A;margin-bottom:12px">E-DESLAY Admin</h2>
    <?php if(isset($error)) echo '<p style="color:#ef4444">'.htmlspecialchars($error).'</p>'; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required style="width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #e5e7eb">
      <input type="password" name="password" placeholder="Password" required style="width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #e5e7eb">
      <button type="submit" name="login" class="btn primary" style="width:100%">Login</button>
    </form>
  </div>
</body>
</html>
