<?php
session_start();
require_once 'config.php';

// Semakan log masuk admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// LEFT JOIN so all voters appear, voted or not
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
        LEFT JOIN pengundian pn2 ON pg.noKP = pn2.noKP
        LEFT JOIN calon c ON pn2.idCalon = c.idCalon
        ORDER BY 
            CASE WHEN pn2.tarikh IS NULL THEN 1 ELSE 0 END ASC,
            pn2.tarikh DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - Undian Pembangunan Permainan</title>
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

    <div class="page-wrapper" style="max-width: 100%; margin: 0 auto;">
        <div class="container">

            <div class="header">
                <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
            </div>

            <div class="nav-bar">
                <a href="admin.php" class="nav-item active">Papan Pemuka</a>
                <a href="import.php" class="nav-item">Import</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="logout.php" class="nav-item">Keluar</a>
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
                                <th>Nama Penuh</th>
                                <th>Katalaluan</th>
                                <th>Kelas</th>
                                <th>No. Kad Pengenalan</th>
                                <th>Pilihan Calon</th>
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
                                    $sudahUndi = !empty($row['tarikh']);
                                    $rowClass  = $sudahUndi ? 'sudah-undi' : 'belum-undi';

                                    echo "<tr class='{$rowClass}'>";
                                    echo "<td>" . $bil++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['katalaluan']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['noKP']) . "</td>";

                                    // Calon & vote info — show placeholder if not voted
                                    echo "<td>" . ($sudahUndi ? htmlspecialchars($row['namaCalon']) : "<em style='color:#64748b;'>Belum Mengundi</em>") . "</td>";
                                    echo "<td>" . ($sudahUndi ? htmlspecialchars($row['idCalon'])   : "<em style='color:#64748b;'>-</em>") . "</td>";
                                    echo "<td>" . ($sudahUndi ? date("d-m-Y", strtotime($row['tarikh'])) : "<em style='color:#64748b;'>-</em>") . "</td>";

                                    // Action buttons — disable cancel if not voted
                                    echo "<td class='aksi-column'>";
                                    echo "<a href='kemaskini.php?noKP=" . urlencode($row['noKP']) . "' class='btn btn-primary' style='background-color:#3b82f6; padding:6px 12px; font-size:0.85rem; text-decoration:none; margin-right:5px;'>Kemaskini</a>";
                                    if ($sudahUndi) {
                                        echo "<button onclick=\"bukaPengesahan('" . addslashes($row['noKP']) . "', '" . addslashes($row['nama']) . "', '" . addslashes($row['tarikh']) . "')\" class='btn' style='background-color:#ef4444; color:white; padding:6px 12px; font-size:0.85rem;'>Batal</button>";
                                    } else {
                                        echo "<button disabled class='btn' style='background-color:#334155; color:#64748b; padding:6px 12px; font-size:0.85rem; cursor:not-allowed;'>Batal</button>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' style='text-align:center;'>Tiada rekod pengundi dijumpai.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

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
                <div class="btn-container" style="display:flex; gap:10px; justify-content:center;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('batalModal').style.display='none'">Kembali</button>
                    <button type="submit" class="btn" style="background-color:#ef4444; color:white;">Ya, Batal Undian</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_batal'])) {
        $noKP = $_POST['noKP'];
        $stmt = $conn->prepare("DELETE FROM pengundian WHERE noKP = ?");
        $stmt->bind_param("s", $noKP);
        if ($stmt->execute()) {
            echo "<script>alert('Rekod undian berjaya dipadam!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Ralat semasa memadam rekod.'); window.location.href='admin.php';</script>";
        }
    }
    ?>

    <script>
        function bukaPengesahan(noKP, nama, tarikh) {
            document.getElementById('modal-nama').innerText = nama;
            document.getElementById('modal-noKP').value = noKP;
            document.getElementById('modal-tarikh').value = tarikh;
            document.getElementById('batalModal').style.display = 'flex';
        }
    </script>
</body>
</html>