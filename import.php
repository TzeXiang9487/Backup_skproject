<?php
session_start();
require_once 'config.php';

// Semakan akses admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// ── Mengendalikan import melalui AJAX POST ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $rawInput = '';
    $handle = fopen('php://input', 'r');
    if ($handle) {
        while (!feof($handle)) { $rawInput .= fread($handle, 8192); }
        fclose($handle);
    }

    $data = json_decode($rawInput, true);

    if (!$data || !isset($data['records'])) {
        echo json_encode(['success' => false, 'message' => 'Tiada data diterima.']);
        exit();
    }

    $records = $data['records'];
    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    if (empty($records)) {
        echo json_encode(['success' => false, 'message' => 'Fail kosong atau tiada data.']);
        exit();
    }

    // Sahkan bahawa data adalah jenis kelas
    $firstCell = isset($records[0][0]) ? trim($records[0][0]) : '';
    if (!preg_match('/^K/i', $firstCell)) {
        echo json_encode(['success' => false, 'message' => 'Format tidak dikenali. Lajur pertama mestilah idKelas (contoh: K01).']);
        exit();
    }

    foreach ($records as $index => $row) {
        $rowNum  = $index + 2;
        $idKelas = isset($row[0]) ? trim($row[0]) : '';
        $kelas   = isset($row[1]) ? trim($row[1]) : '';

        if (empty($idKelas) || empty($kelas)) {
            $errors[] = "Baris {$rowNum}: idKelas atau nama kelas kosong.";
            $skipped++; continue;
        }

        $chk = $conn->prepare("SELECT idKelas FROM kelas WHERE idKelas = ?");
        $chk->bind_param("s", $idKelas);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) { $skipped++; $chk->close(); continue; }
        $chk->close();

        $stmt = $conn->prepare("INSERT INTO kelas (idKelas, kelas) VALUES (?, ?)");
        $stmt->bind_param("ss", $idKelas, $kelas);
        if ($stmt->execute()) { $inserted++; } else { $errors[] = "Baris {$rowNum}: Ralat memasukkan kelas."; $skipped++; }
        $stmt->close();
    }

    echo json_encode([
        'success'  => true,
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'errors'   => $errors
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Import Data - Sistem Undian</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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

    <div class="page-wrapper">
        <div class="container">
            <div class="header">
                <span>Sistem D'Undi Pertandingan Penciptaan Permainan Video</span>
                <button id="theme-btn" class="theme-toggle-btn" onclick="toggleTheme()" title="Tukar Mod Tema">🌙</button>
            </div>

            <div class="nav-bar">
                <a href="admin.php" class="nav-item">Papan Pemuka</a>
                <a href="import.php" class="nav-item active">Import</a>
                <a href="keputusan.php" class="nav-item">Keputusan</a>
                <a href="logout.php" class="nav-item">Keluar</a>
            </div>

            <div class="content">
                <h2 style="color: #ef4444; margin-bottom: 5px;">Import Data Kelas</h2>
                <p style="color: #94a3b8; margin-bottom: 15px;">
                    Muat naik fail Excel atau CSV yang mengandungi data kelas. Lajur pertama mestilah <strong>idKelas</strong> (contoh: K01) dan lajur kedua <strong>nama kelas</strong>.
                </p>

                <!-- Result message -->
                <div class="import-result" id="import-result"></div>

                <!-- Drop Zone -->
                <div class="drop-zone" id="drop-zone" onclick="document.getElementById('fileInput').click()">
                    <div class="drop-zone-icon">📂</div>
                    <div class="drop-zone-title">Klik untuk pilih fail atau seret & lepas di sini</div>
                    <div class="drop-zone-sub">Format disokong: .xlsx, .xls, .csv</div>
                    <button type="button" class="btn btn-primary" style="pointer-events: none;">Pilih Fail Excel / CSV</button>
                </div>
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;" onchange="handleFile(this.files[0])">

                <!-- Preview Section (replaces drop zone on file load) -->
                <div class="preview-section" id="preview-section">
                    <div class="preview-table-wrap">
                        <table id="preview-table" style="width: 100%;"></table>
                    </div>

                    <div class="confirm-buttons">
                        <button type="button" class="btn btn-secondary" onclick="batalImport()">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="sahImport()">Sah</button>
                    </div>
                </div>
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <div id="drag-overlay">
        <div id="drag-overlay-text">📂 Lepaskan fail Excel/CSV di sini</div>
    </div>

    <script>
        let pendingRecords = null; // Holds parsed CSV data waiting for confirmation

        // ── Parse file and show preview ──
        function handleFile(file) {
            if (!file) return;

            // Reset any previous result message
            sembunyikanHasil();

            const reader = new FileReader();
            reader.onload = function(e) {
                const data     = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array', cellDates: true });
                const sheet    = workbook.Sheets[workbook.SheetNames[0]];
                const rows     = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

                const records = rows
                    .filter(r => r.some(c => String(c).trim() !== ''))
                    .map(r => r.map(c => {
                        if (c instanceof Date) {
                            return c.getFullYear() + '-' +
                                   String(c.getMonth()+1).padStart(2,'0') + '-' +
                                   String(c.getDate()).padStart(2,'0');
                        }
                        return String(c).trim();
                    }));

                if (records.length === 0) {
                    tunjukkanHasil('Fail kosong atau tiada data dijumpai.', false);
                    return;
                }

                // Validate it's kelas data
                const first = records[0][0];
                if (!/^K/i.test(first)) {
                    tunjukkanHasil('❌ Format tidak dikenali. Lajur pertama mestilah idKelas (contoh: K01).', false);
                    return;
                }

                // Store records and show preview
                pendingRecords = records;
                tunjukkanPratonton(records);
            };
            reader.readAsArrayBuffer(file);
            document.getElementById('fileInput').value = '';
        }

        // ── Show preview table, hide drop zone ──
        function tunjukkanPratonton(records) {
            const dropZone      = document.getElementById('drop-zone');
            const previewSection = document.getElementById('preview-section');
            const table         = document.getElementById('preview-table');

            const thead = `<thead><tr><th>ID Kelas</th><th>Nama Kelas</th></tr></thead>`;
            const tbody = `<tbody>${records.map(r =>
                `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`
            ).join('')}</tbody>`;
            table.innerHTML = thead + tbody;

            // Swap visibility
            dropZone.style.display      = 'none';
            previewSection.style.display = 'block';
        }

        // ── "Batal" — go back to drop zone ──
        function batalImport() {
            pendingRecords = null;
            document.getElementById('drop-zone').style.display      = 'block';
            document.getElementById('preview-section').style.display = 'none';
            document.getElementById('preview-table').innerHTML       = '';
            sembunyikanHasil();
        }

        // ── "Sah" — send data to server ──
        function sahImport() {
            if (!pendingRecords) return;

            const sahBtn = document.querySelector('.confirm-buttons .btn-primary');
            sahBtn.disabled    = true;
            sahBtn.textContent = 'Mengimport...';

            fetch('import.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ records: pendingRecords })
            })
            .then(res => {
                if (!res.ok) {
                    return res.text().then(t => { throw new Error('Ralat Pelayan: ' + t); });
                }
                return res.json();
            })
            .then(result => {
                // Hide preview, show drop zone again
                batalImport();

                if (result.success) {
                    let msg = `Berjaya diimport ke <strong>Jadual: Kelas</strong> — Rekod Baharu: <strong>${result.inserted}</strong>, Dilangkau (Wujud): <strong>${result.skipped}</strong>.`;
                    if (result.errors && result.errors.length > 0) {
                        msg += '<br><small style="color:#fca5a5;">' + result.errors.join('<br>') + '</small>';
                    }
                    tunjukkanHasil(msg, true);
                } else {
                    tunjukkanHasil('❌ ' + result.message, false);
                }
            })
            .catch(err => {
                batalImport();
                tunjukkanHasil('❌ ' + err.message, false);
            })
            .finally(() => {
                sahBtn.disabled    = false;
                sahBtn.textContent = 'Sah';
            });
        }

        // ── Result message helpers ──
        function tunjukkanHasil(msg, success) {
            const el = document.getElementById('import-result');
            el.style.display         = 'block';
            el.style.backgroundColor = success ? '#064e3b' : '#7f1d1d';
            el.style.color           = success ? '#6ee7b7' : '#fca5a5';
            el.innerHTML             = msg;
        }

        function sembunyikanHasil() {
            const el = document.getElementById('import-result');
            el.style.display = 'none';
            el.innerHTML     = '';
        }

        // ── Drag and Drop ──
        const dropZone = document.getElementById('drop-zone');
        dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFile(e.dataTransfer.files[0]);
        });

        const overlay = document.getElementById('drag-overlay');
        document.addEventListener('dragenter', e => { e.preventDefault(); overlay.classList.add('active'); });
        document.addEventListener('dragover',  e => e.preventDefault());
        document.addEventListener('dragleave', e => { if (!e.relatedTarget) overlay.classList.remove('active'); });
        document.addEventListener('drop', e => {
            e.preventDefault();
            overlay.classList.remove('active');
            if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
        });
    </script>
</body>
</html>