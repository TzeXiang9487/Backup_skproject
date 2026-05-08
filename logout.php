<?php
session_start();

// Buang semua pembolehubah session
$_SESSION = array();

// Musnahkan session pada pelayan
session_destroy();

// Hapus kuki session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect ke login dengan mesej
echo "<script>alert('Anda telah berjaya log keluar.'); window.location.replace('index.php');</script>";
exit();
?>