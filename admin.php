<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT 
            pg.noKP,
            pg.nama,
            pn.katalaluan,
            k.kelas,
            c.namaCalon,
            c.idCalon,
            pn2.tarikh
        FROM pengundi pg
        JOIN pengguna pn ON pg.noKP = pn.noKP
        JOIN kelas k ON pg.idKelas = k.idKelas
        INNER JOIN pengundian pn2 ON pg.noKP = pn2.noKP
        INNER JOIN calon c ON pn2.idCalon = c.idCalon
        ORDER BY pg.nama ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        table th, table td { white-space: nowrap; }

        /* Hide scrollbar but keep scroll functionality */
        html, body {
            scrollbar-width: none;       /* Firefox */
            -ms-overflow-style: none;    /* IE / Edge */
        }
        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            display: none;               /* Chrome, Safari */
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #1e293b;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px rgba(0,0,0,0.8);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .modal-content h3 { color: #ef4444; margin-bottom: 15px; }
        .modal-content p  { color: #cbd5e1; margin-bottom: 25px; line-height: 1.6; }
        .modal-content strong { color: #3b82f6; font-size: 1.1rem; }

        .print-title { display: none; }

        @page { margin: 0; }

        @media print {
            .header, .nav-bar, .btn-cetak, .footer, .aksi-column, h2 { display: none !important; }

            .print-title {
                display: block;
                text-align: center;
                font-size: 1.3rem;
                font-weight: bold;
                color: black;
                margin: 30px 0 20px 0;
            }

            body, .container { background: white !important; color: black !important; }
            .content { padding: 0 20px !important; }
            table { border: 1px solid black; width: 100% !important; }
            th, td { border-bottom: 1px solid black; color: black !important; background: white !important; font-size: 0.85rem; }
            th { color: black !important; }
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

    <div class="page-wrapper" style="max-width: 100%; margin: 0 auto;">
        <div class="container">

            <div class="header">
    <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
    <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
</div>
            <div class="nav-bar">
                <a href="admin.php" class="nav-item active">Dashboard Admin</a>
                <a href="import.php" class="nav-item">Import</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="#" class="nav-item" onclick="keluarAkaun()">Keluar (Admin)</a>
            </div>

            <div class="content">

                <div class="print-title">Laporan Undian Pertandingan Penciptaan Permainan Video</div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #ef4444;">Senarai Maklumat Pengundi</h2>
                    <button onclick="window.print()" class="btn btn-primary btn-cetak" style="background-color: #f59e0b;">Cetak</button>
                </div>

                <div style="overflow-x: auto;">
                    <table style="min-width: 100%; width: max-content;">
                        <thead>
                            <tr>
                                <th>Bil</th>
                                <th>Nama</th>
                                <th>Katalaluan</th>
                                <th>Kelas</th>
                                <th>Kad Pengenalan</th>
                                <th>Calon</th>
                                <th>Kod Calon</th>
                                <th>Tarikh Undi</th>
                                <th class="aksi-column">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $bil = 1;
                                while ($row = $result->fetch_assoc()) {
                                    $tarikh = date("d-m-Y", strtotime($row['tarikh']));
                                    echo "<tr>";
                                    echo "<td>" . $bil++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['katalaluan']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['noKP']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['namaCalon']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['idCalon']) . "</td>";
                                    echo "<td>" . $tarikh . "</td>";
                                    echo "<td class='aksi-column'>";
                                    echo "<a href='kemaskini.php?noKP=" . urlencode($row['noKP']) . "' class='btn btn-primary' style='background-color: #3b82f6; padding: 6px 12px; font-size: 0.85rem; text-decoration: none; margin-right: 5px;'>Edit</a>";
                                    echo "<button onclick=\"bukaPengesahan('" . addslashes($row['noKP']) . "', '" . addslashes($row['nama']) . "', '" . addslashes($row['tarikh']) . "')\" class='btn' style='background-color: #ef4444; color: white; padding: 6px 12px; font-size: 0.85rem;'>Batal</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' style='text-align: center;'>Tiada rekod pengundian dijumpai.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <!-- Batal Confirmation Modal -->
    <div id="batalModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Pengesahan Batal Undian</h3>
            <p>
                Anda akan memadam rekod undian untuk:<br>
                <strong id="modal-nama"></strong><br><br>
                Tindakan ini tidak boleh dipulihkan. Teruskan?
            </p>
            <form action="admin.php" method="POST">
                <input type="hidden" name="confirm_batal" value="1">
                <input type="hidden" name="noKP" id="modal-noKP">
                <input type="hidden" name="tarikh" id="modal-tarikh">
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('batalModal').style.display='none'">Kembali</button>
                    <button type="submit" class="btn" style="background-color: #ef4444; color: white;">Ya, Batal Undian</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_batal'])) {
        $noKP = $_POST['noKP'];
        $tarikh = $_POST['tarikh'];
        $stmt = $conn->prepare("DELETE FROM pengundian WHERE noKP = ? AND tarikh = ?");
        $stmt->bind_param("ss", $noKP, $tarikh);
        if ($stmt->execute()) {
            echo "<script>alert('Rekod undian berjaya dipadam!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Ralat semasa memadam rekod.'); window.location.href='admin.php';</script>";
        }
    }
    ?>
    </div>

    <script>
        function bukaPengesahan(noKP, nama, tarikh) {
            document.getElementById('modal-nama').innerText = nama;
            document.getElementById('modal-noKP').value = noKP;
            document.getElementById('modal-tarikh').value = tarikh;
            document.getElementById('batalModal').style.display = 'flex';
        }

        function keluarAkaun() {
            localStorage.removeItem('voter_noKP');
            localStorage.removeItem('voter_name');
            window.location.href = 'login.php?logout=1';
        }
    </script>
</body>
</html>