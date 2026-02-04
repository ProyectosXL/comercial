/* ========================================
   js/index.js - CORREGIDO (Modal y Negativos)
   ======================================== */

document.addEventListener('DOMContentLoaded', function() {
    initTotales();
    initBusquedaRapida();
    initEdicionTracking();
    initModalCliente();
    initBotonLimpiarMarcas();
    checkEditedRow();
});

// Actualizar al volver atrás
window.addEventListener('pageshow', function(event) {
    if (event.persisted) checkEditedRow();
});

/* --- UTILIDADES --- */

// Formateador oficial Argentina
const formateadorPesos = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
});

// Función robusta para limpiar números (acepta negativos)
function parseMoneda(valor) {
    if (!valor) return 0;
    if (typeof valor === 'number') return valor;
    
    // Convertir a string
    let str = valor.toString();
    
    // Si viene con formato raro ej: "1.000,00-", lo arreglamos (formato contable viejo)
    if (str.includes('-') && str.indexOf('-') > 0) {
        str = '-' + str.replace('-', '');
    }
    
    // Eliminar todo excepto números, puntos, comas y signo menos
    let limpio = str.replace(/[^0-9.,-]/g, '');
    
    // Reemplazar coma decimal por punto para JS
    limpio = limpio.replace(',', '.');
    
    let num = parseFloat(limpio);
    return isNaN(num) ? 0 : num;
}

/* --- TOTALES --- */
function initTotales() {
    // Cálculo simple de totales de la tabla principal
    const celdasTotal = document.querySelectorAll('td.sumTotal');
    let totalArt = 0;
    celdasTotal.forEach(c => totalArt += (parseInt(c.textContent) || 0));
    
    const elTotal = document.getElementById('total');
    if (elTotal) elTotal.textContent = new Intl.NumberFormat('de-DE').format(totalArt);

    const filas = document.querySelectorAll('.table-modern tbody tr');
    const elClient = document.getElementById('totalClient');
    if (elClient) elClient.textContent = filas.length;

    // Suma de importes (que ya vienen formateados en PHP)
    const celdasImp = document.querySelectorAll('td.sumImporte');
    let totalImp = 0;
    celdasImp.forEach(c => {
        // Limpiamos el texto ($ 1.000.000 -> 1000000)
        let limpio = c.textContent.replace(/[^0-9-]/g, ''); 
        totalImp += parseInt(limpio) || 0;
    });

    const elImp = document.getElementById('totalImp');
    if (elImp) elImp.textContent = formateadorPesos.format(totalImp);
}

/* --- MODAL CLIENTE (LA CORRECCIÓN IMPORTANTE) --- */

function initModalCliente() {
    document.querySelectorAll('.btn-info-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const fila = this.closest('tr');
            // Buscamos el código. Asegúrate que tu PHP pone id="cod_client" en la celda
            const celdaCod = fila.querySelector('#cod_client');
            if(celdaCod) {
                const cod = celdaCod.textContent.trim();
                buscarCliente(cod);
            }
        });
    });
}

function buscarCliente(codigo) {
    // 1. Poner "Cargando..." visualmente
    const titulo = document.getElementById('codigoCliente');
    if(titulo) titulo.textContent = 'Cargando datos...';
    
    // 2. Resetear valores visuales a $0
    resetearModal();

    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    // Intenta parsear
                    const respuesta = JSON.parse(xhr.responseText);
                    // A veces devuelve array vacío o array con 1 objeto
                    const datos = (Array.isArray(respuesta) && respuesta.length > 0) ? respuesta[0] : null;
                    
                    if (datos) {
                        llenarModal(datos);
                    } else {
                        if(titulo) titulo.textContent = 'Sin datos para el cliente ' + codigo;
                    }
                } catch (e) {
                    console.error("Error JSON", e);
                    if(titulo) titulo.textContent = 'Error de datos';
                }
            } else {
                if(titulo) titulo.textContent = 'Error de conexión';
            }
        }
    };
    xhr.open('GET', 'Class/ppp.php?cliente=' + encodeURIComponent(codigo), true);
    xhr.send();
}

function resetearModal() {
    // Pone todo en guiones mientras carga
    const ids = ['spanCupoCred', 'totalDisponible', 'saldo', 'vencidas', 'montoPedidos', 'cheque', 'cheques10Dias', 'totalDeuda'];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if(el) {
            el.textContent = '-';
            el.className = ''; // Limpiar colores rojos previos
            // Restaurar clases base si es necesario (badges)
            if(id !== 'spanCupoCred' && id !== 'totalDisponible' && id !== 'totalDeuda') {
                 el.classList.add('badge', 'rounded-pill', 'px-3', 'py-2');
                 // Asignar color base temporal
                 if(id === 'saldo') el.classList.add('bg-info');
                 if(id === 'vencidas') el.classList.add('bg-danger');
                 // etc... pero mejor dejamos que llenarModal asigne colores
            }
        }
    });
}

function llenarModal(d) {
    // 1. Título: Probamos varias keys por si la DB cambia nombres de columna
    const codigo = d.COD_CLIENT || d.COD_CLIENTE || d.CLIENTE || '';
    const razon = d.RAZON_SOCI || d.RAZON_SOCIAL || d.NOMBRE || '';
    
    const titulo = document.getElementById('codigoCliente');
    if(titulo) titulo.textContent = `${codigo} - ${razon}`;

    // 2. Función helper interna para asignar valor y color
    const setVal = (id, rawVal, defaultClass) => {
        const el = document.getElementById(id);
        if(!el) return;
        
        const num = parseMoneda(rawVal);
        el.textContent = formateadorPesos.format(num);
        
        // Lógica de colores para badges (saldo, cheques, etc)
        // Si defaultClass existe, es un badge. Si no, es texto plano (H4)
        if (defaultClass) {
            el.className = `badge rounded-pill px-3 py-2 ${defaultClass}`;
            // Si es negativo, forzamos rojo
            if (num < 0) {
                el.classList.remove(defaultClass);
                el.classList.add('bg-danger');
            }
        } else {
            // Es un H4 o texto grande (Cupo, Disponible, Deuda Total)
            if (num < 0) el.style.color = '#dc3545'; // Rojo
            else el.style.color = ''; // Reset
        }
        return num;
    };

    // Asignamos valores
    const cupo = setVal('spanCupoCred', d.CUPO_CRED, null); // null porque es H4
    setVal('totalDisponible', d.TOTAL_DISPONIBLE, null);
    
    // Badges con colores por defecto
    setVal('saldo', d.SALDO_CC, 'bg-info');
    setVal('vencidas', d.VENCIDAS, 'bg-danger');
    setVal('montoPedidos', d.MONTO_PEDIDOS, 'bg-warning text-dark');
    setVal('cheque', d.CHEQUE, 'bg-secondary');
    setVal('cheques10Dias', d.CHEQUES_10_DIAS, 'bg-primary');
    
    // Total Deuda (Card Grande)
    const deuda = setVal('totalDeuda', d.TOTAL_DEUDA, null);
    
    // Porcentaje
    const elPorc = document.getElementById('porcentajeDeuda');
    if(elPorc && cupo !== 0) {
        // Deuda negativa significa saldo a favor, el porcentaje seria negativo
        const p = ((deuda / cupo) * 100);
        elPorc.textContent = p.toFixed(1) + '%';
    }
}

/* --- OTROS --- */
function initBusquedaRapida() {
    const input = document.getElementById('textbusq');
    if(input) input.addEventListener('input', filtrarTabla);
}

function filtrarTabla() {
    const filtro = this.value.toUpperCase();
    const filas = document.querySelectorAll('.table-modern tbody tr');
    let visibles = 0;
    
    filas.forEach(fila => {
        const texto = fila.textContent;
        if (texto.toUpperCase().indexOf(filtro) > -1) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });
    
    const elClient = document.getElementById('totalClient');
    if(elClient) elClient.textContent = visibles;
    
    // Recalcular totales visibles (opcional, pero recomendado)
    initTotalesVisible();
}

function initTotalesVisible() {
    let totalArt = 0; 
    let totalImp = 0;
    
    document.querySelectorAll('.table-modern tbody tr').forEach(fila => {
        if(fila.style.display !== 'none') {
            const celdaArt = fila.querySelector('.sumTotal');
            const celdaImp = fila.querySelector('.sumImporte');
            if(celdaArt) totalArt += (parseInt(celdaArt.textContent) || 0);
            if(celdaImp) totalImp += (parseInt(celdaImp.textContent.replace(/[^0-9-]/g, '')) || 0);
        }
    });
    
    document.getElementById('total').textContent = new Intl.NumberFormat('de-DE').format(totalArt);
    document.getElementById('totalImp').textContent = formateadorPesos.format(totalImp);
}

// ... (Resto de funciones: initEdicionTracking, checkEditedRow, etc. se mantienen igual)
function initEdicionTracking() {
    document.querySelectorAll('.btn-edit').forEach(boton => {
        boton.addEventListener('click', function() {
            const fila = this.closest('tr');
            const cod = fila.querySelector('#cod_client').textContent.trim();
            let editados = JSON.parse(sessionStorage.getItem('clientesEditados') || '[]');
            if (!editados.includes(cod)) {
                editados.push(cod);
                sessionStorage.setItem('clientesEditados', JSON.stringify(editados));
            }
        });
    });
}
function checkEditedRow() {
    const editados = JSON.parse(sessionStorage.getItem('clientesEditados') || '[]');
    if (editados.length === 0) return;
    document.querySelectorAll('.table-modern tbody tr').forEach(fila => {
        const celda = fila.querySelector('#cod_client');
        if (celda && editados.includes(celda.textContent.trim())) {
            fila.classList.add('edited-row');
        }
    });
}
function initBotonLimpiarMarcas() {
    const btn = document.getElementById('btnLimpiarMarcas');
    if(btn) btn.addEventListener('click', () => {
        sessionStorage.removeItem('clientesEditados');
        location.reload();
    });
}