<?php
require_once 'config.php'; 
session_start();

$message = ""; 

// --- ADMIN LOGIN LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['is_admin_login'])) {
    $admin_user = $_POST['admin_username'];
    $admin_pass = $_POST['admin_password'];

    if ($admin_user === "admin" && $admin_pass === "123") {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = 1; 
        header("Location: admin.php");
        exit();
    } else {
        $message = "<p style='color: #ef4444; text-align: center; font-weight: bold;'>Log masuk admin gagal! Nama pengguna atau katalaluan salah.</p>";
    }
}

// --- USER LOGIN LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['is_admin_login'])) {
    $noKP = $_POST['noKP'];
    $katalaluan = $_POST['katalaluan']; 

    $stmt = $conn->prepare("SELECT p.noKP, v.nama FROM pengguna p JOIN PENGUNDI v ON p.noKP = v.noKP WHERE p.noKP = ? AND p.katalaluan = ?");
    $stmt->bind_param("ss", $noKP, $katalaluan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['voter_noKP'] = $user['noKP'];
        $_SESSION['voter_name'] = $user['nama']; 
        header("Location: utama.php");
        exit();
    } else {
        $message = "<p style='color: #ef4444; text-align: center; font-weight: bold;'>No. KP atau Katalaluan salah.</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    
    <div style="max-width: 800px; width: 100%;">
        <div class="header" style="margin-bottom: 0;">Sistem D'Undi Pertandingan Penciptaan Permainan Video</div>
        
        <div style="background-color: var(--dark-navy); border: 2px solid var(--dark-gray); border-top: none; padding: 30px;">
            <h2 style="text-align: center; color: var(--gold); margin: 0 0 10px 0;">Log Masuk Pengguna</h2>
            <p style="text-align: center; color: var(--light-gray); margin-bottom: 20px;">Selamat kembali! Sila masukkan butiran anda untuk log masuk.</p>
            
            <?php echo $message; ?>

            <form action="login.php" method="POST">
                <div style="border: 1px solid var(--dark-gray); border-radius: 8px; overflow: hidden; margin-bottom: 15px;">
                    <table style="width: 100%; border-collapse: collapse; background: transparent; margin: 0;">
                        <tr style="border-bottom: 1px solid var(--dark-gray);">
                            <td style="text-align: right; width: 40%; padding: 15px 10px;"><label style="color: white; font-weight: bold;">No Kad Pengenalan :</label></td>
                            <td style="text-align: left; padding: 15px 10px;"><input type="text" name="noKP" required placeholder="Cth: 010203040506"></td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding: 15px 10px;"><label style="color: white; font-weight: bold;">Katalaluan :</label></td>
                            <td style="text-align: left; padding: 15px 10px;"><input type="password" name="katalaluan" required></td>
                        </tr>
                    </table>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn" style="padding: 10px 40px; font-size: 1.1rem;">Log Masuk</button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 20px; line-height: 1.6;">
                <p style="color: var(--light-gray); margin: 0;">Admin? <a href="#" onclick="document.getElementById('adminModal').style.display='flex'; return false;" style="color: var(--gold); font-weight: bold; text-decoration: none;">Sila klik di sini.</a></p>
                <p style="color: var(--light-gray); margin: 0;">Belum Daftar? <a href="register.php" style="color: #3b82f6; font-weight: bold; text-decoration: none;">Sila klik di sini.</a></p>
            </div>
        </div>
        
        <div class="footer" style="border-top: none; margin-top: 15px;">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
    </div>

    <div id="adminModal" style="display: <?php echo (isset($_POST['is_admin_login']) && $admin_user !== "admin") ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); justify-content: center; align-items: center; z-index: 1000;">
        <div style="background-color: var(--dark-navy); border: 2px solid var(--dark-gray); width: 450px; max-width: 90%; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #172554 0%, var(--blue) 100%); color: var(--gold); padding: 15px; text-align: center; font-size: 1.4rem; font-weight: bold; border-bottom: 2px solid var(--dark-gray);">
                Log Masuk Admin
            </div>
            <div style="padding: 20px;">
                <form action="login.php" method="POST">
                    <input type="hidden" name="is_admin_login" value="1">
                    
                    <div style="border: 1px solid var(--dark-gray); border-radius: 8px; overflow: hidden; margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse; background: transparent; margin: 0;">
                            <tr style="border-bottom: 1px solid var(--dark-gray);">
                                <td style="text-align: right; width: 40%; padding: 15px 10px;"><label style="color: white; font-weight: bold;">Nama Pengguna :</label></td>
                                <td style="text-align: left; padding: 15px 10px;"><input type="text" name="admin_username" required></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; padding: 15px 10px;"><label style="color: white; font-weight: bold;">Katalaluan :</label></td>
                                <td style="text-align: left; padding: 15px 10px;"><input type="password" name="admin_password" required></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" class="btn" style="background-color: #3b82f6; color: white;">Log Masuk</button>
                        &nbsp;&nbsp;
                        <button type="button" onclick="document.getElementById('adminModal').style.display='none'" class="btn" style="background-color: var(--dark-gray); color: white;">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>