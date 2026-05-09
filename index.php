<?php
require_once 'config.php'; 
session_start();

// Jika ?logout=1 ada dalam URL, musnahkan sesi PHP
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
}

// Jika sudah log masuk, langkau halaman log masuk terus ke dashboard yang berkaitan
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}
if (isset($_SESSION['voter_noKP'])) {
    header("Location: utama.php");
    exit();
}

$message = "";

// --- LOGIK LOG MASUK ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['is_admin_login'])) {
    $admin_user = $_POST['admin_username'];
    $admin_pass = $_POST['admin_password'];

    // Kelayakan admin (Hardcoded berdasarkan kod asal)
    if ($admin_user === "admin" && $admin_pass === "123") {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $message = "<div class='message error'>Log masuk admin gagal! Nama pengguna atau katalaluan salah.</div>";
    }
}

// --- LOGIK LOG MASUK PENGUNDI ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['is_admin_login'])) {
    $noKP = $_POST['noKP'];
    $katalaluan = $_POST['katalaluan'];

    $stmt = $conn->prepare("
        SELECT p.noKP, v.nama 
        FROM pengguna p 
        JOIN pengundi v ON p.noKP = v.noKP 
        WHERE p.noKP = ? AND p.katalaluan = ?
    ");
    $stmt->bind_param("ss", $noKP, $katalaluan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $storedNoKP = $user['noKP'];
        $userName = $user['nama'];

        // Tetapkan sesi PHP
        $_SESSION['voter_noKP'] = $storedNoKP;
        $_SESSION['voter_name'] = $userName;

        // Tetapkan localStorage dan hantar ke halaman utama
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
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Sistem Undian</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Memastikan peralihan tema yang lancar */
        .spin { animation: spin 0.4s ease-in-out; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="centered-page">
<script>
    // 1. Semak dan laksanakan tema dengan segera untuk elakkan "white flash"
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }

    // 2. Tetapkan ikon yang betul sebaik sahaja halaman dimuatkan
    window.addEventListener('DOMContentLoaded', () => {
        const themeBtn = document.getElementById('theme-btn');
        if (themeBtn) {
            themeBtn.innerText = document.body.classList.contains('light-mode') ? '☀️' : '🌙';
        }
    });

    // 3. Fungsi Tukar Tema
    function toggleTheme() {
        var body = document.body;
        var themeBtn = document.getElementById('theme-btn');
        
        themeBtn.classList.remove('spin');
        void themeBtn.offsetWidth; // Paksa pelayar untuk mula semula animasi
        themeBtn.classList.add('spin');

        body.classList.toggle('light-mode');
        
        setTimeout(() => {
            if (body.classList.contains('light-mode')) {
                localStorage.setItem('theme', 'light');
                themeBtn.innerText = '☀️';
            } else {
                localStorage.setItem('theme', 'dark');
                themeBtn.innerText = '🌙';
            }
        }, 200); 
    }

    // 4. Halang butang 'Back' pelayar
    window.history.forward();
    function noBack() { window.history.forward(); }
    setTimeout("noBack()", 0);
    window.onunload = function () { null };
</script>

    <div class="page-wrapper">
        <div class="container">
            <div class="header">
                <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
            </div>
            <div class="content">
                <p style="text-align: center; color: #94a3b8; margin-bottom: 20px;">
                    Selamat kembali! Sila masukkan maklumat anda untuk log masuk.
                </p>
                
                <?php echo $message; ?>

                <form action="index.php" method="POST">
                    <div class="form-group">
                        <label>No. Kad Pengenalan :</label>
                        <input type="text" name="noKP" id="noKP" required placeholder="Contoh: 000000-00-0000"
                            maxlength="14"
                            oninput="formatNoKP(this)">
                    </div>
                    <div class="form-group">
                        <label>Katalaluan :</label>
                        <input type="password" name="katalaluan" required placeholder="Masukkan katalaluan anda">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;" onclick="return sahkanBorang()">Log Masuk</button>
                </form>

                <script>
                    function formatNoKP(input) {
                        let val = input.value.replace(/\D/g, '');
                        if (val.length > 6)  val = val.slice(0,6) + '-' + val.slice(6);
                        if (val.length > 9)  val = val.slice(0,9) + '-' + val.slice(9);
                        if (val.length > 14) val = val.slice(0,14);
                        input.value = val;
                    }

                    function sahkanBorang() {
                        const noKP = document.getElementById('noKP').value.trim();
                        if (!/^\d{6}-\d{2}-\d{4}$/.test(noKP)) {
                            alert('Format No. KP tidak sah. Sila gunakan format: 000000-00-0000');
                            return false;
                        }
                        return true;
                    }
                </script>

                <div class="links">
                    <p>Admin? <a href="#" onclick="document.getElementById('adminModal').style.display='flex'">Klik di sini</a></p>
                    <p>Belum Daftar? <a href="daftar.php">Klik di sini untuk mendaftar</a></p>
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
            <form action="index.php" method="POST">
                <input type="hidden" name="is_admin_login" value="1">
                
                <div class="form-group">
                    <label>Nama Pengguna :</label>
                    <input type="text" name="admin_username" required placeholder="Masukkan nama pengguna admin">
                </div>
                
                <div class="form-group">
                    <label>Katalaluan :</label>
                    <input type="password" name="admin_password" required placeholder="Masukkan katalaluan admin">
                </div>

                <div class="btn-container" style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('adminModal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Log Masuk</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>