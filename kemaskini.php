<?php
session_start();
require_once 'config.php';

// Semakan akses admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$message = "";

// 1. Mengendalikan Penghantaran Borang Kemas Kini
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sah_edit'])) {
    $oldNoKP    = $_POST['oldNoKP'];
    $nama       = trim($_POST['nama']);
    $idKelas    = $_POST['idKelas'];
    $katalaluan = trim($_POST['katalaluan']);

    $conn->begin_transaction();
    try {
        // noKP tidak boleh ditukar — hanya kemas kini nama, kelas, dan katalaluan
        $s1 = $conn->prepare("UPDATE pengundi SET nama=?, idKelas=? WHERE noKP=?");
        $s1->bind_param("sss", $nama, $idKelas, $oldNoKP);
        $s1->execute();

        $s2 = $conn->prepare("UPDATE pengguna SET katalaluan=? WHERE noKP=?");
        $s2->bind_param("ss", $katalaluan, $oldNoKP);
        $s2->execute();

        $conn->commit();
        echo "<script>alert('Maklumat pengundi berjaya dikemas kini!'); window.location.href='admin.php';</script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='message error'>Ralat: Terdapat masalah sistem semasa mengemas kini data.</div>";
    }
}

// 2. Muat data pengundi berdasarkan noKP dari URL
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

// Muat senarai kelas untuk pilihan dropdown
$kelas_result = $conn->query("SELECT idKelas, kelas FROM kelas");
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Kemas Kini Pengundi - Sistem Undian</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()"  >🌙</button>
            </div>

            <div class="content">
                <h3 class="page-title" style="text-align: center; margin-bottom: 25px;">Kemas Kini Data Pengundi</h3>

                <?php echo $message; ?>

                <form action="kemaskini.php" method="POST">
                    <input type="hidden" name="sah_edit" value="1">
                    <input type="hidden" name="oldNoKP" value="<?php echo htmlspecialchars($voter['noKP']); ?>">
                    <input type="hidden" name="noKP" value="<?php echo htmlspecialchars($voter['noKP']); ?>">

                    <div class="form-group">
                        <label>Nama Penuh :</label>
                        <input type="text" name="nama" id="nama" required value="<?php echo htmlspecialchars($voter['nama']); ?>"
                            oninput="this.value = this.value.replace(/[^a-zA-Z ]/g, '')">
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
                        <label>No. Kad Pengenalan :</label>
                        <input type="text" value="<?php echo htmlspecialchars($voter['noKP']); ?>"
                            disabled
                            style="opacity: 0.5; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>Kata Laluan :</label>
                        <input type="text" name="katalaluan" required value="<?php echo htmlspecialchars($voter['katalaluan']); ?>">
                    </div>

                    <div class="btn-container" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='admin.php'">Batal</button>
                        <button type="submit" class="btn btn-primary" onclick="return sahkanBorang()">Sah</button>
                    </div>
                </form>

                <script>
                    function sahkanBorang() {
                        const nama = document.getElementById('nama').value.trim();
                        if (!/^[a-zA-Z ]+$/.test(nama)) {
                            alert('Nama hanya boleh mengandungi huruf abjad dan ruang sahaja.');
                            return false;
                        }
                        return true;
                    }
                </script>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>