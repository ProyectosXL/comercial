
// dashboard-charts.js - Manejo de gráficos
const DashboardCharts = {
    
    // Función para actualizar gráficos principales
    actualizarGraficos(datos, agregados) {
        if (!agregados) {
            console.error('Objeto agregados no definido');
            return;
        }
        
        // Guardar los agregados en una variable global para uso posterior
        window.datosCompletos = datos;
        window.agregados = agregados;
        
        // Determinar qué gráfico mostrar según la selección actual
        const tipoGraficoPie = $('input[name="pieChartType"]:checked').attr('id');
        
        if (tipoGraficoPie === 'pieChartSupervisora') {
            DashboardCharts.actualizarGraficoPieSupervisora(agregados);
        } else {
            DashboardCharts.actualizarGraficoPieEfectivoTarjeta(agregados);
        }
        
        DashboardCharts.actualizarGraficoBarras(agregados);
    },
    
    // Función para actualizar el gráfico de pastel Efectivo vs Tarjeta
    actualizarGraficoPieEfectivoTarjeta(agregados) {
        if (!agregados) {
            console.error('Objeto agregados no definido');
            return;
        }
        
        const ctx = document.getElementById('pieChart').getContext('2d');
        
        // Destruir el gráfico anterior si existe
        if (window.dashboardCharts.pieChart) {
            window.dashboardCharts.pieChart.destroy();
            window.dashboardCharts.pieChart = null;
        }
        
        const efectivo = agregados.totalEfectivo || 0;
        const tarjeta = agregados.totalTarjeta || 0;
        
        try {
            window.dashboardCharts.pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Efectivo', 'Tarjeta'],
                    datasets: [{
                        data: [efectivo, tarjeta],
                        backgroundColor: ['#1cc88a', '#f6c23e'],
                        hoverBackgroundColor: ['#17a673', '#f4b619']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${DashboardUtils.formatearImporte(value)} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 500
                    }
                }
            });
        } catch (e) {
            console.error('Error al crear el gráfico de pastel:', e);
        }
    },
    
    // Función para actualizar el gráfico de pastel por Supervisora
    actualizarGraficoPieSupervisora(agregados) {
        if (!agregados || !agregados.porSupervisora) {
            console.error('Objeto agregados o porSupervisora no definido');
            return;
        }
        
        const ctx = document.getElementById('pieChart').getContext('2d');
        
        // Destruir el gráfico anterior si existe
        if (window.dashboardCharts.pieChart) {
            window.dashboardCharts.pieChart.destroy();
            window.dashboardCharts.pieChart = null;
        }
        
        // Preparar datos para el gráfico
        const supervisoras = Object.keys(agregados.porSupervisora);
        const totalesPorSupervisora = supervisoras.map(supervisora => {
            const datos = agregados.porSupervisora[supervisora];
            return (datos.efectivo || 0) + (datos.tarjeta || 0);
        });
        
        // Paleta de colores para las supervisoras
        const colores = [
            '#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', 
            '#6f42c1', '#fd7e14', '#20c9a6', '#5a5c69'
        ];
        
        // Limitar a 8 supervisoras para mejor visualización
        const supervisorasOrdenadas = supervisoras
            .map((supervisora, index) => ({
                supervisora,
                total: totalesPorSupervisora[index]
            }))
            .sort((a, b) => b.total - a.total)
            .slice(0, 8);
        
        const labelsSupervisoras = supervisorasOrdenadas.map(item => item.supervisora);
        const datosSupervisoras = supervisorasOrdenadas.map(item => item.total);
        
        // Asignar colores
        const coloresSupervisoras = labelsSupervisoras.map((_, index) => colores[index % colores.length]);
        const coloresHoverSupervisoras = coloresSupervisoras.map(color => {
            return color.replace(')', ', 0.8)').replace('rgb', 'rgba');
        });
        
        try {
            window.dashboardCharts.pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labelsSupervisoras,
                    datasets: [{
                        data: datosSupervisoras,
                        backgroundColor: coloresSupervisoras,
                        hoverBackgroundColor: coloresHoverSupervisoras
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${DashboardUtils.formatearImporte(value)} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 500
                    }
                }
            });
        } catch (e) {
            console.error('Error al crear el gráfico de pastel por Supervisora:', e);
        }
    },
    
    // Función para actualizar el gráfico de barras
    actualizarGraficoBarras(agregados) {
        if (!agregados || !agregados.porConcepto) {
            console.error('Objeto agregados o porConcepto no definido');
            return;
        }
        
        const ctx = document.getElementById('barChart').getContext('2d');
        
        // Destruir el gráfico anterior si existe
        if (window.dashboardCharts.barChart) {
            window.dashboardCharts.barChart.destroy();
            window.dashboardCharts.barChart = null;
        }
        
        // Limitar estrictamente a 5 conceptos
        const conceptosOrdenados = Object.entries(agregados.porConcepto)
            .map(([concepto, datos]) => ({
                concepto: concepto.substring(0, 15),
                total: (datos.efectivo || 0) + (datos.tarjeta || 0),
                efectivo: datos.efectivo || 0,
                tarjeta: datos.tarjeta || 0
            }))
            .sort((a, b) => b.total - a.total)
            .slice(0, 5);
        
        const labels = conceptosOrdenados.map(item => item.concepto);
        const efectivoData = conceptosOrdenados.map(item => item.efectivo);
        const tarjetaData = conceptosOrdenados.map(item => item.tarjeta);
        
        // Calcular totales para porcentajes
        const totalesPorConcepto = conceptosOrdenados.map(item => item.total);
        
        try {
            window.dashboardCharts.barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Efectivo',
                            data: efectivoData,
                            backgroundColor: '#1cc88a'
                        },
                        {
                            label: 'Tarjeta',
                            data: tarjetaData,
                            backgroundColor: '#f6c23e'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw || 0;
                                    
                                    // Calcular el porcentaje
                                    const index = context.dataIndex;
                                    const total = totalesPorConcepto[index];
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    
                                    return `${label}: ${DashboardUtils.formatearImporte(value)} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return DashboardUtils.formatearImporte(value, true);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 500
                    }
                }
            });
        } catch (e) {
            console.error('Error al crear el gráfico de barras:', e);
        }
    },
    
    // Función para actualizar el gráfico de línea de evolución temporal
    actualizarGraficoLinea(datos) {
        if (!datos || !datos.length) {
            console.error('No hay datos para el gráfico de línea');
            return;
        }
        
        const ctx = document.getElementById('lineChart').getContext('2d');
        
        // Destruir el gráfico anterior si existe
        if (window.dashboardCharts.lineChart) {
            window.dashboardCharts.lineChart.destroy();
            window.dashboardCharts.lineChart = null;
        }
        
        // Determinar si estamos viendo datos mensuales o semanales
        const esEscalaMensual = $('#scaleMonthly').prop('checked');
        
        // Obtener la fecha actual para calcular los últimos 4 meses
        const fechaActual = new Date();
        const mesActual = fechaActual.getMonth();
        const anioActual = fechaActual.getFullYear();
        
        // Calcular los últimos 4 meses
        const ultimosMeses = [];
        for (let i = 0; i < 4; i++) {
            let mes = mesActual - i;
            let anio = anioActual;
            
            if (mes < 0) {
                mes = 12 + mes;
                anio = anioActual - 1;
            }
            
            ultimosMeses.push({
                mes: mes + 1, // Ajustar para que mes sea 1-12 en lugar de 0-11
                anio: anio
            });
        }
        
        ultimosMeses.reverse(); // Ordenar cronológicamente
        
        // Función para convertir semana en mes aproximado
        function semanaAMes(semana) {
            return Math.ceil(semana / 4.34); // Aproximación: 52 semanas / 12 meses ≈ 4.34 semanas/mes
        }
        
        // Función para determinar si un dato entra en los últimos 4 meses
        function estaEnUltimosMeses(dato) {
            const anio = parseInt(dato.ANIO || '0');
            const semana = parseInt(dato.SEMANA || '0');
            const mesAproximado = semanaAMes(semana);
            
            return ultimosMeses.some(periodo => {
                if (anio > periodo.anio) return true;
                if (anio < periodo.anio) return false;
                return mesAproximado >= periodo.mes;
            });
        }
        
        // Filtrar datos para los últimos 4 meses
        const datosUltimosMeses = datos.filter(estaEnUltimosMeses);
        
        // Agrupar datos por mes o semana según la escala seleccionada
        const datosAgrupados = {};
        
        datosUltimosMeses.forEach(dato => {
            const anio = parseInt(dato.ANIO || '0');
            const semana = parseInt(dato.SEMANA || '0');
            const efectivo = parseFloat(dato.IMPORTE || 0);
            const tarjeta = parseFloat(dato.IMPORTE_TARJETA || 0);
            
            let periodo;
            let ordenPeriodo; // Para ordenar cronológicamente
            
            if (esEscalaMensual) {
                // Agrupar por mes
                const mesAproximado = semanaAMes(semana);
                periodo = `${DashboardUtils.mesANombre(mesAproximado)} ${anio}`;
                ordenPeriodo = anio * 100 + mesAproximado; // Para ordenar YYYYMM
            } else {
                // Agrupar por semana
                periodo = `Sem ${semana} ${anio}`;
                ordenPeriodo = anio * 100 + semana; // Para ordenar YYYYWW
            }
            
            if (!datosAgrupados[periodo]) {
                datosAgrupados[periodo] = {
                    periodo,
                    ordenPeriodo,
                    efectivo: 0,
                    tarjeta: 0
                };
            }
            
            datosAgrupados[periodo].efectivo += efectivo;
            datosAgrupados[periodo].tarjeta += tarjeta;
        });
        
        // Convertir a array y ordenar cronológicamente
        const periodos = Object.values(datosAgrupados)
            .sort((a, b) => a.ordenPeriodo - b.ordenPeriodo);
        
        // Limitar a mostrar como máximo los últimos 16 períodos para evitar sobrecarga visual
        const periodosLimitados = periodos.slice(-16);
        
        const labels = periodosLimitados.map(p => p.periodo);
        const efectivoData = periodosLimitados.map(p => p.efectivo);
        const tarjetaData = periodosLimitados.map(p => p.tarjeta);
        const totalData = periodosLimitados.map(p => p.efectivo + p.tarjeta);
        
        try {
            window.dashboardCharts.lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total',
                            data: totalData,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        },
                        {
                            label: 'Efectivo',
                            data: efectivoData,
                            borderColor: '#1cc88a',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false
                        },
                        {
                            label: 'Tarjeta',
                            data: tarjetaData,
                            borderColor: '#f6c23e',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolución de gastos en los últimos 4 meses',
                            font: {
                                size: 14
                            }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw || 0;
                                    return `${label}: ${DashboardUtils.formatearImporte(value)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return DashboardUtils.formatearImporte(value, true);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    animation: {
                        duration: 500
                    }
                }
            });
        } catch (e) {
            console.error('Error al crear el gráfico de línea:', e);
        }
    }
};