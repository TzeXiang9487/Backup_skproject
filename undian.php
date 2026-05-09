<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['voter_noKP'])) {
    header("Location: index.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_GET['vote_id']) && isset($_GET['voter_ic'])) {
    $idCalon = $_GET['vote_id'];
    $noKP = $_GET['voter_ic']; 
    $tarikh = date("Y-m-d");

    $sql_check = "SELECT * FROM PENGUNDIAN WHERE noKP = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $noKP);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<script>alert('Error: You have already voted! Each user can only vote once.'); window.location.href='undian.php';</script>";
    } else {
        $sql_vote = "INSERT INTO PENGUNDIAN (noKP, tarikh, idCalon) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_vote);
        $stmt->bind_param("sss", $noKP, $tarikh, $idCalon);

        if ($stmt->execute()) {
            echo "<script>alert('Undian Berjaya!'); window.location.href='keputusan.php';</script>";
        } else {
            echo "<script>alert('Ralat semasa merekod undian.'); window.location.href='undian.php';</script>";
        }
        $stmt->close();
    }
    $stmt_check->close();
    exit();
}

$sql_calon = "SELECT c.*, 
             (SELECT COUNT(*) FROM PENGUNDIAN p WHERE p.idCalon = c.idCalon) as jumlah_undi 
             FROM CALON c";
$result_calon = $conn->query($sql_calon);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Undian - Game Dev Vote</title>
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
            
            <div class="header">
                <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()"  >🌙</button>
            </div>
            
            <div class="nav-bar">
                <a href="utama.php" class="nav-item">Laman Utama</a>
                <a href="undian.php" class="nav-item active">Undian</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="logout.php" class="nav-item">Keluar</a>
            </div>
            
            <div class="content">
                <h2 class="senarai-title">Senarai Calon Pertandingan Permainan Video</h2>
                <p style="text-align: center; color: #94a3b8; margin-bottom: 10px; font-size: 1rem;">
                    Sila hover (halakan kursor) pada gambar untuk melihat maklumat, kemudian undi permainan video pilihan anda!
                </p>

                <div class="voting-container">
                    <?php
                    if ($result_calon->num_rows > 0) {
                        while($row = $result_calon->fetch_assoc()) {
                            $lokasi_gambar = "image/placeholder.jpg";
                            $keterangan = "Sebuah ciptaan permainan video yang hebat.";

                            if ($row['namaCalon'] == 'Hollow Knight') {
                                $lokasi_gambar = "image/C01.jpg";
                                $keterangan = "Terokai dunia serangga bawah tanah yang luas dan misteri dalam permainan aksi-pengembaraan 2D (Metroidvania) yang sangat menakjubkan dan mencabar ini.";
                            } else if ($row['namaCalon'] == 'NineSols' || $row['namaCalon'] == 'Nine Sols') {
                                $lokasi_gambar = "image/C02.jpg";
                                $keterangan = "Permainan platformer aksi 2D yang dilukis tangan dengan sistem pertempuran berfokuskan pesongan (deflection) pantas, diilhamkan oleh Sekiro dalam dunia siber-fantasi yang unik.";
                            } else if ($row['namaCalon'] == 'Cuphead') {
                                $lokasi_gambar = "image/C03.png";
                                $keterangan = "Permainan aksi tembak-menembak (run and gun) klasik yang memfokuskan kepada pertarungan bos epik, dengan gaya seni kartun era 1930-an lukisan tangan yang sangat retro.";
                            }
                            ?>
                            
                            <div class="candidate-card">
                                <div class="card-left">
                                    <img src="<?php echo $lokasi_gambar; ?>" onerror="this.src='image/placeholder.jpg';" alt="<?php echo htmlspecialchars($row['namaCalon']); ?>">
                                </div>
                                
                                <div class="card-right">
                                    <h3 class="game-title"><?php echo htmlspecialchars($row['namaCalon']); ?></h3>
                                    <span class="candidate-count"><?php echo $row['jumlah_undi']; ?> Undi Terkumpul</span>
                                    <p class="game-desc"><?php echo $keterangan; ?></p>
                                    <button type="button" onclick="prosesUndian('<?php echo $row['idCalon']; ?>')" class="btn-vote">
                                        Undi Sekarang
                                    </button>
                                </div>
                            </div>
                            
                            <?php
                        }
                    } else {
                        echo "<p class='empty-message'>Tiada calon dijumpai dalam pangkalan data.</p>";
                    }
                    ?>
                </div>
            </div>
            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <script>
        function prosesUndian(idCalon) {
            const noKP = localStorage.getItem('voter_noKP');
            
            if (!noKP) {
                alert("Sila log masuk terlebih dahulu sebelum mengundi!");
                window.location.href = 'index.php';
                return;
            }
            
            if (confirm("Adakah anda pasti mahu mengundi ciptaan permainan video ini? Anda hanya boleh mengundi sekali sahaja.")) {
                window.location.href = 'undian.php?vote_id=' + idCalon + '&voter_ic=' + noKP;
            }
        }

        function keluarAkaun() {
            localStorage.removeItem('voter_noKP');
            localStorage.removeItem('voter_name');
            window.location.href = 'logout.php'; 
        }
    </script>
</body>
</html>