<?php
session_start();
require_once 'config.php';

// SEMAKAN DIPERBAIKI: Gunakan nama sesi yang betul dari login.php
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true; 
$is_user = isset($_SESSION['voter_noKP']); 

// Jika tiada sesi langsung, tendang keluar
if (!$is_admin && !$is_user) {
    header("Location: login.php");
    exit();
}

// SQL Query: Kira undian untuk setiap calon dan susun dari tertinggi ke terendah
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
        /* Print-friendly styling */
        @media print {
            .nav-bar, .btn-cetak, .footer { display: none !important; }
            body, .container, .page-wrapper { background: white !important; color: black !important; }
            .card { border: 1px solid black !important; background: white !important; color: black !important; break-inside: avoid; }
            .card h3, .card h4, .card p, .card span { color: black !important; }
            .grid-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper" style="max-width: 1000px; margin: 0 auto;">
        <div class="container">
            
            <?php if ($is_admin): ?>
                <div class="header">Panel Kawalan Admin - Keputusan</div>
                <div class="nav-bar">
                    <a href="admin.php" class="nav-item">Dashboard Admin</a>
                    <a href="keputusan.php" class="nav-item active">Keputusan Undian</a>
                    <a href="logout.php" class="nav-item">Keluar (Admin)</a>
                </div>
            <?php else: ?>
                <div class="header">Sistem D'Undi Pertandingan Penciptaan Permainan Video</div>
                <div class="nav-bar">
                    <a href="utama.php" class="nav-item">Laman Utama</a>
                    <a href="undian.php" class="nav-item">Undian</a>
                    <a href="keputusan.php" class="nav-item active">Keputusan</a>
                    <a href="logout.php" class="nav-item">Keluar</a>
                </div>
            <?php endif; ?>

            <div class="content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h2 style="color: var(--saber-gold);">Kedudukan Terkini</h2>
                    <button onclick="window.print()" class="btn btn-primary btn-cetak">Cetak</button>
                </div>

                <div class="grid-container" style="justify-content: center; align-items: stretch;">
                    <?php
                    if ($result->num_rows > 0) {
                        $kedudukan = 1;
                        // Array untuk menukar nombor kepada perkataan Melayu
                        $tempat_nama = ["", "Pertama", "Kedua", "Ketiga", "Keempat", "Kelima"];

                        while ($row = $result->fetch_assoc()) {
                            
                            // --- TEMPAT UNTUK MASUKKAN LOKASI GAMBAR ---
                            $lokasi_gambar = "image/placeholder.jpg"; 

                            if ($row['namaCalon'] == 'Hollow Knight') {
                                $lokasi_gambar = "image/C01.jpg";
                            } else if ($row['namaCalon'] == 'NineSols' || $row['namaCalon'] == 'Nine Sols') {
                                $lokasi_gambar = "image/C02.jpg";
                            } else if ($row['namaCalon'] == 'Cuphead') {
                                $lokasi_gambar = "image/C03.png";
                            }
                            // --------------------------------------------

                            // Tentukan warna teks untuk kedudukan (Emas untuk Pertama, Perak untuk Kedua, Gangsa untuk Ketiga)
                            $warna_tempat = "var(--saber-gold)"; // Default Gold
                            if ($kedudukan == 2) $warna_tempat = "var(--saber-armor-light)"; // Silver
                            if ($kedudukan == 3) $warna_tempat = "#d97706"; // Bronze

                            echo "<div class='card' style='max-width: 300px; margin: 0 auto; display: flex; flex-direction: column; background-color: var(--saber-dark); padding: 15px; border-radius: 8px; border: 1px solid var(--saber-armor-dark);'>";
                            
                            // Rank / Kedudukan
                            echo "<h3 style='color: {$warna_tempat}; margin-bottom: 10px; font-size: 1.4rem; text-align: center;'>Tempat " . $tempat_nama[$kedudukan] . "</h3>";
                            
                            // Nama Permainan
                            echo "<h4 style='color: white; margin-bottom: 10px; font-size: 1.2rem; text-align: center;'>" . htmlspecialchars($row['namaCalon']) . "</h4>";
                            
                            // Jumlah Undian
                            echo "<p style='color: var(--saber-armor-light); font-weight: bold; margin-bottom: 15px; text-align: center;'>Jumlah Undian: <span style='color: var(--saber-blue); font-size: 1.3rem;'>" . htmlspecialchars($row['jumlah_undian']) . "</span></p>";
                            
                            // Gambar Permainan
                            echo "<div style='margin-top: auto;'>";
                            echo "<img src='{$lokasi_gambar}' onerror=\"this.src='image/placeholder.jpg';\" alt='" . htmlspecialchars($row['namaCalon']) . "' style='width: 100%; height: auto; border-radius: 4px; border: 1px solid var(--saber-armor-dark);'>";
                            echo "</div>";

                            echo "</div>";

                            $kedudukan++;
                        }
                    } else {
                        echo "<p style='text-align: center; width: 100%; color: var(--saber-armor-light);'>Belum ada undian direkodkan.</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>