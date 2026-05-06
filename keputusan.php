<?php
session_start();
require_once 'config.php';

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true; 
$is_user = isset($_SESSION['voter_noKP']); 

if (!$is_admin && !$is_user) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT c.namaCalon, COUNT(p.idCalon) as jumlah_undian 
        FROM calon c
        LEFT JOIN pengundian p ON c.idCalon = p.idCalon
        GROUP BY c.idCalon, c.namaCalon
        ORDER BY jumlah_undian DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Keputusan Undian - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* =========================================
           FULL SCREEN & NO SCROLL LAYOUT
           ========================================= */
        html, body {
            overflow: hidden; /* Force hide the scrollbar */
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            height: 100vh; /* Exactly 100% of screen height */
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .content {
            flex: 1; /* Automatically stretches to fill remaining space */
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: center;
            overflow: hidden;
            padding: 10px 20px !important;
        }

        /* Container holding the cards */
        .ranking-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: nowrap; /* FORCES cards to stay on one line */
            width: 100%;
            max-width: 1200px;
        }

        /* Make cards responsive so they squeeze instead of pushing down */
        .ranking-card {
            flex: 1 1 0; /* Let cards grow and shrink evenly */
            max-width: 250px; /* Don't get bigger than this */
            min-width: 150px; /* Don't get smaller than this */
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Responsive square images */
        .ranking-image-container {
            width: 100%;
            aspect-ratio: 1 / 1; /* Always perfectly square based on width */
            height: auto;
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
        }

        .ranking-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ranking-label {
            width: 100%;
            padding: 8px 0;
        }
    </style>
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
            
            <?php if ($is_admin): ?>
                <div class="header">
                    <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                    <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
                </div>
                <div class="nav-bar">
                    <a href="admin.php" class="nav-item">Dashboard Admin</a>
                    <a href="import.php" class="nav-item">Import</a>
                    <a href="keputusan.php" class="nav-item active">Keputusan</a>
                    <a href="#" class="nav-item" onclick="keluarAkaun()">Keluar (Admin)</a>
                </div>
            <?php else: ?>
                <div class="header">
                    <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                    <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
                </div>
                <div class="nav-bar">
                    <a href="utama.php" class="nav-item">Laman Utama</a>
                    <a href="undian.php" class="nav-item">Undian</a>
                    <a href="keputusan.php" class="nav-item active">Keputusan</a>
                    <a href="#" class="nav-item" onclick="keluarAkaun()">Keluar</a>
                </div>
            <?php endif; ?>

            <div class="content">

                <h2 style="color: #3b82f6; text-align: center; margin-bottom: 20px;">
                    Berikut ialah keputusan Undi !
                </h2>

                <div class="ranking-container">
                    
                    <?php
                    if ($result->num_rows > 0) {
                        $tempat_nama = array(1 => "Pertama", 2 => "Kedua", 3 => "Ketiga", 4 => "Keempat", 5 => "Kelima");

                        $kedudukan = 1;       // Actual position counter (always increments)
                        $rank_display = 1;    // Rank shown to user (only updates when votes differ)
                        $prev_undi = null;    // Previous row's vote count

                        while ($row = $result->fetch_assoc()) {
                            $lokasi_gambar = "image/placeholder.jpg"; 
                            if ($row['namaCalon'] == 'Hollow Knight') {
                                $lokasi_gambar = "image/C01.jpg";
                            } else if ($row['namaCalon'] == 'NineSols' || $row['namaCalon'] == 'Nine Sols') {
                                $lokasi_gambar = "image/C02.jpg";
                            } else if ($row['namaCalon'] == 'Cuphead') {
                                $lokasi_gambar = "image/C03.png";
                            }

                            // If vote count differs from previous, update displayed rank to actual position
                            if ($prev_undi !== null && $row['jumlah_undian'] < $prev_undi) {
                                $rank_display = $kedudukan;
                            }

                            $label_kedudukan = isset($tempat_nama[$rank_display]) ? $tempat_nama[$rank_display] : "Ke-" . $rank_display;

                            echo "<div class='ranking-card'>";

                            // New Responsive Image Box
                            echo "  <div class='ranking-image-container'>";
                            echo "      <img src='{$lokasi_gambar}' alt='" . htmlspecialchars($row['namaCalon']) . "'>";
                            echo "  </div>";

                            // Ranking label
                            echo "  <div class='ranking-label'>";
                            echo        $label_kedudukan;
                            echo "  </div>";

                            // Name and vote count
                            echo "  <div class='ranking-name'>";
                            echo        htmlspecialchars($row['namaCalon']) . "<br>";
                            echo "      <span class='ranking-votes'>(" . $row['jumlah_undian'] . " Undi)</span>";
                            echo "  </div>";

                            echo "</div>";

                            $prev_undi = $row['jumlah_undian'];
                            $kedudukan++;
                        }
                    } else {
                        echo "<p class='empty-message'>Belum ada keputusan undian direkodkan.</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="footer">
                Hak Cipta Goh Tze Xiang @ SPM 2025
            </div>
        </div>
    </div>

    <script>
        function keluarAkaun() {
            localStorage.removeItem('voter_noKP');
            localStorage.removeItem('voter_name');
            window.location.href = 'login.php?logout=1';
        }
    </script>
</body>
</html>
