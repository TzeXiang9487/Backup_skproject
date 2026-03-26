<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Laman Utama - Game Dev Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
            <div class="header">Sistem D'Undi Pertandingan Penciptaan Permainan Video</div>
            
            <div class="nav-bar">
                <a href="utama.php" class="nav-item active">Laman Utama</a>
                <a href="undian.php" class="nav-item">Undian</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="pengundi.php" class="nav-item">Pengundi</a>
                <a href="logout.php" class="nav-item">Keluar</a>
            </div>

            <div class="content" style="text-align: center;">
                <h2 id="user-greeting" style="color: #3b82f6; margin-bottom: 20px;">Selamat Datang!</h2>

                <p style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px;">
                    Sertai undian untuk menentukan ciptaan permainan video terbaik! 
                    Lihat semua pilihan yang ada, kemudian pilih dan undi permainan video yang paling anda gemari. 
                    Terima kasih kerana menyokong para pencipta!
                </p>
                
                <button class="btn btn-primary" onclick="window.location.href='undian.php'" style="max-width: 300px;">
                    Undi Permainan Video
                </button>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <script>
        // 1. Get the user's name from localStorage
        const userName = localStorage.getItem('voter_name');

        // 2. Check if the name exists
        if (userName) {
            // Update the header with the actual name
            document.getElementById('user-greeting').innerText = "Selamat Datang, " + userName + "!";
        } else {
            // 3. If no name found (not logged in), redirect to login page
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>