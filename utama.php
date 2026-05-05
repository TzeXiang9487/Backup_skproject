<?php
session_start();
if (!isset($_SESSION['voter_noKP'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Laman Utama - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
            
            <div class="nav-bar">
                <a href="utama.php" class="nav-item active">Laman Utama</a>
                <a href="undian.php" class="nav-item">Undian</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="#" class="nav-item" onclick="keluarAkaun()">Keluar</a>
            </div>

            <div class="content" style="text-align: center;">
                <h2 style="color: #3b82f6; margin-bottom: 20px;">
                    Selamat Datang, <?php echo htmlspecialchars($_SESSION['voter_name']); ?>!
                </h2>

                <p style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px;">
                    Sertai undian untuk menentukan ciptaan permainan video terbaik! 
                    Lihat semua pilihan yang ada, kemudian pilih dan undi permainan video yang paling anda gemari. 
                    Terima kasih kerana menyokong para pencipta!
                </p>
                
                <button class="btn btn-primary" onclick="window.location.href='undian.php'" style="max-width: 300px;">
                    Undi Permainan Video
                </button>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <script>
        // Keep localStorage in sync with session
        localStorage.setItem('voter_name', '<?php echo addslashes($_SESSION['voter_name']); ?>');
        localStorage.setItem('voter_noKP', '<?php echo addslashes($_SESSION['voter_noKP']); ?>');

        // ✅ LOGOUT FUNCTION
        function keluarAkaun() {
            localStorage.removeItem('voter_noKP');
            localStorage.removeItem('voter_name');
            window.location.href = 'login.php?logout=1';
        }
    </script>
</body>
</html>