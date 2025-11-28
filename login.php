<?php
session_start();
include "config/db.php";
$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='{$username}' AND password='{$password}'");

    if (mysqli_num_rows($q)) {
        $user = mysqli_fetch_assoc($q);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Desa Banjardowo</title>

    <style>
        body.login-body {
            min-height: 100vh;
            margin: 0;
            background: url('assets/images/bg_login.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        /* ================= HEADER FIX ================= */
        .login-header {
            width: 100%;
            padding: 18px 32px;
            display: flex;
            align-items: center;
            background: transparent
            gap: 15px; /* Jarak aman antara logo & teks */
        }

        .logo-kabupaten {
            height: 55px;
            flex-shrink: 0; /* jangan mengecil */
        }

        .desa-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin: 0;
            padding-left: 10px;
            flex-grow: 0;      /* WAJIB: jangan terdorong ke kanan */
            flex-basis: auto;  /* WAJIB */
        }

        .desa-info h1 {
            margin: 0;
            font-size: 26px;
            color: #174087;
            font-weight: bold;
        }

        .desa-info p {
            margin: 2px 0 0 0;
            font-size: 17px;
            color: #174087;
            font-weight: 500;
        }

        /* =============== LOGIN WRAPPER =============== */
        .login-container {
            display: flex;
            max-width: 1000px;
            min-height: 530px;
            margin: 36px auto;
            border-radius: 14px;
            background: rgba(255,255,255,0.80);
            box-shadow: 0 8px 48px rgba(66,150,200,0.10);
            overflow: hidden;
        }

        .login-left {
            width: 50%;
            position: relative;
            background: #e5f4fd;
            display: flex;
            align-items: center;
        }

        .img-bg-left {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
        }

        .left-overlay-content {
            position: relative;
            padding: 60px 40px 0 45px;
            z-index: 2;
        }

        .logo-desa {
            position: absolute;   /* WAJIB SUPAYA BISA DIGESER DENGAN PX */
            top: -95px;            /* geser dari atas */
            left: 0px;           /* geser dari kiri */
            width: 150px;         /* ukuran */
            height: auto;
            z-index: 10;          /* pastikan muncul di depan */
        }


        .left-overlay-content h2 {
            font-size: 42px;
            font-weight: bold;
            color: #002550;
            margin-top: 48px;
        }

        .left-overlay-content p {
            margin-top: 28px;
            color: #082b4f;
            font-size: 17px;
            width: 90%;
            line-height: 27px;
        }

        .login-right {
            width: 50%;
            padding: 40px 38px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon-user {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, #99baf9 40%, #4782b1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 26px;
            box-shadow: 0 5px 20px rgba(110,180,255,0.16);
        }

        .icon-user svg {
            width: 60px;
            height: 60px;
            fill: #fff;
        }

        .login-form {
            width: 100%;
            max-width: 340px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 12px 13px 40px;
            border-radius: 7px;
            border: 1px solid #b2b5ca;
            background: #f7faff;
            font-size: 16px;
        }

        .form-group input:focus {
            border: 1.8px solid #4782b1;
        }

        .btn-login {
          width: 120px;          /* kecil seperti contoh */
          height: 38px;          /* tinggi proporsional */
          background: #36a3ff;   /* mirip warna biru contoh */
          color: #fff;
          font-weight: 700;
          font-size: 15px;
          border: none;
          border-radius: 8px;    /* sedikit bulat seperti gambar */
          cursor: pointer;
          display: block;
          margin: 25px auto 0;   /* center + jarak dari atas */
          text-align: center;
        }

        .btn-login:hover {
        background: #0a77e4;
        }

        .forgot-password {
          position: relative;
          left: 283px;      /* geser kanan */
          top: -5px;       /* geser atas/bawah */
          color: #085b9c;
          font-size: 14px;
          text-decoration: none;
        }




        .forgot-password:hover {
            text-decoration: underline;
        }

        .message.error {
            background: #ffe7e7;
            border: 1px solid #ffa5a5;
            color: #da3535;
            padding: 9px 17px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .form-group {
            position: relative;
        }
        .icon-input {
            position: absolute;
            left: 13px;
            top: 34px;         /* jika icon kurang pas, geser top naik/turun */
            z-index: 2;
            pointer-events: none;
        }
        .input-icon-image {
            width: 22px;       /* buat lebih kecil/besar sesuai ikon kamu */
            height: 22px;
            display: block;
            opacity: 0.88;
        }
        .form-group input {
            padding-left: 48px;
        }

        /* group khusus password */
        .password-group {
            position: relative;  /* sudah ada dari .form-group, tapi aman */
        }

        /* kasih ruang di kanan untuk icon mata */
        .password-group input {
            padding-right: 12px;
        }

        /* posisi icon mata di kanan dalam input */
        .toggle-password {
            position: absolute;
            right: -35px;    /* geser kanan */
            top: 34px;      /* geser naik/turun kalau kurang pas */
            cursor: pointer;
        }

        .toggle-password-icon {
            width: 22px;
            height: 22px;
            opacity: 0.9;
        }

        @media(max-width: 900px) {
            .login-container { flex-direction: column; }
            .login-left, .login-right { width: 100%; }
            .login-left { min-height: 220px; }
        }
    </style>
</head>

<body class="login-body">
<header class="login-header">
    <img src="assets/images/logo-nganjuk.png" class="logo-kabupaten">
    <div class="desa-info">
        <h1>Desa Banjardowo</h1>
        <p>Kecamatan Lengkong, Kabupaten Nganjuk</p>
    </div>
</header>

<div class="login-container">

    <div class="login-left">
        <img src="assets/images/bg_sayap.jpg" class="img-bg-left">
        <div class="left-overlay-content">
            <img src="assets/images/logo-big.png" class="logo-desa">
            <h2>Hello,<br>Welcome!</h2>
            <p>Masuk untuk mengelola data dan layanan Desa Banjardowo.</p>
        </div>
    </div>

    <div class="login-right">
        <div class="icon-user">
            <svg viewBox="0 0 60 60">
                <circle cx="30" cy="22" r="14"/>
                <ellipse cx="30" cy="44" rx="18" ry="11"/>
            </svg>
        </div>

        <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
    <div class="form-group">
        <label>Username</label>
        <span class="icon-input">
            <img src="assets/icons/user.png" alt="User" class="input-icon-image">
        </span>
        <input type="text" name="username" required placeholder="Masukkan Username">
    </div>

   <div class="form-group password-group">
    <label>Password</label>

    <!-- icon kunci di kiri -->
    <span class="icon-input">
        <img src="assets/icons/icon_kunci.png" alt="Lock" class="input-icon-image">
    </span>

    <!-- input password (tambah id) -->
    <input type="password" id="passwordInput" name="password" required placeholder="Masukkan Password">

    <!-- icon mata di kanan -->
    <span class="toggle-password" onclick="togglePassword()">
        <img src="assets/icons/mata_buka.png" alt="Show Password" class="toggle-password-icon" id="eyeIcon">
    </span>
</div>

    <a href="lupa_password.php" class="forgot-password">Forgot Password?</a>
    <button type="submit" name="login" class="btn-login">LOGIN</button>
</form>

    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const eye   = document.getElementById('eyeIcon');

    if (!input || !eye) return;

    if (input.type === 'password') {
        // password kelihatan
        input.type = 'text';
        eye.src = 'assets/icons/mata_tutup.png';   // mata tertutup
        eye.alt = 'Hide Password';
    } else {
        // password disembunyikan
        input.type = 'password';
        eye.src = 'assets/icons/mata_buka.png';    // mata terbuka
        eye.alt = 'Show Password';
    }
}
</script>
</body>
</html>
