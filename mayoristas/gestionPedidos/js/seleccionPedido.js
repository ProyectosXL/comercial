/* ========================================
   JavaScript específico para seleccionPedido.php
   Gestión de Pedidos - XL Extra
   ======================================== */

// Variables globales
let Pedidos = [];

/**
 * Inicialización cuando el DOM está listo
 */
window.addEventListener('DOMContentLoaded', function() {
    console.log('seleccionPedido.js cargado correctamente');
    
    // Inicializar funcionalidades
    iniciarEscucha();
    establecerMinFecha();
    formatearImportes();
    initTotalesComprobantes();
    
    // Event listener para guardar
    document.getElementById('btn_save').addEventListener('click', actualizarDB);
});

/* ========================================
   Búsqueda rápida en tabla
   ======================================== */

/**
 * Filtra las filas de la tabla según el texto de búsqueda
 */
function myFunction() {
    var input, filter, table, tr, td, i, txtValue, visible;
    input = document.getElementById("textbusq");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        visible = false;
        td = tr[i].getElementsByTagName("td");

        for (j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                visible = true;
            }
        }
        
        if (visible === true) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

/* ========================================
   Formateo de importes
   ======================================== */

/**
 * Formatea los importes de la tabla
 */
function formatearImportes() {
    let importe = document.querySelectorAll('#importe');
    importe.forEach(el => {
        el.innerHTML = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 0
        }).format(el.innerHTML);
    });
}

/* ========================================
   Edición de pedidos
   ======================================== */

/**
 * Inicializa los event listeners para edición
 */
function iniciarEscucha() {
    let edit = document.querySelectorAll(".btnEdit");
    edit.forEach(ele => {
        ele.addEventListener('click', editarPedido, false);
    });
    
    // Event listeners para cambios en selects
    let select = document.querySelectorAll('.form-select');
    select.forEach(el => {
        el.addEventListener('change', (e) => {
            guardarDatos(e.target.closest('tr'), e);
            sumarComprobantesFacturas();
        });
    });
    
    // Event listeners para cambios en fechas
    let fechas = document.querySelectorAll('input[type="date"]');
    fechas.forEach(el => {
        el.addEventListener('change', (e) => {
            guardarDatos(e.target.closest('tr'), e);
        });
    });
}

/**
 * Habilita la edición de un pedido
 */
function editarPedido(pedido) {
    habilitarInputs(pedido);
    pedido = pedido.target.closest('tr');
    guardarDatos(pedido);
    sumarComprobantesFacturas();
}

/**
 * Habilita los inputs de una fila para edición
 */
function habilitarInputs(input) {
    let row = input.target.closest('tr');
    
    // Habilitar selects y fechas (columnas 8-13)
    for (let i = 8; i < 14; i++) {
        let campo = row.children[i].children[0];
        campo.disabled = false;
        
        // Mantener deshabilitado el primer option de selects
        if (campo.tagName === 'SELECT' && campo.children[0]) {
            campo.children[0].disabled = true;
        }
    }
}

/**
 * Guarda los datos de un pedido editado
 */
function guardarDatos(row, campo) {
    const codigo = row.children[4].innerHTML;
    
    // Buscar si el pedido ya existe en el array
    const existeIndex = Pedidos.findIndex(pedido => pedido.codigo == codigo);
    
    const infoPedido = {
        codigo: codigo,
        fecha: row.children[0].innerHTML,
        hora: row.children[1].innerHTML,
        cod_client: (document.getElementById('cod_client').value).replace('&','%26'),
        razon_soci: (document.getElementById('razon_soci').value).replace('&','%26'),
        localidad: document.getElementById('localidad').value,
        cod_vended: document.getElementById('cod_vended').value,
        vendedor: document.getElementById('vendedor').value,
        estado: row.children[2].innerText,
        talonario: row.children[3].innerHTML,
        unidPedido: row.children[5].innerHTML,
        unidPendiente: row.children[6].innerHTML,
        importePendiente: parseFloat(row.children[7].innerText.replace('$', '').replace(/\./g, '').replace(',', '.')).toFixed(3),
        tipoComp: row.children[8].querySelector('select').value,
        embalaje: row.children[9].querySelector('select').value,
        despacho: row.children[10].querySelector('select').value,
        arreglo: row.children[11].querySelector('select').value,
        prioridad: row.children[12].querySelector('select').value,
        fechaDespacho: row.children[13].children[0].value
    };
    
    if (existeIndex === -1) {
        // Agregar nuevo pedido
        Pedidos.push(infoPedido);
    } else {
        // Actualizar pedido existente
        Pedidos[existeIndex] = infoPedido;
    }
    
    console.log('Pedidos actualizados:', Pedidos);
}

/* ========================================
   Copiar primera fila
   ======================================== */

/**
 * Copia los valores de la primera fila a todas las filas editables
 */
function copiarPrimerFila() {
    let allTr = document.querySelectorAll('#trBody');
    
    if (allTr.length === 0) return;
    
    let primerFila = allTr[0];
    
    // Obtener valores de la primera fila
    let tipoComprobante = primerFila.querySelectorAll('td')[8].querySelector("select").value;
    let embalaje = primerFila.querySelectorAll('td')[9].querySelector("select").value;
    let despacho = primerFila.querySelectorAll('td')[10].querySelector("select").value;
    let asignarFecha = primerFila.querySelectorAll('td')[13].querySelector("input").value;
    
    // Aplicar a todas las filas editables
    allTr.forEach(element => {
        if (element.querySelectorAll('td')[8].querySelector("select").disabled == false) {
            element.querySelectorAll('td')[8].querySelector("select").value = tipoComprobante;
            element.querySelectorAll('td')[9].querySelector("select").value = embalaje;
            element.querySelectorAll('td')[10].querySelector("select").value = despacho;
            element.querySelectorAll('td')[13].querySelector("input").value = asignarFecha;
            
            // Guardar datos de esta fila
            guardarDatos(element);
        }
    });
    
    sumarComprobantesFacturas();
    
    Swal.fire({
        icon: 'success',
        title: 'Valores copiados',
        text: 'Se aplicaron los valores de la primera fila a todos los pedidos editables',
        timer: 2000,
        showConfirmButton: false
    });
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

/**
 * Suma los totales de Facturas y Remitos
 */
function sumarComprobantesFacturas() {
    let totalFactura = 0;
    let totalRemito = 0;
    
    document.querySelectorAll('.Comprobante').forEach(element => {
        let valorTexto = element.closest('tr').querySelectorAll('td')[7].innerText;
        let valor = parseFloat(valorTexto.replace("$", "").replace(/\./g, "").replace(",", "."));
        
        if (element.value == 'FACTURA') {
            totalFactura += valor;
        } else if (element.value == 'REMITO') {
            totalRemito += valor;
        }
    });
    
    document.getElementById('total').value = totalFactura.toLocaleString('es-AR', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
    
    document.getElementById('totalImp').value = totalRemito.toLocaleString('es-AR', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
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
        Swal.fire({
            icon: 'error',
            title: 'No se modificaron datos',
            text: 'Edite pedidos y haga click en guardar'
        });
        return;
    }
    
    if (checkInpustNoVacios()) {
        Swal.fire({
            icon: 'error',
            title: 'Campos vacíos',
            text: 'Debe completar todos los campos obligatorios'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando cambios...',
        html: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Realizar petición AJAX
    let conexion = new XMLHttpRequest();
    conexion.onreadystatechange = function() {
        if (conexion.readyState === 4) {
            if (conexion.status >= 200 && conexion.status < 300) {
                Swal.fire({
                    icon: 'success',
                    title: 'Pedidos modificados exitosamente',
                    text: "Nro. de pedido/s: " + conexion.responseText,
                    showConfirmButton: true,
                }).then(function() {
                    // Volver a index.php
                    window.location.href = 'index.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de carga',
                    text: 'No se modificó ningún pedido. ' + conexion.responseText
                }).then(function() {
                    location.reload();
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
