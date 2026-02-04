/* ========================================
   js/seleccionPedido.js - CORREGIDO ENTEROS
   ======================================== */

let Pedidos = [];

window.addEventListener('DOMContentLoaded', function() {
    iniciarEscucha();
    establecerMinFecha();
    
    // Pequeño delay para asegurar carga DOM
    setTimeout(() => {
        sumarTotalesDash();
    }, 100);

    document.getElementById('btn_save').addEventListener('click', actualizarDB);
});

/**
 * Convierte cualquier texto de moneda (ej: "$ 1.250.000") a número puro (1250000)
 */
function toNum(texto) {
    if (!texto) return 0;
    let str = texto.toString();
    
    // Si es un input type number, viene directo
    if(typeof texto === 'number') return texto;

    // 1. Eliminar signo $, puntos de mil y espacios
    let limpio = str.replace(/[$. ]/g, '');
    
    // 2. Si llegara a haber una coma decimal, reemplazar por punto para JS
    limpio = limpio.replace(',', '.');
    
    // 3. Parsear a flotante y si falla devolver 0
    let n = parseFloat(limpio);
    return isNaN(n) ? 0 : n;
}

/**
 * Formateador unificado: Enteros, Formato Argentina
 */
const formateadorMoneda = new Intl.NumberFormat("es-AR", { 
    style: "currency", 
    currency: "ARS", 
    minimumFractionDigits: 0, 
    maximumFractionDigits: 0 
});

function sumarTotalesDash() {
    let totalF = 0;
    let totalR = 0;
    const filas = document.querySelectorAll('tbody#table tr');
    
    filas.forEach(fila => {
        // Celda 7 es la de Importe (Indices empiezan en 0)
        let celdaImporte = fila.cells[7];
        let celdaTipo = fila.cells[8];
        let selectComp = celdaTipo.querySelector('select');
        
        if (celdaImporte && selectComp) {
            let valor = 0;
            let input = celdaImporte.querySelector('input');
            
            // Si hay input (modo edición), tomar value. Si no, tomar innerText
            if (input) {
                valor = parseFloat(input.value) || 0;
            } else {
                valor = toNum(celdaImporte.innerText);
            }
            
            if (selectComp.value === 'FACTURA') totalF += valor;
            else if (selectComp.value === 'REMITO') totalR += valor;
        }
    });
    
    document.getElementById('total').textContent = formateadorMoneda.format(totalF);
    document.getElementById('totalImp').textContent = formateadorMoneda.format(totalR);
}

function habilitarInputs(e) {
    let row = e.target.closest('tr'); 
    
    let celdaI = row.cells[7];
    // Si no tiene input, crearlo
    if (!celdaI.querySelector('input')) {
        let v = toNum(celdaI.innerText);
        // Creamos input type number, PERO visualmente queremos enteros
        // step="1" fuerza enteros en el control
        celdaI.innerHTML = `<input type="number" step="1" class="form-control form-control-sm" value="${Math.round(v)}" style="width:130px">`;
        
        celdaI.querySelector('input').addEventListener('input', () => {
            sumarTotalesDash();
            guardarDatos(row);
        });
    }

    row.querySelectorAll('select, input').forEach(el => {
        el.disabled = false;
        // Evitar habilitar la opción vacía en selects obligatorios
        if (el.tagName === 'SELECT' && el.options[0] && el.options[0].value === "") {
             el.options[0].disabled = true;
        }
    });
}

function guardarDatos(row) {
    const codigo = row.cells[4].innerText.trim();
    const existeIndex = Pedidos.findIndex(p => p.codigo == codigo);
    
    let celdaI = row.cells[7];
    let input = celdaI.querySelector('input');
    
    // Obtener valor numérico. Si es texto "$...", toNum lo limpia. Si es input, parseFloat.
    let finalV = input ? parseFloat(input.value) : toNum(celdaI.innerText);

    const codClient = document.getElementById('cod_client').value;
    const razonSoci = document.getElementById('razon_soci').value;

    const info = {
        codigo: codigo,
        fecha: row.cells[0].innerText,
        hora: row.cells[1].innerText,
        cod_client: codClient,
        razon_soci: razonSoci,
        localidad: document.getElementById('localidad').value,
        cod_vended: document.getElementById('cod_vended').value,
        vendedor: document.getElementById('vendedor').value,
        estado: row.cells[2].innerText, // Asegúrate de que esta celda no contenga HTML complejo
        talonario: row.cells[3].innerText,
        unidPedido: row.cells[5].innerText,
        unidPendiente: row.cells[6].innerText,
        // Guardamos como string con punto decimal para la BD, SIN separador de miles
        importePendiente: finalV.toFixed(0), // toFixed(0) asegura entero
        tipoComp: row.cells[8].querySelector('select').value,
        embalaje: row.cells[9].querySelector('select').value,
        despacho: row.cells[10].querySelector('select').value,
        arreglo: row.cells[11].querySelector('select').value,
        prioridad: row.cells[12].querySelector('select').value,
        fechaDespacho: row.cells[13].querySelector('input').value
    };
    
    if (existeIndex === -1) {
        Pedidos.push(info);
    } else {
        Pedidos[existeIndex] = info;
    }
}

function checkInputsNoVacios() {
    let hayErrores = false;
    // Solo validamos inputs habilitados dentro de la tabla
    const inputsValidar = document.querySelectorAll('tbody#table input:not([disabled]), tbody#table select:not([disabled])');

    inputsValidar.forEach(el => {
        let td = el.closest('td');
        if (!td) return; 

        // Excepciones que pueden ser vacías o null
        if (!td.innerHTML.includes('Prioridad') && !td.innerHTML.includes('Arreglo')) {
            if (el.value === '') {
                el.classList.add('is-invalid');
                hayErrores = true;
            } else {
                el.classList.remove('is-invalid');
            }
        }
    });
    return hayErrores;
}

function actualizarDB() {
    if (Pedidos.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin cambios', text: 'No has editado ninguna fila.' });
        return;
    }

    if (checkInputsNoVacios()) {
         Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Por favor completa los campos marcados en rojo.' });
         return;
    }

    Swal.fire({ 
        title: 'Guardando...', 
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); } 
    });

    let xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log("Respuesta servidor:", xhr.responseText);
                Swal.fire({ icon: 'success', title: '¡Guardado!' }).then(() => { 
                    location.reload(); 
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error en el servidor.' });
            }
        }
    };

    xhr.open('POST', './Controller/updateDatos.php', true);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    let datosJSON = JSON.stringify(Pedidos);
    xhr.send(datosJSON);
}

function iniciarEscucha() {
    document.querySelectorAll(".btnEdit").forEach(btn => btn.addEventListener('click', e => {
        e.preventDefault();
        habilitarInputs(e);
        guardarDatos(e.target.closest('tr'));
    }));

    // Escuchar cambios en selects y fechas para recalcular totales o actualizar objeto
    document.querySelectorAll('.form-select, input[type="date"]').forEach(el => el.addEventListener('change', e => {
        if (!e.target.disabled) {
            let row = e.target.closest('tr');
            guardarDatos(row);
            sumarTotalesDash();
        }
    }));
}

function establecerMinFecha() {
    let d = new Date(); 
    d.setDate(d.getDate() + 2);
    let fechaMin = d.toISOString().split('T')[0];
    document.querySelectorAll('[type=date]').forEach(el => el.setAttribute("min", fechaMin));
}

function copiarPrimerFila() {
    let rows = document.querySelectorAll('tbody#table tr');
    if (rows.length < 2) return;
    
    let ref = rows[0];
    // Validar si la primera fila está editada/habilitada
    if (ref.cells[8].querySelector("select").disabled) {
        Swal.fire('Atención', 'Primero debes editar la primera fila (lápiz) para usarla de modelo.', 'warning');
        return;
    }

    let vals = {
        comp: ref.cells[8].querySelector("select").value,
        emb: ref.cells[9].querySelector("select").value,
        desp: ref.cells[10].querySelector("select").value,
        fec: ref.cells[13].querySelector("input").value
    };

    let contador = 0;
    rows.forEach((row, index) => {
        if (index === 0) return; 

        let sel = row.cells[8].querySelector("select");
        // Solo replicar en filas que ya estén habilitadas para edición
        if (!sel.disabled) {
            sel.value = vals.comp;
            row.cells[9].querySelector("select").value = vals.emb;
            row.cells[10].querySelector("select").value = vals.desp;
            row.cells[13].querySelector("input").value = vals.fec;
            guardarDatos(row);
            contador++;
        }
    });

    sumarTotalesDash();
    
    if (contador > 0) {
        Swal.fire({ icon: 'success', title: `Replicado a ${contador} filas`, timer: 1500, showConfirmButton: false });
    } else {
        Swal.fire({ icon: 'info', title: 'Nada que replicar', text: 'Habilita otras filas primero.' });
    }
}