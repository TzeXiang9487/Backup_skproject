<?php
// 1. Start the session to access it
session_start();

// 2. Clear all session variables
$_SESSION = array();

// 3. Destroy the session on the server
session_destroy();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Logging Out - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="centered-page">
    <div class="page-wrapper">
        <div class="container">
            <div class="header">Sistem D'Undi Pertandingan Penciptaan Permainan Video</div>
            
            <div class="content" style="text-align: center;">
                <p style="font-size: 1.2rem; color: #94a3b8; margin-top: 50px;">
                    Log keluar sedang diproses...
                </p>
                <div class="loader" style="margin: 20px auto; border: 4px solid #334155; border-top: 4px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <script>
        // 4. Clear the localStorage data (voter_noKP and voter_name)
        localStorage.clear();

        // 5. Redirect the user back to the login page after a short delay
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 1500);
    </script>

    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>