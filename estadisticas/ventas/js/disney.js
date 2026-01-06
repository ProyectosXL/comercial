document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reportForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const fechaDesde = document.getElementById('fecha_desde');
    const fechaHasta = document.getElementById('fecha_hasta');
    const btnDescargar = document.getElementById('btnDescargar');
    const previewPlaceholder = document.getElementById('previewPlaceholder');
    const previewContainer = document.getElementById('previewContainer');
    const previewTable = document.getElementById('previewTable');
    const previewInfo = document.getElementById('previewInfo');

    let datosActuales = null;

    // Validación de fechas
    function validarFechas() {
        if (!fechaDesde.value || !fechaHasta.value) return true;
        
        const desde = new Date(fechaDesde.value);
        const hasta = new Date(fechaHasta.value);
        
        if (desde > hasta) {
            alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
            return false;
        }
        
        return true;
    }

    fechaDesde.addEventListener('change', validarFechas);
    fechaHasta.addEventListener('change', validarFechas);

    // Generar vista previa
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFechas()) return;
        
        // Validar que el período no sea mayor a 12 meses para evitar timeout
        const desde = new Date(fechaDesde.value);
        const hasta = new Date(fechaHasta.value);
        const diffMonths = (hasta.getFullYear() - desde.getFullYear()) * 12 + (hasta.getMonth() - desde.getMonth());
        
        if (diffMonths > 12) {
            if (!confirm('El período seleccionado es mayor a 12 meses. La consulta puede tardar varios minutos. ¿Deseas continuar?')) {
                return;
            }
        }
        
        loadingOverlay.classList.add('active');
        btnDescargar.disabled = true;
        
        const params = new URLSearchParams({
            fecha_desde: fechaDesde.value,
            fecha_hasta: fechaHasta.value
        });
        
        fetch('Controller/obtenerDatos.php?' + params.toString(), {
            signal: AbortSignal.timeout(180000) // 3 minutos timeout
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                loadingOverlay.classList.remove('active');
                
                if (!data.success) {
                    alert(data.message || 'Error al obtener los datos');
                    return;
                }
                
                datosActuales = data;
                mostrarVistaPrevia(data);
                btnDescargar.disabled = false;
            })
            .catch(error => {
                loadingOverlay.classList.remove('active');
                console.error('Error completo:', error);
                
                let mensaje = 'Error al cargar los datos';
                if (error.name === 'AbortError') {
                    mensaje = 'La consulta está tardando demasiado. Intenta con un período más corto.';
                } else if (error.message) {
                    mensaje += ': ' + error.message;
                }
                
                alert(mensaje);
            });
    });

    // Descargar Excel
    btnDescargar.addEventListener('click', function() {
        if (!fechaDesde.value || !fechaHasta.value) return;
        
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingText = document.getElementById('loadingText');
        
        loadingTitle.textContent = 'Generando Excel...';
        loadingText.textContent = 'Esto puede tardar unos segundos';
        loadingOverlay.classList.add('active');
        
        const params = new URLSearchParams({
            action: 'download',
            fecha_desde: fechaDesde.value,
            fecha_hasta: fechaHasta.value
        });
        
        // Establecer una cookie para detectar cuando termine la descarga
        const downloadToken = new Date().getTime();
        document.cookie = `downloadToken=${downloadToken}; path=/`;
        
        // Crear iframe para la descarga
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = 'reporteDisney.php?' + params.toString() + '&downloadToken=' + downloadToken;
        document.body.appendChild(iframe);
        
        // Monitorear la cookie para saber cuándo terminó
        let attempts = 0;
        const maxAttempts = 120; // 2 minutos máximo (120 * 1000ms)
        
        const checkDownload = setInterval(function() {
            attempts++;
            const cookies = document.cookie.split(';');
            let downloadStarted = false;
            
            for (let cookie of cookies) {
                if (cookie.trim().startsWith('downloadComplete=')) {
                    downloadStarted = true;
                    break;
                }
            }
            
            if (downloadStarted || attempts >= maxAttempts) {
                clearInterval(checkDownload);
                loadingOverlay.classList.remove('active');
                loadingTitle.textContent = 'Procesando datos...';
                loadingText.textContent = 'Por favor espere';
                
                // Limpiar
                document.body.removeChild(iframe);
                document.cookie = 'downloadToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
                document.cookie = 'downloadComplete=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
                
                if (attempts >= maxAttempts && !downloadStarted) {
                    alert('La descarga está tardando demasiado. Por favor, intenta con un período más corto o verifica tu conexión.');
                }
            }
        }, 1000); // Verificar cada segundo
    });

    // Mostrar vista previa
    function mostrarVistaPrevia(data) {
        previewPlaceholder.style.display = 'none';
        previewContainer.style.display = 'block';
        
        // Info
        const totalProductos = data.productos.length;
        const totalMeses = data.meses.length;
        previewInfo.textContent = `${totalProductos} productos | ${totalMeses} meses`;
        
        // Construir tabla
        let html = '<thead><tr>';
        html += '<th>Código</th>';
        html += '<th>Descripción</th>';
        
        data.meses.forEach(mes => {
            html += `<th class="text-right">${mes.abrev}<br>Unidades</th>`;
            html += `<th class="text-right">${mes.abrev}<br>Importe</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        // Datos
        const totales = {};
        data.meses.forEach(mes => {
            totales[mes.key] = { cantidad: 0, importe: 0 };
        });
        
        data.productos.forEach(producto => {
            html += '<tr>';
            html += `<td>${producto.COD_ARTICU}</td>`;
            html += `<td>${producto.DESC_CTA_ARTICULO}</td>`;
            
            data.meses.forEach(mes => {
                const mesData = producto.meses[mes.key] || { CANTIDAD: 0, IMPORTE: 0 };
                html += `<td class="text-right">${formatNumber(mesData.CANTIDAD)}</td>`;
                html += `<td class="text-right">$${formatNumber(mesData.IMPORTE)}</td>`;
                
                totales[mes.key].cantidad += mesData.CANTIDAD;
                totales[mes.key].importe += mesData.IMPORTE;
            });
            
            html += '</tr>';
        });
        
        // Totales
        html += '<tr class="total-row">';
        html += '<td colspan="2">TOTALES</td>';
        
        data.meses.forEach(mes => {
            html += `<td class="text-right">${formatNumber(totales[mes.key].cantidad)}</td>`;
            html += `<td class="text-right">$${formatNumber(totales[mes.key].importe)}</td>`;
        });
        
        html += '</tr></tbody>';
        
        previewTable.innerHTML = html;
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
});
