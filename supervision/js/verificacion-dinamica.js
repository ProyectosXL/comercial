// Función simple para verificar dinámicamente si hay gastos guardados para una semana específica
function verificarGastosGuardadosParaSemana(semana, anio, supervisora, callback) {
    $.ajax({
        url: 'Controller/verificarGastosGuardados.php',
        method: 'POST',
        data: JSON.stringify({
            semana: semana,
            anio: anio,
            supervisora: supervisora
        }),
        contentType: 'application/json',
        dataType: 'json',
    })
        .done(function (response) {
            console.log(`💾 Verificación gastos para semana ${semana}:`, response);
            let hayGastosGuardados = response.success && response.hayGastosGuardados;
            callback(hayGastosGuardados);
        })
        .fail(function () {
            console.log(`❌ Error verificando gastos semana ${semana} - asumiendo sin gastos`);
            callback(false);
        });
}

// Función para actualizar la lista de imágenes con verificación dinámica de gastos
function actualizarListaImagenesConVerificacion(id_conc, type) {
    console.log("🔄 Actualizando lista con verificación dinámica:", id_conc, type);

    let semanaCompleta = $('#semanaRetail').val();
    let semana = semanaCompleta ? semanaCompleta.split('|')[0] : '39';
    let anio = semanaCompleta ? semanaCompleta.split('|')[1] : new Date().getFullYear();
    let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA';

    console.log(`📅 Consultando para: Semana ${semana}, Año ${anio}, Supervisora ${supervisora}`);

    // Primero verificar si hay gastos guardados para esta semana específica
    verificarGastosGuardadosParaSemana(semana, anio, supervisora, function (hayGastosGuardados) {
        console.log(`✅ Resultado verificación: ${hayGastosGuardados ? 'CON gastos guardados' : 'SIN gastos guardados'}`);

        // Ahora obtener las imágenes con el contexto correcto
        $.ajax({
            url: 'Controller/obtenerImagenes.php',
            method: 'POST',
            data: JSON.stringify({
                id_conc: id_conc,
                semana: semana,
                anio: anio,
                supervisora: supervisora,
                type: type
            }),
            contentType: 'application/json',
            dataType: 'json',
        })
            .done(function (response) {
                console.log("📥 Respuesta obtenerImagenes:", response);
                if (response.success) {
                    if (response.imagenes && response.imagenes.length > 0) {
                        console.log(`🖼️ Encontradas ${response.imagenes.length} imágenes para ${id_conc}_${type}`);

                        if (hayGastosGuardados) {
                            // CASO 1: Gastos guardados - Solo mostrar cantidad
                            $(`#imagenes_${id_conc}_${type}`).html(`
                            <div class="d-flex align-items-center">
                                <i class="bi-images me-2 text-success"></i>
                                <span class="text-success">${response.imagenes.length} imagen(es) cargada(s)</span>
                            </div>
                        `);
                            console.log(`💾 Modo guardado: Solo cantidad mostrada`);
                        } else {
                            // CASO 2: Sin gastos guardados - Lista completa con botones
                            let listaImagenes = response.imagenes.map(img => {
                                let nombreCorto = img.length > 20 ? img.substring(0, 17) + '...' : img;

                                return `
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi-image me-2"></i>
                                        <span title="${img}">${nombreCorto}</span>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="mostrarImagen('${img}')" title="Ver">
                                            <i class="bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarImagen('${img}', ${id_conc}, '${type}')" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </li>`;
                            }).join('');
                            $(`#imagenes_${id_conc}_${type}`).html(`<ul class="list-unstyled">${listaImagenes}</ul>`);
                            console.log(`📷 Modo temporal: Lista completa con botones`);
                        }

                        // Mostrar botón "Ver imágenes"
                        let $verBtn = $(`.card .ver-imagenes-btn`).filter(function () {
                            let $card = $(this).closest('.card');
                            let cardIdConc = $card.find('.concepto-id').val();
                            let onclick = $(this).attr('onclick');
                            return cardIdConc == id_conc && onclick && onclick.includes(`'${type}'`);
                        });
                        $verBtn.show();

                    } else {
                        console.log(`📭 No hay imágenes para ${id_conc}_${type}`);
                        $(`#imagenes_${id_conc}_${type}`).html(`<p>No hay archivos para este concepto.</p>`);

                        // Ocultar botón "Ver imágenes"
                        let $verBtn = $(`.card .ver-imagenes-btn`).filter(function () {
                            let $card = $(this).closest('.card');
                            let cardIdConc = $card.find('.concepto-id').val();
                            let onclick = $(this).attr('onclick');
                            return cardIdConc == id_conc && onclick && onclick.includes(`'${type}'`);
                        });
                        $verBtn.hide();
                    }
                } else {
                    console.error('❌ Error:', response.message);
                    $(`#imagenes_${id_conc}_${type}`).html(`<p>Error: ${response.message}</p>`);
                }
            })
            .fail(function (xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                $(`#imagenes_${id_conc}_${type}`).html(`<p>Error: ${error}</p>`);
            });
    });
}

// Sobrescribir la función original
window.actualizarListaImagenes = actualizarListaImagenesConVerificacion;

//Carrousel para mostrar imagenes una vez cargados los gastos de una semana//
function mostrarVisorImagenes(id_conc, type) {
    console.log(`🎬 Abriendo visor de imágenes: ${id_conc}_${type}`);

    let semanaCompleta = $('#semanaRetail').val();
    let semana = semanaCompleta ? semanaCompleta.split('|')[0] : '39';
    let anio = semanaCompleta ? semanaCompleta.split('|')[1] : new Date().getFullYear();
    let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA';

    $.ajax({
        url: 'Controller/obtenerImagenes.php',
        method: 'POST',
        data: JSON.stringify({
            id_conc: id_conc,
            semana: semana,
            anio: anio, // Pass anio here too
            supervisora: supervisora,
            type: type
        }),
        contentType: 'application/json',
        dataType: 'json',
    })
        .done(function (response) {
            if (response.success && response.imagenes && response.imagenes.length > 0) {
                console.log(`🖼️ Mostrando ${response.imagenes.length} imágenes en carrusel`);

                let carouselItems = response.imagenes.map((img, index) => {
                    return `
                    <div class="carousel-item ${index === 0 ? 'active' : ''}" data-file-type="image" data-filename="${img}">
                        <img src="/comercial/imagenes/compSupervision/${img}?t=${new Date().getTime()}" class="d-block w-100 zoomable" alt="Imagen ${index + 1}" 
                             style="max-height: 60vh; object-fit: contain;">
                    </div>`;
                }).join('');

                let carouselHtml = `
                <div id="imageCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner">
                        ${carouselItems}
                    </div>
                    ${response.imagenes.length > 1 ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    ` : ''}
                </div>
                <div id="imageCounter" class="text-center mt-3">
                    Archivo <span id="currentImageIndex">1</span> de ${response.imagenes.length}
                    <br>
                    <small id="currentFileName" class="text-muted">${response.imagenes[0]}</small>
                </div>
            `;

                Swal.fire({
                    html: carouselHtml,
                    width: '90%',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        container: 'custom-swal-container'
                    },
                    didOpen: () => {
                        console.log(`✅ Modal de carrusel abierto con ${response.imagenes.length} imágenes`);

                        // Manejar el evento de cambio de slide
                        const carousel = document.querySelector('#imageCarousel');
                        if (carousel) {
                            carousel.addEventListener('slid.bs.carousel', (event) => {
                                const activeItem = event.target.querySelector('.carousel-item.active');
                                const allItems = [...event.target.querySelectorAll('.carousel-item')];
                                const activeIndex = allItems.indexOf(activeItem);

                                // Actualizar contador
                                const counterElement = document.querySelector('#currentImageIndex');
                                if (counterElement) {
                                    counterElement.textContent = activeIndex + 1;
                                }

                                // Actualizar nombre del archivo
                                const fileName = activeItem ? activeItem.dataset.filename : '';
                                const fileNameElement = document.querySelector('#currentFileName');
                                if (fileNameElement) {
                                    fileNameElement.textContent = fileName;
                                }
                            });
                        }
                    }
                });
            } else {
                console.log(`📭 No hay imágenes para mostrar`);
                Swal.fire('Sin imágenes', 'No hay imágenes para mostrar en este concepto.', 'info');
            }
        })
        .fail(function (xhr, status, error) {
            console.error('❌ Error al cargar imágenes para visor:', error);
            Swal.fire('Error', 'No se pudieron cargar las imágenes. Por favor, intente de nuevo.', 'error');
        });
}

// Función para mostrar imagen individual
function mostrarImagen(nombreImagen) {
    console.log(`🖼️ Mostrando imagen individual: ${nombreImagen}`);
    let rutaImagen = `/comercial/imagenes/compSupervision/${nombreImagen}`;
    Swal.fire({
        imageUrl: rutaImagen,
        imageAlt: 'Imagen de comprobante',
        confirmButtonText: 'Cerrar',
        width: '80%'
    });
}

// Función para eliminar imagen
function eliminarImagen(nombreImagen, id_conc, type) {
    console.log(`🗑️ Eliminando imagen: ${nombreImagen}`);

    // Extraer datos directamente del nombre del archivo
    // Formato: SEMANA_SUPERVISORA_ID_TIPO_NUMERO.extension
    // Ejemplo: 39_JOSEFINA_PASTORINO_1_tarjeta_1.jpg

    const partes = nombreImagen.split('_');
    let semana = '';
    let supervisora = '';

    if (partes.length >= 5) {
        semana = partes[0];
        // La supervisora son las partes desde la 1 hasta antes de los últimos 3 elementos
        let supervisoraParts = [];
        for (let i = 1; i < partes.length - 3; i++) {
            supervisoraParts.push(partes[i]);
        }
        supervisora = supervisoraParts.join('_');
    }

    console.log("📋 Datos extraídos para eliminación:", {
        nombreImagen: nombreImagen,
        semana: semana,
        supervisora: supervisora,
        id_conc: id_conc,
        type: type,
        partesDelNombre: partes
    });

    Swal.fire({
        title: '¿Estás seguro?',
        text: "No podrás revertir esta acción",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'Controller/eliminarImagen.php',
                method: 'POST',
                data: JSON.stringify({
                    nombreImagen: nombreImagen,
                    id_conc: id_conc,
                    semana: semana,
                    supervisora: supervisora,
                    type: type
                }),
                contentType: 'application/json',
                dataType: 'json'
            })
                .done(function (response) {
                    console.log("🔥 Respuesta eliminación:", response);
                    if (response.success) {
                        Swal.fire('Eliminado', 'La imagen ha sido eliminada.', 'success');
                        // Refrescar la lista
                        actualizarListaImagenesConVerificacion(id_conc, type);
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar la imagen: ' + response.message, 'error');
                    }
                })
                .fail(function (xhr, status, error) {
                    console.error('Error al eliminar imagen:', error);
                    console.error('Respuesta del servidor:', xhr.responseText);

                    let errorMsg = 'Hubo un problema al eliminar la imagen.';
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMsg = errorResponse.message;
                        }
                    } catch (e) {
                        if (xhr.responseText) {
                            errorMsg += ' Detalle: ' + xhr.responseText;
                        }
                    }

                    Swal.fire('Error', errorMsg, 'error');
                });
        }
    });
}

// Función para refrescar botones de un concepto completo
function refrescarBotonesConcepto(id_conc) {
    console.log(`🔄 Refrescando concepto completo: ${id_conc}`);

    // Actualizar ambos tipos
    actualizarListaImagenes(id_conc, 'tarjeta');
    actualizarListaImagenes(id_conc, 'efectivo');
}

// Hacer funciones globales
window.mostrarVisorImagenes = mostrarVisorImagenes;
window.mostrarImagen = mostrarImagen;
window.eliminarImagen = eliminarImagen;
window.refrescarBotonesConcepto = refrescarBotonesConcepto;

console.log("✅ Funciones de verificación dinámica cargadas");