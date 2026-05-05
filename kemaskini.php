<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$message = "";

// 1. Handle Edit Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sah_edit'])) {
    $oldNoKP   = $_POST['oldNoKP'];
    $newNoKP   = trim($_POST['noKP']);
    $nama      = trim($_POST['nama']);
    $idKelas   = $_POST['idKelas'];
    $katalaluan = trim($_POST['katalaluan']);

    $conn->begin_transaction();
    try {
        if ($newNoKP !== $oldNoKP) {
            // noKP changed — update all related tables carefully
            // Temporarily disable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS=0");

            // Update pengundi
            $s1 = $conn->prepare("UPDATE pengundi SET noKP=?, nama=?, idKelas=? WHERE noKP=?");
            $s1->bind_param("ssss", $newNoKP, $nama, $idKelas, $oldNoKP);
            $s1->execute();

            // Update pengguna
            $s2 = $conn->prepare("UPDATE pengguna SET noKP=?, katalaluan=? WHERE noKP=?");
            $s2->bind_param("sss", $newNoKP, $katalaluan, $oldNoKP);
            $s2->execute();

            // Update pengundian if exists
            $s3 = $conn->prepare("UPDATE pengundian SET noKP=? WHERE noKP=?");
            $s3->bind_param("ss", $newNoKP, $oldNoKP);
            $s3->execute();

            $conn->query("SET FOREIGN_KEY_CHECKS=1");
        } else {
            // noKP unchanged — just update nama, kelas, katalaluan
            $s1 = $conn->prepare("UPDATE pengundi SET nama=?, idKelas=? WHERE noKP=?");
            $s1->bind_param("sss", $nama, $idKelas, $oldNoKP);
            $s1->execute();

            $s2 = $conn->prepare("UPDATE pengguna SET katalaluan=? WHERE noKP=?");
            $s2->bind_param("ss", $katalaluan, $oldNoKP);
            $s2->execute();
        }

        $conn->commit();
        echo "<script>alert('Maklumat pengundi berjaya dikemas kini!'); window.location.href='admin.php';</script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $message = "<div class='message error'>Ralat: Kad Pengenalan mungkin sudah wujud atau masalah sistem.</div>";
    }
}

// 2. Load voter data from URL noKP
if (isset($_GET['noKP'])) {
    $noKP_url = $_GET['noKP'];
} elseif (isset($_POST['oldNoKP'])) {
    $noKP_url = $_POST['oldNoKP'];
} else {
    header("Location: admin.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT pg.noKP, pg.nama, pg.idKelas, pn.katalaluan
    FROM pengundi pg
    JOIN pengguna pn ON pg.noKP = pn.noKP
    WHERE pg.noKP = ?
");
$stmt->bind_param("s", $noKP_url);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin.php");
    exit();
}
$voter = $result->fetch_assoc();

// Load kelas list for dropdown
$kelas_result = $conn->query("SELECT idKelas, kelas FROM kelas");
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Kemas Kini Pengundi - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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
                <h3 style="text-align: center; margin-bottom: 25px; color: #f8fafc;">Kemas Kini Data Pengundi</h3>

                <?php echo $message; ?>

                <form action="kemaskini.php" method="POST">
                    <input type="hidden" name="sah_edit" value="1">
                    <input type="hidden" name="oldNoKP" value="<?php echo htmlspecialchars($voter['noKP']); ?>">

                    <div class="form-group">
                        <label>Nama :</label>
                        <input type="text" name="nama" required value="<?php echo htmlspecialchars($voter['nama']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Kelas :</label>
                        <select name="idKelas" required>
                            <option value="">-- Sila Pilih Kelas --</option>
                            <?php while ($k = $kelas_result->fetch_assoc()): ?>
                                <option value="<?php echo $k['idKelas']; ?>" <?php echo ($k['idKelas'] == $voter['idKelas']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($k['kelas']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kad Pengenalan :</label>
                        <input type="text" name="noKP" required value="<?php echo htmlspecialchars($voter['noKP']); ?>" placeholder="000000-00-0000">
                    </div>

                    <div class="form-group">
                        <label>Kata laluan :</label>
                        <input type="text" name="katalaluan" required value="<?php echo htmlspecialchars($voter['katalaluan']); ?>">
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='admin.php'">Batal</button>
                        <button type="submit" class="btn btn-primary">Sah</button>
                    </div>
                </form>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>