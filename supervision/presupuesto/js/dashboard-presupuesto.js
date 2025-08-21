
// dashboard-presupuesto.js - Manejo del control presupuestario
const DashboardPresupuesto = {
    
    // Función para actualizar KPIs de presupuesto
    actualizarKPIsPresupuesto(kpis) {
        $('#totalPresupuesto').text(DashboardUtils.formatearImporte(kpis.totalPresupuesto));
        $('#gastoReal').text(DashboardUtils.formatearImporte(kpis.totalGastoReal));
        $('#saldoPresupuesto').text(DashboardUtils.formatearImporte(kpis.saldoPresupuesto));
        $('#porcentajeEjecucion').text(kpis.porcentajeEjecucion.toFixed(1) + '%');
        
        // Cambiar color de la tarjeta de saldo según si es positivo o negativo
        const saldoCard = $('#saldoCard');
        saldoCard.removeClass('bg-success bg-danger text-white text-dark');
        
        if (kpis.saldoPresupuesto >= 0) {
            saldoCard.addClass('bg-success text-white');
        } else {
            saldoCard.addClass('bg-danger text-white');
        }
    },
    
    // Función para actualizar gráficos de presupuesto
    actualizarGraficosPresupuesto(agregados) {
        if (!agregados) {
            console.error('Objeto agregados no definido para presupuesto');
            return;
        }
        
        DashboardPresupuesto.actualizarGraficoPresupuestoConcepto(agregados.porConcepto);
        DashboardPresupuesto.actualizarGraficoPresupuestoSupervisora(agregados.porSupervisora);
    },
    
    // Gráfico de presupuesto vs gasto por concepto
    actualizarGraficoPresupuestoConcepto(datosPorConcepto) {
        if (!datosPorConcepto) {
            console.error('Datos por concepto no definidos');
            return;
        }
        
        const ctx = document.getElementById('presupuestoConceptoChart').getContext('2d');
        
        if (window.dashboardCharts.presupuestoConceptoChart) {
            window.dashboardCharts.presupuestoConceptoChart.destroy();
        }
        
        const conceptos = Object.keys(datosPorConcepto).slice(0, 8); // Limitar a 8 conceptos
        const presupuestoData = conceptos.map(concepto => datosPorConcepto[concepto].presupuesto);
        const gastoRealData = conceptos.map(concepto => datosPorConcepto[concepto].gastoReal);
        
        try {
            window.dashboardCharts.presupuestoConceptoChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: conceptos,
                    datasets: [
                        {
                            label: 'Presupuesto',
                            data: presupuestoData,
                            backgroundColor: '#36b9cc',
                            borderColor: '#258391',
                            borderWidth: 1
                        },
                        {
                            label: 'Gasto Real',
                            data: gastoRealData,
                            backgroundColor: '#f6c23e',
                            borderColor: '#dda20a',
                            borderWidth: 1
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
                                    return `${context.dataset.label}: ${DashboardUtils.formatearImporte(context.raw)}`;
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
            console.error('Error al crear el gráfico de presupuesto por concepto:', e);
        }
    },
    
    // Gráfico de presupuesto vs gasto por supervisora
    actualizarGraficoPresupuestoSupervisora(datosPorSupervisora) {
        if (!datosPorSupervisora) {
            console.error('Datos por supervisora no definidos');
            return;
        }
        
        const ctx = document.getElementById('presupuestoSupervisoraChart').getContext('2d');
        
        if (window.dashboardCharts.presupuestoSupervisoraChart) {
            window.dashboardCharts.presupuestoSupervisoraChart.destroy();
        }
        
        const supervisoras = Object.keys(datosPorSupervisora);
        const presupuestoData = supervisoras.map(supervisora => datosPorSupervisora[supervisora].presupuesto);
        const gastoRealData = supervisoras.map(supervisora => datosPorSupervisora[supervisora].gastoReal);
        
        try {
            window.dashboardCharts.presupuestoSupervisoraChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: supervisoras,
                    datasets: [
                        {
                            label: 'Presupuesto',
                            data: presupuestoData,
                            backgroundColor: '#36b9cc',
                            borderColor: '#258391',
                            borderWidth: 1
                        },
                        {
                            label: 'Gasto Real',
                            data: gastoRealData,
                            backgroundColor: '#f6c23e',
                            borderColor: '#dda20a',
                            borderWidth: 1
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
                                    return `${context.dataset.label}: ${DashboardUtils.formatearImporte(context.raw)}`;
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
            console.error('Error al crear el gráfico de presupuesto por supervisora:', e);
        }
    },
    
    // Función para actualizar tabla de presupuesto
    actualizarTablaPresupuesto(datos) {
        if (!datos || !datos.length) {
            console.error('No hay datos para la tabla de presupuesto');
            return;
        }
        
        const presupuestoTable = window.dashboardTables.presupuestoTable;
        presupuestoTable.clear();
        
        datos.forEach(dato => {
            const mes = dato.MES || '';
            const anio = dato.ANIO || '';
            const supervisora = dato.SUPERVISORA || '';
            const concepto = dato.CONCEPTO || '';
            const presupuesto = parseFloat(dato.IMP_PRESUPUESTO || 0);
            const gastoReal = parseFloat(dato.IMPORTE_GASTO || 0);
            const saldo = parseFloat(dato.SALDO_PRESPUESTO || 0);
            const porcentaje = presupuesto > 0 ? (gastoReal / presupuesto) * 100 : 0;
            
            // Formatear el porcentaje con color
            let porcentajeFormateado = porcentaje.toFixed(1) + '%';
            if (porcentaje > 100) {
                porcentajeFormateado = `<span class="text-danger fw-bold">${porcentajeFormateado}</span>`;
            } else if (porcentaje > 80) {
                porcentajeFormateado = `<span class="text-warning fw-bold">${porcentajeFormateado}</span>`;
            } else {
                porcentajeFormateado = `<span class="text-success">${porcentajeFormateado}</span>`;
            }
            
            // Formatear el saldo con color
            let saldoFormateado = DashboardUtils.formatearImporte(saldo);
            if (saldo < 0) {
                saldoFormateado = `<span class="text-danger fw-bold">${saldoFormateado}</span>`;
            } else {
                saldoFormateado = `<span class="text-success">${saldoFormateado}</span>`;
            }
            
            presupuestoTable.row.add([
                mes,
                anio,
                supervisora,
                concepto,
                DashboardUtils.formatearImporte(presupuesto),
                DashboardUtils.formatearImporte(gastoReal),
                saldoFormateado,
                porcentajeFormateado
            ]);
        });
        
        presupuestoTable.draw();
    }
};