<?php
session_start();
require_once 'config.php';

// Security check: If they aren't logged in as admin, kick them out
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$message = "";

// 1. Process the Deletion if the Admin clicked "Ya, Padam"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_reset'])) {
    $noKP = $_POST['noKP'];
    $tarikh = $_POST['tarikh'];

    // Delete the vote record from the pengundian table
    $stmt = $conn->prepare("DELETE FROM pengundian WHERE noKP = ? AND tarikh = ?");
    $stmt->bind_param("ss", $noKP, $tarikh);
    
    if ($stmt->execute()) {
        echo "<script>
            alert('Rekod undian telah berjaya dipadam! Pengundi kini boleh mengundi semula.');
            window.location.href = 'admin.php';
        </script>";
        exit();
    } else {
        $message = "<div class='message error'>Ralat memadam rekod undian. Sila cuba lagi.</div>";
    }
}

// 2. Get the data from the URL to display on the confirmation page
if (isset($_GET['noKP']) && isset($_GET['tarikh'])) {
    $noKP_url = $_GET['noKP'];
    $tarikh_url = $_GET['tarikh'];
} else {
    // If someone tries to access kemaskini.php directly without a noKP, send them back
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Reset Undian - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="centered-page"> <div class="page-wrapper">
        <div class="container">
            <div class="header" style="background-color: #ef4444;">Pengesahan Reset Undian</div>
            
            <div class="content" style="text-align: center;">
                <?php echo $message; ?>
                
                <h3 style="color: #f8fafc; margin-bottom: 15px;">Adakah anda pasti?</h3>
                <p style="color: #cbd5e1; margin-bottom: 25px;">
                    Anda sedang memadam rekod undian untuk pengundi dengan No. Kad Pengenalan:<br>
                    <strong style="color: #3b82f6; font-size: 1.2rem;"><?php echo htmlspecialchars($noKP_url); ?></strong>
                    <br><br>
                    Tindakan ini tidak boleh dipulihkan dan pengundi perlu mengundi semula.
                </p>

                <form action="kemaskini.php" method="POST">
                    <input type="hidden" name="noKP" value="<?php echo htmlspecialchars($noKP_url); ?>">
                    <input type="hidden" name="tarikh" value="<?php echo htmlspecialchars($tarikh_url); ?>">
                    <input type="hidden" name="confirm_reset" value="1">
                    
                    <div class="btn-container">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='admin.php'">Batal</button>
                        <button type="submit" class="btn" style="background-color: #ef4444; color: white;">Ya, Padam Undian</button>
                    </div>
                </form>
            </div>
            
            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>
</body>
</html>