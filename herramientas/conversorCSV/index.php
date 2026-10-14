<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

Auth::exigir();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversor Excel a CSV · XL Extra Large</title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- SheetJS (XLSX & XLS reader/writer) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        :root {
            --primary: #0066cc;
            --emerald-1: #10b981;
            --emerald-2: #059669;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            min-height: 100vh;
        }

        .main-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .page-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .conv-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            max-width: 680px;
            margin: 2rem auto;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .conv-card .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 1.25rem 1.75rem;
            border: none;
        }

        .conv-card .card-header h5 {
            color: #ffffff;
            font-weight: 700;
            margin: 0;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'Sora', sans-serif;
        }

        .conv-card .card-body {
            padding: 2rem;
        }

        .drop-zone {
            border: 2px dashed #a7f3d0;
            border-radius: 14px;
            padding: 2.75rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            background: #f0fdf4;
        }

        .drop-zone:hover, .drop-zone.drag-over {
            border-color: #10b981;
            background: #ecfdf5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .drop-zone i {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 0.75rem;
            display: block;
            transition: transform 0.2s;
        }

        .drop-zone:hover i {
            transform: scale(1.1);
        }

        .drop-zone p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .drop-zone .file-name {
            color: #059669;
            font-weight: 700;
            margin-top: 0.5rem;
            font-size: 1rem;
        }

        .file-badge {
            background: #d1fae5;
            color: #065f46;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .option-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text-main);
            margin-bottom: 0.4rem;
        }

        .btn-convert {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #ffffff;
            font-weight: 700;
            padding: 0.75rem 2.5rem;
            border-radius: 10px;
            font-size: 1.05rem;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-convert:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: #ffffff;
        }

        .btn-convert:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .preview-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            font-size: 0.8rem;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
    </style>
</head>
<body>

    <!-- Header Principal -->
    <header class="main-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php" class="text-decoration-none text-muted"><i class="bi bi-grid-fill"></i> COMER</a></li>
                        <li class="breadcrumb-item text-muted">Precios, Productos y Auditoría</li>
                        <li class="breadcrumb-item active fw-bold text-success" aria-current="page">Conversor CSV</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-filetype-csv text-success"></i> Conversor Excel a CSV
                </h1>
            </div>
        </div>
    </header>

    <div class="container py-4">

        <div class="card conv-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                    Convertir archivo Excel (.xlsx / .xls) a CSV
                </h5>
            </div>
            <div class="card-body">

                <!-- Zona de Drop -->
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none">

                <div class="drop-zone mb-4" id="dropZone">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <p>Arrastrá tu archivo aquí o <strong>hacé clic para seleccionarlo</strong></p>
                    <div id="fileInfo" style="display:none;">
                        <p class="file-name mb-0" id="fileName"></p>
                        <span class="file-badge" id="fileSize"></span>
                    </div>
                </div>

                <!-- Selector de Hoja -->
                <div class="mb-3" id="sheetSelector" style="display:none;">
                    <label class="option-label"><i class="bi bi-layers-half text-success"></i> Hoja a convertir</label>
                    <select class="form-select" id="sheetSelect"></select>
                </div>

                <!-- Opciones de Formato -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="option-label"><i class="bi bi-distribute-horizontal text-primary"></i> Separador</label>
                        <select class="form-select" id="separatorSelect">
                            <option value=";" selected>Punto y coma ( ; )</option>
                            <option value=",">Coma ( , )</option>
                            <option value="&#9;">Tabulador ( TAB )</option>
                            <option value="|">Pipe ( | )</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="option-label"><i class="bi bi-translate text-primary"></i> Codificación</label>
                        <select class="form-select" id="encodingSelect">
                            <option value="utf-8-bom" selected>UTF-8 con BOM (recomendado para Excel)</option>
                            <option value="utf-8">UTF-8 (estándar)</option>
                            <option value="latin1">Latin-1 / ISO-8859-1</option>
                        </select>
                    </div>
                </div>

                <!-- Previsualización Opcional -->
                <div class="preview-box mb-4" id="previewBox">
                    <div class="fw-bold text-muted mb-1"><i class="bi bi-eye"></i> Vista previa (primeras 5 filas):</div>
                    <pre class="m-0 font-monospace" id="previewContent"></pre>
                </div>

                <!-- Botón de Descarga -->
                <div class="text-center">
                    <button type="button" class="btn btn-convert" id="btnConvert" disabled>
                        <i class="bi bi-download" id="btnIcon"></i>
                        <span id="btnText">Descargar CSV</span>
                    </button>
                </div>

            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let currentWorkbook = null;
        let currentFileName = '';

        const dropZone       = document.getElementById('dropZone');
        const fileInput      = document.getElementById('fileInput');
        const fileInfo       = document.getElementById('fileInfo');
        const fileName       = document.getElementById('fileName');
        const fileSize       = document.getElementById('fileSize');
        const sheetSelector  = document.getElementById('sheetSelector');
        const sheetSelect    = document.getElementById('sheetSelect');
        const separatorSelect= document.getElementById('separatorSelect');
        const encodingSelect = document.getElementById('encodingSelect');
        const btnConvert     = document.getElementById('btnConvert');
        const previewBox     = document.getElementById('previewBox');
        const previewContent = document.getElementById('previewContent');

        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', e => {
            if (e.target.files.length) handleFile(e.target.files[0]);
        });

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));

        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function handleFile(file) {
            currentFileName = file.name.replace(/\.[^/.]+$/, "");
            fileName.textContent = file.name;
            fileSize.textContent = `${formatBytes(file.size)}`;
            fileInfo.style.display = 'block';

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    currentWorkbook = XLSX.read(data, { type: 'array', raw: false });

                    sheetSelect.innerHTML = '';
                    currentWorkbook.SheetNames.forEach(name => {
                        const opt = document.createElement('option');
                        opt.value = name;
                        opt.textContent = name;
                        sheetSelect.appendChild(opt);
                    });

                    if (currentWorkbook.SheetNames.length > 1) {
                        sheetSelector.style.display = 'block';
                    } else {
                        sheetSelector.style.display = 'none';
                    }

                    btnConvert.disabled = false;
                    updatePreview();

                } catch (err) {
                    Swal.fire('Error al leer archivo', 'El archivo seleccionado no parece ser un Excel válido.', 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function generateCSV() {
            if (!currentWorkbook) return '';

            const selectedSheet = sheetSelect.value || currentWorkbook.SheetNames[0];
            const worksheet = currentWorkbook.Sheets[selectedSheet];
            const sep = separatorSelect.value;

            // Generar CSV usando XLSX utils
            return XLSX.utils.sheet_to_csv(worksheet, {
                FS: sep,
                RS: '\r\n',
                blankrows: false
            });
        }

        function updatePreview() {
            if (!currentWorkbook) return;
            const csv = generateCSV();
            const lines = csv.split('\r\n').filter(l => l.trim() !== '').slice(0, 5);
            if (lines.length > 0) {
                previewContent.textContent = lines.join('\n');
                previewBox.style.display = 'block';
            } else {
                previewBox.style.display = 'none';
            }
        }

        sheetSelect.addEventListener('change', updatePreview);
        separatorSelect.addEventListener('change', updatePreview);

        btnConvert.addEventListener('click', function() {
            if (!currentWorkbook) return;

            const csvData = generateCSV();
            const encoding = encodingSelect.value;
            const outName = `${currentFileName}.csv`;

            let blob;
            if (encoding === 'utf-8-bom') {
                const bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
                blob = new Blob([bom, csvData], { type: 'text/csv;charset=utf-8;' });
            } else if (encoding === 'latin1') {
                // Conversión a ISO-8859-1 (Latin1)
                const buffer = new ArrayBuffer(csvData.length);
                const bufView = new Uint8Array(buffer);
                for (let i = 0; i < csvData.length; i++) {
                    bufView[i] = csvData.charCodeAt(i) & 0xFF;
                }
                blob = new Blob([buffer], { type: 'text/csv;charset=windows-1252;' });
            } else {
                blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
            }

            // Descarga automática
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', outName);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            Swal.fire({
                title: '¡Conversión Exitosa!',
                text: `El archivo ${outName} se descargó correctamente.`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>
</body>
</html>
