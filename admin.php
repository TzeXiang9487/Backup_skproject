<?php
session_start();
require_once 'config.php'; // Connect to the database

// Security check: If they aren't logged in as admin, kick them back to login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// SQL Query to pull and combine all the necessary data
$sql = "SELECT p.noKP, pg.nama, k.kelas, p.tarikh, c.namaCalon 
        FROM pengundian p
        JOIN pengundi pg ON p.noKP = pg.noKP
        JOIN kelas k ON pg.idKelas = k.idKelas
        JOIN calon c ON p.idCalon = c.idCalon
        ORDER BY p.tarikh DESC"; // Sorts by newest vote first

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* Hide the navigation and buttons when printing */
        @media print {
            .nav-bar, .btn-cetak, .footer { display: none !important; }
            body, .container { background: white !important; color: black !important; }
            table { border: 1px solid black; }
            th, td { border-bottom: 1px solid black; color: black !important; background: white !important;}
            th { color: black !important; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper" style="max-width: 1000px; margin: 0 auto;"> <div class="container">
            <div class="header" style="background-color: #b91c1c;">Panel Kawalan Admin</div>
            
            <div class="nav-bar">
                <a href="admin.php" class="nav-item active">Dashboard Admin</a>
                <a href="keputusan.php" class="nav-item">Keputusan Undian</a> <a href="logout.php" class="nav-item">Keluar (Admin)</a>
            </div>

            <div class="content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #ef4444;">Senarai Rekod Pengundian Keseluruhan</h2>
                    <button onclick="window.print()" class="btn btn-primary btn-cetak" style="background-color: #f59e0b;">Cetak</button>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Bil</th>
                                <th>No. Kad Pengenalan</th>
                                <th>Nama Pengundi</th>
                                <th>Kelas</th>
                                <th>Tarikh Undian</th>
                                <th>Nama Calon</th>
                                <th class="aksi-column">Aksi</th> </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $bil = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $bil++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['noKP']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
                                    
                                    // Format the date to DD-MM-YYYY
                                    $formattedDate = date("d-m-Y", strtotime($row['tarikh']));
                                    echo "<td>" . $formattedDate . "</td>";
                                    
                                    echo "<td>" . htmlspecialchars($row['namaCalon']) . "</td>";
                                    
                                    // NEW AKSI LINKS: [Kemaskini] | [Padam]
                                    echo "<td class='aksi-column'>";
                                    echo "<a href='kemaskini.php?noKP=" . urlencode($row['noKP']) . "' style='color: #3b82f6; text-decoration: none;'>[Kemaskini]</a> | ";
                                    echo "<a href='padam.php?noKP=" . urlencode($row['noKP']) . "&tarikh=" . urlencode($row['tarikh']) . "' style='color: #ef4444; text-decoration: none;'>[Padam]</a>";
                                    echo "</td>";
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align: center;'>Tiada rekod pengundian dijumpai.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>