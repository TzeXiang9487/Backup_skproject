<?php
session_start();

if (!isset($_SESSION['voter_noKP'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Laman Utama - Sistem Undian</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .welcome-text {
            color: #3b82f6;
            margin-bottom: 20px;
        }
        body.light-mode .welcome-text {
            color: #d4af37;
        }
    </style>
</head>
<body>
<script>
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }

    window.addEventListener('DOMContentLoaded', () => {
        const themeBtn = document.getElementById('theme-btn');
        if (themeBtn) {
            themeBtn.innerText = document.body.classList.contains('light-mode') ? '☀️' : '🌙';
        }
    });

    function toggleTheme() {
        var body = document.body;
        var themeBtn = document.getElementById('theme-btn');
        
        themeBtn.classList.remove('spin');
        void themeBtn.offsetWidth;
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
                <a href="logout.php" class="nav-item">Keluar</a>
            </div>

            <div class="content" style="text-align: center;">
                <h2 class="welcome-text">
                    Selamat Datang, <?php echo htmlspecialchars($_SESSION['voter_name']); ?>!
                </h2>

                <p style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px; color: var(--text-color);">
                    Sertai undian untuk menentukan ciptaan permainan video terbaik bagi tahun ini! 
                    Sila teliti setiap calon yang bertanding, kemudian pilih dan undi permainan video yang paling anda gemari. 
                    Satu undian anda amat bermakna dalam menghargai bakat para pencipta muda.
                </p>
                
                <div style="display: flex; justify-content: center; gap: 15px;">
                    <button class="btn btn-primary" onclick="window.location.href='undian.php'" style="max-width: 250px; padding: 15px 30px; font-weight: bold;">
                        Mula Mengundi
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.href='keputusan.php'" style="max-width: 250px; padding: 15px 30px;">
                        Lihat Keputusan
                    </button>
                </div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <script>
        localStorage.setItem('voter_name', '<?php echo addslashes($_SESSION['voter_name']); ?>');
        localStorage.setItem('voter_noKP', '<?php echo addslashes($_SESSION['voter_noKP']); ?>');

        function keluarAkaun() {
            if (confirm("Adakah anda pasti mahu log keluar?")) {
                localStorage.removeItem('voter_noKP');
                localStorage.removeItem('voter_name');
                window.location.href = 'logout.php'; 
            }
        }
    </script>
</body>
</html>