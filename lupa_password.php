<?php
session_start();
include "config/db.php";
include "config/phpmailer/PHPMailer.php";
include "config/phpmailer/SMTP.php";
include "config/phpmailer/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if (isset($_POST['kirim_otp'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='{$email}' LIMIT 1"));

    if ($user) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['reset_user'] = $user['username'];
        $_SESSION['otp_expiry'] = time() + 300; 

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
            $message = "<p class='message success'>Kode OTP telah dikirim ke email Anda.</p>";
        } catch (Exception $e) {
            $message = "<p class='message error'>Gagal mengirim kode OTP. Silakan coba lagi.</p>";
        }
    } else {
        $message = "<p class='message error'>Email tidak ditemukan di sistem!</p>";
    }
}

if (isset($_POST['verifikasi'])) {
    $otp_input = $_POST['otp'];
    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $otp_input && time() < $_SESSION['otp_expiry']) {
        header("Location: reset_password.php");
        exit;
    } else {
        $message = "<p class='message error'>Kode OTP salah atau sudah kadaluarsa!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lupa Password - E-Deslay</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
</head>
<body class="login-body">

<div class="login-container">
    <div class="glass-card">
        <h2>Kode OTP akan dikirimkan ke email Anda</h2>
        <?= $message ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    required placeholder="Masukkan Email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    <?php echo isset($_SESSION['otp']) ? 'readonly' : ''; ?>
                >
            </div>

            <div class="form-group otp-group">
                <label for="otp">Kode OTP</label>
                <input 
                    type="text" 
                    name="otp" 
                    id="otp" 
                    placeholder="Masukkan Kode OTP"
                >
                <button type="submit" name="kirim_otp" class="btn-send-otp">Kirim Kode</button>
            </div>

        <button type="submit" name="verifikasi" class="btn-login">Verifikasi</button>
        <a href="login.php" class="forgot-password">Kembali ke Halaman Login</a>
        </form>
    </div>
</div>

</body>
</html>
