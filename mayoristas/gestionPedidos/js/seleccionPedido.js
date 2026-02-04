/* ========================================
   js/seleccionPedido.js - CORRECCIÓN ERROR NULL
   ======================================== */

let Pedidos = [];

window.addEventListener('DOMContentLoaded', function() {
    iniciarEscucha();
    establecerMinFecha();
    
    setTimeout(() => {
        formatearYCorregirTabla();
        sumarTotalesDash();
    }, 100);

    document.getElementById('btn_save').addEventListener('click', actualizarDB);
});

function toNum(texto) {
    if (!texto) return 0;
    let limpio = texto.toString().replace(/[$. ]/g, '').replace(',', '.');
    let n = parseFloat(limpio) || 0;
    return n;
}

function sumarTotalesDash() {
    let totalF = 0;
    let totalR = 0;
    const filas = document.querySelectorAll('tbody#table tr');
    
    filas.forEach(fila => {
        let celdaImporte = fila.cells[7];
        let selectComp = fila.cells[8].querySelector('select');
        
        if (celdaImporte && selectComp) {
            let valor = 0;
            let input = celdaImporte.querySelector('input');
            valor = input ? parseFloat(input.value) : toNum(celdaImporte.innerText);
            
            if (selectComp.value === 'FACTURA') totalF += valor;
            else if (selectComp.value === 'REMITO') totalR += valor;
        }
    });
    
    const fmt = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 0 });
    document.getElementById('total').textContent = fmt.format(totalF);
    document.getElementById('totalImp').textContent = fmt.format(totalR);
}

function formatearYCorregirTabla() {
    const fmt = new Intl.NumberFormat("es-AR", { style: "currency", currency: "ARS", minimumFractionDigits: 0 });
    document.querySelectorAll('tbody#table tr').forEach(fila => {
        let celda = fila.cells[7];
        if (celda && !celda.querySelector('input')) {
            let n = toNum(celda.innerText);
            if (n > 100000000) n = n / 1000; 
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
        estado: row.cells[2].innerText,
        talonario: row.cells[3].innerText,
        unidPedido: row.cells[5].innerText,
        unidPendiente: row.cells[6].innerText,
        importePendiente: finalV.toFixed(2).replace('.', ','),
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

/**
 * Validar que no haya vacíos.
 * CORREGIDO: Ahora solo busca inputs DENTRO de la tabla (tbody#table)
 * para evitar error con el buscador o inputs ocultos.
 */
function checkInputsNoVacios() {
    let hayErrores = false;
    
    // CORRECCIÓN AQUI: Agregamos "tbody#table" al selector
    const inputsValidar = document.querySelectorAll('tbody#table input:not([disabled]), tbody#table select:not([disabled])');

    inputsValidar.forEach(el => {
        let td = el.closest('td');
        
        // Verificación de seguridad por si acaso
        if (!td) return; 

        if (!td.innerHTML.includes('Prioridad') && 
            !td.innerHTML.includes('Arreglo')) {
            
            if (el.value === '') {
                el.classList.add('is-invalid');
                el.style.borderColor = 'red'; // Refuerzo visual
                hayErrores = true;
            } else {
                el.classList.remove('is-invalid');
                el.style.borderColor = '';
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
                // Si el PHP devuelve texto (ej: IDs guardados), asumimos éxito
                console.log("Respuesta servidor:", xhr.responseText);
                Swal.fire({ icon: 'success', title: '¡Guardado!' }).then(() => { 
                    location.reload(); 
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error en el servidor: ' + xhr.responseText });
            }
        }
    };

    // CAMBIO CRÍTICO: Usamos POST en lugar de GET
    xhr.open('POST', './Controller/updateDatos.php', true);
    // Establecemos la cabecera para indicar que enviamos JSON
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    
    // Enviamos el objeto JSON directamente
    let datosJSON = JSON.stringify(Pedidos);
    xhr.send(datosJSON);
}

function iniciarEscucha() {
    document.querySelectorAll(".btnEdit").forEach(btn => btn.addEventListener('click', e => {
        e.preventDefault();
        habilitarInputs(e);
        guardarDatos(e.target.closest('tr'));
    }));

    document.querySelectorAll('.form-select, input[type="date"]').forEach(el => el.addEventListener('change', e => {
        if (!e.target.disabled) {
            guardarDatos(e.target.closest('tr'));
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