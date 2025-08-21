
/**
 * presupuestos-observaciones.js
 * Maneja la funcionalidad de múltiples observaciones por supervisora
 * Afecta: #observacionesModal, .btn-agregar-observacion, .btn-ver-observacion
 */

const PresupuestosObservaciones = {
    
    // Variables del módulo
    modal: null,
    modoEdicion: false,
    observacionActual: null,
    supervisoraActual: null,
    periodoActual: null,
    
    /**
     * Inicializa el módulo de observaciones
     */
    init() {
        this.inicializarModal();
        this.asignarEventos();
        console.log('Módulo de observaciones múltiples inicializado');
    },
    
    /**
     * Inicializa el modal de observaciones
     * @private
     */
    inicializarModal() {
        const modalElement = document.getElementById('observacionesModal');
        if (modalElement) {
            this.modal = new bootstrap.Modal(modalElement);
            this.configurarContadorCaracteres();
        } else {
            console.warn('Modal de observaciones no encontrado en el DOM');
        }
    },
    
    /**
     * Configura el contador de caracteres
     * @private
     */
    configurarContadorCaracteres() {
        const textarea = document.getElementById('observacionTexto');
        const contador = document.getElementById('contadorCaracteres');
        
        if (textarea && contador) {
            textarea.addEventListener('input', function() {
                const longitud = this.value.length;
                contador.textContent = longitud;
                
                // Cambiar color según proximidad al límite
                contador.className = '';
                if (longitud > 900) {
                    contador.className = 'text-danger fw-bold';
                } else if (longitud > 800) {
                    contador.className = 'text-warning fw-bold';
                } else {
                    contador.className = 'text-muted';
                }
            });
        }
    },
    
    /**
     * Asigna eventos a los elementos de observaciones
     * @private
     */
    asignarEventos() {
        // Eventos de botones del modal
        const btnGuardar = document.getElementById('btnGuardarObservacion');
        const btnEliminar = document.getElementById('btnEliminarObservacion');
        const btnAgregarNueva = document.getElementById('btnAgregarNuevaObs');
        const btnCancelar = document.getElementById('btnCancelarEdicion');
        
        if (btnGuardar) {
            btnGuardar.addEventListener('click', () => this.guardarObservacion());
        }
        
        if (btnEliminar) {
            btnEliminar.addEventListener('click', () => this.eliminarObservacion());
        }
        
        if (btnAgregarNueva) {
            btnAgregarNueva.addEventListener('click', () => this.mostrarTabNuevaObservacion());
        }
        
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => this.cancelarEdicion());
        }
        
        // Eventos de pestañas
        const tabVerObservaciones = document.getElementById('ver-observaciones-tab');
        const tabNuevaObservacion = document.getElementById('nueva-observacion-tab');
        
        if (tabVerObservaciones) {
            tabVerObservaciones.addEventListener('shown.bs.tab', () => this.mostrarBotonesVer());
        }
        
        if (tabNuevaObservacion) {
            tabNuevaObservacion.addEventListener('shown.bs.tab', () => this.mostrarBotonesNueva());
        }
        
        // Eventos de botones en la tabla (delegación de eventos)
        document.addEventListener('click', (e) => {
            const btnAgregar = e.target.closest('.btn-agregar-observacion');
            const btnVer = e.target.closest('.btn-ver-observacion');
            
            if (btnAgregar) {
                this.abrirModalObservaciones(
                    btnAgregar.dataset.supervisorId,
                    btnAgregar.dataset.supervisorNombre,
                    'nueva'
                );
            } else if (btnVer) {
                this.abrirModalObservaciones(
                    btnVer.dataset.supervisorId,
                    btnVer.dataset.supervisorNombre,
                    'ver'
                );
            }
        });
        
        // Limpiar modal al cerrarse
        const modalElement = document.getElementById('observacionesModal');
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', () => {
                this.limpiarModal();
            });
        }
    },
    
    /**
     * Actualiza la visibilidad de los botones según el período
     */
    actualizarBotonesObservaciones() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        if (!periodo) return;
        
        const esMesActualOPosterior = PresupuestosUtils.esPeriodoEditable(periodo);
        
        // Ocultar todos los botones primero
        document.querySelectorAll('.btn-agregar-observacion, .btn-ver-observacion').forEach(btn => {
            btn.classList.add('d-none');
        });
        
        if (esMesActualOPosterior) {
            // Mostrar botones de agregar para mes actual y posteriores
            document.querySelectorAll('.btn-agregar-observacion').forEach(btn => {
                btn.classList.remove('d-none');
            });
        }
        
        // Verificar observaciones existentes y actualizar contadores
        this.actualizarContadoresObservaciones();
    },

    /**
     * Actualiza los contadores de observaciones en los botones
     * @private
     */
    async actualizarContadoresObservaciones() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        if (!periodo) return;
        
        try {
            const data = await PresupuestosAjax.enviarDatos(
                PresupuestosConfig.endpoints.observaciones,
                {
                    accion: 'contarPorPeriodo',
                    periodo: periodo
                }
            );
            
            if (data.success && data.data) {
                // Actualizar botones según conteos
                Object.keys(data.data).forEach(supervisorId => {
                    const cantidad = data.data[supervisorId];
                    this._actualizarBotonObservacion(supervisorId, cantidad);
                });
                
                // Limpiar botones sin observaciones
                document.querySelectorAll('.btn-agregar-observacion').forEach(btn => {
                    const supervisorId = btn.dataset.supervisorId;
                    if (!data.data[supervisorId]) {
                        this._actualizarBotonObservacion(supervisorId, 0);
                    }
                });
            }
            
        } catch (error) {
            console.error('Error actualizando contadores:', error);
        }
    },
    
    /**
     * Actualiza el botón de observación con contador
     * @private
     */
    _actualizarBotonObservacion(supervisorId, cantidad) {
        const btnAgregar = document.querySelector(
            `.btn-agregar-observacion[data-supervisor-id="${supervisorId}"]`
        );
        const btnVer = document.querySelector(
            `.btn-ver-observacion[data-supervisor-id="${supervisorId}"]`
        );
        
        if (cantidad > 0) {
            // Mostrar botón de ver con contador
            if (btnAgregar) btnAgregar.classList.add('d-none');
            if (btnVer) {
                btnVer.classList.remove('d-none');
                btnVer.innerHTML = `<i class="fas fa-comments"></i> <span class="badge bg-info">${cantidad}</span>`;
            }
        } else {
            // Mostrar botón de agregar
            if (btnVer) btnVer.classList.add('d-none');
            if (btnAgregar && PresupuestosUtils.esPeriodoActual(document.getElementById('periodoPresupuesto').value)) {
                btnAgregar.classList.remove('d-none');
            }
        }
    },
    
    /**
     * Abre el modal de observaciones
     */
    abrirModalObservaciones(supervisorId, supervisorNombre, modo = 'ver') {
        const periodo = document.getElementById('periodoPresupuesto').value;
        if (!periodo) {
            PresupuestosUtils.mostrarError('Debe seleccionar un período primero');
            return;
        }
        
        // Guardar datos actuales
        this.supervisoraActual = { id: supervisorId, nombre: supervisorNombre };
        this.periodoActual = periodo;
        
        // Configurar el modal
        this._configurarModal(supervisorId, supervisorNombre, periodo);
        
        // Verificar si puede editar
        const puedeEditar = PresupuestosUtils.esPeriodoEditable(periodo);
        
        // Configurar visibilidad de pestañas según permisos
        const tabNuevaObservacion = document.getElementById('nueva-observacion-tab');
        const btnAgregarNueva = document.getElementById('btnAgregarNuevaObs');
        
        if (!puedeEditar) {
            // Solo lectura - ocultar elementos de edición
            if (tabNuevaObservacion) {
                tabNuevaObservacion.style.display = 'none';
            }
            if (btnAgregarNueva) {
                btnAgregarNueva.style.display = 'none';
            }
            // Forzar mostrar pestaña de ver
            this.mostrarTabVerObservaciones();
        } else {
            // Puede editar - mostrar todos los elementos
            if (tabNuevaObservacion) {
                tabNuevaObservacion.style.display = 'block';
            }
            if (btnAgregarNueva) {
                btnAgregarNueva.style.display = 'block';
            }
            
            // Mostrar pestaña según modo
            if (modo === 'nueva') {
                this.mostrarTabNuevaObservacion();
            } else {
                this.mostrarTabVerObservaciones();
            }
        }
        
        // Cargar observaciones existentes
        this.cargarObservaciones(supervisorId, periodo);
        
        // Mostrar modal
        if (this.modal) {
            this.modal.show();
        }
    },
    
    /**
     * Configura el modal
     * @private
     */
    _configurarModal(supervisorId, supervisorNombre, periodo) {
        const inputSupervisorId = document.getElementById('observacionSupervisorId');
        const inputPeriodo = document.getElementById('observacionPeriodo');
        const tituloModal = document.getElementById('observacionesModalTitulo');
        
        if (inputSupervisorId) {
            inputSupervisorId.value = supervisorId;
        }
        
        if (inputPeriodo) {
            inputPeriodo.value = periodo;
        }
        
        if (tituloModal) {
            tituloModal.textContent = `${supervisorNombre} (${PresupuestosUtils.formatearPeriodo(periodo)})`;
        }
    },

    /**
     * Carga las observaciones existentes
     */
    async cargarObservaciones(supervisorId, periodo) {
        try {
            PresupuestosUtils.mostrarCargando('Cargando observaciones...');
            
            const data = await PresupuestosAjax.enviarDatos(
                PresupuestosConfig.endpoints.observaciones,
                {
                    accion: 'obtener',
                    idSupervisora: supervisorId,
                    periodo: periodo
                }
            );
            
            if (data.success) {
                this._mostrarListaObservaciones(data.data || []);
                
                // Actualizar contador en la pestaña
                const contador = document.getElementById('contadorObservaciones');
                if (contador) {
                    contador.textContent = (data.data || []).length;
                }
            } else {
                this._mostrarListaObservaciones([]);
            }
            
        } catch (error) {
            console.error('Error cargando observaciones:', error);
            PresupuestosUtils.mostrarError('Error al cargar observaciones');
            this._mostrarListaObservaciones([]);
        } finally {
            PresupuestosUtils.ocultarCargando();
        }
    },
    
    /**
     * Muestra la lista de observaciones
     * @private
     */
    _mostrarListaObservaciones(observaciones) {
        const contenedor = document.getElementById('listaObservaciones');
        const sinObservaciones = document.getElementById('sinObservaciones');
        
        if (!observaciones || observaciones.length === 0) {
            if (sinObservaciones) {
                sinObservaciones.classList.remove('d-none');
                contenedor.innerHTML = '';
                contenedor.appendChild(sinObservaciones);
            }
            return;
        }
        
        if (sinObservaciones) {
            sinObservaciones.classList.add('d-none');
        }
        
        const html = observaciones.map(obs => this._generarHTMLObservacion(obs)).join('');
        contenedor.innerHTML = html;
        
        // Asignar eventos a los botones de cada observación
        this._asignarEventosObservaciones();
    },
    
    /**
     * Genera HTML para una observación
     * @private
     */
    _generarHTMLObservacion(observacion) {
        const prioridadTexto = ['', '🟢 Baja', '🟡 Media', '🔴 Alta'][observacion.PRIORIDAD] || '🟢 Baja';
        const fechaCreacion = PresupuestosUtils.formatearFecha(observacion.FECHA_CREACION);
        const fechaModif = PresupuestosUtils.formatearFecha(observacion.FECHA_MODIF);
        const puedeEditar = PresupuestosUtils.esPeriodoEditable(this.periodoActual);
        
        return `
            <div class="card mb-3 observacion-item" data-id="${observacion.ID}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">${PresupuestosUtils.escaparHTML(observacion.TITULO || 'Sin título')}</h6>
                        <small class="text-muted">
                            Prioridad: ${prioridadTexto} | 
                            Creado: ${fechaCreacion} por ${observacion.USUARIO_CREACION}
                        </small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        ${puedeEditar ? `
                            <button class="btn btn-outline-primary btn-editar-obs" data-id="${observacion.ID}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-eliminar-obs" data-id="${observacion.ID}">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : `
                            <span class="badge bg-secondary">Solo lectura</span>
                        `}
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">${PresupuestosUtils.escaparHTML(observacion.OBSERVACIONES).replace(/\n/g, '<br>')}</p>
                    ${fechaModif !== fechaCreacion ? `
                        <small class="text-muted">
                            <i class="fas fa-edit me-1"></i>Modificado: ${fechaModif} por ${observacion.USUARIO_MODIF}
                        </small>
                    ` : ''}
                </div>
            </div>
        `;
    },
    
    /**
     * Asigna eventos a los botones de observaciones
     * @private
     */
    _asignarEventosObservaciones() {
        // Botones de editar
        document.querySelectorAll('.btn-editar-obs').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.closest('button').dataset.id;
                this.editarObservacion(id);
            });
        });
        
        // Botones de eliminar
        document.querySelectorAll('.btn-eliminar-obs').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.closest('button').dataset.id;
                this.confirmarEliminarObservacion(id);
            });
        });
    },
    
    /**
     * Muestra la pestaña de nueva observación
     */
    mostrarTabNuevaObservacion() {
        const tab = new bootstrap.Tab(document.getElementById('nueva-observacion-tab'));
        tab.show();
        this.limpiarFormulario();
    },
    
    /**
     * Muestra la pestaña de ver observaciones
     */
    mostrarTabVerObservaciones() {
        const tab = new bootstrap.Tab(document.getElementById('ver-observaciones-tab'));
        tab.show();
    },
    
    /**
     * Muestra botones correspondientes a ver observaciones
     * @private
     */
    mostrarBotonesVer() {
        document.getElementById('botonesVerObservaciones').classList.remove('d-none');
        document.getElementById('botonesNuevaObservacion').classList.add('d-none');
    },
    
    /**
     * Muestra botones correspondientes a nueva observación
     * @private
     */
    mostrarBotonesNueva() {
        document.getElementById('botonesVerObservaciones').classList.add('d-none');
        document.getElementById('botonesNuevaObservacion').classList.remove('d-none');
    },
    
    /**
     * Edita una observación existente
     */
    async editarObservacion(idObservacion) {
        try {
            PresupuestosUtils.mostrarCargando('Cargando observación...');
            
            const data = await PresupuestosAjax.enviarDatos(
                PresupuestosConfig.endpoints.observaciones,
                {
                    accion: 'obtenerPorId',
                    idObservacion: idObservacion
                }
            );
            
            if (data.success && data.data) {
                this._llenarFormularioEdicion(data.data);
                this.mostrarTabNuevaObservacion();
            } else {
                PresupuestosUtils.mostrarError('No se pudo cargar la observación');
            }
            
        } catch (error) {
            PresupuestosUtils.mostrarError('Error al cargar observación: ' + error.message);
        } finally {
            PresupuestosUtils.ocultarCargando();
        }
    },
    
    /**
     * Llena el formulario para edición
     * @private
     */
    _llenarFormularioEdicion(observacion) {
        document.getElementById('observacionId').value = observacion.ID;
        document.getElementById('observacionTitulo').value = observacion.TITULO || '';
        document.getElementById('observacionTexto').value = observacion.OBSERVACIONES || '';
        document.getElementById('observacionPrioridad').value = observacion.PRIORIDAD || 1;
        
        // Actualizar contador
        const evento = new Event('input');
        document.getElementById('observacionTexto').dispatchEvent(evento);
        
        // Mostrar botón eliminar
        document.getElementById('btnEliminarObservacion').classList.remove('d-none');
        
        this.modoEdicion = true;
        this.observacionActual = observacion;
    },
    
    /**
     * Guarda la observación (nueva o editada)
     */
    async guardarObservacion() {
        const titulo = document.getElementById('observacionTitulo').value.trim();
        const texto = document.getElementById('observacionTexto').value.trim();
        const prioridad = document.getElementById('observacionPrioridad').value;
        const idObservacion = document.getElementById('observacionId').value;
        
        if (!titulo) {
            PresupuestosUtils.mostrarAdvertencia('Debe ingresar un título');
            return;
        }
        
        if (!texto) {
            PresupuestosUtils.mostrarAdvertencia('Debe ingresar el texto de la observación');
            return;
        }
        
        const esEdicion = this.modoEdicion && idObservacion;
        const accion = esEdicion ? 'actualizar' : 'guardar';
        const mensajeConfirmacion = esEdicion ? 
            '¿Actualizar esta observación?' : 
            '¿Guardar nueva observación?';
        
        const confirmacion = await PresupuestosUtils.confirmar(
            mensajeConfirmacion,
            esEdicion ? 'Se actualizará la observación existente' : 'Se creará una nueva observación'
        );
        
        if (!confirmacion) return;
        
        try {
            PresupuestosUtils.mostrarCargando(esEdicion ? 'Actualizando...' : 'Guardando...');
            
            const datos = {
                accion: accion,
                titulo: titulo,
                observaciones: texto,
                prioridad: parseInt(prioridad)
            };
            
            if (esEdicion) {
                datos.idObservacion = idObservacion;
            } else {
                datos.idSupervisora = this.supervisoraActual.id;
                datos.periodo = this.periodoActual;
            }
            
            const data = await PresupuestosAjax.enviarDatos(
                PresupuestosConfig.endpoints.observaciones,
                datos
            );
            
            if (data.success) {
                PresupuestosUtils.mostrarExito(data.message);
                
                // Recargar lista de observaciones
                await this.cargarObservaciones(this.supervisoraActual.id, this.periodoActual);
                
                // Volver a la pestaña de ver
                this.mostrarTabVerObservaciones();
                
                // Actualizar botones en la tabla principal
                this.actualizarBotonesObservaciones();
                
                // Limpiar formulario
                this.limpiarFormulario();
                
            } else {
                PresupuestosUtils.mostrarError(data.message);
            }
            
        } catch (error) {
            PresupuestosUtils.mostrarError('Error al guardar: ' + error.message);
        } finally {
            PresupuestosUtils.ocultarCargando();
        }
    },
    
    /**
     * Confirma y elimina una observación
     */
    async confirmarEliminarObservacion(idObservacion) {
        const confirmacion = await PresupuestosUtils.confirmar(
            '¿Eliminar esta observación?',
            'Esta acción no se puede deshacer',
            'warning'
        );
        
        if (!confirmacion) return;
        
        await this.eliminarObservacionPorId(idObservacion);
    },
    
    /**
     * Elimina la observación actual del formulario
     */
    async eliminarObservacion() {
        const idObservacion = document.getElementById('observacionId').value;
        if (!idObservacion) return;
        
        await this.confirmarEliminarObservacion(idObservacion);
    },
    
    /**
     * Elimina una observación por ID
     */
    async eliminarObservacionPorId(idObservacion) {
        try {
            PresupuestosUtils.mostrarCargando('Eliminando...');
            
            const data = await PresupuestosAjax.enviarDatos(
                PresupuestosConfig.endpoints.observaciones,
                {
                    accion: 'eliminar',
                    idObservacion: idObservacion
                }
            );
            
            if (data.success) {
                PresupuestosUtils.mostrarExito(data.message);
                
                // Recargar lista
                await this.cargarObservaciones(this.supervisoraActual.id, this.periodoActual);
                
                // Si estamos editando esta observación, volver a ver
                if (this.modoEdicion && document.getElementById('observacionId').value === idObservacion) {
                    this.mostrarTabVerObservaciones();
                    this.limpiarFormulario();
                }
                
                // Actualizar botones
                this.actualizarBotonesObservaciones();
                
            } else {
                PresupuestosUtils.mostrarError(data.message);
            }
            
        } catch (error) {
            PresupuestosUtils.mostrarError('Error al eliminar: ' + error.message);
        } finally {
            PresupuestosUtils.ocultarCargando();
        }
    },
    
    /**
     * Cancela la edición actual
     */
    cancelarEdicion() {
        this.limpiarFormulario();
        this.mostrarTabVerObservaciones();
    },
    
    /**
     * Limpia el formulario de observación
     */
    limpiarFormulario() {
        document.getElementById('observacionId').value = '';
        document.getElementById('observacionTitulo').value = '';
        document.getElementById('observacionTexto').value = '';
        document.getElementById('observacionPrioridad').value = '1';
        document.getElementById('contadorCaracteres').textContent = '0';
        document.getElementById('contadorCaracteres').className = 'text-muted';
        document.getElementById('btnEliminarObservacion').classList.add('d-none');
        
        this.modoEdicion = false;
        this.observacionActual = null;
    },
    
    /**
     * Limpia el modal completo
     */
    limpiarModal() {
        this.limpiarFormulario();
        this.supervisoraActual = null;
        this.periodoActual = null;
        
        // Volver a la primera pestaña
        this.mostrarTabVerObservaciones();
        
        // Limpiar lista
        const contenedor = document.getElementById('listaObservaciones');
        const sinObservaciones = document.getElementById('sinObservaciones');
        
        if (contenedor && sinObservaciones) {
            contenedor.innerHTML = '';
            contenedor.appendChild(sinObservaciones);
            sinObservaciones.classList.remove('d-none');
        }
        
        // Resetear contador
        const contador = document.getElementById('contadorObservaciones');
        if (contador) {
            contador.textContent = '0';
        }
        
        // Restaurar visibilidad de pestañas
        const tabNuevaObservacion = document.getElementById('nueva-observacion-tab');
        const btnAgregarNueva = document.getElementById('btnAgregarNuevaObs');
        
        if (tabNuevaObservacion) {
            tabNuevaObservacion.style.display = 'block';
        }
        if (btnAgregarNueva) {
            btnAgregarNueva.style.display = 'block';
        }
    },
};