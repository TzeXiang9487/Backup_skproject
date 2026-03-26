<?php
require_once 'config.php'; 

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $idKelas = $_POST['idKelas']; 
    $noKP = $_POST['noKP'];
    $katalaluan = $_POST['katalaluan'];

    if (!empty($nama) && !empty($idKelas) && !empty($noKP) && !empty($katalaluan)) {
        
        $conn->begin_transaction();

        try {
            $stmt1 = $conn->prepare("INSERT INTO PENGUNDI (noKP, nama, idKelas) VALUES (?, ?, ?)");
            $stmt1->bind_param("sss", $noKP, $nama, $idKelas);
            $stmt1->execute();

            $stmt2 = $conn->prepare("INSERT INTO pengguna (noKP, katalaluan) VALUES (?, ?)");
            $stmt2->bind_param("ss", $noKP, $katalaluan);
            $stmt2->execute();

            $conn->commit();
            $message = "<p style='color: #22c55e; text-align: center; font-weight: bold;'>Pendaftaran Berjaya! Anda akan dibawa ke halaman Log Masuk.</p>";
            header("refresh:2;url=login.php");

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<p style='color: #ef4444; text-align: center; font-weight: bold;'>Ralat: Pendaftaran gagal atau No KP sudah wujud.</p>";
        }
    }
}

// Fetch classes for the dropdown
$sql_kelas = "SELECT * FROM KELAS";
$result_kelas = $conn->query($sql_kelas);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pengguna - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    
    <div style="max-width: 600px; width: 100%;">
        <div class="header" style="margin-bottom: 0;">Pendaftaran Pengguna Baru</div>
        <div style="background-color: var(--saber-dark); border: 2px solid var(--saber-armor-dark); border-top: none; padding: 30px;">
            
            <?php echo $message; ?>

            <form action="register.php" method="POST">
                <table style="width: 100%; border: none; background: transparent; margin: 0;">
                    <tr style="border: none;">
                        <td style="border: none; text-align: right; width: 35%; padding: 10px;"><label style="color: white; font-weight: bold;">Nama :</label></td>
                        <td style="border: none; text-align: left; padding: 10px;"><input type="text" name="nama" required></td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none; text-align: right; padding: 10px;"><label style="color: white; font-weight: bold;">Kelas :</label></td>
                        <td style="border: none; text-align: left; padding: 10px;">
                            <select name="idKelas" required style="width: 100%; padding: 10px; border: 1px solid var(--saber-armor-dark); background-color: #1e293b; color: white; border-radius: 4px;">
                                <option value="">-- Sila Pilih Kelas --</option>
                                <?php 
                                while($row = $result_kelas->fetch_assoc()) {
                                    echo "<option value='".$row['idKelas']."'>".$row['kelas']."</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none; text-align: right; padding: 10px;"><label style="color: white; font-weight: bold;">No Kad Pengenalan :</label></td>
                        <td style="border: none; text-align: left; padding: 10px;"><input type="text" name="noKP" placeholder="Cth: 090214071234" required></td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none; text-align: right; padding: 10px;"><label style="color: white; font-weight: bold;">Katalaluan :</label></td>
                        <td style="border: none; text-align: left; padding: 10px;"><input type="password" name="katalaluan" required></td>
                    </tr>
                    <tr style="border: none;">
                        <td colspan="2" style="border: none; text-align: center; padding-top: 20px;">
                            <button type="submit" class="btn">Daftar</button>
                            &nbsp;&nbsp;
                            <button type="button" class="btn" style="background-color: var(--saber-armor-dark); color: white;" onclick="window.location.href='login.php'">Batal</button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

</body>
</html>