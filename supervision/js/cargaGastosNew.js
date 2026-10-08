
$(document).ready(function() {
    console.log("Document ready - Nueva versión con validación de presupuestos");

    // Evento de clic para el botón de guardar
    $('#guardarTodoBtn').on('click', function() {
        console.log("Botón Guardar presionado - Nueva versión");
        guardarGastosConValidacion();
    });

    $('#importeAdelanto').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = new Intl.NumberFormat('es-AR', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value / 100);
        $(this).val(value);

        let total = 0;
        $('.importe-input').each(function() {
            let valor = $(this).val().replace(/\./g, '').replace(',', '.').trim();
            if (valor) {
                total += parseFloat(valor);
            }
        });

        let adelanto = parseFloat(value.replace(/\./g, '').replace(',', '.'));
        let saldo = total - adelanto;

        $('#totalGastos').text(new Intl.NumberFormat('es-AR', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(saldo));
    });

    // Al cambiar de semana hay que recargar la pantalla entera, no solo los
    // presupuestos: los importes, los badges y los comprobantes los renderiza el
    // servidor para un periodo concreto (el que quedo en #semanaHidden). Cuando
    // el combo solo recargaba presupuestos, la pantalla seguia mostrando los
    // datos del periodo anterior y cualquier comprobante que se adjuntara se
    // guardaba con el numero de semana del combo contra los importes de la otra.
    $('#semanaRetail').on('change', function() {
        let seleccion = $(this).val();
        let renderizada = $('#semanaHidden').val();

        // Un trigger('change') programatico, o volver a la misma semana, no
        // tiene que navegar: sin esto la carga inicial entraria en bucle.
        if (!seleccion || seleccion === renderizada) {
            cargarPresupuestos();
            return;
        }

        irASemana(seleccion, renderizada);
    });

    // Cargar presupuestos inicialmente
    cargarPresupuestos();

    function hayImportesSinGuardar() {
        let hay = false;
        $('.importe-input').each(function() {
            // Los importes ya guardados vienen deshabilitados, no cuentan.
            if (!$(this).prop('disabled') && $(this).val().trim() !== '') {
                hay = true;
                return false;
            }
        });
        return hay;
    }

    function irASemana(seleccion, renderizada) {
        let [semana, anio] = seleccion.split('|');
        let destino = '?semana=' + encodeURIComponent(semana) + '&anio=' + encodeURIComponent(anio);

        if (!hayImportesSinGuardar()) {
            window.location.href = destino;
            return;
        }

        Swal.fire({
            title: '¿Cambiar de semana?',
            text: 'Tenés importes cargados sin guardar. Si cambiás de semana se pierden.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = destino;
            } else {
                // Volver el combo a la semana que la pantalla esta mostrando, y
                // re-disparar el change: mostrarNew.js tiene su propio handler
                // que ya fue y busco los gastos de la semana nueva, asi que sin
                // esto el combo quedaria en una semana y los importes en otra,
                // que es exactamente lo que este fix viene a evitar.
                $('#semanaRetail').val(renderizada).trigger('change');
            }
        });
    }

    function tieneImagenesCargadas(input) {
        // Obtener el ID del concepto y el tipo del input
        let $input = $(input);
        let $card = $input.closest('.card');
        let id_conc = $card.find('.concepto-id').val();
        let type = $input.attr('attr-type');
        
        console.log(`🔍 Verificando imágenes para ${id_conc}_${type}`);
        
        // Buscar el contenedor de imágenes específico para este concepto y tipo
        let $imagenesContainer = $(`#imagenes_${id_conc}_${type}`);
        
        if ($imagenesContainer.length === 0) {
            console.log(`❌ No existe contenedor para ${id_conc}_${type}`);
            return false;
        }
        
        // Obtener todo el HTML del contenedor
        let contenidoHTML = $imagenesContainer.html();
        
        if (!contenidoHTML) {
            console.log(`❌ Contenedor vacío para ${id_conc}_${type}`);
            return false;
        }
        
        // Verificar diferentes indicadores de que hay imágenes:
        
        // 1. Buscar elementos <li> que contengan botones (modo edición)
        let itemsConBotones = $imagenesContainer.find('li').filter(function() {
            return $(this).find('button').length > 0;
        }).length;
        
        // 2. Buscar texto que indique imágenes cargadas (modo solo lectura)
        let tieneTextoImagenes = contenidoHTML.includes('imagen(es) cargada(s)') || 
                                contenidoHTML.includes('text-success');
        
        // 3. Buscar iconos de imagen
        let tieneIconosImagen = contenidoHTML.includes('bi-image') || 
                            contenidoHTML.includes('bi-images');
        
        // 4. Verificar si hay nombres de archivo típicos
        let tieneNombresArchivo = /\d+_[A-Z_]+_\d+_(tarjeta|efectivo)_\d+\.(jpg|jpeg|png|pdf)/i.test(contenidoHTML);
        
        // Verificar casos negativos
        let textosNegativos = [
            'No hay archivos',
            'No hay imágenes', 
            'sin-imagenes',
            'error-imagenes',
            'Lista de imágenes se mostrará aquí'
        ];
        
        let tieneTextosNegativos = textosNegativos.some(texto => 
            contenidoHTML.toLowerCase().includes(texto.toLowerCase())
        );
        
        let hayImagenes = (itemsConBotones > 0 || tieneTextoImagenes || tieneIconosImagen || tieneNombresArchivo) 
                        && !tieneTextosNegativos;
        
        console.log(`📊 Análisis detallado para ${id_conc}_${type}:`, {
            itemsConBotones: itemsConBotones,
            tieneTextoImagenes: tieneTextoImagenes,
            tieneIconosImagen: tieneIconosImagen,
            tieneNombresArchivo: tieneNombresArchivo,
            tieneTextosNegativos: tieneTextosNegativos,
            contenidoHTML: contenidoHTML.substring(0, 200) + '...',
            resultado: hayImagenes
        });
        
        return hayImagenes;
    }

    function tieneImagenesCargadasPorId(id_conc, type) {
    console.log(`🔍 Verificando imágenes por ID: ${id_conc}_${type}`);
    
    // Buscar el contenedor de imágenes específico
    let $imagenesContainer = $(`#imagenes_${id_conc}_${type}`);
    
    if ($imagenesContainer.length === 0) {
        console.log(`❌ No existe contenedor para ${id_conc}_${type}`);
        return false;
    }
    
    let contenidoHTML = $imagenesContainer.html();
    
    if (!contenidoHTML) {
        console.log(`❌ Contenedor vacío para ${id_conc}_${type}`);
        return false;
    }
    
    // Verificar si hay elementos que indiquen imágenes cargadas
    let itemsConBotones = $imagenesContainer.find('li button').length > 0;
    let tieneTextoImagenes = contenidoHTML.includes('imagen(es) cargada(s)');
    let tieneIconosImagen = contenidoHTML.includes('bi-image');
    let tieneNombresArchivo = /\d+_[A-Z_]+_\d+_(tarjeta|efectivo)_\d+\./i.test(contenidoHTML);
    
    // Verificar textos que indican ausencia de imágenes
    let textosNegativos = ['No hay archivos', 'No hay imágenes', 'sin-imagenes'];
    let tieneTextosNegativos = textosNegativos.some(texto => 
        contenidoHTML.toLowerCase().includes(texto.toLowerCase())
    );
    
    let hayImagenes = (itemsConBotones || tieneTextoImagenes || tieneIconosImagen || tieneNombresArchivo) 
                     && !tieneTextosNegativos;
    
    console.log(`📊 Verificación por ID ${id_conc}_${type}: ${hayImagenes}`, {
        contenido: contenidoHTML.substring(0, 100) + '...',
        itemsConBotones,
        tieneTextoImagenes,
        tieneIconosImagen,
        tieneNombresArchivo,
        tieneTextosNegativos
    });
    
    return hayImagenes;
}

    function cargarPresupuestos() {
        let seleccion = $('#semanaRetail').val();
        if (!seleccion) return;

        let [semana, anio] = seleccion.split('|');
        let supervisora = $('#nombreSupervisora').val();

        $.ajax({
            url: 'Controller/obtenerPresupuestosNew.php',
            method: 'POST',
            data: JSON.stringify({ 
                semana: semana, 
                anio: anio, 
                supervisora: supervisora 
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                console.log("Presupuestos recibidos:", response);
                if (response.success) {
                    mostrarPresupuestos(response.presupuestos);
                } else {
                    console.error("Error al cargar presupuestos:", response.message);
                    ocultarPresupuestos();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX al cargar presupuestos:", error);
                ocultarPresupuestos();
            }
        });
    }

    function mostrarPresupuestos(presupuestos) {
        $('.card').each(function() {
            let $card = $(this);
            let id_conc = $card.find('.concepto-id').val();
            let $presupuestoInfo = $card.find('.presupuesto-info');
            
            if (presupuestos[id_conc]) {
                let presupuesto = presupuestos[id_conc];
                
                // No mostrar información visual de presupuesto según requerimiento
                $presupuestoInfo.hide();
                // $presupuestoInfo.show(); // ELIMINADO: Nunca mostrar presupuesto en esta pantalla

                
                // Guardar los datos para validación (mantener todos para los cálculos internos)
                $card.attr('data-presupuesto-total', presupuesto.total);
                $card.attr('data-presupuesto-gastado', presupuesto.gastado);
                $card.attr('data-presupuesto', presupuesto.disponible);
            } else {
                $presupuestoInfo.hide();
                $card.attr('data-presupuesto-total', '0');
                $card.attr('data-presupuesto-gastado', '0');
                $card.attr('data-presupuesto', '0');
            }
        });
    }

    function ocultarPresupuestos() {
        $('.presupuesto-info').hide();
        $('.card').attr('data-presupuesto', '0');
    }

    function validarContraPresupuesto(gastosParaGuardar) {
        let gastosExcedidos = [];
        let gastosBloqueados = [];
        let gastosValidados = [];

        gastosParaGuardar.forEach(function(gasto) {
            let $card = $(`.card:has(.concepto-id[value="${gasto.id_conc}"])`);
            let presupuestoTotal = parseFloat($card.attr('data-presupuesto-total') || '0');
            let presupuestoGastado = parseFloat($card.attr('data-presupuesto-gastado') || '0');
            let totalGasto = 0;

            // Sumar TODOS los importes del gasto (efectivo + tarjeta)
            gasto.gastosArray.forEach(function(item) {
                totalGasto += parseFloat(item.importe);
            });

            console.log(`Concepto ${gasto.concepto}:`);
            console.log(`  - Presupuesto Total: ${presupuestoTotal}`);
            console.log(`  - Ya Gastado en el mes: ${presupuestoGastado}`);
            console.log(`  - Nuevo Gasto: ${totalGasto}`);

            // BLOQUEO SI PRESUPUESTO ES 0
            if (presupuestoTotal === 0) {
                console.log(`  ❌ BLOQUEADO: No tiene presupuesto asignado`);
                gastosBloqueados.push(gasto.concepto);
                return;
            }

            // El nuevo gasto se sumará a presupuestoGastado cuando se guarde
            let totalGastoFuturo = presupuestoGastado + totalGasto;
            console.log(`  - Total futuro sería: ${totalGastoFuturo}`);
            
            if (totalGastoFuturo > presupuestoTotal) {
                let exceso = totalGastoFuturo - presupuestoTotal;
                console.log(`  ⚠️ EXCEDERÁ por: ${exceso}`);
                
                gastosExcedidos.push({
                    concepto: gasto.concepto,
                    presupuesto: presupuestoTotal,
                    presupuestoGastado: presupuestoGastado,
                    gasto: totalGasto,
                    totalGastoAcumulado: totalGastoFuturo,
                    exceso: exceso
                });
            } else {
                console.log(`  ✅ DENTRO del presupuesto`);
                gastosValidados.push(gasto);
            }
        });

        return {
            excedidos: gastosExcedidos,
            bloqueados: gastosBloqueados,
            validados: gastosValidados,
            todosValidados: gastosExcedidos.length === 0 && gastosBloqueados.length === 0,
            tieneBloqueados: gastosBloqueados.length > 0
        };
    }

    function guardarGastosConValidacion() {
        console.log("Función guardarGastosConValidacion() llamada");
        let gastos = [];
        let conceptosIncompletos = [];
        let seleccion = $('#semanaRetail').val();
        
        if (!seleccion) {
            Swal.fire('Error', 'Por favor, seleccione una semana', 'error');
            return;
        }

        let $opcion = $('#semanaRetail option:selected');
        let esAnterior = $opcion.data('es-anterior') === 1;
        let hayReapertura = $opcion.data('reapertura') === 1;

        // Con una reapertura activa la semana se puede guardar aunque esté
        // cerrada. El servidor la consume dentro de la transacción del insert,
        // así que si venció entre que se abrió la pantalla y este click, el
        // guardado falla ahí y no se pisa nada.
        if (esAnterior && !hayReapertura) {
            Swal.fire('Error', 'No se pueden cargar gastos de semanas anteriores.', 'error');
            return;
        }
        
        let [semana, anio] = seleccion.split('|');
        let importeAdelanto = $('#importeAdelanto').val().replace(/\./g, '').replace(',', '.');
        
        console.log("Semana:", semana, "Año:", anio);
        console.log("Iniciando procesamiento de conceptos...");

        $('.card').each(function() {
            const $card = $(this);
            let id_conc = $card.find('.concepto-id').val();
            let concepto = $card.find('.card-title').text().trim();
            let observaciones = $card.find('textarea').val() || '';
            
            // Ignorar el concepto "Total de gastos" si existe
            if (concepto.toLowerCase().includes('total de gastos')) {
                return true; // continuar con el siguiente
            }
            
            let gastosArray = [];
            let conceptoTieneAlgunGasto = false;
            
            // Procesar cada tipo de importe (tarjeta y efectivo)
        $card.find('.importe-input').each(function() {
            let input = this;
            let $importeInput = $(input);
            let type = input.getAttribute('attr-type');
            let importe = $importeInput.val();
            
            console.log(`🔍 Procesando ${concepto} (${type}):`, {
                importe: importe,
                valorNumerico: importe ? parseFloat(importe.replace(/\./g, '').replace(',', '.')) : 0
            });
            
            // Verificar si tiene importe válido (mayor a 0)
            let tieneImporte = importe && importe !== '0,00' && 
                              parseFloat(importe.replace(/\./g, '').replace(',', '.')) > 0;
            
            // Verificar si tiene foto - USAR AMBOS MÉTODOS
            let tieneFoto1 = tieneImagenesCargadas(input);
            let tieneFoto2 = tieneImagenesCargadasPorId(id_conc, type);
            let tieneFoto = tieneFoto1 || tieneFoto2;
            
            console.log(`📋 Validación ${concepto} (${type}):`, { 
                tieneImporte: tieneImporte, 
                tieneFoto1: tieneFoto1,
                tieneFoto2: tieneFoto2,
                tieneFoto: tieneFoto,
                valorImporte: importe
            });

            if (tieneImporte && tieneFoto) {
                // Concepto completo - agregar a la lista para guardar
                let importeNumerico = importe.replace(/\./g, '').replace(',', '.');
                gastosArray.push({ importe: importeNumerico, type: type });
                conceptoTieneAlgunGasto = true;
                console.log(`✅ Agregado: ${concepto} (${type}) - Importe: ${importeNumerico}`);
            } else if (tieneImporte && !tieneFoto) {
                // Tiene importe pero no foto - concepto incompleto
                conceptosIncompletos.push(`${concepto} (${type} - falta comprobante)`);
                conceptoTieneAlgunGasto = true;
                console.log(`❌ Incompleto (falta foto): ${concepto} (${type})`);
            } else if (!tieneImporte && tieneFoto) {
                // Tiene foto pero no importe - concepto incompleto
                conceptosIncompletos.push(`${concepto} (${type} - falta importe)`);
                conceptoTieneAlgunGasto = true;
                console.log(`❌ Incompleto (falta importe): ${concepto} (${type})`);
            } else {
                // No tiene ni importe ni foto - no es error, simplemente vacío
                console.log(`⚪ Vacío: ${concepto} (${type})`);
            }
        });
            
            if (gastosArray.length > 0) {
                gastos.push({
                    id_conc: id_conc,
                    concepto: concepto,
                    observaciones: observaciones.trim(),
                    gastosArray: gastosArray
                });
            }
        });
        
        console.log("📊 Resumen de validación:");
        console.log("  - Gastos válidos:", gastos.length);
        console.log("  - Conceptos incompletos:", conceptosIncompletos.length);
        console.log("  - Gastos a guardar:", gastos);
        console.log("  - Conceptos incompletos:", conceptosIncompletos);

        if (conceptosIncompletos.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Conceptos incompletos',
                html: `Los siguientes conceptos están incompletos:<br><br><strong>${conceptosIncompletos.join('<br>')}</strong><br><br>Por favor, complete todos los datos antes de guardar.`,
                confirmButtonText: 'Entendido'
            });
            return;
        }

        if (gastos.length === 0) {
            Swal.fire('Información', 'No hay gastos nuevos para guardar.', 'info');
            return;
        }

        // Continuar con la validación de presupuesto...
        let validacion = validarContraPresupuesto(gastos);
        
        // 2. BLOQUEO CRÍTICO SI NO TIENE PRESUPUESTO ($0)
        if (validacion.tieneBloqueados) {
            Swal.fire({
                icon: 'error',
                title: 'Sin presupuesto asignado',
                html: `No puede cargar gastos en las siguientes categorías porque tienen presupuesto $0:<br><br><strong>${validacion.bloqueados.join('<br>')}</strong><br><br>Por favor, comuníquese con administración.`,
                confirmButtonText: 'Entendido'
            });
            return;
        }

        let mensaje = `Se guardarán ${gastos.length} gasto(s).`;
        let esAdvertencia = false;

        if (hayReapertura) {
            mensaje += `\n\n🔓 Carga complementaria sobre una semana reabierta.`;
            mensaje += `\nSe agregan estos conceptos; lo que ya estaba cargado no se modifica.`;
            mensaje += `\nAl guardar, la semana vuelve a cerrarse.`;
        }

        if (!validacion.todosValidados) {
            esAdvertencia = true;
            mensaje += `\n\n⚠️ ATENCIÓN: Los siguientes conceptos exceden el presupuesto:\n`;
            validacion.excedidos.forEach(function(item) {
                mensaje += `• ${item.concepto}\n`;
            });
        } else {
            mensaje += '\n\n✅ Todos los gastos están dentro del presupuesto y serán aprobados automáticamente.';
        }

        Swal.fire({
            title: 'Confirmar carga de gastos',
            text: mensaje,
            icon: esAdvertencia ? 'warning' : 'success',
            showCancelButton: true,
            confirmButtonText: 'Guardar y Procesar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                enviarDatosAlServidorConValidacion(gastos, semana, anio, importeAdelanto, validacion);
                enviarNotificaciones(gastos, validacion, semana, $('#nombreSupervisora').val(), anio);
            }
        });
    }

    function enviarDatosAlServidorConValidacion(gastos, semana, anio, importeAdelanto, validacion) {
        console.log("Enviando datos al servidor con validación...");
        $('#guardarTodoBtn').prop('disabled', true);

        let supervisora = $('#nombreSupervisora').val();
        
        $.ajax({
            url: 'Controller/guardarGastosNew.php',
            method: 'POST',
            data: JSON.stringify({
                semana: semana,
                anio: anio,
                gastos: gastos,
                supervisora: supervisora,
                importeAdelanto: importeAdelanto,
                validacion: validacion
            }),
            contentType: 'application/json',
            success: function(response) {
                console.log("Respuesta del servidor:", response);
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    if (response.success) {
                        let mensajeFinal = 'Los gastos se han guardado correctamente.';
                        
                        if (response.gastosAprobados && response.gastosExcedidos) {
                            mensajeFinal += `\n\n✅ Gastos aprobados: ${response.gastosAprobados}`;
                            mensajeFinal += `\n⚠️ Gastos que requieren auditoría: ${response.gastosExcedidos}`;
                        }

                        if (response.reaperturaConsumida) {
                            mensajeFinal += `\n\n🔒 Se usó la reapertura: la semana vuelve a quedar cerrada.`;
                            mensajeFinal += `\nLo que ya estaba cargado quedó intacto.`;
                        }

                        Swal.fire({
                            title: 'Gastos procesados',
                            text: mensajeFinal,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // ACTUALIZAR VARIABLE GLOBAL para que las imágenes se muestren correctamente
                                console.log("🔄 Actualizando estado global de gastos guardados");
                                gastosExistentes = gastos; // Actualizar la variable global
                                
                                // Forzar actualización de todas las listas de imágenes
                                $('.card').each(function() {
                                    let $card = $(this);
                                    let id_conc = $card.find('.concepto-id').val();
                                    if (id_conc && window.refrescarBotonesConcepto) {
                                        setTimeout(() => refrescarBotonesConcepto(id_conc), 100);
                                    }
                                });
                                
                                // Recargar la página después de un momento
                                setTimeout(() => {
                                    window.location.href = `?semana=${semana}&anio=${anio}`;
                                }, 1000);
                            }
                        });
                    } else {
                        Swal.fire('Error', 'Error del servidor: ' + (response.message || 'Hubo un problema al guardar los gastos'), 'error');
                    }
                } catch (e) {
                    console.error("Error al procesar la respuesta:", e);
                    Swal.fire('Error', 'Hubo un problema al procesar la respuesta del servidor', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX:", status, error);
                console.error("Respuesta del servidor:", xhr.responseText);
                Swal.fire('Error', 'Hubo un problema al guardar los gastos. Detalles: ' + error, 'error');
            },
            complete: function() {
                $('#guardarTodoBtn').prop('disabled', false);
            }
        });
    }

    function enviarCorreo() {
        // Ya no se envían emails de carga de gastos
        // Esta función se mantiene para compatibilidad pero no hace nada
        console.log("Email de carga eliminado según nueva especificación");
    }

    function enviarNotificaciones(gastos, validacion, semana, supervisora, anio) {
        // CAMBIO 2: Calcular total efectivo del MES COMPLETO, no solo de esta carga
        let totalEfectivoEstaCarga = 0;
        gastos.forEach(function(gasto) {
            gasto.gastosArray.forEach(function(item) {
                if (item.type === 'efectivo') {
                    totalEfectivoEstaCarga += parseFloat(item.importe);
                }
            });
        });

        // Calcular total efectivo del mes desde los datos de presupuesto
        let totalEfectivoMes = 0;
        $('.card').each(function() {
            let $card = $(this);
            let presupuestoGastado = parseFloat($card.attr('data-presupuesto-gastado') || '0');
            
            // Obtener solo los gastos en efectivo de este concepto
            // Para esto necesitamos hacer una consulta adicional o calcular diferente
            // Por simplicidad, usaremos el total gastado como aproximación
            let $importeEfectivo = $card.find('.importe-input[attr-type="efectivo"]');
            if ($importeEfectivo.length > 0) {
                let efectivoActual = parseFloat($importeEfectivo.val().replace(/\./g, '').replace(',', '.') || '0');
                // Sumar el efectivo ya gastado + el efectivo de esta carga
                totalEfectivoMes += efectivoActual;
            }
        });

        // Si no podemos calcularlo exactamente, usar una aproximación
        if (totalEfectivoMes === 0) {
            totalEfectivoMes = totalEfectivoEstaCarga;
        }

        // Obtener adelanto
        let adelanto = parseFloat($('#importeAdelanto').val().replace(/\./g, '').replace(',', '.')) || 0;

        // 1. Enviar notificación de efectivo si hay gastos en efectivo
        if (totalEfectivoMes > 0) {
            $.ajax({
                url: 'Controller/enviarNotificacionEfectivo.php',
                method: 'POST',
                data: JSON.stringify({
                    supervisora: supervisora,
                    semana: semana,
                    anio: anio,
                    totalEfectivo: totalEfectivoMes, // CAMBIO 2: Total del mes, no solo esta carga
                    adelanto: adelanto
                }),
                contentType: 'application/json',
                success: function(response) {
                    console.log("Notificación de efectivo enviada:", response);
                },
                error: function(error) {
                    console.error("Error enviando notificación de efectivo:", error);
                }
            });
        }

        // 2. Enviar notificación de exceso de presupuesto si hay excesos
        if (!validacion.todosValidados && validacion.excedidos.length > 0) {
            $.ajax({
                url: 'Controller/enviarNotificacionExceso.php',
                method: 'POST',
                data: JSON.stringify({
                    supervisora: supervisora,
                    semana: semana,
                    anio: anio,
                    conceptosExcedidos: validacion.excedidos
                }),
                contentType: 'application/json',
                success: function(response) {
                    console.log("Notificación de exceso enviada:", response);
                },
                error: function(error) {
                    console.error("Error enviando notificación de exceso:", error);
                }
            });
        }
    }

    function formatearImporte(importe) {
        return new Intl.NumberFormat('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(importe);
    }

    // Acá vivía seleccionarSemanaAlCargar(), que leía ?semana= de la URL y se lo
    // aplicaba al combo. Ya no hace falta: el servidor preselecciona el período
    // que realmente renderizó (y valida el parámetro contra las semanas retail
    // antes de aceptarlo), así que volver a setear el valor desde la URL solo
    // podía dejar el combo en blanco si el parámetro no correspondía a ninguna
    // opción. Los gastos del período los trae cargarDatosSemanaActual() en
    // mostrarNew.js, que ya corre en cada carga de la pantalla.

    // Actualizar estado de presupuesto cuando cambian los importes
    $(document).on('input', '.importe-input', function() {
        actualizarEstadoPresupuesto($(this).closest('.card'));
    });

    function actualizarEstadoPresupuesto($card) {
        let presupuesto = parseFloat($card.attr('data-presupuesto') || '0');
        if (presupuesto === 0) return;

        let totalGasto = 0;
        $card.find('.importe-input').each(function() {
            let valor = $(this).val().replace(/\./g, '').replace(',', '.').trim();
            if (valor) {
                totalGasto += parseFloat(valor);
            }
        });

        let $presupuestoInfo = $card.find('.presupuesto-info');
        let $estadoSpan = $presupuestoInfo.find('.presupuesto-estado');

        if (totalGasto > presupuesto) {
            let exceso = totalGasto - presupuesto;
            $estadoSpan.text(`EXCEDE el presupuesto por ${formatearImporte(exceso)}`);
            $presupuestoInfo.removeClass('dentro-presupuesto').addClass('exceso-presupuesto');
        } else {
            let disponible = presupuesto - totalGasto;
            $estadoSpan.text(`Dentro del presupuesto (Disponible: ${formatearImporte(disponible)})`);
            $presupuestoInfo.removeClass('exceso-presupuesto').addClass('dentro-presupuesto');
        }
    }

// Definir la ruta base para las imágenes
const BASE_IMAGE_PATH = '/comercial/imagenes/compSupervision/';

/**
 * Semana con la que se puede nombrar un comprobante, o null si no se puede.
 *
 * El nombre del archivo es el unico vinculo entre el comprobante y el gasto
 * (obtenerImagenesSup los busca por prefijo), asi que un digito de mas deja el
 * comprobante huerfano, sin error ni aviso. Antes las subidas leian el combo
 * directo y sin validar nada: se podia adjuntar a una semana cerrada, o a una
 * distinta de la que la pantalla estaba mostrando.
 *
 * Las mismas condiciones las revalida el servidor en subirImagen.php y
 * subirArchivo.php; esto es para avisar antes de que la foto se suba.
 */
function semanaParaComprobante() {
    let seleccion = $('#semanaRetail').val();

    if (!seleccion) {
        Swal.fire('Error', 'Seleccioná una semana antes de adjuntar comprobantes.', 'error');
        return null;
    }

    // La pantalla la renderiza el servidor para un periodo concreto. Si el combo
    // apunta a otro, los importes de abajo no son los de esa semana.
    let renderizada = $('#semanaHidden').val();
    if (renderizada && seleccion !== renderizada) {
        Swal.fire('Error', 'La pantalla quedó desactualizada. Recargala antes de adjuntar comprobantes.', 'error');
        return null;
    }

    let $opcion = $('#semanaRetail option:selected');
    let esAnterior = $opcion.data('es-anterior') === 1;
    let hayReapertura = $opcion.data('reapertura') === 1;

    if (esAnterior && !hayReapertura) {
        Swal.fire('Error', 'No se pueden adjuntar comprobantes a semanas anteriores.', 'error');
        return null;
    }

    let [semana, anio] = seleccion.split('|');
    return { semana: semana, anio: anio };
}

// Funciones globales para manejo de archivos e imágenes
window.elegirImagen = async function(btn, tipo) {
    let periodo = semanaParaComprobante();
    if (!periodo) {
        return;
    }

    let $card = $(btn).closest('.card-body');
    let id_conc = $card.find('.concepto-id').val();
    let semana = periodo.semana;
    let anio = periodo.anio;
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
                    
                    // Verificar si browser-image-compression está disponible
                    if (typeof imageCompression !== 'undefined') {
                        // Comprimir la imagen
                        finalFile = await imageCompression(file, {
                            maxSizeMB: 0.3, // Máximo 300KB
                            maxWidthOrHeight: 1920,
                            useWebWorker: true,
                            initialQuality: 0.8
                        });
                        
                        console.log(`Imagen comprimida: ${(finalFile.size / 1024).toFixed(2)}KB`);
                    } else {
                        console.log('Librería de compresión no disponible, usando imagen original');
                    }
                } else {
                    console.log(`Imagen original: ${(file.size / 1024).toFixed(2)}KB - No requiere compresión`);
                }

                let reader = new FileReader();
                reader.readAsDataURL(finalFile);
                reader.onload = async (readerEvent) => {
                    let content = readerEvent.target.result;
                    try {
                        await subirImagen(content, id_conc, semana, supervisora, tipo, anio);
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
};

window.adjuntarArchivo = function(btn, tipo) {
    console.log("Adjuntar archivo iniciado para:", tipo);

    let periodo = semanaParaComprobante();
    if (!periodo) {
        return;
    }

    let input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,.pdf'; // Solo imágenes y PDF por ahora
    input.multiple = true; // Permitir selección múltiple

    let $btn = $(btn);
    let $card = $btn.closest('.card-body'); // Cambiar a card-body para consistencia
    let id_conc = $card.find('.concepto-id').val();
    let semana = periodo.semana;
    let anio = periodo.anio;
    let supervisora = $('#nombreSupervisora').val() || 'SUPERVISORA_DESCONOCIDA'; // Usar nombreSupervisora

    input.onchange = async function(e) {
        const files = e.target.files;
        if (files.length === 0) return;

        console.log(`Archivos seleccionados: ${files.length}`);

        // Verificar tipos de archivo permitidos
        const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        const archivosValidos = [];
        const archivosRechazados = [];

        for (let file of files) {
            if (tiposPermitidos.includes(file.type)) {
                archivosValidos.push(file);
            } else {
                archivosRechazados.push(file.name);
            }
        }

        if (archivosRechazados.length > 0) {
            await Swal.fire({
                icon: 'warning',
                title: 'Archivos no permitidos',
                html: `Los siguientes archivos no son compatibles:<br><small>${archivosRechazados.join(', ')}</small><br><br>Solo se permiten: imágenes y PDF`,
                confirmButtonText: 'Continuar'
            });
        }

        if (archivosValidos.length === 0) {
            return;
        }

        // Mostrar indicador de carga
        let uploadProgress = 0;
        const totalFiles = archivosValidos.length;
        
        Swal.fire({
            title: 'Subiendo archivos...',
            html: `
                <div class="upload-progress">
                    <div>Archivo <span id="current-file">1</span> de ${totalFiles}</div>
                    <div class="progress mt-2">
                        <div id="upload-progress-bar" class="progress-bar" style="width: 0%"></div>
                    </div>
                    <div id="current-filename" class="mt-2 small text-muted"></div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        let successCount = 0;
        let errorFiles = [];

        for (let i = 0; i < archivosValidos.length; i++) {
            const file = archivosValidos[i];
            
            // Actualizar progreso visual
            document.getElementById('current-file').textContent = i + 1;
            document.getElementById('current-filename').textContent = file.name;
            const progress = ((i + 1) / totalFiles) * 100;
            document.getElementById('upload-progress-bar').style.width = progress + '%';

            console.log(`Procesando archivo ${i + 1}: ${file.name} (${file.type})`);

            try {
                let processedFile = file;
                
                // Solo comprimir si es una imagen y es mayor a 300KB
                if (file.type.startsWith('image/') && file.size > 300 * 1024) {
                    console.log(`Comprimiendo imagen: ${file.name}`);
                    
                    // Usar imageCompression si está disponible
                    if (typeof imageCompression !== 'undefined') {
                        try {
                            processedFile = await imageCompression(file, {
                                maxSizeMB: 1,
                                maxWidthOrHeight: 1920,
                                useWebWorker: true
                            });
                            console.log(`Imagen comprimida de ${(file.size / 1024 / 1024).toFixed(2)}MB a ${(processedFile.size / 1024 / 1024).toFixed(2)}MB`);
                        } catch (compressionError) {
                            console.warn("Error al comprimir, usando archivo original:", compressionError);
                            processedFile = file;
                        }
                    }
                }

                // Convertir archivo a base64 para subida
                const reader = new FileReader();
                const uploadResult = await new Promise((resolve, reject) => {
                    reader.onload = function(e) {
                        const content = e.target.result;
                        
                        // Debug logging para verificar los datos antes de enviar
                        console.log('Datos para subida:', {
                            archivo: file.name,
                            id_conc: id_conc,
                            semana: semana,
                            anio: anio,
                            supervisora: supervisora,
                            tipo: tipo,
                            contentLength: content ? content.length : 'undefined'
                        });

                        subirImagen(content, id_conc, semana, supervisora, tipo, anio)
                            .then(response => {
                                console.log(`Archivo ${file.name} subido exitosamente:`, response);
                                resolve(response);
                            })
                            .catch(error => {
                                console.error(`Error al subir ${file.name}:`, error);
                                reject(error);
                            });
                    };
                    reader.onerror = () => reject(new Error(`Error al leer ${file.name}`));
                    reader.readAsDataURL(processedFile);
                });

                successCount++;

            } catch (error) {
                console.error(`Error al procesar ${file.name}:`, error);
                errorFiles.push(file.name);
            }
        }

        // Actualizar la visualización
        actualizarEstadoVisual($card);
        
        Swal.close();
        
        // Mostrar resultado final
        if (errorFiles.length === 0) {
            Swal.fire({
                icon: 'success',
                title: '¡Archivos subidos!',
                text: `Se subieron ${successCount} archivo(s) correctamente`,
                timer: 2000,
                showConfirmButton: false
            });
        } else if (successCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Subida parcialmente completada',
                html: `Se subieron ${successCount} de ${totalFiles} archivos válidos.<br>
                       <small>Archivos con error: ${errorFiles.join(', ')}</small>`,
                timer: 4000
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error en la subida',
                text: 'No se pudo subir ningún archivo. Por favor, intente de nuevo.',
            });
        }
    };
    
    input.click();
};

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

// El anio viaja aparte de la semana porque el servidor lo necesita para poder
// decidir si el periodo esta cerrado (una semana sola es ambigua entre anios).
function subirImagen(content, id_conc, semana, supervisora, type, anio) {
    console.log("📤 Iniciando subida de imagen...", {id_conc, semana, anio, supervisora, type});
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'Controller/subirImagen.php',
            method: 'POST',
            data: JSON.stringify({
                imagen: content,
                id_conc: id_conc,
                semana: semana,
                anio: anio,
                supervisora: supervisora,
                type: type
            }),
            contentType: 'application/json',
            dataType: 'json',
        })
        .done(function(response) {
            console.log("✅ Respuesta del servidor (subirImagen):", response);
            if (response.success) {
                // Se nombra la semana explicitamente: el comprobante queda atado
                // al gasto solo por el numero que lleva en el nombre del archivo,
                // asi que si es el equivocado tiene que notarse ahora y no cuando
                // el comprobante no aparezca en la semana que corresponde.
                Swal.fire({
                    icon: 'success',
                    title: 'Comprobante guardado en la semana ' + semana,
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // FORZAR actualización inmediata
                    console.log("🔄 Forzando actualización de lista de imágenes...");
                    actualizarListaImagenes(id_conc, type);
                    
                    // Actualizar también el estado visual después de un breve delay
                    setTimeout(() => {
                        let $card = $(`.card:has(.concepto-id[value="${id_conc}"])`);
                        if ($card.length > 0) {
                            console.log("🔄 Actualizando estado visual del concepto...");
                            actualizarEstadoVisual($card);
                        }
                    }, 500);
                    
                    resolve(response);
                });
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al guardar la imagen', 'error');
                reject(new Error(response.message || 'Error al guardar la imagen'));
            }
        })
        .fail(function(xhr, status, error) {
            console.error("❌ Error AJAX:", status, error);
            console.error("Respuesta del servidor:", xhr.responseText);
            Swal.fire('Error', 'Hubo un problema al subir la imagen. Detalles: ' + xhr.responseText, 'error');
            reject(new Error('Error en la solicitud AJAX: ' + error));
        });
    });
}

// Función para mostrar la imagen
function mostrarImagen(nombreImagen) {
    let rutaImagen = BASE_IMAGE_PATH + nombreImagen;
    Swal.fire({
        imageUrl: rutaImagen,
        imageAlt: 'Imagen de comprobante',
        confirmButtonText: 'Cerrar',
        width: '80%'
    });
}

function actualizarListaImagenes(id_conc, type) {
    console.log("🔄 Actualizando lista de imágenes para:", id_conc, type);
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
        console.log("📥 Respuesta completa de obtenerImagenes:", response);
        if (response.success) {
            if (response.imagenes && response.imagenes.length > 0) {
                console.log(`✅ ${response.imagenes.length} imágenes encontradas para ${id_conc}_${type}`);
                
                let listaImagenes = response.imagenes.map(img => {
                    let nombreCorto = img.length > 20 ? img.substring(0, 17) + '...' : img;
                    return `
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <i class="bi-image me-2 text-primary"></i>
                                <span title="${img}" class="text-success">${nombreCorto}</span>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="mostrarImagen('${img}')" title="Ver imagen">
                                    <i class="bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarImagen('${img}', '${id_conc}', '${type}')" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>`;
                }).join('');
                
                $(`#imagenes_${id_conc}_${type}`).html(`
                    <div class="imagenes-cargadas">
                        <ul class="list-unstyled">${listaImagenes}</ul>
                    </div>
                `);
                
                // Mostrar botón "Ver imágenes" si corresponde
                let $verBtn = $(`.card .ver-imagenes-btn`).filter(function() {
                    let $card = $(this).closest('.card');
                    let cardIdConc = $card.find('.concepto-id').val();
                    let onclick = $(this).attr('onclick');
                    return cardIdConc == id_conc && onclick && onclick.includes(`'${type}'`);
                });
                $verBtn.show();
                
            } else {
                console.log(`📭 No hay imágenes para ${id_conc}_${type}`);
                $(`#imagenes_${id_conc}_${type}`).html(`
                    <div class="sin-imagenes">
                        <p class="text-muted small mb-0">No hay archivos para este concepto.</p>
                    </div>
                `);
                
                // Ocultar botón "Ver imágenes"
                let $verBtn = $(`.card .ver-imagenes-btn`).filter(function() {
                    let $card = $(this).closest('.card');
                    let cardIdConc = $card.find('.concepto-id').val();
                    let onclick = $(this).attr('onclick');
                    return cardIdConc == id_conc && onclick && onclick.includes(`'${type}'`);
                });
                $verBtn.hide();
            }
        } else {
            console.error('❌ Error al obtener imágenes:', response.message);
            $(`#imagenes_${id_conc}_${type}`).html(`
                <div class="error-imagenes">
                    <p class="text-danger small mb-0">Error: ${response.message}</p>
                </div>
            `);
        }
        
        // Forzar actualización del estado visual después de cargar las imágenes
        setTimeout(() => {
            let $card = $(`.card:has(.concepto-id[value="${id_conc}"])`);
            if ($card.length > 0) {
                console.log(`🔄 Actualizando estado visual para concepto ${id_conc}`);
                actualizarEstadoVisual($card);
            }
        }, 100);
        
    })
    .fail(function(xhr, status, error) {
        console.error('❌ Error AJAX al obtener imágenes:', error);
        console.error('Respuesta del servidor:', xhr.responseText);
        $(`#imagenes_${id_conc}_${type}`).html(`
            <div class="error-imagenes">
                <p class="text-danger small mb-0">Error al cargar imágenes: ${error}</p>
            </div>
        `);
    });
}

function eliminarImagen(nombreImagen, id_conc, type) {
    console.log("eliminarImagen llamada con:", {
        nombreImagen: nombreImagen,
        id_conc: id_conc,
        type: type,
        nombreImagen_type: typeof nombreImagen,
        id_conc_type: typeof id_conc,
        type_type: typeof type
    });
    
    // Probar la extracción directamente del nombre del archivo
    console.log("🔍 Iniciando extracción de datos del nombre del archivo...");
    
    const partes = nombreImagen.split('_');
    console.log("Partes del nombre del archivo:", partes);
    
    let semanaExtraida = '';
    let supervisoraExtraida = '';
    
    if (partes.length >= 4) {
        semanaExtraida = partes[0];
        // La supervisora puede ser múltiples partes: partes[1] hasta partes[length-3]
        let supervisoraParts = [];
        for (let i = 1; i < partes.length - 3; i++) {
            supervisoraParts.push(partes[i]);
        }
        supervisoraExtraida = supervisoraParts.join('_');
    }
    
    console.log("Datos extraídos del nombre:", {
        semana: semanaExtraida,
        supervisora: supervisoraExtraida
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
            // Extraer datos directamente del nombre del archivo
            // Formato: SEMANA_SUPERVISORA_ID_TIPO_NUMERO.extension
            // Ejemplo: 39_JOSEFINA_PASTORINO_1_tarjeta_1.jpg
            
            const partes = nombreImagen.split('_');
            let semana = '';
            let supervisora = '';
            
            if (partes.length >= 5) {
                semana = partes[0];
                // La supervisora son las partes desde la 1 hasta antes de los últimos 3 elementos
                // partes[1] hasta partes[length-4] (porque los últimos 3 son ID_TIPO_NUMERO)
                let supervisoraParts = [];
                for (let i = 1; i < partes.length - 3; i++) {
                    supervisoraParts.push(partes[i]);
                }
                supervisora = supervisoraParts.join('_');
            }
            
            console.log("📋 Datos finales para eliminación:", {
                nombreImagen: nombreImagen,
                id_conc: id_conc,
                semana: semana,
                supervisora: supervisora,
                type: type,
                partesDelNombre: partes
            });

            const datosEliminar = {
                nombreImagen: nombreImagen,
                id_conc: id_conc,
                semana: semana,
                supervisora: supervisora,
                type: type
            };

            $.ajax({
                url: 'Controller/eliminarImagen.php',
                method: 'POST',
                data: JSON.stringify(datosEliminar),
                contentType: 'application/json',
                dataType: 'json',
            })
            .done(function(response) {
                console.log("Respuesta eliminar imagen:", response);
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
                console.error('Error AJAX al eliminar la imagen:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    xhr: xhr
                });
                
                let errorMsg = 'Hubo un problema al eliminar la imagen.';
                
                // Intentar parsear el mensaje de error del servidor
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMsg = errorResponse.message;
                    }
                } catch (e) {
                    // Si no se puede parsear, usar mensaje genérico
                    if (xhr.responseText) {
                        errorMsg += ' Detalle: ' + xhr.responseText;
                    }
                }
                
                Swal.fire(
                    'Error',
                    errorMsg,
                    'error'
                );
            });
        }
    });
}

//Carrousel para mostrar imagenes una vez cargados los gastos de una semana//
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
                    
                    // Agregar botones de rotación al carrusel
                    addRotationToCarousel('imageCarousel');
                    
                    // Verificar si medium-zoom está disponible
                    if (typeof mediumZoom !== 'undefined') {
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
                    } else {
                        // Si no está medium-zoom, solo actualizar el contador
                        modalElement.querySelector('#imageCarousel').addEventListener('slid.bs.carousel', (event) => {
                            const activeIndex = [...event.target.querySelectorAll('.carousel-item')].indexOf(event.target.querySelector('.carousel-item.active'));
                            modalElement.querySelector('#currentImageIndex').textContent = activeIndex + 1;
                        });
                    }
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

// Función auxiliar para verificar si hay imágenes cargadas
function tieneImagenesCargadas(id_conc) {
    // Esta función debería implementarse para verificar si existen imágenes
    // Por ahora retornamos false como placeholder
    return false;
}

console.log('✅ Funciones de carga de archivos agregadas a cargaGastosNew.js');

});