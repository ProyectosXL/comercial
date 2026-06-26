<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coeficiente De Ajuste</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"
        integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">


    <style>

    </style>
</head>

<body>

    <div class="modal fade" id="modalCA" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"
        integrity="sha512-TPh2Oxlg1zp+kz3nFA0C5vVC6leG/6mm1z9+mA81MI5eaUVqasPLO8Cuk4gMF4gUfP5etR73rgU/8PNMsSesoQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>



</body>
<style>
    #tableImportExcel thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #343a40;
        color: #fff;
        font-size: .8rem;
        font-weight: 600;
        letter-spacing: .4px;
        white-space: nowrap;
    }
    #tableImportExcel tbody tr td {
        font-size: .82rem;
        vertical-align: middle;
    }
    .import-stat-card {
        flex: 1;
        border-radius: 8px;
        padding: 12px 18px;
        border-left: 4px solid;
    }
    .import-stat-card .stat-value {
        font-size: 1.7rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .import-stat-card .stat-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #666;
        margin-top: 2px;
    }
    tr.row-error { background-color: #fff5f5 !important; }
    tr.row-ok    { background-color: #f6fff8 !important; }
</style>
<script>

    let _importFile = null;

    const displayFileName = (input) => {
        if (!input.files[0]) return;
        _importFile = input.files[0];
        var fileName = _importFile.name;

        document.querySelector('#modal-subtitle').textContent = fileName;
        document.querySelector('#upload-area').innerHTML = `
            <i class="bi bi-file-earmark-check" style="font-size:3rem; color:#198754;"></i>
            <p style="margin-top:12px; font-size:1rem; color:#198754; font-weight:600; margin-bottom:4px;">${fileName}</p>
            <p style="font-size:.82rem; color:#888; margin-bottom:0;">Archivo seleccionado y listo para procesar</p>
        `;
        document.querySelector('.modal-footer').innerHTML = `
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFileInput()">
                <i class="bi bi-x-circle"></i> Quitar archivo
            </button>
            <button type="button" class="btn btn-primary" onclick="readExcelFile()">
                <i class="bi bi-eye"></i> Vista previa
            </button>
        `;
    }

    const clearFileInput = () => {
        _importFile = null;
        resetearImport();
    }

    const readExcelFile = () => {
        var file = _importFile;
        if (!file) return;
        var reader = new FileReader();

        reader.onload = function (e) {
            var data = new Uint8Array(e.target.result);
            var workbook = XLSX.read(data, { type: 'array' });
            var sheet = workbook.Sheets[workbook.SheetNames[0]];

            document.querySelector('#titleModal').innerHTML =
                "<i class='bi bi-arrow-repeat'></i> Actualizar maestro de destinos";

            var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });
            let arrayDeError = [];
            let arrayDeErrorLiquidacion = [];

            jsonData.forEach((element, x) => {
                if (x == 0) return;
                const destinosValidos = ['CONTINUO','DISCONTINUO','DISCONTINUO VERANO','DISCONTINUO INVIERNO','GUARDAR','GUARDAR VERANO','GUARDAR INVIERNO'];
                if (!destinosValidos.includes(element[1])) arrayDeError.push(element[0]);
                if (element[2] != 'SI' && element[2] != null) arrayDeErrorLiquidacion.push(element[0]);
            });

            if (arrayDeError.length > 0) {
                Swal.fire({ icon: 'error', title: 'Error en el archivo', text: `Los siguientes artículos tienen un valor inválido en DESTINO: ${arrayDeError.join(', ')}` });
                return;
            }
            if (arrayDeErrorLiquidacion.length > 0) {
                Swal.fire({ icon: 'error', title: 'Error en el archivo', text: `Los siguientes artículos tienen un valor inválido en LIQUIDACION: ${arrayDeErrorLiquidacion.join(', ')}. Solo se permiten SI o vacío.` });
                return;
            }

            let totalRegistros = jsonData.length - 1;

            Swal.fire({ title: 'Cargando...', text: 'Obteniendo descripciones.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                type: 'POST',
                url: '../Controller/maestroDestinosController.php?accion=traerDescripcion',
                data: { data: JSON.stringify(jsonData) },
                success: function (res) {
                    res = JSON.parse(res);
                    const descMap = new Map();
                    res.forEach(item => descMap.set(item[0].toString().trim(), item[1]));

                    var tableHtml = `
                        <table class="table table-hover table-sm table-striped text-center mb-0" id="tableImportExcel">
                            <thead>
                                <tr>
                                    <th style="width:130px">Artículo</th>
                                    <th style="text-align:left">Descripción</th>
                                    <th style="width:180px">Destino</th>
                                    <th style="width:110px">Liquidación</th>
                                    <th style="width:36px"></th>
                                </tr>
                            </thead>
                            <tbody>`;

                    jsonData.forEach((element, x) => {
                        if (x == 0) return;
                        let cod = (element[0] || '').toString().trim();
                        let descripcion = descMap.get(cod) || '';
                        element[1] = element[1] || '';
                        element[2] = element[2] || '';
                        tableHtml += `<tr>
                            <td style="font-weight:500">${cod}</td>
                            <td style="text-align:left">${descripcion}</td>
                            <td>${element[1]}</td>
                            <td>${element[2]}</td>
                            <td></td>
                        </tr>`;
                    });

                    tableHtml += `</tbody></table>`;

                    document.querySelector('#modalImportBody').innerHTML = `
                        <div style="display:flex; gap:10px; margin-bottom:18px;">
                            <div class="import-stat-card" style="background:#e3f2fd; border-color:#1976d2;">
                                <div class="stat-value" style="color:#1565c0;" id="stat-total">${totalRegistros}</div>
                                <div class="stat-label">Registros cargados</div>
                            </div>
                            <div class="import-stat-card" style="background:#f1f8e9; border-color:#689f38;" id="card-ok">
                                <div class="stat-value" style="color:#33691e;" id="stat-ok">—</div>
                                <div class="stat-label">Válidos</div>
                            </div>
                            <div class="import-stat-card" style="background:#fce4ec; border-color:#e53935;" id="card-errors">
                                <div class="stat-value" style="color:#b71c1c;" id="stat-errors">—</div>
                                <div class="stat-label">Con errores</div>
                            </div>
                        </div>
                        <div style="max-height:420px; overflow-y:auto; border-radius:6px; border:1px solid #dee2e6;">
                            ${tableHtml}
                        </div>
                    `;

                    document.querySelector('.modal-footer').innerHTML = `
                        <span style="flex:1; color:#888; font-size:.82rem;">
                            <i class="bi bi-info-circle"></i> Revise la vista previa y haga clic en <strong>Validar y actualizar</strong>.
                        </span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" onclick="actualizar()">
                            <i class="bi bi-check2-circle"></i> Validar y actualizar
                        </button>
                    `;
                    document.querySelector('.modal-footer').style.cssText += 'display:flex; align-items:center;';

                    Swal.close();
                }
            });
        };

        reader.readAsArrayBuffer(file);
    }

    const actualizar = () => {
        Swal.fire({
            title: 'Validando...',
            text: 'Verificando artículos en el maestro.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        let allRows = document.querySelectorAll('#tableImportExcel tbody tr');
        let newArray = [];

        allRows.forEach(element => {
            newArray.push([
                element.querySelectorAll('td')[0].textContent.trim(),
                element.querySelectorAll('td')[1].textContent.trim(),
                element.querySelectorAll('td')[2].textContent.trim(),
                element.querySelectorAll('td')[3].textContent.trim()
            ]);
        });

        $.ajax({
            type: 'POST',
            url: '../Controller/maestroDestinosController.php?accion=comprobarArticulos',
            data: { data: JSON.stringify(newArray) },
            success: function (data) {
                data = JSON.parse(data);
                const existenceMap = new Map();
                data.forEach(item => existenceMap.set(item[0].toString().trim(), item[1]));

                let puedeContinuar = true;
                let totalErrores = 0;

                allRows.forEach(element => {
                    let errores = '';
                    let codArt  = element.querySelectorAll('td')[0].textContent.trim();
                    let destino = element.querySelectorAll('td')[2].textContent.trim();

                    if (codArt == '')  errores += 'Artículo requerido\n';
                    if (destino == '') errores += 'Destino requerido\n';
                    if (codArt != '' && existenceMap.get(codArt) !== 1)
                        errores += 'Artículo no existe en el maestro\n';

                    if (errores != '') {
                        totalErrores++;
                        puedeContinuar = false;
                        element.classList.add('row-error');
                        element.classList.remove('row-ok');
                        element.querySelectorAll('td')[4].innerHTML =
                            `<i class="bi bi-exclamation-circle-fill" style="color:#dc3545; cursor:help;"
                               title="${errores.trim()}" data-toggle="tooltip" data-placement="left"></i>`;
                    } else {
                        element.classList.add('row-ok');
                        element.classList.remove('row-error');
                        element.querySelectorAll('td')[4].innerHTML =
                            `<i class="bi bi-check-circle-fill" style="color:#198754;"></i>`;
                    }
                });

                // Actualizar stats
                let totalOk = allRows.length - totalErrores;
                document.querySelector('#stat-ok').textContent     = totalOk;
                document.querySelector('#stat-errors').textContent = totalErrores;
                if (totalErrores == 0) {
                    document.querySelector('#card-errors').style.background   = '#f1f8e9';
                    document.querySelector('#card-errors').style.borderColor  = '#689f38';
                    document.querySelector('#stat-errors').style.color        = '#33691e';
                } else {
                    document.querySelector('#card-errors').style.background  = '#ffebee';
                    document.querySelector('#card-errors').style.borderColor = '#e53935';
                }

                $('[data-toggle="tooltip"]').tooltip();

                if (puedeContinuar) {
                    Swal.fire({
                        title: 'Actualizando...',
                        text: 'Procesando cambios masivos.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    $.ajax({
                        type: 'POST',
                        url: '../Controller/maestroDestinosController.php?accion=actualizarMaestro',
                        data: { data: JSON.stringify(newArray) },
                        success: function () {
                            Swal.fire({
                                icon: 'success',
                                title: 'Actualización completada',
                                html: `
                                    <div style="font-size:2.2rem;font-weight:700;color:#1976d2;margin:8px 0 4px;">${newArray.length}</div>
                                    <div style="color:#555;font-size:.95rem;">registro${newArray.length !== 1 ? 's' : ''} actualizado${newArray.length !== 1 ? 's' : ''} correctamente</div>
                                    <div style="margin-top:10px;font-size:.8rem;color:#999;">El maestro de destinos fue actualizado con los destinos anteriores registrados.</div>
                                `,
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#044f8e',
                                timer: 4000,
                                timerProgressBar: true
                            }).then(() => location.reload());
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Hay errores en el archivo',
                        html: `<b>${totalErrores}</b> fila${totalErrores > 1 ? 's tienen' : ' tiene'} errores.<br>
                               Revise los íconos <span style="color:#dc3545">●</span> en la tabla.`,
                        confirmButtonText: 'Entendido'
                    });
                }
            }
        });
    }

</script>