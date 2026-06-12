let currentEnv = 'ARG';
let currentMode = 'auto';
let mainDataTable = null;
let importedData = [];
let databasePrices = {};

$(document).ready(function() {
    initEvents();
    // Stop propagation on the L95 export checkbox to prevent sorting the column when clicked
    $(document).on('click', '#exportL95', function(e) {
        e.stopPropagation();
    });
});

function initEvents() {
    // Help Button
    $('#helpBtn').click(function() {
        $('#modalManual').modal('show');
    });

    // Env selection
    $('.card-selection[data-env]').click(function() {
        currentEnv = $(this).data('env');
        const envName = (currentEnv === 'ARG') ? 'ARGENTINA' : 'URUGUAY';
        
        $('#envBadgeHeader').text(envName);
        $('#modeSubtitle').text(`Entorno actual: ${envName}`);
        
        goToStep('step-mode');
    });

    // Mode selection
    $('.card-selection[data-mode]').click(function() {
        currentMode = $(this).data('mode');
        
        // Reset state when switching mode
        importedData = [];
        if (mainDataTable) {
            mainDataTable.clear().draw();
        }
        $('#table-container').addClass('d-none');
        $('#baseCodes').val('');
        
        if (currentMode === 'auto') {
            $('#import-area').removeClass('d-none');
            $('#manual-area').addClass('d-none');
            $('#action-title').text('Carga Automática');
        } else {
            $('#import-area').addClass('d-none');
            $('#manual-area').removeClass('d-none');
            $('#action-title').text('Edición Manual');
        }
        
        goToStep('step-action');
    });

    // Manual search button
    $('#searchManualBtn').click(function() {
        const baseInput = $('#baseCodes').val();
        if (!baseInput.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor, ingrese al menos un código'
            });
            return;
        }
        // Split by comma, space or newline and clean
        const codes = baseInput.split(/[\s,]+/).filter(c => c.trim() !== '');
        loadManualTable(codes);
    });

    // Dropzone logic
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('excelFile');

    dropzone.addEventListener('click', () => fileInput.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFile(e.target.files[0]);
        }
    });

    // Export button
    $('#exportBtn').click(exportToTango);

    // Navigation buttons
    window.goToStep = function(stepId) {
        // If we go back from step-action, clear data
        if (stepId === 'step-mode' || stepId === 'step-env') {
            importedData = [];
            if (mainDataTable) mainDataTable.clear().draw();
            $('#table-container').addClass('d-none');
            $('#baseCodes').val('');
        }
        
        $('section').addClass('d-none');
        $('#' + stepId).removeClass('d-none');
    };

    $('#downloadTemplate').click(function(e) {
        e.preventDefault();
        const data = [
            ["COD_ARTICU", "PRECIO"],
            ["EJEMPLO01", 1500.50],
            ["EJEMPLO02", 2900.00]
        ];
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Modelo");
        XLSX.writeFile(wb, "Modelo_Precios_Tango.xlsx");
    });
}

function handleFile(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        const json = XLSX.utils.sheet_to_json(worksheet);
        
        processExcelData(json);
    };
    reader.readAsArrayBuffer(file);
}

async function processExcelData(data) {
    if (data.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Archivo vacío',
            text: 'El archivo de Excel seleccionado no contiene datos.'
        });
        return;
    }

    const firstRow = data[0];
    if (!firstRow.hasOwnProperty('COD_ARTICU') || !firstRow.hasOwnProperty('PRECIO')) {
        Swal.fire({
            icon: 'error',
            title: 'Formato incorrecto',
            text: 'El archivo debe contener exactamente las columnas COD_ARTICU y PRECIO.'
        });
        return;
    }

    showLoading('Obteniendo información de la base de datos...');

    try {
        const codes = data.map(item => item.COD_ARTICU);
        
        const dbData = await $.ajax({
            url: 'controller/ajax_handler.php',
            method: 'POST',
            data: {
                action: 'get_articles_info',
                env: currentEnv,
                codes: JSON.stringify(codes)
            },
            dataType: 'json'
        });

        if (dbData.error) throw new Error(dbData.error);

        databasePrices = dbData.prices;
        const descriptions = dbData.descriptions;

        importedData = data.map(item => {
            const code = item.COD_ARTICU.toString().trim().toUpperCase();
            const newPrice20 = parseFloat(item.PRECIO);
            const currentPrice20 = databasePrices[code] ? parseFloat(databasePrices[code].PRECIO) : 0;
            const desc = descriptions[code] || { DESCRIPCIO: 'No encontrado', DESCRIP_01: '', SINONIMO: '', COSTO_LIMITE: 0 };
            const iva = desc.PORCENTAJE ? parseFloat(desc.PORCENTAJE) : 21;
            const limitPrice = desc.COSTO_LIMITE ? parseFloat(desc.COSTO_LIMITE) : 0;

            return calculateRow(code, desc, currentPrice20, newPrice20, iva, limitPrice);
        });

        renderTable();
        hideLoading();
        $('#import-area').addClass('d-none');
        $('#table-container').removeClass('d-none');

    } catch (error) {
        hideLoading();
        Swal.fire({
            icon: 'error',
            title: 'Error de procesamiento',
            text: error.message
        });
    }
}

function calculateRow(code, desc, currentP20, newP20, iva, limitPrice) {
    let row = {
        COD_ARTICU: code,
        DESCRIPCIO: desc.DESCRIPCIO,
        DESCRIP_01: desc.DESCRIP_01,
        SINONIMO: desc.SINONIMO,
        BASE: desc.BASE || '',
        PRECIO_ACTUAL: currentP20,
        PRECIO_NUEVO: newP20,
        IVA: iva,
        COSTO_LIMITE: limitPrice || 0
    };

    if (currentEnv === 'ARG') {
        // L10: 50% de descuento sobre lista 20
        row.LISTA_10 = newP20 * 0.50;
        // L20: CF - C/IVA INCL
        row.LISTA_20 = newP20;
        // L30: 54,55% de descuento sobre lista 20
        row.LISTA_30 = newP20 * (1 - 0.5455);
        // L70: 20% de descuento sobre lista 30
        row.LISTA_70 = row.LISTA_30 * (1 - 0.20);
        // L95: Replicar L20
        row.LISTA_95 = newP20;
    } else {
        // URY Logic: Only L20 and L45
        // L45 has 75% discount over L20
        row.LISTA_20 = newP20;
        row.LISTA_45 = newP20 * (1 - 0.75);
        // L95: Replicar L20
        row.LISTA_95 = newP20;
    }

    return row;
}

function renderTable() {
    let columns = [
        { title: "CÓDIGO", data: "COD_ARTICU" },
        { title: "DESCRIPCIÓN", data: "DESCRIPCIO", render: (data, type, row) => data === 'No encontrado' ? `<span class="text-danger font-weight-bold">${data}</span>` : data },
        { title: "L20 (ACTUAL)", data: "PRECIO_ACTUAL", render: formatCurrency },
        { 
            title: "L20 (NUEVO)", 
            data: "PRECIO_NUEVO",
            render: (data, type, row) => {
                const isInvalid = currentEnv === 'ARG' && data < row.COSTO_LIMITE;
                const errorClass = isInvalid ? 'is-invalid border-danger text-danger' : '';
                const title = isInvalid ? `Aviso: El precio ($${data.toFixed(2)}) es menor al costo limite ($${row.COSTO_LIMITE.toFixed(2)})` : '';
                return `<input type="number" step="0.01" class="form-control form-control-sm price-input ${errorClass}" style="min-width: 110px;" title="${title}" value="${data.toFixed(2)}" data-code="${row.COD_ARTICU}">`;
            }
        }
    ];

    if (currentEnv === 'ARG') {
        columns.push(
            { title: "L10 (MAY S/I)", data: "LISTA_10", render: formatCurrency },
            { title: "L30 (FRA S/I)", data: "LISTA_30", render: formatCurrency },
            { title: "L70 (TOP S/I)", data: "LISTA_70", render: formatCurrency },
            { title: 'L95 (C/IVA INCL) <br><input type="checkbox" id="exportL95" checked style="cursor: pointer;" title="Exportar L95">', data: "LISTA_95", render: formatCurrency }
        );
    } else {
        columns.push(
            { title: "L45 (FRA S/I)", data: "LISTA_45", render: formatCurrency },
            { title: 'L95 (C/IVA INCL) <br><input type="checkbox" id="exportL95" checked style="cursor: pointer;" title="Exportar L95">', data: "LISTA_95", render: formatCurrency }
        );
    }

    if (mainDataTable) {
        mainDataTable.destroy();
        $('#pricesTable').empty();
    }

    mainDataTable = $('#pricesTable').DataTable({
        data: importedData,
        columns: columns,
        responsive: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json' },
        pageLength: 25,
        dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
        createdRow: function(row, data, dataIndex) {
            if (data.COD_ARTICU === data.BASE) {
                $(row).addClass('row-base');
            }
        },
        drawCallback: function() {
            // Re-bind events after each draw
            $('.price-input').off('change').on('change', function() {
                const newVal = parseFloat($(this).val());
                const code = $(this).data('code');
                if (!isNaN(newVal)) {
                    updateRowData(code, newVal);
                }
            });
        }
    });
}

function updateRowData(code, newP20) {
    const idx = importedData.findIndex(item => item.COD_ARTICU === code);
    if (idx !== -1) {
        const targetItem = importedData[idx];
        const base = targetItem.BASE;

        // Validation for Argentina (on the specific item being edited)
        if (currentEnv === 'ARG' && newP20 < targetItem.COSTO_LIMITE) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación de Costo',
                html: `El precio ingresado (<b>${formatCurrency(newP20)}</b>) es menor al precio límite permitido (<b>${formatCurrency(targetItem.COSTO_LIMITE)}</b>) para el artículo <b>${code}</b>.`,
                confirmButtonText: 'Entendido'
            });
        }

        // Propagate price to ALL articles with the same BASE
        importedData.forEach((item, i) => {
            if (item.BASE === base) {
                importedData[i] = calculateRow(item.COD_ARTICU, item, item.PRECIO_ACTUAL, newP20, item.IVA, item.COSTO_LIMITE);
            }
        });

        renderTable();
    }
}

function formatCurrency(val) {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(val);
}

function showLoading(text) {
    $('#loadingText').text(text || 'Procesando...');
    $('#loadingModal').modal('show');
}

function hideLoading() {
    // Small delay to ensure the 'show' transition doesn't conflict with 'hide'
    setTimeout(() => {
        $('#loadingModal').modal('hide');
        // Fallback: manually remove backdrops if Bootstrap hangs
        if ($('.modal-backdrop').length > 0) {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }
    }, 400);
}

function exportToTango() {
    // Final validation for Argentina - Warning only
    if (currentEnv === 'ARG') {
        const errors = importedData.filter(item => item.PRECIO_NUEVO < item.COSTO_LIMITE);
        if (errors.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención: Margen bajo',
                html: `Hay <b>${errors.length}</b> artículos con precios menores al límite de costo.<br>¿Desea continuar con la exportación de todas formas?`,
                showCancelButton: true,
                confirmButtonText: 'Sí, exportar',
                cancelButtonText: 'No, revisar',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeExport();
                }
            });
            return;
        }
    }

    executeExport();
}

function executeExport() {
    let exportData = [];
    
    // Row 1: CUIT/RUT based on environment
    const docNumber = (currentEnv === 'ARG') ? "30-70300163-3" : "21-93936500-1";
    const row1 = [docNumber];
    
    // Row 2: Real Headers
    const headers = [
        "Cód. Artículo", "Descripción artículo", "Descripción adicional artículo", "Sinónimo", "Cód. Lista", "Desc. Lista", "Moneda", "Unidad", "Precio", "Bonif."
    ];
    
    // Row 3+: Data Rows
    importedData.forEach(item => {
        if (currentEnv === 'ARG') {
            // ARG Exports: L10, L20, L30, L70, L95 (conditional)
            exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 10, '$ MAYORISTA - S/IVA', 'Corriente', 'C/U', item.LISTA_10.toFixed(2), '0.00']);
            exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 20, '$ CF - C/IVA INCL', 'Corriente', 'C/U', item.LISTA_20.toFixed(2), '0.00']);
            exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 30, '$ FRANQUICIA - S/IVA', 'Corriente', 'C/U', item.LISTA_30.toFixed(2), '0.00']);
            exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 70, '$ FRANQTOP - S/IVA', 'Corriente', 'C/U', item.LISTA_70.toFixed(2), '0.00']);
            if ($('#exportL95').is(':checked')) {
                exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 95, 'ECOMMERCE', 'Corriente', 'C/U', item.LISTA_95.toFixed(2), '0.00']);
            }
        } else {
            // URY Exports: L20, L45, L95 (conditional)
            exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 20, '$ CF - C/IVA INCL', 'Corriente', 'C/U', item.LISTA_20.toFixed(2), '0.00']);
            exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 45, '$ FRANQUICIA - S/IVA', 'Corriente', 'C/U', item.LISTA_45.toFixed(2), '0.00']);
            if ($('#exportL95').is(':checked')) {
                exportData.push([item.COD_ARTICU, item.DESCRIPCIO, item.DESCRIP_01 || '', item.SINONIMO || '', 95, 'ECOMMERCE', 'Corriente', 'C/U', item.LISTA_95.toFixed(2), '0.00']);
            }
        }
    });

    // Create Worksheet with correct row offset
    const ws = XLSX.utils.aoa_to_sheet([row1, headers, ...exportData]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Precios");
    
    const fileName = `Precios_Tango_${currentEnv}_${new Date().toISOString().slice(0,10)}.xlsx`;
    XLSX.writeFile(wb, fileName);
}

function loadManualTable(codes) {
    showLoading('Buscando variantes de artículos...');
    $.ajax({
        url: 'controller/ajax_handler.php',
        method: 'POST',
        data: {
            action: 'get_articles_by_base',
            env: currentEnv,
            codes: JSON.stringify(codes)
        },
        success: function(response) {
            const dbData = JSON.parse(response);
            
            if (dbData.length === 0) {
                hideLoading();
                Swal.fire({
                    icon: 'info',
                    title: 'Sin resultados',
                    text: 'No se encontraron variantes para los códigos ingresados.'
                });
                return;
            }

            importedData = dbData.map(item => {
                const p20 = item.PRECIO ? parseFloat(item.PRECIO) : 0;
                const iva = item.PORCENTAJE ? parseFloat(item.PORCENTAJE) : 21;
                const limitPrice = item.COSTO_LIMITE ? parseFloat(item.COSTO_LIMITE) : 0;
                
                return calculateRow(item.COD_ARTICU, item, p20, p20, iva, limitPrice);
            });

            renderTable();
            $('#manual-area').addClass('d-none');
            $('#table-container').removeClass('d-none');
            hideLoading();
        },
        error: function() {
            hideLoading();
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo consultar la base de datos.'
            });
        }
    });
}
