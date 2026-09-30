// js/gestionarPremiosEcommerce.js //
// Sub-pestañas "Ecommerce · Personas" y "Ecommerce · Escalas" de gestionarPremios.php.
// Es el único lugar donde se editan: el dashboard /bi/premios/ solo las muestra.

document.addEventListener('DOMContentLoaded', function() {
    const personasWrap = document.getElementById('ecom-personas-wrap');
    const escalasWrap = document.getElementById('ecom-escalas-wrap');
    let personasCargadas = false;
    let escalasCargadas = false;

    /** Los nombres y mails los tipea el usuario: se escapan antes de meterlos en el HTML. */
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, ch =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
    }

    function error(texto) {
        return Swal.fire({ icon: 'error', title: 'Error', text: texto });
    }

    /** fetch que siempre lee el JSON, para no perder el mensaje real del servidor. */
    async function pedir(url, body) {
        const opciones = body === undefined ? {} : {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        };
        const res = await fetch(url, opciones);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.status !== 'success') {
            throw new Error(data.message || `Error ${res.status}`);
        }
        return data;
    }

    function errorCarga(wrap, mensaje) {
        wrap.innerHTML = `<div class="alert alert-danger">No se pudo cargar: ${esc(mensaje)}</div>`;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Escalas de premio (las "PAUTAS")
       ═══════════════════════════════════════════════════════════════════════ */

    /* Los umbrales se guardan como ratio (1.0 = 100 %) pero se EDITAN en la unidad en la
       que están escritas las pautas: en % para los conceptos de cumplimiento (100, 90,
       111) y en puntos de tasa para los de valor absoluto (0,80). Estas dos funciones son
       la única conversión — cambiar una sin la otra desalinea las escalas guardadas. */
    function umbralAInput(tipoUmbral, umbral) {
        return tipoUmbral === 'PCT_CUMPLIMIENTO' ? Math.round(umbral * 10000) / 100 : umbral;
    }
    function umbralDesdeInput(tipoUmbral, valor) {
        return tipoUmbral === 'PCT_CUMPLIMIENTO' ? valor / 100 : valor;
    }

    function filaTramoHTML(tipoUmbral, tramo) {
        const u = tramo ? umbralAInput(tipoUmbral, tramo.umbral) : '';
        const i = tramo ? tramo.importe : '';
        return `<div class="ecom-tramo-fila">
            <input type="number" class="form-control form-control-sm ecom-tramo-umbral" step="0.01" min="0" value="${u}">
            <span class="ecom-tramo-unidad">${tipoUmbral === 'PCT_CUMPLIMIENTO' ? '% cumpl.' : '% tasa'}</span>
            <input type="number" class="form-control form-control-sm ecom-tramo-importe" step="1000" min="0" value="${i}">
            <span class="ecom-tramo-unidad">$</span>
            <button type="button" class="btn btn-sm btn-outline-danger ecom-tramo-quitar" title="Quitar tramo">
                <i class="fas fa-trash"></i>
            </button>
        </div>`;
    }

    function bloqueConceptoHTML(c) {
        return `<div class="ecom-concepto" data-concepto="${c.id}" data-tipo-umbral="${c.tipo_umbral}">
            <div class="ecom-concepto-titulo">
                ${esc(c.etiqueta)} <span class="badge bg-secondary ms-1">${esc(c.canal ?? 'VTEX + ML')}</span>
            </div>
            <div class="ecom-tramos">
                ${c.escalas.map(t => filaTramoHTML(c.tipo_umbral, t)).join('')}
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary ecom-tramo-agregar">
                <i class="fas fa-plus me-1"></i>Agregar tramo
            </button>
        </div>`;
    }

    async function cargarEscalas() {
        escalasWrap.innerHTML = 'Cargando…';
        try {
            const { data: personas } = await pedir('Controller/obtenerEscalasEcommerce.php');
            escalasWrap.innerHTML = `
                <div class="alert alert-info">Se paga el importe completo del <strong>tramo más alto
                    alcanzado</strong>, sin prorratear. Los tramos de cumplimiento van en % del
                    objetivo (100, 90, 111…); los de tasa de conversión, en puntos de tasa (0,80).</div>
                ${personas.length ? personas.map(p => `
                    <div class="ecom-persona-titulo">${esc(p.nombre)}</div>
                    ${p.conceptos.length ? p.conceptos.map(bloqueConceptoHTML).join('') : '<p class="text-muted">Sin conceptos asignados.</p>'}
                `).join('') : '<p class="text-muted">No hay personas activas.</p>'}
                <div class="text-end mt-4">
                    <button type="button" class="btn btn-primary" id="ecom-escalas-guardar">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>`;
            escalasCargadas = true;
        } catch (err) {
            errorCarga(escalasWrap, err.message);
        }
    }

    // Un listener delegado para agregar/quitar tramos y guardar (el contenido se repinta).
    escalasWrap.addEventListener('click', function(e) {
        const agregar = e.target.closest('.ecom-tramo-agregar');
        if (agregar) {
            const bloque = agregar.closest('.ecom-concepto');
            bloque.querySelector('.ecom-tramos')
                .insertAdjacentHTML('beforeend', filaTramoHTML(bloque.dataset.tipoUmbral, null));
            return;
        }
        const quitar = e.target.closest('.ecom-tramo-quitar');
        if (quitar) {
            quitar.closest('.ecom-tramo-fila').remove();
            return;
        }
        const guardar = e.target.closest('#ecom-escalas-guardar');
        if (guardar) guardarEscalas(guardar);
    });

    async function guardarEscalas(btn) {
        const conceptos = [];
        for (const bloque of escalasWrap.querySelectorAll('.ecom-concepto')) {
            const tipoUmbral = bloque.dataset.tipoUmbral;
            const escalas = [];
            for (const fila of bloque.querySelectorAll('.ecom-tramo-fila')) {
                const u = fila.querySelector('.ecom-tramo-umbral').value;
                const i = fila.querySelector('.ecom-tramo-importe').value;
                if (u === '' || i === '') {
                    error('Hay tramos con el umbral o el importe vacíos. Completalos o quitalos.');
                    return;
                }
                escalas.push({ umbral: umbralDesdeInput(tipoUmbral, Number(u)), importe: Number(i) });
            }
            if (!escalas.length) {
                const titulo = bloque.querySelector('.ecom-concepto-titulo').textContent.trim();
                error(`El concepto "${titulo}" quedó sin tramos. Un concepto sin escala nunca paga premio.`);
                return;
            }
            conceptos.push({ id: Number(bloque.dataset.concepto), escalas });
        }

        const confirmacion = await Swal.fire({
            title: '¿Confirmar actualización?',
            text: 'Cambiar una escala cambia lo que se liquida en Premios Ecommerce.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        btn.disabled = true;
        try {
            const data = await pedir('Controller/guardarEscalasEcommerce.php', { conceptos });
            await Swal.fire({ icon: 'success', title: 'Éxito', text: data.message, confirmButtonText: 'Aceptar' });
            await cargarEscalas();
        } catch (err) {
            error(err.message);
        } finally {
            btn.disabled = false;
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Personas (alta con puesto, mail, edición, baja)
       ═══════════════════════════════════════════════════════════════════════ */

    let personas = [];
    let puestos = [];

    function filaPersonaHTML(p) {
        const mail = p.email ? esc(p.email) : '<span class="text-muted fst-italic">sin mail</span>';
        const btnEstado = p.activo
            ? `<button type="button" class="btn btn-sm btn-outline-danger" data-accion="baja" title="Dar de baja (deja de liquidarse; no se borra nada)"><i class="fas fa-user-minus"></i></button>`
            : `<button type="button" class="btn btn-sm btn-outline-success" data-accion="reactivar" title="Reactivar con sus conceptos y tramos"><i class="fas fa-undo"></i></button>`;
        return `<tr class="${p.activo ? '' : 'ecom-persona-baja'}" data-id="${p.id}">
            <td>${esc(p.nombre)}</td>
            <td>${p.puesto ? esc(p.puesto) : '<span class="text-muted fst-italic">sin puesto</span>'}</td>
            <td>${mail}</td>
            <td class="text-center">${p.activo ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">De baja</span>'}</td>
            <td class="text-center text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-primary" data-accion="editar" title="Editar nombre y mail"><i class="fas fa-pen"></i></button>
                ${btnEstado}
            </td>
        </tr>`;
    }

    function filaPersonaEdicionHTML(p) {
        return `<tr data-id="${p.id}">
            <td><input class="form-control form-control-sm" data-campo="nombre" maxlength="100" value="${esc(p.nombre)}"></td>
            <td>${p.puesto ? esc(p.puesto) : '<span class="text-muted fst-italic">sin puesto</span>'}</td>
            <td><input class="form-control form-control-sm" data-campo="email" type="email" maxlength="150" value="${esc(p.email ?? '')}" placeholder="mail@xl.com.ar"></td>
            <td></td>
            <td class="text-center text-nowrap">
                <button type="button" class="btn btn-sm btn-primary" data-accion="guardar" title="Guardar"><i class="fas fa-check"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-accion="cancelar" title="Cancelar"><i class="fas fa-times"></i></button>
            </td>
        </tr>`;
    }

    function conceptosPuestoTexto(puesto) {
        return puesto && puesto.conceptos.length
            ? `Cobra por: ${puesto.conceptos.map(esc).join(' · ')}`
            : 'Este puesto no tiene conceptos cargados.';
    }

    async function cargarPersonas() {
        personasWrap.innerHTML = 'Cargando…';
        try {
            const { data } = await pedir('Controller/obtenerPersonasEcommerce.php');
            personas = data.personas;
            puestos = data.puestos;
            personasWrap.innerHTML = `
                <div class="alert alert-info">Cada persona hereda del <strong>puesto</strong> los conceptos
                    por los que cobra y sus tramos ($), que después se ajustan en «Ecommerce · Escalas».
                    El puesto no se cambia después del alta: para eso, dala de baja y volvé a darla de alta.
                    El mail es al que se envía el detalle de premios desde el dashboard.</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr><th>Nombre</th><th>Puesto</th><th>Mail</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody id="ecom-personas-tbody">
                            ${personas.length ? personas.map(filaPersonaHTML).join('') : '<tr><td colspan="5" class="text-muted text-center">No hay personas cargadas.</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <h5 class="mt-4">Agregar persona</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="ecom-alta-nombre" class="form-label">Nombre</label>
                        <input class="form-control" id="ecom-alta-nombre" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label for="ecom-alta-email" class="form-label">Mail</label>
                        <input class="form-control" id="ecom-alta-email" type="email" maxlength="150" placeholder="mail@xl.com.ar">
                    </div>
                    <div class="col-md-4">
                        <label for="ecom-alta-puesto" class="form-label">Puesto</label>
                        <select class="form-select" id="ecom-alta-puesto">
                            ${puestos.map(pu => `<option value="${pu.id}">${esc(pu.nombre)}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <p class="text-muted small mt-2" id="ecom-alta-conceptos">${conceptosPuestoTexto(puestos[0])}</p>
                <div class="text-end">
                    <button type="button" class="btn btn-primary" id="ecom-alta-guardar" ${puestos.length ? '' : 'disabled'}>
                        <i class="fas fa-user-plus me-2"></i>Agregar
                    </button>
                </div>`;

            const selPuesto = document.getElementById('ecom-alta-puesto');
            selPuesto.addEventListener('change', () => {
                document.getElementById('ecom-alta-conceptos').innerHTML =
                    conceptosPuestoTexto(puestos.find(pu => pu.id === Number(selPuesto.value)));
            });
            document.getElementById('ecom-alta-guardar').addEventListener('click', (e) => accionPersona(e.currentTarget, {
                accion: 'crear',
                nombre: document.getElementById('ecom-alta-nombre').value,
                email: document.getElementById('ecom-alta-email').value,
                id_puesto: Number(selPuesto.value)
            }));
            personasCargadas = true;
        } catch (err) {
            errorCarga(personasWrap, err.message);
        }
    }

    /** Corre una acción y, si sale bien, repinta Personas y marca Escalas para recargar. */
    async function accionPersona(btn, payload) {
        btn.disabled = true;
        try {
            const data = await pedir('Controller/guardarPersonaEcommerce.php', payload);
            await Swal.fire({ icon: 'success', title: 'Éxito', text: data.message, confirmButtonText: 'Aceptar' });
            escalasCargadas = false; // un alta/baja cambia qué conceptos se ven en Escalas
            await cargarPersonas();
        } catch (err) {
            btn.disabled = false;
            error(err.message);
        }
    }

    // Un solo listener delegado para los botones de todas las filas (incluidas las que se
    // reemplazan al pasar a modo edición).
    personasWrap.addEventListener('click', async function(e) {
        const btn = e.target.closest('#ecom-personas-tbody button[data-accion]');
        if (!btn) return;
        const fila = btn.closest('tr');
        const p = personas.find(x => x.id === Number(fila.dataset.id));
        switch (btn.dataset.accion) {
            case 'editar':
                fila.outerHTML = filaPersonaEdicionHTML(p);
                break;
            case 'cancelar':
                fila.outerHTML = filaPersonaHTML(p);
                break;
            case 'guardar':
                await accionPersona(btn, {
                    accion: 'editar', id: p.id,
                    nombre: fila.querySelector('[data-campo="nombre"]').value,
                    email: fila.querySelector('[data-campo="email"]').value
                });
                break;
            case 'baja': {
                const r = await Swal.fire({
                    title: 'Dar de baja',
                    html: `¿Dar de baja a <strong>${esc(p.nombre)}</strong>? Deja de aparecer en el dashboard y de
                           liquidarse. No se borra nada: se puede reactivar con los mismos tramos.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Sí, dar de baja',
                    cancelButtonText: 'Cancelar'
                });
                if (r.isConfirmed) await accionPersona(btn, { accion: 'baja', id: p.id });
                break;
            }
            case 'reactivar':
                await accionPersona(btn, { accion: 'reactivar', id: p.id });
                break;
        }
    });

    /* ── Carga perezosa: cada sub-pestaña se pide la primera vez que se abre ── */
    document.getElementById('ecom-personas-tab').addEventListener('shown.bs.tab', () => {
        if (!personasCargadas) cargarPersonas();
    });
    document.getElementById('ecom-escalas-tab').addEventListener('shown.bs.tab', () => {
        if (!escalasCargadas) cargarEscalas();
    });
});
