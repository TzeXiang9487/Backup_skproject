<?php
session_start();
require_once 'config.php';

// Security check: Only admin can access
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$message = "";

// 1. KEMASKINI LOGIC: When Admin clicks "Simpan"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $old_noKP = $_POST['old_noKP']; // The original IC we hid in the form
    $new_noKP = $_POST['noKP'];     // The new IC they might have typed
    $nama = $_POST['nama'];
    $idKelas = $_POST['idKelas'];
    $katalaluan = $_POST['katalaluan']; // New password

    // Start a transaction and temporarily disable foreign key checks so we can change the Primary Key
    $conn->begin_transaction();
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    try {
        // 1. Update the Main Student Table (pengundi)
        $stmt1 = $conn->prepare("UPDATE pengundi SET noKP = ?, nama = ?, idKelas = ? WHERE noKP = ?");
        $stmt1->bind_param("ssss", $new_noKP, $nama, $idKelas, $old_noKP);
        $stmt1->execute();

        // 2. Update the Login Table (pengguna)
        $stmt2 = $conn->prepare("UPDATE pengguna SET noKP = ?, katalaluan = ? WHERE noKP = ?");
        $stmt2->bind_param("sss", $new_noKP, $katalaluan, $old_noKP);
        $stmt2->execute();

        // 3. Update the Voting Table (pengundian) so their old vote history transfers to the new IC
        $stmt3 = $conn->prepare("UPDATE pengundian SET noKP = ? WHERE noKP = ?");
        $stmt3->bind_param("ss", $new_noKP, $old_noKP);
        $stmt3->execute();

        // Turn the security checks back on and save the changes!
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $conn->commit();

        echo "<script>
            alert('Maklumat pengguna dan katalaluan berjaya dikemaskini!');
            window.location.href = 'admin.php';
        </script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Cancel everything if there is an error
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $message = "<div class='message error'>Ralat mengemaskini: No. KP mungkin sudah wujud.</div>";
    }
}

// 2. PAPAR DATA LOGIC: Get data when the page loads
if (isset($_GET['noKP'])) {
    $noKP_url = $_GET['noKP'];

    // We use a JOIN here to fetch both their normal info AND their password
    $stmt = $conn->prepare("
        SELECT p.*, pg.katalaluan 
        FROM pengundi p 
        JOIN pengguna pg ON p.noKP = pg.noKP 
        WHERE p.noKP = ?
    ");
    $stmt->bind_param("s", $noKP_url);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        header("Location: admin.php");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}

$sql_kelas = "SELECT * FROM kelas";
$result_kelas = $conn->query($sql_kelas);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Kemaskini Pengguna - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="centered-page">
    <div class="page-wrapper">
        <div class="container">
            <div class="header" style="background-color: #b91c1c;">Panel Kawalan Admin</div>
            
            <div class="content">
                <h2 style="color: #3b82f6; text-align: center; margin-bottom: 20px;">Kemaskini Maklumat Pengguna</h2>
                
                <?php echo $message; ?>

                <form action="kemaskini.php" method="POST">
                    <input type="hidden" name="update_user" value="1">
                    
                    <input type="hidden" name="old_noKP" value="<?php echo htmlspecialchars($user['noKP']); ?>">
                    
                    <div class="form-group">
                        <label>No. Kad Pengenalan :</label>
                        <input type="text" name="noKP" value="<?php echo htmlspecialchars($user['noKP']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Pengundi :</label>
                        <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Katalaluan :</label>
                        <input type="text" name="katalaluan" value="<?php echo htmlspecialchars($user['katalaluan']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Kelas :</label>
                        <select name="idKelas" required>
                            <option value="">-- Sila Pilih Kelas --</option>
                            <?php 
                            if ($result_kelas->num_rows > 0) {
                                while($row = $result_kelas->fetch_assoc()) {
                                    $selected = ($row['idKelas'] == $user['idKelas']) ? "selected" : "";
                                    echo "<option value='".$row['idKelas']."' ".$selected.">".$row['kelas']."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='admin.php'">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
            
            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>