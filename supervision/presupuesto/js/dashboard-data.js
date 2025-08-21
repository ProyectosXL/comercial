
// dashboard-data.js - Actualizado con nuevos filtros y mejor manejo de errores
const DashboardData = {
    
    // Función principal para cargar datos
    cargarDatos() {
        // Obtener filtros
        const periodo = $('#periodoSelect').val();
        const supervisora = $('#supervisoraFilter').val();
        const concepto = $('#conceptoFilter').val();
        let fechaDesde = $('#fechaDesde').val();
        let fechaHasta = $('#fechaHasta').val();
        
        // Validar fechas para período personalizado
        if (periodo === 'custom') {
            if (!fechaDesde || !fechaHasta) {
                DashboardUtils.mostrarError('Para período personalizado debe seleccionar fechas desde y hasta');
                return;
            }
            
            if (new Date(fechaDesde) > new Date(fechaHasta)) {
                DashboardUtils.mostrarError('La fecha desde debe ser menor o igual a la fecha hasta');
                return;
            }
        }
        
        // Mostrar cargando
        DashboardUtils.mostrarCargando(true);
        
        // Log para debugging
        console.log('Enviando solicitud con parámetros:', {
            periodo, supervisora, concepto, fechaDesde, fechaHasta
        });
        
        // Realizar petición AJAX
        $.ajax({
            url: 'Controller/dash-obtenerDatosDashboard.php',
            method: 'POST',
            data: JSON.stringify({
                periodo: periodo,
                supervisora: supervisora,
                concepto: concepto,
                fechaDesde: fechaDesde,
                fechaHasta: fechaHasta,
                incluirPeriodoAnterior: true
            }),
            contentType: 'application/json',
            dataType: 'json',
            timeout: 30000 // 30 segundos timeout
        })
        .done(function(response) {
            console.log("Respuesta dashboard:", response);
            
            if (response && response.success) {
                const datos = response.datos || [];
                const datosPeriodoAnterior = response.datosPeriodoAnterior || [];
                
                // Guardar datos para uso posterior
                window.datosCompletos = datos;
                window.datosPeriodoAnterior = datosPeriodoAnterior;
                
                // Obtener información del período anterior para el título
                let periodoAnteriorInfo = null;
                if (response.periodoAnteriorInfo) {
                    periodoAnteriorInfo = response.periodoAnteriorInfo;
                }
                
                // Actualizar título con información de comparación
                DashboardData.actualizarTituloPeriodo(periodo, fechaDesde, fechaHasta, periodoAnteriorInfo);
                
                // Generar resumen incluyendo comparación con período anterior
                const resumen = response.resumen || DashboardData.calcularResumenConComparacion(datos, datosPeriodoAnterior);
                
                // Generar agregados 
                const agregados = response.agregados || DashboardData.calcularAgregadosCliente(datos);
                
                // Guardar para uso posterior
                window.agregados = agregados;
                
                // Actualizar la interfaz
                DashboardMetrics.actualizarMetricas(resumen);
                DashboardCharts.actualizarGraficos(datos, agregados);
                DashboardTables.actualizarTabla(datos);
                
                // Cargar las listas de filtros
                DashboardData.cargarListasFiltros(datos);
                
                DashboardUtils.log('Datos cargados exitosamente', 'success');
            } else {
                const errorMsg = response ? (response.message || 'Error desconocido en la respuesta') : 'Respuesta vacía del servidor';
                DashboardUtils.mostrarError(errorMsg);
                DashboardUtils.log('Error en respuesta: ' + errorMsg, 'error');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Error AJAX dashboard:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            
            let errorMsg = 'Error de comunicación con el servidor';
            
            // Manejo específico de diferentes tipos de error
            if (xhr.status === 0) {
                errorMsg = 'No se pudo conectar al servidor. Verifique su conexión a internet.';
            } else if (xhr.status === 404) {
                errorMsg = 'El archivo del servidor no fue encontrado (404).';
            } else if (xhr.status === 500) {
                errorMsg = 'Error interno del servidor (500).';
            } else if (status === 'timeout') {
                errorMsg = 'La solicitud tardó demasiado tiempo. Intente nuevamente.';
            } else if (status === 'parsererror') {
                errorMsg = 'Error al procesar la respuesta del servidor.';
            } else if (xhr.responseText) {
                try {
                    const respuesta = JSON.parse(xhr.responseText);
                    if (respuesta.message) {
                        errorMsg = respuesta.message;
                    }
                } catch (e) {
                    // Si no se puede parsear, usar el texto tal como viene
                    if (xhr.responseText.length < 200) {
                        errorMsg += ': ' + xhr.responseText;
                    }
                }
            }
            
            DashboardUtils.mostrarError(errorMsg);
            DashboardUtils.log('Error AJAX: ' + errorMsg, 'error');
        })
        .always(function() {
            DashboardUtils.mostrarCargando(false);
        });
    },
    
    // Función para cargar datos de presupuesto (actualizada para usar nuevos filtros)
    cargarDatosPresupuesto() {
        // Obtener los mismos filtros que usa el dashboard de gastos
        const periodo = $('#periodoSelect').val();
        const supervisora = $('#supervisoraFilter').val();
        const concepto = $('#conceptoFilter').val();
        let fechaDesde = $('#fechaDesde').val();
        let fechaHasta = $('#fechaHasta').val();
        
        DashboardUtils.mostrarCargando(true);
        
        $.ajax({
            url: 'Controller/dash-obtenerDatosPresupuesto.php',
            method: 'POST',
            data: JSON.stringify({
                periodo: periodo,          // Enviar el mismo período
                fechaDesde: fechaDesde,    // Enviar las mismas fechas
                fechaHasta: fechaHasta,    // Enviar las mismas fechas
                supervisora: supervisora,
                concepto: concepto
            }),
            contentType: 'application/json',
            dataType: 'json',
            timeout: 30000
        })
        .done(function(response) {
            console.log("Respuesta presupuesto:", response);
            
            if (response && response.success) {
                window.datosPresupuesto = response;
                DashboardPresupuesto.actualizarKPIsPresupuesto(response.kpis);
                DashboardPresupuesto.actualizarGraficosPresupuesto(response.agregados);
                DashboardPresupuesto.actualizarTablaPresupuesto(response.datos);
                
                // Log para debugging
                if (response.debug) {
                    DashboardUtils.log('Fechas de presupuesto: ' + response.debug.fechaDesde + ' a ' + response.debug.fechaHasta, 'debug');
                }
                
                DashboardUtils.log('Datos de presupuesto cargados exitosamente', 'success');
            } else {
                const errorMsg = response ? (response.message || 'Error desconocido en presupuesto') : 'Respuesta vacía del servidor de presupuesto';
                DashboardUtils.mostrarError(errorMsg);
                DashboardUtils.log('Error en presupuesto: ' + errorMsg, 'error');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Error AJAX presupuesto:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            
            let errorMsg = 'Error cargando datos de presupuesto';
            
            if (xhr.status === 0) {
                errorMsg = 'No se pudo conectar al servidor de presupuesto.';
            } else if (xhr.responseText) {
                try {
                    const respuesta = JSON.parse(xhr.responseText);
                    if (respuesta.message) {
                        errorMsg = respuesta.message;
                    }
                } catch (e) {
                    // Usar mensaje genérico si no se puede parsear
                }
            }
            
            DashboardUtils.mostrarError(errorMsg);
            DashboardUtils.log('Error AJAX presupuesto: ' + errorMsg, 'error');
        })
        .always(function() {
            DashboardUtils.mostrarCargando(false);
        });
    },
    
    // Función para cargar datos temporales
    cargarDatosTemporales() {
        // Mostrar cargando
        DashboardUtils.mostrarCargando(true);
        
        // Calcular los últimos 4 meses
        const fechaActual = new Date();
        const mesActual = fechaActual.getMonth();
        const anioActual = fechaActual.getFullYear();
        
        let mesInicio = mesActual - 3;
        let anioInicio = anioActual;
        
        if (mesInicio < 0) {
            mesInicio = 12 + mesInicio;
            anioInicio = anioActual - 1;
        }
        
        // Convertir a fechas
        const fechaInicio = new Date(anioInicio, mesInicio, 1);
        const fechaFin = new Date(anioActual, mesActual + 1, 0); // Último día del mes actual
        
        // Formatear fechas como strings YYYY-MM-DD
        const fechaInicioStr = fechaInicio.toISOString().split('T')[0];
        const fechaFinStr = fechaFin.toISOString().split('T')[0];
        
        // Obtener los filtros actuales
        const supervisora = $('#supervisoraFilter').val();
        const concepto = $('#conceptoFilter').val();
        
        // Realizar petición AJAX para datos temporales
        $.ajax({
            url: 'Controller/dash-obtenerDatosTemporales.php',
            method: 'POST',
            data: JSON.stringify({
                fechaDesde: fechaInicioStr,
                fechaHasta: fechaFinStr,
                supervisora: supervisora,
                concepto: concepto
            }),
            contentType: 'application/json',
            dataType: 'json',
            timeout: 30000
        })
        .done(function(response) {
            if (response && response.success) {
                window.datosTemporales = response.datos || [];
                DashboardCharts.actualizarGraficoLinea(window.datosTemporales);
                DashboardUtils.log('Datos temporales cargados exitosamente', 'success');
            } else {
                const errorMsg = response ? (response.message || 'Error en datos temporales') : 'Error cargando evolución temporal';
                DashboardUtils.mostrarError(errorMsg);
                DashboardUtils.log('Error temporal: ' + errorMsg, 'error');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Error AJAX temporal:', error);
            DashboardUtils.mostrarError('Error cargando datos de evolución temporal');
            DashboardUtils.log('Error AJAX temporal: ' + error, 'error');
        })
        .always(function() {
            DashboardUtils.mostrarCargando(false);
        });
    },
    
    // Función para actualizar título del período (actualizada para nuevos filtros)
    actualizarTituloPeriodo(periodo, fechaDesde, fechaHasta, periodoAnteriorInfo) {
        let tituloPeriodo = "";
        let tituloComparacion = "";
        
        // Formatear el título del período actual según el nuevo sistema
        switch (periodo) {
            case 'mes_actual':
                tituloPeriodo = 'Mes actual';
                break;
            case 'mes_anterior':
                tituloPeriodo = 'Mes anterior';
                break;
            case '4_semanas':
                tituloPeriodo = 'Últimas 4 semanas';
                break;
            case 'anio_actual':
                tituloPeriodo = 'Año actual';
                break;
            case 'anio_anterior':
                tituloPeriodo = 'Año anterior';
                break;
            case 'custom':
                tituloPeriodo = `${fechaDesde} a ${fechaHasta}`;
                break;
            default:
                tituloPeriodo = 'Período seleccionado';
        }
        
        // Formatear el título del período de comparación
        if (periodoAnteriorInfo) {
            switch (periodo) {
                case 'mes_actual':
                    tituloComparacion = 'Mes anterior';
                    break;
                case 'mes_anterior':
                    tituloComparacion = 'Mes previo';
                    break;
                case '4_semanas':
                    tituloComparacion = '4 semanas anteriores';
                    break;
                case 'anio_actual':
                    tituloComparacion = 'Año anterior';
                    break;
                case 'anio_anterior':
                    tituloComparacion = 'Año previo';
                    break;
                case 'custom':
                    tituloComparacion = `${periodoAnteriorInfo.fechaDesde} a ${periodoAnteriorInfo.fechaHasta}`;
                    break;
                default:
                    tituloComparacion = 'Período anterior';
            }
            
            // Actualizar el elemento HTML
            $('#periodoSeleccionado').html(`
                <span class="periodo-actual">${tituloPeriodo}</span>
                <span class="periodo-separador">vs</span>
                <span class="periodo-anterior">${tituloComparacion}</span>
            `);
        } else {
            // Si no hay información de comparación, mostrar solo el período actual
            $('#periodoSeleccionado').text(tituloPeriodo);
        }
    },
    
    // Resto de funciones sin cambios...
    cargarListasFiltros(datos) {
        if (!datos || !datos.length) return;
        
        // Extraer supervisoras únicas
        const supervisoras = [...new Set(datos.map(item => item.SUPERVISORA))].filter(Boolean).sort();
        
        // Extraer conceptos únicos
        const conceptos = [...new Set(datos.map(item => item.CONCEPTO))].filter(Boolean).sort();
        
        // Actualizar listas de filtros
        DashboardData.actualizarListaSupervisoras(supervisoras);
        DashboardData.actualizarListaConceptos(conceptos);
    },
    
    actualizarListaSupervisoras(supervisoras) {
        if (!supervisoras || !supervisoras.length) return;
        
        const select = $('#supervisoraFilter');
        const valorActual = select.val();
        
        select.empty();
        select.append('<option value="">Todas</option>');
        
        supervisoras.forEach(supervisora => {
            if (supervisora) {
                select.append(`<option value="${supervisora}">${supervisora}</option>`);
            }
        });
        
        if (valorActual && supervisoras.includes(valorActual)) {
            select.val(valorActual);
        }
    },
    
    actualizarListaConceptos(conceptos) {
        if (!conceptos || !conceptos.length) return;
        
        const select = $('#conceptoFilter');
        const valorActual = select.val();
        
        select.empty();
        select.append('<option value="">Todos</option>');
        
        conceptos.forEach(concepto => {
            if (concepto) {
                select.append(`<option value="${concepto}">${concepto}</option>`);
            }
        });
        
        if (valorActual && conceptos.includes(valorActual)) {
            select.val(valorActual);
        }
    },
    
    calcularResumenConComparacion(datos, datosPeriodoAnterior) {
        let totalEfectivo = 0;
        let totalTarjeta = 0;
        
        datos.forEach(dato => {
            totalEfectivo += parseFloat(dato.IMPORTE || 0);
            totalTarjeta += parseFloat(dato.IMPORTE_TARJETA || 0);
        });
        
        const totalGastos = totalEfectivo + totalTarjeta;
        
        let totalEfectivoAnterior = 0;
        let totalTarjetaAnterior = 0;
        
        datosPeriodoAnterior.forEach(dato => {
            totalEfectivoAnterior += parseFloat(dato.IMPORTE || 0);
            totalTarjetaAnterior += parseFloat(dato.IMPORTE_TARJETA || 0);
        });
        
        const totalGastosAnterior = totalEfectivoAnterior + totalTarjetaAnterior;
        
        const varTotalGastos = DashboardData.calcularVariacionPorcentual(totalGastos, totalGastosAnterior);
        const varTotalEfectivo = DashboardData.calcularVariacionPorcentual(totalEfectivo, totalEfectivoAnterior);
        const varTotalTarjeta = DashboardData.calcularVariacionPorcentual(totalTarjeta, totalTarjetaAnterior);
        
        const fechasUnicas = new Set();
        datos.forEach(dato => {
            if (dato.FECHA_MODIF) {
                const fecha = new Date(dato.FECHA_MODIF).toISOString().split('T')[0];
                fechasUnicas.add(fecha);
            }
        });
        const dias = fechasUnicas.size;
        const promedioDiario = dias > 0 ? totalGastos / dias : 0;
        const promedioSemanal = promedioDiario * 7;
        
        return {
            totalGastos,
            totalEfectivo,
            totalTarjeta,
            promedioSemanal,
            varTotalGastos,
            varTotalEfectivo,
            varTotalTarjeta,
            totalGastosAnterior,
            totalEfectivoAnterior,
            totalTarjetaAnterior
        };
    },
    
    calcularVariacionPorcentual(valorActual, valorAnterior) {
        if (valorAnterior === 0) return valorActual > 0 ? 100 : 0;
        return ((valorActual - valorAnterior) / valorAnterior) * 100;
    },
    
    calcularAgregadosCliente(datos) {
        if (!datos || !datos.length) {
            return {
                totalEfectivo: 0,
                totalTarjeta: 0,
                porConcepto: {},
                porSemana: {},
                porSupervisora: {}
            };
        }
        
        let totalEfectivo = 0;
        let totalTarjeta = 0;
        const porConcepto = {};
        const porSemana = {};
        const porSupervisora = {};
        
        datos.forEach(dato => {
            const efectivo = parseFloat(dato.IMPORTE || 0);
            const tarjeta = parseFloat(dato.IMPORTE_TARJETA || 0);
            
            totalEfectivo += efectivo;
            totalTarjeta += tarjeta;
            
            const concepto = dato.CONCEPTO || 'Sin concepto';
            if (!porConcepto[concepto]) {
                porConcepto[concepto] = { efectivo: 0, tarjeta: 0 };
            }
            porConcepto[concepto].efectivo += efectivo;
            porConcepto[concepto].tarjeta += tarjeta;
            
            const fecha = dato.FECHA_MODIF ? new Date(dato.FECHA_MODIF).toISOString().split('T')[0] : 'Sin fecha';
            if (!porSemana[fecha]) {
                porSemana[fecha] = { efectivo: 0, tarjeta: 0 };
            }
            porSemana[fecha].efectivo += efectivo;
            porSemana[fecha].tarjeta += tarjeta;
            
            const supervisora = dato.SUPERVISORA || 'Sin supervisora';
            if (!porSupervisora[supervisora]) {
                porSupervisora[supervisora] = { efectivo: 0, tarjeta: 0 };
            }
            porSupervisora[supervisora].efectivo += efectivo;
            porSupervisora[supervisora].tarjeta += tarjeta;
        });
        
        return {
            totalEfectivo,
            totalTarjeta,
            porConcepto,
            porSemana,
            porSupervisora
        };
    }
};