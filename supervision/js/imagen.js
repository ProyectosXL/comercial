
// Definir la ruta base para las imágenes
const BASE_IMAGE_PATH = '/comercial/imagenes/compSupervision/';
async function elegirImagen(btn, tipo) {
    let $card = $(btn).closest('.card-body');
    let id_conc = $card.find('.concepto-id').val();
    let semana = $('#semanaRetail').val().split('|')[0];
    let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA';
    let $importeInput = $card.find(`.importe-input[attr-type="${tipo}"]`);

    let input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.capture = 'camera';
    input.onchange = async (e) => {
        let file = e.target.files[0];
        
        if (file) {
            Swal.fire({
                title: 'Procesando imagen...',
                text: 'Por favor, espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                let finalFile = file;
                
                // Solo comprimir si el archivo es mayor a 300KB (300 * 1024 bytes)
                if (file.size > 300 * 1024) {
                    console.log(`Imagen original: ${(file.size / 1024).toFixed(2)}KB - Comprimiendo...`);
                    
                    // Comprimir la imagen
                    finalFile = await imageCompression(file, {
                        maxSizeMB: 0.3, // Máximo 300KB
                        maxWidthOrHeight: 1920,
                        useWebWorker: true,
                        initialQuality: 0.8
                    });
                    
                    console.log(`Imagen comprimida: ${(finalFile.size / 1024).toFixed(2)}KB`);
                } else {
                    console.log(`Imagen original: ${(file.size / 1024).toFixed(2)}KB - No requiere compresión`);
                }

                let reader = new FileReader();
                reader.readAsDataURL(finalFile);
                reader.onload = async (readerEvent) => {
                    let content = readerEvent.target.result;
                    try {
                        await subirImagen(content, id_conc, semana, supervisora, tipo);
                        console.log("Imagen subida exitosamente");
                        if (!$importeInput.val() || $importeInput.val() === '0,00') {
                            $importeInput.addClass('is-invalid');
                        }
                    } catch (error) {
                        console.error("Error al subir la imagen:", error);
                        Swal.fire('Error', 'No se pudo subir la imagen. Por favor, intente de nuevo.', 'error');
                        $(btn).addClass('btn-warning').removeClass('btn-outline-primary');
                    }
                }
            } catch (error) {
                console.error("Error al procesar la imagen:", error);
                Swal.fire('Error', 'No se pudo procesar la imagen. Por favor, intente de nuevo.', 'error');
            }
        }
    }
    input.click();
}

function actualizarEstadoVisual($card) {
    let $importeInput = $card.find('.importe-input');
    let id_conc = $card.find('.concepto-id').val();

    let tieneImporte = $importeInput.val() && $importeInput.val() !== '0,00';
    let tieneFoto = tieneImagenesCargadas(id_conc);

    if (tieneImporte && tieneFoto) {
        // Todo está completo
        $importeInput.removeClass('is-invalid');
    } else if (tieneImporte && !tieneFoto) {
        // Falta foto
        $importeInput.removeClass('is-invalid');
    } else if (!tieneImporte && tieneFoto) {
        // Falta importe
        $importeInput.addClass('is-invalid');
    } else {
        // Ambos están vacíos
        $importeInput.removeClass('is-invalid');
    }
}

function subirImagen(content, id_conc, semana, supervisora, type) {
    console.log("Iniciando subida de imagen...");
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'Controller/subirImagen.php',
            method: 'POST',
            data: JSON.stringify({
                imagen: content,
                id_conc: id_conc,
                semana: semana,
                supervisora: supervisora,
                type: type
            }),
            contentType: 'application/json',
            dataType: 'json',
        })
        .done(function(response) {
            console.log("Respuesta del servidor (subirImagen):", response);
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    actualizarListaImagenes(id_conc, type);
                    resolve(response); // Resolvemos la Promise con la respuesta
                });
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al guardar la imagen', 'error');
                reject(new Error(response.message || 'Error al guardar la imagen')); // Rechazamos la Promise con un error
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Error AJAX:", status, error);
            console.error("Respuesta del servidor:", xhr.responseText);
            Swal.fire('Error', 'Hubo un problema al subir la imagen. Detalles: ' + xhr.responseText, 'error');
            reject(new Error('Error en la solicitud AJAX: ' + error)); // Rechazamos la Promise con el error
        });
    });
}



// Función para mostrar la imagen
function mostrarImagen(nombreImagen) {
    let rutaImagen = BASE_IMAGE_PATH + nombreImagen;
    Swal.fire({
        imageUrl: rutaImagen,
        imageAlt: 'Imagen de comprobante',
        confirmButtonText: 'Cerrar'
    });
}

function actualizarListaImagenes(id_conc, type) {
    console.log("Actualizando lista de imágenes para ID_CONC:", id_conc);
    let semana = $('#semanaRetail').val().split('|')[0];
    let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA';

    $.ajax({
        url: 'Controller/obtenerImagenes.php',
        method: 'POST',
        data: JSON.stringify({
            id_conc: id_conc,
            semana: semana,
            supervisora: supervisora,
            type: type
        }),
        contentType: 'application/json',
        dataType: 'json',
    })
    .done(function(response) {
        console.log("Respuesta completa de obtenerImagenes:", response);
        if (response.success) {
            if (response.imagenes && response.imagenes.length > 0) {
                let listaImagenes = response.imagenes.map(img => {
                    let nombreCorto = img.length > 20 ? img.substring(0, 17) + '...' : img;
                    return `
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <span title="${img}">${nombreCorto}</span>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="mostrarImagen('${img}')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarImagen('${img}', ${id_conc}, '${type}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>`;
                }).join('');
                $(`#imagenes_${id_conc}_${type}`).html(`<ul class="list-unstyled">${listaImagenes}</ul>`);
            } else {
                $(`#imagenes_${id_conc}_${type}`).html(`<p>No hay imágenes para este concepto.</p>`);
            }
        } else {
            console.error('Error al obtener la lista de imágenes:', response.message);
            $(`#imagenes_${id_conc}_${type}`).html(`<p>Error al cargar las imágenes: ${response.message}</p>`);
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Error al obtener la lista de imágenes:', error);
        console.error('Respuesta del servidor:', xhr.responseText);
        $(`#imagenes_${id_conc}_${type}`).html(`<p>Error al cargar las imágenes: ${error}</p>`);
    });
}

function eliminarImagen(nombreImagen, id_conc, type) {
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
            let semana = $('#semanaRetail').val().split('|')[0];
            let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA';

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
                dataType: 'json',
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire(
                        'Eliminada',
                        'La imagen ha sido eliminada.',
                        'success'
                    );
                    actualizarListaImagenes(id_conc, type);
                } else {
                    Swal.fire(
                        'Error',
                        'No se pudo eliminar la imagen: ' + response.message,
                        'error'
                    );
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error al eliminar la imagen:', error);
                Swal.fire(
                    'Error',
                    'Hubo un problema al eliminar la imagen.',
                    'error'
                );
            });
        }
    });
}


//Carrousel pra mostrar imagenes una vez cargados los gastos de una semana//
function mostrarVisorImagenes(id_conc, type) {
    let semana = $('#semanaRetail').val().split('|')[0];
    let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA';

    $.ajax({
        url: 'Controller/obtenerImagenes.php',
        method: 'POST',
        data: JSON.stringify({
            id_conc: id_conc,
            semana: semana,
            supervisora: supervisora,
            type: type
        }),
        contentType: 'application/json',
        dataType: 'json',
    })
    .done(function(response) {
        if (response.success && response.imagenes && response.imagenes.length > 0) {
            let carouselItems = response.imagenes.map((img, index) => `
                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                    <img src="${BASE_IMAGE_PATH}${img}" class="d-block w-100 zoomable" alt="Imagen ${index + 1}">
                </div>
            `).join('');
            
            let carouselHtml = `
                <div id="imageCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${carouselItems}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                </div>
                <div id="imageCounter" class="text-center mt-2">
                    Foto <span id="currentImageIndex">1</span> de ${response.imagenes.length}
                </div>
            `;
            
            Swal.fire({
                html: carouselHtml,
                width: '90%',
                height: '90vh',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal-large-modal'
                },
                didOpen: (modalElement) => {
                    const carousel = new bootstrap.Carousel(modalElement.querySelector('#imageCarousel'));
                    const zoomInstances = mediumZoom('.zoomable', {
                        margin: 50,
                        background: '#000000e6'
                    });

                    modalElement.querySelector('#imageCarousel').addEventListener('slid.bs.carousel', (event) => {
                        const activeIndex = [...event.target.querySelectorAll('.carousel-item')].indexOf(event.target.querySelector('.carousel-item.active'));
                        modalElement.querySelector('#currentImageIndex').textContent = activeIndex + 1;
                        
                        // Cerrar el zoom si está abierto
                        zoomInstances.forEach(zoom => zoom.close());
                    });
                }
            });
        } else {
            Swal.fire('Sin imágenes', 'No hay imágenes disponibles para este concepto.', 'info');
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Error al obtener la lista de imágenes:', error);
        Swal.fire('Error', 'No se pudieron cargar las imágenes. Por favor, intente de nuevo.', 'error');
    });
}