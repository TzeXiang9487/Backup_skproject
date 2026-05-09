<?php
session_start();
require_once 'config.php';

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true; 
$is_user = isset($_SESSION['voter_noKP']); 

if (!$is_admin && !$is_user) {
    header("Location: index.php");
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
            
            <?php if ($is_admin): ?>
                <div class="header">
                    <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                    <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
                </div>
                <div class="nav-bar">
                    <a href="admin.php" class="nav-item">Papan Pemuka</a>
                    <a href="import.php" class="nav-item">Import</a>
                    <a href="keputusan.php" class="nav-item active">Keputusan</a>
                    <a href="logout.php" class="nav-item">Keluar</a>
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
                    <a href="logout.php" class="nav-item">Keluar</a>
                </div>
            <?php endif; ?>

            <div class="content">

                <h2 class="content-title">
                    Berikut ialah keputusan Undi !
                </h2>

                <div class="ranking-container">
                    
                    <?php
                    if ($result->num_rows > 0) {
                        $tempat_nama = array(1 => "Pertama", 2 => "Kedua", 3 => "Ketiga", 4 => "Keempat", 5 => "Kelima");

                        $kedudukan = 1;       // Kaunter kedudukan sebenar (sentiasa bertambah)
                        $rank_display = 1;    // Kedudukan ditunjukkan kepada pengguna (hanya dikemas kini apabila undian berbeza)
                        $prev_undi = null;    // Kiraan undi baris sebelumnya

                        while ($row = $result->fetch_assoc()) {
                            $lokasi_gambar = "image/placeholder.jpg"; 
                            if ($row['namaCalon'] == 'Hollow Knight') {
                                $lokasi_gambar = "image/C01.jpg";
                            } else if ($row['namaCalon'] == 'NineSols' || $row['namaCalon'] == 'Nine Sols') {
                                $lokasi_gambar = "image/C02.jpg";
                            } else if ($row['namaCalon'] == 'Cuphead') {
                                $lokasi_gambar = "image/C03.png";
                            }

                            // Jika kiraan undi berbeza daripada sebelumnya, kemas kini kedudukan yang dipaparkan kepada kedudukan sebenar
                            if ($prev_undi !== null && $row['jumlah_undian'] < $prev_undi) {
                                $rank_display = $kedudukan;
                            }

                            $label_kedudukan = isset($tempat_nama[$rank_display]) ? $tempat_nama[$rank_display] : "Ke-" . $rank_display;

                            echo "<div class='ranking-card'>";

                            echo "  <div class='ranking-image-container'>";
                            echo "      <img src='{$lokasi_gambar}' alt='" . htmlspecialchars($row['namaCalon']) . "'>";
                            echo "  </div>";

                            echo "  <div class='ranking-label'>";
                            echo        $label_kedudukan;
                            echo "  </div>";

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
    
    window.location.href = 'logout.php'; 
    }
    </script>
</body>
</html>