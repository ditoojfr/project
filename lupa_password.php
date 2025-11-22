<?php
session_start();
include "config/db.php";
include "config/phpmailer/PHPMailer.php";
include "config/phpmailer/SMTP.php";
include "config/phpmailer/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$show_otp_input = false;
$email_sent = false;

if (isset($_POST['kirim_otp'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='{$email}' LIMIT 1"));

    if ($user) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['reset_user'] = $user['username'];
        $_SESSION['otp_expiry'] = time() + 300; // 5 menit

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'djefriandana@gmail.com'; 
            $mail->Password   = 'yhod yrdg ovog qkjs'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('djefriandana@gmail.com', 'E-Deslay');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Reset Password E-Deslay';
            $mail->Body    = "
                <h3>Halo, {$user['username']}!</h3>
                <p>Kode OTP untuk reset password akun Anda adalah:</p>
                <h2>{$otp}</h2>
                <p>Kode ini berlaku selama 5 menit.</p>
            ";
            $mail->send();
            $message = "<div class='alert alert-success'>Kode OTP telah dikirim ke email Anda.</div>";
            $show_otp_input = true;
            $email_sent = true;
        } catch (Exception $e) {
            $message = "<div class='alert alert-error'>Gagal mengirim kode OTP. Silakan coba lagi.</div>";
        }
    } else {
        $message = "<div class='alert alert-error'>Email tidak ditemukan di sistem!</div>";
    }
}

if (isset($_POST['verifikasi'])) {
    $otp_input = $_POST['otp'];
    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $otp_input && time() < $_SESSION['otp_expiry']) {
        header("Location: reset_password.php");
        exit;
    } else {
        $message = "<div class='alert alert-error'>Kode OTP salah atau sudah kadaluarsa!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - E-Deslay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: url('assets/images/bg-bulu.jpeg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background-color: #f0f5fa;
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

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 20px 0;
        }

        .form-group label {
            font-size: 14px;
            color: #34495e;
            font-weight: 500;
            text-align: left;
        }

        .form-group input {
            padding: 12px 16px;
            border: 1px solid #bdc3c7;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .send-code {
            font-size: 12px;
            color: #7f8c8d;
            text-align: right;
            margin-top: -5px;
            cursor: pointer;
        }

        .send-code-btn {
            background: none;
            border: none;
            color: #0531f8ff;
            font-size: 15px;
            cursor: pointer;
            text-align: right;
            display: block;
            width: fit-content;
            margin-left: auto;
            margin-top: 10px;
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
            margin: 10px 0;
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
        <img src="assets/images/logo-nganjuk.png" alt="Logo Desa">
        <div>
            <h1>Desa Banjardowo</h1>
            <p>Kecamatan Lengkong</p>
        </div>
    </div>

    <!-- Form Card di Tengah -->
    <div class="form-card">
        <img src="assets/images/logo-big.png" alt="E-Deslay Logo" class="logo-e-deslay">
        <h2>Kode OTP akan dikirimkan ke email Anda</h2>
        <?php echo $message; ?>

        <!-- Form -->
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Masukkan Email" required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            </div>

            <div class="form-group">
                <label for="otp">Kode OTP</label>
                <input type="text" name="otp" id="otp" placeholder="Masukkan Kode OTP" maxlength="6"
                >
                <button type="submit" name="kirim_otp" class="send-code-btn">Kirim Kode</button>
            </div>
            <button type="submit" name="verifikasi" class="btn-primary">Verifikasi</button>

            <div class="back-link">
                <a href="login.php">Kembali ke Halaman Login</a>
            </div>
            </form>
    </div>

</body>
</html>