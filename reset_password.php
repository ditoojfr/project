<?php
session_start();
include "config/db.php";

// Proteksi: hanya bisa akses jika sudah verifikasi OTP
if (!isset($_SESSION['reset_user'])) {
    header("Location: lupa_password.php");
    exit;
}

$message = '';

if (isset($_POST['reset'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi: password minimal 8 karakter
    if (strlen($password) < 8) {
        $message = "<div class='alert alert-error'>Password minimal 8 karakter.</div>";
    }
    // Validasi: konfirmasi password harus sama
    elseif ($password !== $confirm_password) {
        $message = "<div class='alert alert-error'>Konfirmasi password tidak cocok.</div>";
    }
    else {
        $new_pass = md5($password); // ⚠️ Hindari MD5 di produksi, gunakan password_hash()
        $user = $_SESSION['reset_user'];

        $result = mysqli_query($conn, "UPDATE users SET password='{$new_pass}' WHERE username='{$user}'");

        if ($result) {
            session_unset();
            session_destroy();
            header("Location: login.php?pesan=reset_sukses");
            exit;
        } else {
            $message = "<div class='alert alert-error'>Gagal mereset password. Silakan coba lagi.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - E-Deslay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: url('assets/images/bg-bulu.jpeg') no-repeat right center;
            background-size: auto 100%;
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Header Logo Desa di Pojok Kiri Atas */
        .header-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10;
        }

        .header-logo img {
            width: 50px;
        }

        .header-logo h1 {
            font-size: 16px;
            color: #2c3e50;
            line-height: 1.4;
        }

        .header-logo p {
            font-size: 14px;
            color: #7f8c8d;
        }

        /* Form Card di Tengah */
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .form-card img.logo-e-deslay {
            height: 40px;
            margin-bottom: 20px;
        }

        .form-card h2 {
            font-size: 20px;
            color: #2c3e50;
            margin: 15px 0;
            line-height: 1.4;
        }

        .form-card p.subtitle {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 15px 0;
            position: relative;
        }

        .form-group label {
            font-size: 14px;
            color: #34495e;
            font-weight: 500;
            text-align: left;
        }

        .form-group input {
            padding: 12px 40px 12px 16px;
            border: 1px solid #bdc3c7;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            position: relative;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 65%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
            font-size: 16px;
            z-index: 2;
        }

        .btn-primary {
            padding: 12px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            margin: 20px 0;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .back-link {
            font-size: 14px;
        }

        .back-link a {
            color: #3498db;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6da;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 30px 20px;
            }
            .header-logo {
                top: 15px;
                left: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Logo Desa di Pojok Kiri Atas -->
    <div class="header-logo">
        <img src="assets/images/logo-nganjuk" alt="Logo Desa">
        <div>
            <h1>Desa Banjardowo</h1>
            <p>Kecamatan Lengkong</p>
        </div>
    </div>

    <!-- Form Card di Tengah -->
    <div class="form-card">
        <img src="assets/images/logo-big.png" alt="E-Deslay Logo" class="logo-e-deslay">
        <h2>Reset Password</h2>
        <p class="subtitle">Password harus minimal 8 karakter</p>

        <?php if ($message): ?>
            <?= $message ?>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" required placeholder="Masukkan new password">
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Konfirmasi new password">
                <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
            </div>

            <button type="submit" name="reset" class="btn-primary">Simpan new password</button>

            <div class="back-link">
                <a href="lupa_password.php">Kembali ke Halaman Kode OTP</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
        }
    </script>

</body>
</html>