<?php
require_once 'config.php'; 
session_start();

// ✅ LOGOUT: If ?logout=1 is in the URL, destroy the PHP session
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
}

// ✅ FEATURE: If already logged in, skip login page entirely
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}
if (isset($_SESSION['voter_noKP'])) {
    header("Location: utama.php");
    exit();
}

$message = "";

// --- ADMIN LOGIN LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['is_admin_login'])) {
    $admin_user = $_POST['admin_username'];
    $admin_pass = $_POST['admin_password'];

    if ($admin_user === "admin" && $admin_pass === "123") {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $message = "<div class='message error'>Log masuk admin gagal! Nama pengguna atau katalaluan salah.</div>";
    }
}

// 1. Handle Login Verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['is_admin_login'])) {
    $noKP = $_POST['noKP'];
    $katalaluan = $_POST['katalaluan'];

    $stmt = $conn->prepare("
        SELECT p.noKP, v.nama 
        FROM pengguna p 
        JOIN PENGUNDI v ON p.noKP = v.noKP 
        WHERE p.noKP = ? AND p.katalaluan = ?
    ");
    $stmt->bind_param("ss", $noKP, $katalaluan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $storedNoKP = $user['noKP'];
        $userName = $user['nama'];

        // Set PHP session
        $_SESSION['voter_noKP'] = $storedNoKP;
        $_SESSION['voter_name'] = $userName;

        // Set localStorage and redirect
        echo "<script>
            localStorage.setItem('voter_noKP', '" . addslashes($storedNoKP) . "');
            localStorage.setItem('voter_name', '" . addslashes($userName) . "');
            window.location.href = 'utama.php';
        </script>";
        exit();
    } else {
        $message = "<div class='message error'>No. KP atau Katalaluan salah. Sila cuba lagi.</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="centered-page">
<script>
    // 1. Check and apply theme immediately to prevent white flashes
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }

    // 2. Set the correct icon (Sun or Moon) as soon as the page loads
    window.addEventListener('DOMContentLoaded', () => {
        const themeBtn = document.getElementById('theme-btn');
        if (themeBtn) {
            themeBtn.innerText = document.body.classList.contains('light-mode') ? '☀️' : '🌙';
        }
    });

    // 3. The Toggle Function
    function toggleTheme() {
        var body = document.body;
        var themeBtn = document.getElementById('theme-btn');
        
        // Remove and re-add the 'spin' class to trigger the CSS animation
        themeBtn.classList.remove('spin');
        void themeBtn.offsetWidth; // This forces the browser to restart the animation
        themeBtn.classList.add('spin');

        // Switch the theme
        body.classList.toggle('light-mode');
        
        // Halfway through the animation (200ms), swap the icon so it looks seamless
        setTimeout(() => {
            if (body.classList.contains('light-mode')) {
                localStorage.setItem('theme', 'light');
                themeBtn.innerText = '☀️'; // Change to Sun
            } else {
                localStorage.setItem('theme', 'dark');
                themeBtn.innerText = '🌙'; // Change to Moon
            }
        }, 200); 
    }
</script>
    <div class="page-wrapper">
        <div class="container">
            <div class="header">
                <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
            </div>
            <div class="content">
                <p style="text-align: center; color: #94a3b8; margin-bottom: 20px;">
                    Welcome back! Please enter your credentials to log in.
                </p>
                
                <?php echo $message; ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label>No. Kad Pengenalan :</label>
                        <input type="text" name="noKP" required placeholder="000000-00-0000">
                    </div>
                    <div class="form-group">
                        <label>Katalaluan :</label>
                        <input type="password" name="katalaluan" required placeholder="Sila masukkan katalaluan">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Log Masuk</button>
                </form>

                <div class="links">
                    <p>Admin? <a href="#" onclick="document.getElementById('adminModal').style.display='flex'">Sila klik di sini.</a></p>
                    <p>Belum Daftar? <a href="register.php">Sila klik di sini.</a></p>
                </div>
            </div>
            <div class="footer">
                Hak Cipta Goh Tze Xiang @ SPM 2025
            </div>
        </div>
    </div>

    <div id="adminModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Log Masuk Admin</h3>
            <form action="login.php" method="POST">
                <input type="hidden" name="is_admin_login" value="1">
                
                <div class="form-group">
                    <label>Nama Pengguna :</label>
                    <input type="text" name="admin_username" required placeholder="Sila masukkan nama pengguna">
                </div>
                
                <div class="form-group">
                    <label>Katalaluan :</label>
                    <input type="password" name="admin_password" required placeholder="Sila masukkan katalaluan">
                </div>

                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('adminModal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Log Masuk</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>