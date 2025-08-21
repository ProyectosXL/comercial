
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

    // Cargar presupuestos al cambiar de semana
    $('#semanaRetail').on('change', function() {
        cargarPresupuestos();
    });

    // Cargar presupuestos inicialmente
    cargarPresupuestos();

    function tieneImagenesCargadas(input) {
        let tieneFoto = input.parentElement.parentElement.parentElement.childNodes[5].childNodes[0].childNodes.length;
        
        if(tieneFoto == 0){
            return false;
        }else{
            return true;
        }
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
                
                // Mostrar solo información del gasto actual
                let infoHtml = `
                    <strong>Ya gastado:</strong> ${formatearImporte(presupuesto.gastado)}
                `;
                
                $presupuestoInfo.find('.presupuesto-monto').html(infoHtml);
                $presupuestoInfo.find('.presupuesto-estado').text('Información de gastos del mes');
                $presupuestoInfo.removeClass('exceso-presupuesto').addClass('dentro-presupuesto');
                $presupuestoInfo.show();
                
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

            // El nuevo gasto se sumará a presupuestoGastado cuando se guarde
            let totalGastoFuturo = presupuestoGastado + totalGasto;
            console.log(`  - Total futuro sería: ${totalGastoFuturo}`);
            
            if (presupuestoTotal > 0 && totalGastoFuturo > presupuestoTotal) {
                let exceso = totalGastoFuturo - presupuestoTotal;
                console.log(`  ❌ EXCEDERÁ por: ${exceso}`);
                
                gastosExcedidos.push({
                    concepto: gasto.concepto,
                    presupuesto: presupuestoTotal, // Solo para cálculos internos
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
            validados: gastosValidados,
            todosValidados: gastosExcedidos.length === 0
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
        
        let [semana, anio] = seleccion.split('|');
        let importeAdelanto = $('#importeAdelanto').val().replace(/\./g, '').replace(',', '.');
        
        console.log("Semana:", semana, "Año:", anio);
        console.log("Iniciando procesamiento de tarjetas...");

        $('.card').each(function() {
            const $card = $(this);
            let id_conc = $card.find('.concepto-id').val();
            let concepto = $card.find('.card-title').text().trim();
            let observaciones = $card.find('textarea').val() || '';
            
            let gastosArray = [];
            $card.find('.importe-input').each(function(e, x) {
                let input = x; 
                let $importeInput = input;
                let type = input.getAttribute('attr-type');
        
                if (concepto.toLowerCase() === 'total de gastos') {
                    return true; 
                }
        
                let tieneImporte = $importeInput.value && $importeInput.value !== '0,00';
                let tieneFoto = tieneImagenesCargadas(input);
        
                console.log(`Procesando ${concepto} (${type}):`, { 
                    tieneImporte, 
                    tieneFoto, 
                    importe: $importeInput.value 
                });

                if (tieneImporte && tieneFoto) {
                    let importe = $importeInput.value.replace(/\./g, '').replace(',', '.');
                    gastosArray.push({ importe, type });
                } else {
                    if (tieneImporte && !tieneFoto) {
                        conceptosIncompletos.push(`${concepto} (${type} - falta foto)`);
                    } else if (tieneFoto && !tieneImporte) {
                        conceptosIncompletos.push(`${concepto} (${type} - falta importe)`);             
                    }
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
        
        console.log("Gastos a guardar:", gastos);
        console.log("Conceptos incompletos:", conceptosIncompletos);

        if (conceptosIncompletos.length > 0) {
            Swal.fire('Error', `Los siguientes conceptos están incompletos: ${conceptosIncompletos.join(', ')}`, 'error');
            return;
        }

        if (gastos.length === 0) {
            Swal.fire('Información', 'No hay gastos nuevos para guardar.', 'info');
            return;
        }

        // Validar contra presupuesto
        let validacion = validarContraPresupuesto(gastos);
        
        let mensaje = `Se guardarán ${gastos.length} gasto(s).`;

        if (!validacion.todosValidados) {
            mensaje += `\n\n⚠️ ATENCIÓN: Los siguientes conceptos exceden el presupuesto y serán auditados por la gerencia comercial:\n`;
            validacion.excedidos.forEach(function(item) {
                // CAMBIO 1: Quitar información del presupuesto total
                mensaje += `• ${item.concepto}: Excede el presupuesto por ${formatearImporte(item.exceso)}\n`;
            });
        } else {
            mensaje += '\n\n✅ Todos los gastos están dentro del presupuesto y serán aprobados automáticamente.';
        }

        Swal.fire({
            title: 'Confirmar carga de gastos',
            text: mensaje,
            icon: validacion.todosValidados ? 'success' : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Guardar y Procesar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                enviarDatosAlServidorConValidacion(gastos, semana, anio, importeAdelanto, validacion);
                // Enviar notificaciones según las nuevas especificaciones
                enviarNotificaciones(gastos, validacion, semana, $('#nombreSupervisora').val());
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
                        
                        Swal.fire({
                            title: 'Gastos procesados',
                            text: mensajeFinal,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = `?semana=${semana}&anio=${anio}`;
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

    function enviarNotificaciones(gastos, validacion, semana, supervisora) {
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

    // Función para seleccionar la semana al cargar la página
    function seleccionarSemanaAlCargar() {
        let params = new URLSearchParams(window.location.search);
        let semana = params.get('semana');
        let anio = params.get('anio');
        
        if (semana && anio) {
            let valorSelect = semana + '|' + anio;
            $('#semanaRetail').val(valorSelect);
            $('#semanaRetail').trigger('change');
            
            if (typeof buscarGastos === 'function') {
                buscarGastos();
            }
        }
    }

    // Llamar a esta función cuando se carga la página
    seleccionarSemanaAlCargar();

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

});