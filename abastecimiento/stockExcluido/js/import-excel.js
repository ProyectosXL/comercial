// Sistema de Importación de Excel
let articulosAImportar = [];
let articulosExistentes = [];
let hayErrores = false;

$(document).ready(function() {
    // Cargar artículos existentes al inicializar
    cargarArticulosExistentes();
    
    // Event listener para el archivo
    $('#fileupload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            procesarArchivoExcel(file);
        }
    });
    
    // Event listener para el botón guardar
    $('#save').on('click', function() {
        importarArticulos();
    });
    
    // Limpiar al cerrar modal
    $('#altaModalImport').on('hidden.bs.modal', function() {
        limpiarImportacion();
    });
});

function cargarArticulosExistentes() {
    articulosExistentes = [];
    $('#table tr').each(function() {
        const codigo = $(this).find('td:nth-child(4)').text().trim();
        if (codigo) {
            articulosExistentes.push(codigo);
        }
    });
    console.log('Artículos existentes cargados:', articulosExistentes);
}

function procesarArchivoExcel(file) {
    console.log('Procesando archivo:', file.name);
    
    // Validar tipo de archivo
    if (!validarTipoArchivo(file)) {
        return;
    }
    
    // Mostrar loading
    mostrarCargando('Procesando archivo Excel...');
    
    // Leer archivo
    const reader = new FileReader();
    
    reader.onload = async function(e) {
        try {
            const data = e.target.result;
            const workbook = XLSX.read(data, { type: 'binary' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
            
            console.log('Datos del Excel:', jsonData);
            await procesarDatosExcel(jsonData);
            
        } catch (error) {
            console.error('Error procesando Excel:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al procesar archivo',
                text: 'No se pudo leer el archivo Excel. Verifique que sea un archivo válido.'
            });
        }
        Swal.close();
    };
    
    reader.onerror = function() {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error al leer archivo',
            text: 'No se pudo leer el archivo seleccionado.'
        });
    };
    
    reader.readAsBinaryString(file);
}

// 👇 ESTA VERSIÓN ES LA CORRECTA
async function procesarDatosExcel(datos) {
    console.log('Procesando datos:', datos);
    
    articulosAImportar = [];
    $('#tblItems tbody').empty();
    hayErrores = false;
    
    let inicioFila = 0;
    if (datos.length > 0 && datos[0]) {
        const primeraFila = datos[0];
        if (primeraFila[0] && primeraFila[0].toString().toLowerCase().includes('articulo')) {
            inicioFila = 1;
            console.log('Saltando fila de encabezados');
        }
    }
    
    for (let i = inicioFila; i < datos.length; i++) {
        const fila = datos[i];
        if (!fila || fila.length === 0 || !fila[0]) continue;
        
        const codigo = fila[0] ? fila[0].toString().trim() : '';
        const cantidad = fila[1] ? parseInt(fila[1]) : 0;
        const observaciones = fila[2] ? fila[2].toString().trim() : '';
        
        if (codigo) {
            await procesarArticulo(codigo, cantidad, observaciones, i + 1);
        }
    }
    
    actualizarContador();
    mostrarResumenProcesamiento();
}

function validarTipoArchivo(file) {
    const fileName = file.name.toLowerCase();
    const extensionesValidas = ['.xlsx', '.xls'];
    const esValido = extensionesValidas.some(ext => fileName.endsWith(ext));
    
    if (!esValido) {
        Swal.fire({
            icon: 'error',
            title: 'Tipo de archivo inválido',
            text: 'Por favor seleccione un archivo Excel (.xlsx o .xls)'
        });
        $('#fileupload').val('');
        return false;
    }
    return true;
}

async function procesarArticulo(codigo, cantidad, observaciones, numeroFila) {
    console.log(`Procesando artículo ${numeroFila}:`, { codigo, cantidad, observaciones });
    
    // Validar el artículo
    const validacion = await validarArticulo(codigo, cantidad);
    
    // Crear objeto del artículo
    const articulo = {
        codigo: codigo,
        cantidad: cantidad,
        observaciones: observaciones,
        descripcion: validacion.descripcion,
        estado: validacion.estado,
        esValido: validacion.esValido,
        numeroFila: numeroFila
    };
    
    articulosAImportar.push(articulo);
    
    // Agregar a la tabla de previsualización
    agregarFilaPreview(articulo);
    
    if (!articulo.esValido) {
        hayErrores = true;
    }
}

async function validarArticulo(codigo, cantidad) {
    let descripcion = '';
    let estado = '';
    let esValido = true;
    
    // Verificar si ya existe en el sistema
    if (articulosExistentes.includes(codigo)) {
        descripcion = 'ARTÍCULO YA EXCLUIDO';
        estado = 'duplicado';
        esValido = false;
        return { descripcion, estado, esValido };
    }
    
    // Verificar cantidad válida
    if (!cantidad || cantidad <= 0) {
        descripcion = 'CANTIDAD INVÁLIDA';
        estado = 'cantidad_invalida';
        esValido = false;
        return { descripcion, estado, esValido };
    }
    
    // Verificar si existe en el maestro usando AJAX
    try {
        const response = await $.ajax({
            url: 'Controller/buscarCodigo.php',
            method: 'POST',
            data: {
                codArticu: codigo
            }
        });
        
        const datos = JSON.parse(response);
        
        if (datos.code === 200) {
            // Artículo existe en el maestro
            const articuloData = JSON.parse(datos.msg);
            if (articuloData && articuloData.length > 0) {
                descripcion = articuloData[0].DESCRIPCIO || 'Descripción no disponible';
                estado = 'valido';
                esValido = true;
            } else {
                descripcion = 'CÓDIGO INEXISTENTE EN MAESTRO';
                estado = 'inexistente';
                esValido = false;
            }
        } else {
            // Artículo no existe
            descripcion = 'CÓDIGO INEXISTENTE EN MAESTRO';
            estado = 'inexistente';
            esValido = false;
        }
        
    } catch (error) {
        console.error('Error validando artículo:', codigo, error);
        descripcion = 'ERROR AL VALIDAR CÓDIGO';
        estado = 'error_validacion';
        esValido = false;
    }
    
    return { descripcion, estado, esValido };
}

function agregarFilaPreview(articulo) {
    let claseEstado = '';
    let badgeEstado = '';
    
    switch (articulo.estado) {
        case 'valido':
            claseEstado = 'table-success';
            badgeEstado = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Válido</span>';
            break;
        case 'duplicado':
            claseEstado = 'table-warning';
            badgeEstado = '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Ya excluido</span>';
            break;
        case 'inexistente':
            claseEstado = 'table-danger';
            badgeEstado = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> No existe</span>';
            break;
        case 'cantidad_invalida':
            claseEstado = 'table-danger';
            badgeEstado = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Cantidad inválida</span>';
            break;
        case 'sin_descripcion':
            claseEstado = 'table-warning';
            badgeEstado = '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Sin descripción</span>';
            break;
        case 'error_validacion':
            claseEstado = 'table-danger';
            badgeEstado = '<span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Error validación</span>';
            break;
        default:
            claseEstado = 'table-secondary';
            badgeEstado = '<span class="badge bg-secondary"><i class="bi bi-question-circle"></i> Desconocido</span>';
    }
    
    const fila = `
        <tr class="${claseEstado}">
            <td><strong>${articulo.codigo}</strong></td>
            <td><span class="badge bg-light text-dark">${articulo.cantidad}</span></td>
            <td>${articulo.observaciones}</td>
            <td>${articulo.descripcion}</td>
            <td>${badgeEstado}</td>
        </tr>
    `;
    
    $('#tblItems tbody').append(fila);
}

function actualizarContador() {
    const total = articulosAImportar.length;
    const validos = articulosAImportar.filter(art => art.esValido).length;
    const invalidos = total - validos;
    
    $('#importCounter').html(`
        <i class="bi bi-list-check"></i> ${total} total | 
        <span class="text-success">${validos} válidos</span> | 
        <span class="text-danger">${invalidos} con errores</span>
    `);
    
    // Actualizar botón
    if (validos > 0) {
        $('#save').prop('disabled', false);
        $('#saveButtonText').text(`Importar ${validos} artículo${validos !== 1 ? 's' : ''}`);
    } else {
        $('#save').prop('disabled', true);
        $('#saveButtonText').text('Sin artículos válidos para importar');
    }
}

function mostrarResumenProcesamiento() {
    const total = articulosAImportar.length;
    const validos = articulosAImportar.filter(art => art.esValido).length;
    
    if (total === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Archivo vacío',
            text: 'No se encontraron datos válidos en el archivo Excel.'
        });
        return;
    }
    
    if (hayErrores) {
        const invalidos = total - validos;
        Swal.fire({
            icon: 'warning',
            title: 'Archivo procesado con advertencias',
            html: `
                <div class="text-start">
                    <p><strong>Total de filas:</strong> ${total}</p>
                    <p class="text-success"><strong>Artículos válidos:</strong> ${validos}</p>
                    <p class="text-danger"><strong>Con errores:</strong> ${invalidos}</p>
                    <hr>
                    <small class="text-muted">Revise la tabla para ver los detalles de cada artículo.</small>
                </div>
            `,
            confirmButtonText: 'Entendido'
        });
    } else {
        Swal.fire({
            icon: 'success',
            title: '¡Archivo procesado correctamente!',
            text: `Se encontraron ${validos} artículos válidos listos para importar.`,
            timer: 2000,
            showConfirmButton: false
        });
    }
}

function importarArticulos() {
    const articulosValidos = articulosAImportar.filter(art => art.esValido);
    
    if (articulosValidos.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Sin artículos válidos',
            text: 'No hay artículos válidos para importar.'
        });
        return;
    }
    
    // Confirmar importación
    Swal.fire({
        title: '¿Confirmar importación?',
        html: `Se importarán <strong>${articulosValidos.length}</strong> artículo(s) válido(s).`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-upload"></i> Sí, importar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarImportacion(articulosValidos);
        }
    });
}

function ejecutarImportacion(articulosValidos) {
    // Mostrar loading
    Swal.fire({
        title: 'Importando artículos...',
        text: `Guardando ${articulosValidos.length} artículos en el sistema`,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Preparar datos para el servidor
    const datosParaServidor = articulosValidos.map(art => [
        art.codigo,
        art.cantidad,
        art.observaciones,
        art.descripcion
    ]);
    
    // Enviar al servidor
    $.ajax({
        url: 'Controller/articuloController.php?action=insert',
        method: 'POST',
        data: {
            articulos: JSON.stringify(datosParaServidor)
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: '¡Importación exitosa!',
                text: `Se importaron ${articulosValidos.length} artículos correctamente.`,
                confirmButtonColor: '#27ae60'
            }).then(() => {
                // Recargar página para mostrar los nuevos artículos
                window.location.reload();
            });
        },
        error: function(xhr, status, error) {
            console.error('Error en importación:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error en la importación',
                text: 'Ocurrió un error al guardar los artículos. Por favor, intente nuevamente.',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

function limpiarImportacion() {
    $('#fileupload').val('');
    $('#tblItems tbody').empty();
    articulosAImportar = [];
    hayErrores = false;
    $('#importCounter').html('<i class="bi bi-list-check"></i> 0 artículos');
    $('#saveButtonText').text('Importar Artículos');
    $('#save').prop('disabled', true);
}

function mostrarCargando(mensaje) {
    Swal.fire({
        title: mensaje,
        text: 'Por favor espere...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
}