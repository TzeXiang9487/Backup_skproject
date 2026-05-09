<?php
require_once 'config.php'; 

$message = ""; 

// Mengendalikan penghantaran borang pendaftaran
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $idKelas = $_POST['idKelas']; 
    $noKP = $_POST['noKP'];
    $katalaluan = $_POST['katalaluan'];

    if (!empty($nama) && !empty($idKelas) && !empty($noKP) && !empty($katalaluan)) {
        
        // Mulakan Transaksi untuk memastikan integriti data pada dua jadual
        $conn->begin_transaction();

        try {
            // 1. Masukkan data ke dalam jadual PENGUNDI
            $stmt1 = $conn->prepare("INSERT INTO PENGUNDI (noKP, nama, idKelas) VALUES (?, ?, ?)");
            $stmt1->bind_param("sss", $noKP, $nama, $idKelas);
            $stmt1->execute();

            // 2. Masukkan data ke dalam jadual pengguna (untuk log masuk)
            $stmt2 = $conn->prepare("INSERT INTO pengguna (noKP, katalaluan) VALUES (?, ?)");
            $stmt2->bind_param("ss", $noKP, $katalaluan);
            $stmt2->execute();

            // Laksanakan 'commit' jika kedua-dua kemasukan berjaya
            $conn->commit();
            $message = "<div class='message success'>Pendaftaran Berjaya! Sila log masuk.</div>";
            
            // Halakan pengguna ke halaman log masuk selepas 2 saat
            header("refresh:2;url=index.php");

        } catch (Exception $e) {
            // Batalkan transaksi jika terdapat ralat (contoh: noKP pendua)
            $conn->rollback();
            $message = "<div class='message error'>Ralat: No. KP sudah wujud atau terdapat masalah sistem.</div>";
        }
    }
}

// Ambil senarai kelas untuk dropdown
$sql_kelas = "SELECT idKelas, kelas FROM KELAS";
$result_kelas = $conn->query($sql_kelas);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pengundi - Sistem Undian</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="centered-page">
<script>
    // 1. Semak dan laksanakan tema dengan segera untuk elakkan "white flash"
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }

    // 2. Tetapkan ikon yang betul sebaik sahaja halaman dimuatkan
    window.addEventListener('DOMContentLoaded', () => {
        const themeBtn = document.getElementById('theme-btn');
        if (themeBtn) {
            themeBtn.innerText = document.body.classList.contains('light-mode') ? '☀️' : '🌙';
        }
    });

    // 3. Fungsi Tukar Tema
    function toggleTheme() {
        var body = document.body;
        var themeBtn = document.getElementById('theme-btn');
        
        themeBtn.classList.remove('spin');
        void themeBtn.offsetWidth; // Paksa pelayar untuk mula semula animasi
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
            <div class="content">
                <h3 style="text-align: center; margin-bottom: 20px;">Pendaftaran Pengundi Baharu</h3>
                
                <?php echo $message; ?>

                <form action="daftar.php" method="POST">
                    <div class="form-group">
                        <label>Nama Penuh :</label>
                        <input type="text" name="nama" required placeholder="Masukkan nama penuh anda">
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
                        <label>No. Kad Pengenalan :</label>
                        <input type="text" name="noKP" placeholder="Contoh: 000000000000" required>
                    </div>

                    <div class="form-group">
                        <label>Katalaluan :</label>
                        <input type="password" name="katalaluan" required placeholder="Cipta katalaluan anda">
                    </div>

                    <div class="btn-container" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Batal</button>
                        <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
                    </div>
                </form>
            </div>
            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>