<?php
session_start();
require_once 'config.php';

// Semakan akses admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$result_json = null;

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

    $records  = $data['records'];
    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    if (empty($records)) {
        echo json_encode(['success' => false, 'message' => 'Fail kosong atau tiada data.']);
        exit();
    }

    // Mengesan jenis data berdasarkan sel pertama
    $firstCell = isset($records[0][0]) ? trim($records[0][0]) : '';

    if (preg_match('/^C/i', $firstCell)) {
        $type = 'calon';
    } elseif (preg_match('/^K/i', $firstCell)) {
        $type = 'kelas';
    } else {
        echo json_encode(['success' => false, 'message' => 'Format tidak dikenali. Lajur pertama mestilah idCalon (C01) atau idKelas (K01).' ]);
        exit();
    }

    foreach ($records as $index => $row) {
        $rowNum = $index + 2; // Untuk rujukan baris dalam mesej ralat

        if ($type === 'calon') {
            $idCalon   = isset($row[0]) ? trim($row[0]) : '';
            $namaCalon = isset($row[1]) ? trim($row[1]) : '';

            if (empty($idCalon) || empty($namaCalon)) {
                $errors[] = "Baris {$rowNum}: idCalon atau namaCalon kosong.";
                $skipped++; continue;
            }

            // Semak jika data sudah wujud
            $chk = $conn->prepare("SELECT idCalon FROM calon WHERE idCalon = ?");
            $chk->bind_param("s", $idCalon);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) { $skipped++; $chk->close(); continue; }
            $chk->close();

            $stmt = $conn->prepare("INSERT INTO calon (idCalon, namaCalon) VALUES (?, ?)");
            $stmt->bind_param("ss", $idCalon, $namaCalon);
            if ($stmt->execute()) { $inserted++; } else { $errors[] = "Baris {$rowNum}: Ralat memasukkan calon."; $skipped++; }
            $stmt->close();

        } elseif ($type === 'kelas') {
            $idKelas = isset($row[0]) ? trim($row[0]) : '';
            $kelas   = isset($row[1]) ? trim($row[1]) : '';

            if (empty($idKelas) || empty($kelas)) {
                $errors[] = "Baris {$rowNum}: idKelas atau kelas kosong.";
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
    }

    echo json_encode([
        'success'  => true,
        'type'     => $type,
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
    <style>
        html, body { scrollbar-width: none; -ms-overflow-style: none; }
        html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

        .drop-zone {
            border: 3px dashed #334155;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s, background-color 0.3s;
            background-color: #1e293b;
            margin: 20px auto;
            max-width: 600px;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #3b82f6;
            background-color: #1e3a5f;
        }
        .drop-zone-icon  { font-size: 3rem; margin-bottom: 15px; }
        .drop-zone-title { color: #f8fafc; font-size: 1.2rem; font-weight: bold; margin-bottom: 8px; }
        .drop-zone-sub   { color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; }

        .import-result {
            display: none;
            max-width: 600px;
            margin: 0 auto 15px auto;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .preview-wrap {
            max-width: 600px;
            margin: 0 auto 15px auto;
            display: none;
        }
        .preview-wrap h4 {
            color: #94a3b8;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .detected-badge {
            display: inline-block;
            background-color: #1d4ed8;
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        #drag-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(59, 130, 246, 0.15);
            border: 4px dashed #3b82f6;
            z-index: 9999;
            justify-content: center;
            align-items: center;
            pointer-events: none;
        }
        #drag-overlay.active { display: flex; }
#drag-overlay-text {
    color: #3b82f6;
    font-size: 2rem;
    font-weight: bold;
    background: #0f172a;
    padding: 30px 50px;
    border-radius: 16px;
    border: 2px solid #3b82f6;
}

/* Light mode fixes for drop zone text */
body.light-mode .drop-zone-title { color: #1e293b; }
body.light-mode .drop-zone-sub   { color: #5c4800; }

/* Light mode drag overlay */
body.light-mode #drag-overlay {
    background: rgba(212, 175, 55, 0.12);
    border-color: #d4af37;
}
body.light-mode #drag-overlay-text {
    color: #b8962e;
    background: #fffdf5;
    border-color: #d4af37;
}
    </style>
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
                <h2 style="color: #ef4444; margin-bottom: 5px;">Import Data</h2>
                <p style="color: #94a3b8; margin-bottom: 15px;">
                    Sistem akan mengesan jadual sasaran secara automatik berdasarkan format lajur pertama fail anda.
                </p>

                <div class="import-result" id="import-result"></div>

                <div class="preview-wrap" id="preview-wrap">
                    <h4>Pratonton Data</h4>
                    <div id="detected-badge" class="detected-badge"></div>
                    <div style="overflow-x: auto;">
                        <table id="preview-table" style="width: 100%;"></table>
                    </div>
                </div>

                <div class="drop-zone" id="drop-zone" onclick="document.getElementById('fileInput').click()">
                    <div class="drop-zone-icon">📂</div>
                    <div class="drop-zone-title">Klik untuk pilih fail atau seret & lepas di sini</div>
                    <div class="drop-zone-sub">Format disokong: .xlsx, .xls, .csv</div>
                    <button type="button" class="btn btn-primary" style="pointer-events: none;">Pilih Fail Excel / CSV</button>
                </div>
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;" onchange="handleFile(this.files[0])">
            </div>

            <div class="footer">Hak Cipta Goh Tze Xiang @ SPM 2025</div>
        </div>
    </div>

    <div id="drag-overlay">
        <div id="drag-overlay-text">📂 Lepaskan fail Excel/CSV di sini</div>
    </div>

    <script>
        const namaJadual = {
            calon: 'Jadual: Calon',
            kelas: 'Jadual: Kelas'
        };

        function handleFile(file) {
            if (!file) return;
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

                const first = records[0][0];
                let detectedType = '';
                if (/^C/i.test(first))      detectedType = 'calon';
                else if (/^K/i.test(first)) detectedType = 'kelas';

                tunjukkanPratonton(records, detectedType);

                fetch('import.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ records })
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(t => { throw new Error('Ralat Pelayan: ' + t); });
                    }
                    return res.json();
                })
                .then(result => {
                    if (result.success) {
                        let msg = `✅ Berjaya diimport ke <strong>${namaJadual[result.type] || result.type}</strong> — Rekod Baharu: <strong>${result.inserted}</strong>, Dilangkau (Wujud): <strong>${result.skipped}</strong>.`;
                        if (result.errors && result.errors.length > 0) {
                            msg += '<br><small style="color:#fca5a5;">' + result.errors.join('<br>') + '</small>';
                        }
                        tunjukkanHasil(msg, true);
                    } else {
                        tunjukkanHasil('❌ ' + result.message, false);
                    }
                })
                .catch(err => tunjukkanHasil('❌ ' + err.message, false));
            };
            reader.readAsArrayBuffer(file);
            document.getElementById('fileInput').value = '';
        }

        function tunjukkanPratonton(records, type) {
            const wrap  = document.getElementById('preview-wrap');
            const badge = document.getElementById('detected-badge');
            const table = document.getElementById('preview-table');
            badge.textContent          = type ? ('🔍 Jenis Dikesan: ' + (namaJadual[type] || type)) : '⚠️ Format tidak dikenali';
            badge.style.backgroundColor = type ? '#1d4ed8' : '#7f1d1d';
            const thead = `<thead><tr>${records[0].map((_,i) => `<th>Lajur ${i+1}</th>`).join('')}</tr></thead>`;
            const tbody = `<tbody>${records.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}</tbody>`;
            table.innerHTML = thead + tbody;
            wrap.style.display = 'block';
        }

        function tunjukkanHasil(msg, success) {
            const el = document.getElementById('import-result');
            el.style.display      = 'block';
            el.style.backgroundColor = success ? '#064e3b' : '#7f1d1d';
            el.style.color        = success ? '#6ee7b7' : '#fca5a5';
            el.innerHTML          = msg;
        }

        // Kawalan Drag and Drop
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