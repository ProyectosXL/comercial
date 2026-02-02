/* ========================================
   js/seleccionPedido.js - SOLUCIÓN DEFINITIVA
   ======================================== */

let Pedidos = [];

window.addEventListener('DOMContentLoaded', function() {
    // 1. Escuchadores de eventos
    iniciarEscucha();
    establecerMinFecha();
    
    // 2. Ejecutar correcciones y sumas iniciales
    // Usamos un pequeño delay para asegurar que el DOM y otros scripts terminaron
    setTimeout(() => {
        formatearYCorregirTabla();
        sumarTotalesDash();
    }, 100);

    document.getElementById('btn_save').addEventListener('click', actualizarDB);
});

/**
 * Función que limpia el texto "$ 240.812,50" y lo hace un número real 240812.5
 */
function toNum(texto) {
    if (!texto) return 0;
    // Borramos $, espacios y el PUNTO de miles
    let limpio = texto.toString().replace(/[$. ]/g, '').replace(',', '.');
    let n = parseFloat(limpio) || 0;
    
    // Si el número es mayor a 1 millón, lo corregimos (error de los ceros)
    if (n > 1000000) n = n / 1000;
    
    return n;
}

/**
 * Suma Facturas y Remitos leyendo directamente las celdas de la tabla
 */
function sumarTotalesDash() {
    let totalF = 0;
    let totalR = 0;
    
    // Seleccionamos todas las filas del cuerpo de la tabla
    const filas = document.querySelectorAll('tbody#table tr');
    
    filas.forEach(fila => {
        // Celda 7 es el Importe, Celda 8 es el Tipo Comp
        let celdaImporte = fila.cells[7];
        let selectComp = fila.cells[8].querySelector('select');
        
        if (celdaImporte && selectComp) {
            let valor = 0;
            let input = celdaImporte.querySelector('input');
            
            // Si está editando usamos el valor del input, sino el texto de la celda
            valor = input ? parseFloat(input.value) : toNum(celdaImporte.innerText);
            
            if (selectComp.value === 'FACTURA') totalF += valor;
            else if (selectComp.value === 'REMITO') totalR += valor;
        }
    });
    
    const fmt = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 0 });
    
    // Inyectamos los totales en los h3 de arriba
    document.getElementById('total').textContent = fmt.format(totalF);
    document.getElementById('totalImp').textContent = fmt.format(totalR);
}

/**
 * Corrige los errores de "millones" al cargar y pone el formato $
 */
function formatearYCorregirTabla() {
    const fmt = new Intl.NumberFormat("es-AR", { style: "currency", currency: "ARS", minimumFractionDigits: 0 });
    document.querySelectorAll('tbody#table tr').forEach(fila => {
        let celda = fila.cells[7];
        if (celda && !celda.querySelector('input')) {
            let n = toNum(celda.innerText);
            celda.innerText = fmt.format(n);
        }
    });
}

function habilitarInputs(e) {
    let row = e.target.closest('tr');
    let celdaI = row.cells[7];
    
    if (!celdaI.querySelector('input')) {
        let v = toNum(celdaI.innerText);
        celdaI.innerHTML = `<input type="number" step="0.01" class="form-control form-control-sm" value="${v.toFixed(2)}" style="width:130px">`;
        
        celdaI.querySelector('input').addEventListener('input', () => {
            sumarTotalesDash();
            guardarDatos(row);
        });
    }

    row.querySelectorAll('select, input').forEach(el => {
        el.disabled = false;
        if (el.tagName === 'SELECT' && el.options[0]) el.options[0].disabled = true;
    });
}

function guardarDatos(row) {
    const codigo = row.cells[4].innerText.trim();
    const existeIndex = Pedidos.findIndex(p => p.codigo == codigo);
    
    let celdaI = row.cells[7];
    let input = celdaI.querySelector('input');
    let finalV = input ? parseFloat(input.value) : toNum(celdaI.innerText);

    const info = {
        codigo: codigo,
        fecha: row.cells[0].innerText,
        hora: row.cells[1].innerText,
        cod_client: document.getElementById('cod_client').value.replace('&','%26'),
        razon_soci: document.getElementById('razon_soci').value.replace('&','%26'),
        localidad: document.getElementById('localidad').value,
        cod_vended: document.getElementById('cod_vended').value,
        vendedor: document.getElementById('vendedor').value,
        estado: row.cells[2].innerText,
        talonario: row.cells[3].innerText,
        unidPedido: row.cells[5].innerText,
        unidPendiente: row.cells[6].innerText,
        importePendiente: finalV.toFixed(2).replace('.', ','), // COMA para el PHP
        tipoComp: row.cells[8].querySelector('select').value,
        embalaje: row.cells[9].querySelector('select').value,
        despacho: row.cells[10].querySelector('select').value,
        arreglo: row.cells[11].querySelector('select').value,
        prioridad: row.cells[12].querySelector('select').value,
        fechaDespacho: row.cells[13].querySelector('input').value
    };
    
    if (existeIndex === -1) Pedidos.push(info);
    else Pedidos[existeIndex] = info;
}

function actualizarDB() {
    if (Pedidos.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin cambios' });
        return;
    }
    Swal.fire({ title: 'Guardando...', didOpen: () => { Swal.showLoading(); } });
    let xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            Swal.fire({ icon: 'success', title: '¡Guardado!' }).then(() => { location.reload(); });
        }
    };
    xhr.open('GET', './Controller/updateDatos.php?datos=' + encodeURIComponent(JSON.stringify(Pedidos)), true);
    xhr.send();
}

function iniciarEscucha() {
    document.querySelectorAll(".btnEdit").forEach(btn => btn.addEventListener('click', e => {
        habilitarInputs(e);
        guardarDatos(e.target.closest('tr'));
    }));
    document.querySelectorAll('.form-select, input[type="date"]').forEach(el => el.addEventListener('change', e => {
        guardarDatos(e.target.closest('tr'));
        sumarTotalesDash();
    }));
}

function establecerMinFecha() {
    let d = new Date(); d.setDate(d.getDate() + 2);
    document.querySelectorAll('[type=date]').forEach(el => el.setAttribute("min", d.toISOString().split('T')[0]));
}

function copiarPrimerFila() {
    let rows = document.querySelectorAll('tbody#table tr');
    if (rows.length < 2) return;
    let ref = rows[0];
    let vals = {
        comp: ref.cells[8].querySelector("select").value,
        emb: ref.cells[9].querySelector("select").value,
        desp: ref.cells[10].querySelector("select").value,
        fec: ref.cells[13].querySelector("input").value
    };
    rows.forEach(row => {
        let sel = row.cells[8].querySelector("select");
        if (!sel.disabled) {
            sel.value = vals.comp;
            row.cells[9].querySelector("select").value = vals.emb;
            row.cells[10].querySelector("select").value = vals.desp;
            row.cells[13].querySelector("input").value = vals.fec;
            guardarDatos(row);
        }
    });
    sumarTotalesDash();
    Swal.fire({ icon: 'success', title: 'Replicado', timer: 1000, showConfirmButton: false });
}
/* ========================================
   Cálculo de totales por tipo de comprobante
   ======================================== */

/**
 * Inicializa los totales de comprobantes
 */
function initTotalesComprobantes() {
    sumarComprobantesFacturas();
}

function limpiarImporte(texto) {
    if (!texto) return 0;
    // Eliminamos el signo $, los espacios y los puntos (que son separadores de miles en es-AR)
    let limpio = texto.replace(/[$. ]/g, '');
    // Cambiamos la coma decimal por un punto decimal para que JS lo entienda
    limpio = limpio.replace(',', '.');
    return parseFloat(limpio) || 0;
}

/**
 * Suma los totales de Facturas y Remitos
 */
function sumarComprobantesFacturas() {
    let totalFactura = 0;
    let totalRemito = 0;
    
    document.querySelectorAll('#trBody').forEach(fila => {
        let select = fila.querySelector('.Comprobante');
        let celdaImporte = fila.querySelector('td:nth-child(8)'); // Columna importe
        
        let inputManual = celdaImporte.querySelector('.edit-importe');
        let valor = inputManual ? parseFloat(inputManual.value) : parseImporte(celdaImporte.innerText);
        
        if (!isNaN(valor)) {
            if (select.value == 'FACTURA') totalFactura += valor;
            else if (select.value == 'REMITO') totalRemito += valor;
        }
    });
    
    const formatter = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 0 });
    document.getElementById('total').textContent = formatter.format(totalFactura);
    document.getElementById('totalImp').textContent = formatter.format(totalRemito);
}

/* ========================================
   Validación y guardado
   ======================================== */

/**
 * Valida que no haya campos vacíos en pedidos editados
 */
function checkInpustNoVacios() {
    let hayErrores = false;
    
    let selects = document.querySelectorAll('select');
    selects.forEach(el => {
        // Validar solo campos obligatorios (no Prioridad ni Arreglo)
        if (!el.closest('td').innerHTML.includes('Prioridad') && 
            !el.closest('td').innerHTML.includes('Arreglo')) {
            if (el.disabled == false && el.value == '') {
                el.classList.add('error');
                el.style.borderColor = 'red';
                hayErrores = true;
            } else {
                el.classList.remove('error');
                el.style.borderColor = '';
            }
        }
    });
    
    let fechas = document.querySelectorAll('input[type="date"]');
    fechas.forEach(el => {
        if (el.disabled == false && el.value == '') {
            el.classList.add('error');
            el.style.borderColor = 'red';
            hayErrores = true;
        } else {
            el.classList.remove('error');
            el.style.borderColor = '';
        }
    });
    
    return hayErrores;
}

/**
 * Actualiza la base de datos con los cambios
 */
function actualizarDB() {
    if (Pedidos.length === 0) {
        Swal.fire({ icon: 'error', title: 'No se modificaron datos', text: 'Edite pedidos y haga click en guardar' });
        return;
    }
    
    if (checkInpustNoVacios()) {
        Swal.fire({ icon: 'error', title: 'Campos vacíos', text: 'Debe completar todos los campos obligatorios' });
        return;
    }
    
    Swal.fire({
        title: 'Guardando cambios...',
        html: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    let conexion = new XMLHttpRequest();
    conexion.onreadystatechange = function() {
        if (conexion.readyState === 4) {
            if (conexion.status >= 200 && conexion.status < 300) {
                Swal.fire({
                    icon: 'success',
                    title: 'Pedidos modificados',
                    text: "Se guardaron los cambios correctamente",
                    showConfirmButton: true,
                }).then(function() {
                    // CAMBIO: En lugar de ir a index.php, recargamos la página actual
                    // para ver los cambios aplicados y seguir con el mismo cliente.
                    location.reload(); 
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de carga',
                    text: 'No se pudo guardar: ' + conexion.responseText
                });
            }
        }
    };
    
    let datos = JSON.stringify(Pedidos);
    conexion.open('GET', './Controller/updateDatos.php?datos=' + datos, true);
    conexion.send();
}


/* ========================================
   Utilidades
   ======================================== */

/**
 * Establece la fecha mínima en los campos de fecha
 */
function establecerMinFecha() {
    let today = new Date();
    let dd = today.getDate() + 2;
    let mm = today.getMonth() + 1;
    let yyyy = today.getFullYear();
    
    if (dd < 10) dd = '0' + dd;
    if (mm < 10) mm = '0' + mm;
    
    today = yyyy + '-' + mm + '-' + dd;
    
    let fechas = document.querySelectorAll('[type=date]');
    fechas.forEach(el => {
        el.setAttribute("min", today);
    });
}
