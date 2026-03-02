// js/main.js
$(document).ready(function () {
    let dataTable;

    // Inicializar Select2
    $('#filtroSucursal').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccionar sucursal...',
        allowClear: true
    });

    // Cargar datos iniciales
    cargarMeses();
    cargarAnios();
    cargarSucursales();

    // Inicializar DataTable
    inicializarDataTable();

    // Cargar objetivos por defecto
    cargarObjetivos();

    // Event listeners
    $('#btnFiltrar').click(cargarObjetivos);
    $(document).on('click', '.btn-guardar', function () {
        const fila = $(this).closest('tr');
        mostrarModalConfirmacion(fila);
    });
    $('#btnConfirmarCambio').click(guardarObjetivo);

    // Formatear números en inputs
    $(document).on('input', '.objetivo-input', function () {
        formatearNumeroInput(this);
    });

    function cargarMeses() {
        const meses = {
            1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril',
            5: 'Mayo', 6: 'Junio', 7: 'Julio', 8: 'Agosto',
            9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre'
        };

        const mesActual = new Date().getMonth() + 1;
        let options = '';

        Object.keys(meses).forEach(key => {
            const selected = key == mesActual ? 'selected' : '';
            options += `<option value="${key}" ${selected}>${meses[key]}</option>`;
        });

        $('#filtroMes').html(options);
    }

    function cargarAnios() {
        const anioActual = new Date().getFullYear();
        let options = '';

        for (let i = anioActual - 2; i <= anioActual + 1; i++) {
            const selected = i === anioActual ? 'selected' : '';
            options += `<option value="${i}" ${selected}>${i}</option>`;
        }

        $('#filtroAnio').html(options);
    }

    function cargarSucursales() {
        $.ajax({
            url: 'controller/sucursales.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data && data.error) {
                    console.error('Error al cargar sucursales:', data.error);
                    return;
                }
                if (!Array.isArray(data)) {
                    console.error('La API de sucursales no devolvió un array:', data);
                    return;
                }
                let options = '<option value="">Todas las sucursales</option>';
                data.forEach(function (sucursal) {
                    options += '<option value="' + sucursal.NRO_SUCURSAL + '">' + sucursal.NRO_SUCURSAL + ' - ' + sucursal.DESC_SUCURSAL + '</option>';
                });
                $('#filtroSucursal').html(options);
            },
            error: function () {
                console.error('Error al cargar sucursales');
            }
        });
    }

    function inicializarDataTable() {
        // Verificar si DataTable ya está inicializada
        if ($.fn.DataTable.isDataTable('#tablaObjetivos')) {
            // Si ya existe, la destruimos primero
            $('#tablaObjetivos').DataTable().destroy();
        }

        dataTable = $('#tablaObjetivos').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 50,
            responsive: true,
            order: [[1, 'asc']],
            columnDefs: [
                {
                    targets: [0], // Nro. Sucursal
                    className: 'text-center'
                },
                {
                    targets: [2], // Objetivo Actual
                    className: 'text-end'
                },
                {
                    targets: [3, 4, 5], // Nuevo Objetivo, Fecha, Acciones
                    className: 'text-center'
                },
                {
                    targets: 5, // Acciones
                    orderable: false
                }
            ]
        });
    }

    function cargarObjetivos() {
        const mes = $('#filtroMes').val();
        const anio = $('#filtroAnio').val();
        const sucursal = $('#filtroSucursal').val();

        $('.loading').show();

        $.ajax({
            url: 'controller/objetivos.php',
            method: 'GET',
            data: { mes: mes, anio: anio, sucursal: sucursal },
            dataType: 'json',
            success: function (data) {
                // Verificar si hay errores en la respuesta
                if (data && data.error) {
                    $('.loading').hide();
                    Swal.fire('Error', data.error, 'error');
                    return;
                }

                // Verificar que data sea un array
                if (!Array.isArray(data)) {
                    $('.loading').hide();
                    console.error('La API no devolvió un array:', data);
                    return;
                }

                // Verificar si DataTable existe antes de usar clear()
                if ($.fn.DataTable.isDataTable('#tablaObjetivos')) {
                    dataTable.clear();
                } else {
                    // Si no existe, inicializar primero
                    inicializarDataTable();
                }

                data.forEach(function (objetivo) {
                    const importeActual = parseFloat(objetivo.importeObjetivos || 0);
                    const fechaMod = formatearFecha(objetivo.fechaUltMod);

                    dataTable.row.add([
                        objetivo.NRO_SUCURSAL,
                        objetivo.DESC_SUCURSAL,
                        '$' + formatearNumero(importeActual, 0),
                        '<input type="text" class="form-control objetivo-input" ' +
                        'value="' + formatearNumero(importeActual, 0) + '" ' +
                        'data-valor="' + importeActual + '" ' +
                        'data-idtango="' + (objetivo.idTango || objetivo.NRO_SUCURSAL) + '" ' +
                        'data-sucursal="' + objetivo.DESC_SUCURSAL + '">',
                        fechaMod,
                        '<button type="button" class="btn btn-sm btn-success btn-guardar" ' +
                        'data-idtango="' + (objetivo.idTango || objetivo.NRO_SUCURSAL) + '" ' +
                        'data-sucursal="' + objetivo.DESC_SUCURSAL + '">' +
                        '<i class="fas fa-save"></i> Guardar</button>'
                    ]);
                });

                dataTable.draw();
                $('.loading').hide();
            },
            error: function (xhr) {
                $('.loading').hide();
                let errMsg = 'No se pudieron cargar los objetivos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errMsg = xhr.responseJSON.error;
                }
                Swal.fire('Error', errMsg, 'error');
            }
        });
    }

    function mostrarModalConfirmacion(fila) {
        const input = fila.find('.objetivo-input');
        const sucursal = input.data('sucursal');
        const objetivoActual = parseFloat(input.data('valor') || 0);
        const objetivoNuevo = parseFloat(limpiarNumero(input.val()));

        $('#sucursalNombre').text(sucursal);
        $('#objetivoActual').text(formatearNumero(objetivoActual, 0));
        $('#objetivoNuevo').text(formatearNumero(objetivoNuevo, 0));

        // Almacenar datos en el modal para el guardado
        $('#modalConfirmacion').data('fila', fila);
        $('#modalConfirmacion').modal('show');
    }

    function guardarObjetivo() {
        const modal = $('#modalConfirmacion');
        const fila = modal.data('fila');
        const input = fila.find('.objetivo-input');

        const datos = {
            idTango: input.data('idtango'),
            mes: $('#filtroMes').val(),
            anio: $('#filtroAnio').val(),
            nuevoImporteObjetivo: limpiarNumero(input.val())
        };

        $.ajax({
            url: 'controller/editar_objetivo.php',
            method: 'POST',
            data: datos,
            dataType: 'json',
            success: function (response) {
                modal.modal('hide');

                if (response.success) {
                    Swal.fire('Éxito', response.message, 'success').then(() => {
                        cargarObjetivos(); // Recargar tabla
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                modal.modal('hide');
                Swal.fire('Error', 'Ocurrió un error al guardar el objetivo', 'error');
            }
        });
    }

    function formatearFecha(fecha) {
        if (!fecha) return '-';

        // Caso objeto de PHP: { date: '2025-08-21 16:55:05.557', ... }
        if (typeof fecha === 'object' && fecha.date) {
            fecha = String(fecha.date);
        }

        let d = null;

        // String crudo
        if (typeof fecha === 'string') {
            const raw = fecha.trim();

            // 1) Intento nativo primero (sirve para ISO)
            const tryNative = new Date(raw);
            if (!isNaN(tryNative.getTime())) {
                d = tryNative;
            } else {
                // 2) SQL Server "YYYY-MM-DD HH:mm:ss[.fff][.ffffff]"
                const m = raw.match(
                    /^(\d{4})[-\/](\d{2})[-\/](\d{2})[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,6}))?)?$/
                );
                if (m) {
                    const [, y, mo, da, h, mi, s = '00', frac = '0'] = m;
                    d = new Date(
                        Number(y),
                        Number(mo) - 1,
                        Number(da),
                        Number(h),
                        Number(mi),
                        Number(s),
                        // milisegundos (si vienen microsegundos, recorto a 3 dígitos)
                        Number(frac.padEnd(3, '0').slice(0, 3))
                    );
                }
            }
        } else if (fecha instanceof Date) {
            d = fecha;
        } else if (typeof fecha === 'number') {
            d = new Date(fecha);
        }

        if (!d || isNaN(d.getTime())) return '-';

        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        const HH = String(d.getHours()).padStart(2, '0');
        const MM = String(d.getMinutes()).padStart(2, '0');

        return `${dd}/${mm}/${yyyy} ${HH}:${MM}`;
    }

    function formatearNumero(numero, decimales) {
        if (decimales === undefined) {
            decimales = 2;
        }
        if (isNaN(numero) || numero === null) {
            return '0';
        }
        return Number(numero).toLocaleString('es-AR', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales
        });
    }

    function limpiarNumero(numeroString) {
        if (!numeroString) {
            return 0;
        }
        return parseFloat(numeroString.toString().replace(/[.,]/g, function (match) {
            return match === ',' ? '.' : '';
        }).replace(/[^\d.]/g, '')) || 0;
    }

    function formatearNumeroInput(input) {
        let valor = input.value.replace(/[^\d]/g, '');
        if (valor) {
            const numero = parseInt(valor);
            input.value = numero.toLocaleString('es-AR');
        }
    }
});