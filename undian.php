<?php
require_once 'config.php';

// 1. Proses undian ke pangkalan data
if (isset($_GET['vote_id']) && isset($_GET['voter_ic'])) {
    $idCalon = $_GET['vote_id'];
    $noKP = $_GET['voter_ic']; 
    $tarikh = date("Y-m-d");

    $sql_vote = "INSERT INTO PENGUNDIAN (noKP, tarikh, idCalon) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_vote);
    $stmt->bind_param("sss", $noKP, $tarikh, $idCalon);

    if ($stmt->execute()) {
        echo "<script>alert('Undian Berjaya!'); window.location.href='keputusan.php';</script>";
    } else {
        echo "<script>alert('Ralat: Anda sudah pun mengundi hari ini!'); window.location.href='undian.php';</script>";
    }
    $stmt->close();
    exit();
}

// 2. Dapatkan senarai calon 
// (Nota: Kita tak perlu COUNT undi lagi di sini sebab kita dah buang dari paparan)
$sql_calon = "SELECT * FROM CALON";
$result_calon = $conn->query($sql_calon);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Undian - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* CSS UNTUK ANIMASI KAD MENGEMBANG KE KANAN (SAIZ DIKECILKAN) */
        .voting-container {
            display: flex;
            flex-wrap: nowrap; /* Paksa supaya sentiasa 1 baris */
            justify-content: center;
            gap: 20px; /* Jarak antara kad dikecilkan sikit */
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .candidate-card {
            display: flex;
            flex-direction: row;
            /* Saiz baru: Lebih kecil supaya muat 3 dalam 1 baris bila mengembang */
            width: 260px; 
            height: 360px; 
            background: #1e293b;
            border-radius: 16px;
            overflow: hidden; 
            border: 1px solid #334155;
            transition: width 0.5s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.4s ease, border-color 0.4s ease;
        }

        .candidate-card:hover {
            /* Mengembang: 260px (Gambar) + 280px (Teks) = 540px */
            width: 540px; 
            border-color: #3b82f6;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        }

        /* BAHAGIAN KIRI: GAMBAR */
        .card-left {
            width: 260px; /* Lebar asal gambar */
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

        /* BAHAGIAN KANAN: MAKLUMAT & BUTANG */
        .card-right {
            width: 280px; /* Lebar panel maklumat yang baru */
            height: 100%;
            flex-shrink: 0; 
            padding: 25px; /* Kurangkan padding sikit */
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

        /* TIPOGRAFI & GAYA DALAMAN KAD */
        .game-title {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px; /* Jarak terus ke keterangan */
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .game-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: auto; /* Tolak butang ke bawah */
        }

        .btn-vote {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        
        .btn-vote:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
            
            <div class="header">Sistem D'Undi Pertandingan Penciptaan Permainan Video</div>
            <div class="nav-bar">
                <a href="utama.php" class="nav-item">Laman Utama</a>
                <a href="undian.php" class="nav-item active">Undian</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="logout.php" class="nav-item">Keluar</a>
            </div>
            <div class="content">
                <h2 style="color: var(--saber-gold); text-align: center; margin-bottom: 20px;">Senarai Calon Pertandingan Permainan Video</h2>
                
                <div class="grid-container" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
                    
            <div class="content">
                <p style="text-align: center; color: #94a3b8; margin-bottom: 30px; font-size: 1.1rem;">
                    Sila hover (halakan kursor) pada gambar untuk melihat maklumat, kemudian undi permainan video pilihan anda!
                </p>

                <div class="voting-container">
                    <?php
                    // MULA GELUNG WHILE UNTUK PAPAR KAD
                    if ($result_calon->num_rows > 0) {
                        while($row = $result_calon->fetch_assoc()) {
                            
                            // --- LOKASI GAMBAR & KETERANGAN ---
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
                            // ----------------------------------
                            ?>
                            
                            <div class="candidate-card">
                                
                                <div class="card-left">
                                    <img src="<?php echo $lokasi_gambar; ?>" onerror="this.src='image/placeholder.jpg';" alt="<?php echo htmlspecialchars($row['namaCalon']); ?>">
                                </div>
                                
                                <div class="card-right">
                                    <h3 class="game-title"><?php echo htmlspecialchars($row['namaCalon']); ?></h3>
                                    
                                    <p class="game-desc">
                                        <?php echo $keterangan; ?>
                                    </p>
                                    
                                    <button type="button" onclick="prosesUndian('<?php echo $row['idCalon']; ?>')" class="btn btn-primary btn-vote">
                                        Undi Sekarang
                                    </button>
                                </div>
                                
                            </div>
                            
                            <?php
                        }
                    } else {
                        echo "<p style='text-align:center; width: 100%; color: white;'>Tiada calon dijumpai dalam pangkalan data.</p>";
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
            window.location.href = 'login.php';
            return;
        }
        
        if (confirm("Adakah anda pasti mahu mengundi ciptaan permainan video ini?")) {
            window.location.href = 'undian.php?vote_id=' + idCalon + '&voter_ic=' + noKP;
        }
    }
    </script>
</body>
</html>