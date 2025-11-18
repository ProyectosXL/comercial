
// dashboard-main.js - Archivo principal del dashboard
$(document).ready(function() {
    // Referencias a los gráficos
    let pieChart, barChart, lineChart, presupuestoConceptoChart, presupuestoSupervisoraChart;
    
    // Configurar DataTables
    let dataTable = $('#datosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        order: [[1, 'desc'], [0, 'desc'], [2, 'desc']], // Ordenar por Año desc, Mes desc, Semana desc
        pageLength: 10
    });
    
    let presupuestoTable = $('#presupuestoTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        order: [[1, 'desc'], [0, 'desc']],
        pageLength: 10
    });
    
    // Exponer variables globales necesarias
    window.dashboardCharts = {
        pieChart, barChart, lineChart, presupuestoConceptoChart, presupuestoSupervisoraChart
    };
    window.dashboardTables = { dataTable, presupuestoTable };
    
    // Manejar cambio de período
    $('#periodoSelect').change(function() {
        if ($(this).val() === 'custom') {
            $('#rangoPersonalizado').removeClass('d-none');
        } else {
            $('#rangoPersonalizado').addClass('d-none');
        }
    });
    
    // Cargar datos iniciales
    DashboardData.cargarDatos();
    DashboardData.cargarDatosPresupuesto();
    DashboardData.cargarDatosTemporales();
    
    // Evento para aplicar filtros
    $('#aplicarFiltros').click(function() {
        DashboardData.cargarDatos();
        DashboardData.cargarDatosPresupuesto();
        DashboardData.cargarDatosTemporales();
    });
    
    // Cambio de escala temporal
    $('input[name="timeScale"]').change(function() {
        if (window.datosTemporales) {
            DashboardCharts.actualizarGraficoLinea(window.datosTemporales);
        }
    });
    
    // Evento para cambiar entre tipos de gráfico circular
    $('input[name="pieChartType"]').change(function() {
        if (window.datosCompletos && window.agregados) {
            if (this.id === 'pieChartEfectivoTarjeta') {
                $('#pieChartTitle').text('Proporción Efectivo vs Tarjeta');
                DashboardCharts.actualizarGraficoPieEfectivoTarjeta(window.agregados);
            } else if (this.id === 'pieChartSupervisora') {
                $('#pieChartTitle').text('Proporción de Gastos por Supervisora');
                DashboardCharts.actualizarGraficoPieSupervisora(window.agregados);
            }
        }
    });
    
    // Eventos de cambio de pestaña
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = e.target.getAttribute('data-bs-target');
        
        // Redimensionar gráficos cuando se cambia de pestaña
        setTimeout(() => {
            if (targetTab === '#gastos' && window.agregados) {
                DashboardCharts.actualizarGraficos(window.datosCompletos, window.agregados);
            } else if (targetTab === '#evolucion-temporal' && window.datosTemporales) {
                DashboardCharts.actualizarGraficoLinea(window.datosTemporales);
            } else if (targetTab === '#control-presupuestario' && window.datosPresupuesto) {
                DashboardPresupuesto.actualizarGraficosPresupuesto(window.datosPresupuesto.agregados);
            }
        }, 100);
    });
    
    // Inicializar tooltips de Bootstrap si están disponibles
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});