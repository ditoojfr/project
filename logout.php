<?php
session_start();

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session jika pakai
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke login (bukan index.php) — ini sesuai kebutuhan Anda
header("Location: index.php");
exit;
?>