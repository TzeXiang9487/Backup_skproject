<?php
require_once 'config.php'; 

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $idKelas = $_POST['idKelas']; 
    $noKP = $_POST['noKP'];
    $katalaluan = $_POST['katalaluan'];

    if (!empty($nama) && !empty($idKelas) && !empty($noKP) && !empty($katalaluan)) {
        
        // Start Transaction
        $conn->begin_transaction();

        try {
            // 1. Insert into PENGUNDI
            $stmt1 = $conn->prepare("INSERT INTO PENGUNDI (noKP, nama, idKelas) VALUES (?, ?, ?)");
            $stmt1->bind_param("sss", $noKP, $nama, $idKelas);
            $stmt1->execute();

            // 2. Insert into PENGGUNA
            $stmt2 = $conn->prepare("INSERT INTO pengguna (noKP, katalaluan) VALUES (?, ?)");
            $stmt2->bind_param("ss", $noKP, $katalaluan);
            $stmt2->execute();

            // Commit if both succeeded
            $conn->commit();
            $message = "<div class='message success'>Pendaftaran Berjaya! Sila log masuk.</div>";
            header("refresh:2;url=index.php");

        } catch (Exception $e) {
            // Rollback if something goes wrong (e.g., duplicate noKP)
            $conn->rollback();
            $message = "<div class='message error'>Ralat: No. KP sudah wujud atau masalah sistem.</div>";
        }
    }
}

$sql_kelas = "SELECT idKelas, kelas FROM KELAS";
$result_kelas = $conn->query($sql_kelas);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css">
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
                <h3>Pendaftaran Undi</h3>
                <?php echo $message; ?>

                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label>Nama :</label>
                        <input type="text" name="nama" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Kelas :</label>
                        <select name="idKelas" required>
                            <option value="">-- Sila Pilih Kelas --</option>
                            <?php 
                            while($row = $result_kelas->fetch_assoc()) {
                                echo "<option value='".$row['idKelas']."'>".$row['kelas']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kad Pengenalan (Username) :</label>
                        <input type="text" name="noKP" placeholder="090214-07-1234" required>
                    </div>

                    <div class="form-group">
                        <label>Katalaluan :</label>
                        <input type="password" name="katalaluan" required>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Batal</button>
                        <button type="submit" class="btn btn-primary">Daftar</button>
                    </div>
                </form>
            </div>
            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>