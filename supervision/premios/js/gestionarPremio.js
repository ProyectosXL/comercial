// js/gestionarPremio.js //

document.addEventListener('DOMContentLoaded', function() {
    const periodoInput = document.getElementById('periodo');
    const guardarBtn = document.querySelector('button[type="submit"]');
    const fechaCargaDiv = document.createElement('div');
    fechaCargaDiv.className = 'alert alert-info mb-3';
    document.querySelector('.table-responsive').insertBefore(fechaCargaDiv, document.querySelector('table'));

    // Función para formatear números
    function formatNumber(value) {
        let numero = value.toString().replace(/[^\d]/g, '');
        return numero.length > 0 ? new Intl.NumberFormat('es-AR').format(parseInt(numero)) : '';
    }

    // Función para cargar datos del período
    function cargarDatosPeriodo(periodo) {
        fetch(`Controller/obtenerPremios.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                console.log('Datos recibidos del servidor:', data);
                
                if (data.status === 'success' && data.data.length > 0) {
                    // Mostrar fecha de carga
                    fechaCargaDiv.innerHTML = `<strong>Fecha de carga:</strong> ${data.data[0].FECHA_FORMATEADA}`;
                    fechaCargaDiv.style.display = 'block';
                    
                    // Verificar si es el período actual
                    if (data.es_periodo_actual) {
                        // Período actual: mostrar botón y habilitar edición
                        guardarBtn.style.display = 'block';
                        document.querySelectorAll('.premio-input').forEach(input => {
                            input.disabled = false;
                        });
                    } else {
                        // Período histórico: ocultar botón y deshabilitar edición
                        guardarBtn.style.display = 'none';
                        document.querySelectorAll('.premio-input').forEach(input => {
                            input.disabled = true;
                        });
                    }

                    // Cargar valores en la tabla
                    data.data.forEach(premio => {
                        console.log('Procesando premio:', premio);
                        document.querySelectorAll('.premio-input').forEach(input => {
                            if (input.dataset.supervisor === premio.ID_SUP.toString()) {
                                const concepto = input.dataset.concepto;
                                let valor = 0;
                                
                                switch(concepto) {
                                    case 'OBJETIVO VENTAS':
                                        valor = premio.P_OBJ_VENTA;
                                        break;
                                    case 'CRECIMIENTO VENTAS':
                                        valor = premio.P_OBJ_CREC_VTA;
                                        break;
                                    case 'TICKET PROMEDIO':
                                        valor = premio.P_TICKET_PROM;
                                        break;
                                    case 'TICKET 2DO. PRODUCTO':
                                        valor = premio.P_TICKET_2PROD;
                                        break;
                                    case 'TICKET 3ER. PRODUCTO':
                                        valor = premio.P_TICKET_3PROD;
                                        break;
                                    case 'OBJETIVO VENTAS FRANQ.':
                                        valor = premio.P_OBJ_VENTA_F;
                                        break;
                                    case 'CRECIMIENTO VENTAS FRANQ.':
                                        valor = premio.P_OBJ_CREC_VTA_F;
                                        break;
                                    case 'VENTA ECOMMERCE':
                                        valor = premio.P_OBJ_VENTA_ECOMM;
                                        break;
                                }
                                
                                console.log('Concepto:', concepto, 'Valor:', valor);
                                if (valor !== null && valor !== undefined) {
                                    input.value = formatNumber(valor);
                                }
                            }
                        });
                    });
                } else {
                    // No hay datos para este período
                    fechaCargaDiv.style.display = 'none';
                    
                    // Verificar si es período actual para mostrar/ocultar botón
                    if (data.es_periodo_actual) {
                        guardarBtn.style.display = 'block';
                        document.querySelectorAll('.premio-input').forEach(input => {
                            input.disabled = false;
                            input.value = '';
                        });
                    } else {
                        guardarBtn.style.display = 'none';
                        document.querySelectorAll('.premio-input').forEach(input => {
                            input.disabled = true;
                            input.value = '';
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar los datos del período'
                });
            });
    }

    // Event listener para cambio de período
    periodoInput.addEventListener('change', function() {
        if (this.value) {
            cargarDatosPeriodo(this.value);
        }
    });

    // Formatear números en los inputs
    document.querySelectorAll('.premio-input').forEach(input => {
        input.addEventListener('input', function() {
            if (!this.disabled) {
                let formattedValue = formatNumber(this.value);
                this.value = formattedValue;
            }
        });
    });

    // Manejar el envío del formulario
    document.getElementById('premiosForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar período
        let periodo = document.getElementById('periodo').value;
        if (!periodo) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor seleccione un período'
            });
            return;
        }

        // Recopilar datos
        let formData = new FormData();
        formData.append('periodo', periodo);

        let premiosData = {};
        let hasValues = false;

        // Obtener todos los inputs y sus valores
        document.querySelectorAll('.premio-input:not(:disabled)').forEach(input => {
            let supervisorId = input.dataset.supervisor;
            let conceptoId = input.dataset.concepto;
            let valor = input.value.replace(/\./g, '');

            if (!premiosData[supervisorId]) {
                premiosData[supervisorId] = {};
            }

            premiosData[supervisorId][conceptoId] = valor;
            
            if (valor && valor !== '0') {
                hasValues = true;
            }
        });

        if (!hasValues) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor ingrese al menos un valor'
            });
            return;
        }

        formData.append('premios', JSON.stringify(premiosData));

        Swal.fire({
            title: '¿Confirmar actualización?',
            text: "¿Desea actualizar los premios del período actual?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('controller/guardarPremio.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: data.message,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            // Recargar los datos después de actualizar
                            cargarDatosPeriodo(periodo);
                        });
                    } else {
                        throw new Error(data.message || 'Error al actualizar los datos');
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Error al procesar la solicitud',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        });
    });
});