<?php
session_start();
require_once 'config.php';

// 2. SECURITY CHECK: Kick out if not logged in
if (!isset($_SESSION['voter_noKP'])) {
    header("Location: index.php");
    exit();
}

// 3. Prevent browser from caching the page (Stops the "Back" button issue completely)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 4. Process voting into the database (Strict: One vote per lifetime)
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
    <style>
        /* ── Desktop only: no scroll ── */
        @media (min-width: 768px) {
            html, body {
                overflow: hidden;
                height: 100%;
            }

            .page-wrapper {
                height: 100vh;
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
                flex: 1;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding-top: 10px !important;
                padding-bottom: 10px !important;
            }

            .voting-container {
                overflow: hidden;
                gap: 15px;
                padding: 10px;
            }

            /* Scale down cards to fit within screen */
            .candidate-card {
                height: 230px;
            }

            .candidate-card:hover {
                width: 460px;
            }

            .card-left {
                width: 200px;
            }

            .card-right {
                width: 280px;
            }
        }

        /* ── Base card styles (all screens) ── */
        .voting-container {
            display: flex;
            flex-wrap: wrap; 
            justify-content: center;
            gap: 25px; 
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .candidate-card {
            display: flex;
            flex-direction: row;
            width: 260px;
            height: 360px; 
            background: #1e293b;
            border-radius: 16px;
            overflow: hidden; 
            border: 1px solid #334155;
            transition: width 0.5s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.4s ease, border-color 0.4s ease;
        }

        .candidate-card:hover {
            width: 540px;
            border-color: #3b82f6;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            z-index: 10;
        }

        .card-left {
            width: 260px; 
            height: 100%;
            flex-shrink: 0; 
            position: relative;
        }

        .card-left img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-right: 1px solid #334155;
        }

        .card-right {
            width: 280px; 
            height: 100%;
            flex-shrink: 0; 
            padding: 25px; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            opacity: 0;
            transform: translateX(-15px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .candidate-card:hover .card-right {
            opacity: 1;
            transform: translateX(0); 
            transition-delay: 0.15s; 
        }

        .game-title {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .candidate-count {
            color: #3b82f6;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: block;
        }

        .game-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: auto;
        }

        .btn-vote {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            cursor: pointer;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-vote:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
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
            
            <div class="header">
                <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
            </div>
            
            <div class="nav-bar">
                <a href="utama.php" class="nav-item">Laman Utama</a>
                <a href="undian.php" class="nav-item active">Undian</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="logout.php" class="nav-item">Keluar</a>
            </div>
            
            <div class="content">
                <h2 style="color: #fbbf24; text-align: center; margin-bottom: 5px;">Senarai Calon Pertandingan Permainan Video</h2>
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
    // Keep these so the frontend forgets the user too
    localStorage.removeItem('voter_noKP');
    localStorage.removeItem('voter_name');
    
    // CHANGE THIS LINE to point to your new file
    window.location.href = 'logout.php'; 
}
    </script>
</body>
</html>