/* ==============================================
   FUNCIONALIDAD DEL BOTÓN DE AYUDA MEJORADO CON SOLAPAS
   ============================================== */

// Objeto para manejar la funcionalidad del botón de ayuda
const HelpButton = {
    
    // Configuración
    config: {
        autoExpandFirstSection: true,
        rememberLastTab: true,
        animationDuration: 300,
        enableKeyboardNavigation: true
    },
    
    // Estado actual
    state: {
        currentTab: 'uso',
        expandedSections: new Set(),
        isModalOpen: false
    },
    
    // Inicializar el botón de ayuda
    init: function() {
        this.createHelpButton();
        this.createHelpModal();
        this.bindEvents();
        this.loadState();
        console.log('Sistema de ayuda inicializado correctamente');
    },
    
    // Crear el botón flotante
    createHelpButton: function() {
        const helpButton = document.createElement('button');
        helpButton.id = 'helpButton';
        helpButton.className = 'help-button';
        helpButton.innerHTML = '?';
        helpButton.title = 'Manual de Usuario - Presione F1 o click aquí';
        helpButton.setAttribute('aria-label', 'Abrir manual de usuario');
        
        document.body.appendChild(helpButton);
    },
    
    // Crear el modal de ayuda
    createHelpModal: function() {
        const modalHTML = this.getModalStructure();
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },
    
    // Estructura completa del modal
    getModalStructure: function() {
        return `
            <div id="helpModal" class="help-modal" role="dialog" aria-labelledby="helpModalTitle" aria-hidden="true">
                <div class="help-modal-content">
                    ${this.getModalHeader()}
                    ${this.getTabs()}
                    ${this.getModalBody()}
                </div>
            </div>
        `;
    },
    
    // Header del modal
    getModalHeader: function() {
        return `
            <div class="help-modal-header">
                <h2 id="helpModalTitle">📚 Manual de Usuario - Gestión de Equivalencias</h2>
                <button class="help-close" id="helpClose" aria-label="Cerrar manual">&times;</button>
            </div>
        `;
    },
    
    // Pestañas del modal
    getTabs: function() {
        return `
            <div class="help-tabs" role="tablist">
                <button class="help-tab active" data-tab="uso" role="tab" aria-selected="true" aria-controls="tab-uso">
                    📖 Guía de Uso
                </button>
                <button class="help-tab" data-tab="consejos" role="tab" aria-selected="false" aria-controls="tab-consejos">
                    💡 Consejos y FAQ
                </button>
            </div>
        `;
    },
    
    // Cuerpo del modal con contenido de pestañas
    getModalBody: function() {
        return `
            <div class="help-modal-body">
                ${this.getTabUso()}
                ${this.getTabConsejos()}
            </div>
        `;
    },
    
    // Contenido de la pestaña "Guía de Uso"
    getTabUso: function() {
        return `
            <div class="help-tab-content active" data-content="uso" id="tab-uso" role="tabpanel" aria-labelledby="tab-uso">
                ${this.createSection('buscar', '🔍 Buscar y Filtrar Grupos', this.getContentBuscar())}
                ${this.createSection('crear', '➕ Crear un Nuevo Grupo', this.getContentCrear())}
                ${this.createSection('editar', '✏️ Editar un Grupo Existente', this.getContentEditar())}
                ${this.createSection('eliminar', '🗑️ Eliminar un Grupo', this.getContentEliminar())}
                ${this.createSection('tabla', '📊 Entender la Tabla Principal', this.getContentTabla())}
                ${this.createSection('buscador', '🔧 Funciones del Buscador de Artículos', this.getContentBuscador())}
                ${this.createSection('validaciones', '⚠️ Mensajes y Validaciones', this.getContentValidaciones())}
            </div>
        `;
    },
    
    // Contenido de la pestaña "Consejos y FAQ"
    getTabConsejos: function() {
        return `
            <div class="help-tab-content" data-content="consejos" id="tab-consejos" role="tabpanel" aria-labelledby="tab-consejos">
                ${this.createSection('consejos', '💡 Consejos de Uso', this.getContentConsejos())}
                ${this.createSection('flujo', '🚀 Flujo de Trabajo Recomendado', this.getContentFlujo())}
                ${this.createSection('faq', '❓ Preguntas Frecuentes de Uso', this.getContentFaq())}
            </div>
        `;
    },
    
    // Crear una sección desplegable
    createSection: function(id, title, content) {
        const isExpanded = this.state.expandedSections.has(id);
        const expandedClass = isExpanded ? 'expanded' : '';
        
        return `
            <div class="help-section ${expandedClass}" data-section="${id}">
                <div class="help-section-header" role="button" aria-expanded="${isExpanded}" aria-controls="section-${id}">
                    <h3>${title}</h3>
                    <span class="help-section-toggle" aria-hidden="true">▶</span>
                </div>
                <div class="help-section-content" id="section-${id}" role="region">
                    ${content}
                </div>
            </div>
        `;
    },
    
    // === CONTENIDO DE CADA SECCIÓN ===
    
    getContentBuscar: function() {
        return `
            <p><strong>Panel de filtros (parte superior):</strong></p>
            <ul>
                <li><strong>Buscar por artículo:</strong> Escribe el código de un artículo para encontrar en qué grupo está</li>
                <li><strong>Buscar por grupo:</strong> Ingresa el número de código de equivalencia directamente</li>
                <li><strong>Rubro:</strong> Filtra solo los grupos de una categoría específica</li>
                <li><strong>Estado:</strong> Muestra grupos activos, inactivos o todos</li>
            </ul>
            <div class="help-highlight">
                <strong>Tip:</strong> Puedes combinar varios filtros. Haz clic en "Buscar" para aplicar los filtros seleccionados.
            </div>
        `;
    },
    
    getContentCrear: function() {
        return `
            <p><strong>Paso a paso:</strong></p>
            <ol>
                <li><strong>Clic en "Nuevo Grupo"</strong> (botón verde en la parte superior derecha)</li>
                <li><strong>Completa los datos básicos:</strong>
                    <ul>
                        <li><strong>Descripción del Grupo:</strong> Nombre que identifique la familia de productos</li>
                        <li><strong>Rubro:</strong> Selecciona la categoría del menú desplegable</li>
                        <li><strong>Estado:</strong> Deja "Activo" (por defecto) o selecciona "Inactivo"</li>
                    </ul>
                </li>
                <li><strong>Agregar artículos al grupo:</strong>
                    <ul>
                        <li>Haz clic en "Agregar Artículo"</li>
                        <li>Se mostrará un campo de búsqueda</li>
                        <li>Escribe al menos 2 caracteres del código o descripción</li>
                        <li>Selecciona el artículo de la lista</li>
                        <li>Haz clic en el ✓ verde para confirmar</li>
                        <li>Repite para agregar más artículos</li>
                    </ul>
                </li>
                <li><strong>Verificar la lista:</strong> Revisa que todos los artículos en la tabla sean correctos</li>
                <li><strong>Guardar:</strong> Haz clic en "Guardar" (botón azul)</li>
            </ol>
            <div class="help-highlight">
                <strong>Importante:</strong> Debes agregar al menos un artículo para poder guardar el grupo.
            </div>
        `;
    },
    
    getContentEditar: function() {
        return `
            <p><strong>Cómo modificar un grupo:</strong></p>
            <ol>
                <li><strong>Localizar el grupo:</strong> Usa los filtros o busca en la tabla</li>
                <li><strong>Clic en "Editar":</strong> Botón con ícono de lápiz (azul) en la columna "Acciones"</li>
                <li><strong>Modificar datos:</strong> Cambia descripción, rubro o estado según necesites</li>
                <li><strong>Gestionar artículos:</strong>
                    <ul>
                        <li><strong>Agregar:</strong> Usa "Agregar Artículo" como en la creación</li>
                        <li><strong>Eliminar:</strong> Haz clic en el ícono de papelera roja junto al artículo</li>
                    </ul>
                </li>
                <li><strong>Guardar cambios:</strong> Haz clic en "Guardar"</li>
            </ol>
        `;
    },
    
    getContentEliminar: function() {
        return `
            <p><strong>Para desactivar un grupo:</strong></p>
            <ol>
                <li>Encuentra el grupo en la tabla</li>
                <li>Haz clic en el botón "Eliminar" (ícono de papelera roja)</li>
                <li>Confirma la acción en el cuadro que aparece</li>
            </ol>
            <div class="help-highlight">
                <strong>Nota:</strong> El grupo se desactiva pero no se borra permanentemente. Puedes reactivarlo editándolo y cambiando el estado a "Activo".
            </div>
        `;
    },
    
    getContentTabla: function() {
        return `
            <p><strong>Columnas de la tabla:</strong></p>
            <ul>
                <li><strong>Código:</strong> Número único del grupo (generado automáticamente)</li>
                <li><strong>Nombre Familia:</strong> Descripción que pusiste al grupo</li>
                <li><strong>Rubro:</strong> Categoría seleccionada</li>
                <li><strong>Artículos:</strong> Cantidad de códigos incluidos en el grupo</li>
                <li><strong>Fecha Creación:</strong> Cuándo se creó el grupo</li>
                <li><strong>Fecha Modificación:</strong> Última vez que se editó</li>
                <li><strong>Estado:</strong> 
                    <ul>
                        <li><span style="color: green;">●</span> <strong>Activo:</strong> El grupo está funcionando</li>
                        <li><span style="color: red;">●</span> <strong>Inactivo:</strong> El grupo está deshabilitado</li>
                    </ul>
                </li>
                <li><strong>Acciones:</strong> Botones para editar (lápiz azul) y eliminar (papelera roja)</li>
            </ul>
        `;
    },
    
    getContentBuscador: function() {
        return `
            <p><strong>Cómo funciona la búsqueda:</strong></p>
            <ul>
                <li><strong>Mínimo 2 caracteres:</strong> Debes escribir al menos 2 letras o números</li>
                <li><strong>Busca en código y descripción:</strong> Encuentra por cualquiera de los dos</li>
                <li><strong>Lista filtrada:</strong> Solo muestra artículos que no están en otros grupos</li>
                <li><strong>Selección:</strong> Haz clic en el artículo deseado para seleccionarlo</li>
            </ul>
            <p><strong>Si no encuentras un artículo:</strong></p>
            <ul>
                <li>Verifica que el código esté bien escrito</li>
                <li>Puede que ya esté asociado a otro grupo</li>
                <li>Prueba buscando por descripción en lugar de código</li>
            </ul>
        `;
    },
    
    getContentValidaciones: function() {
        return `
            <p><strong>Errores comunes y soluciones:</strong></p>
            <ul>
                <li><strong>"La descripción del grupo es obligatoria":</strong> Debes escribir un nombre para el grupo</li>
                <li><strong>"Debe seleccionar un rubro":</strong> Elige una categoría del menú desplegable</li>
                <li><strong>"Debe agregar al menos un artículo":</strong> El grupo necesita tener productos asociados</li>
                <li><strong>"Este artículo ya está en el grupo":</strong> No puedes agregar el mismo código dos veces</li>
                <li><strong>"Artículo eliminado del grupo":</strong> Confirmación de que se quitó correctamente</li>
            </ul>
        `;
    },
    
    getContentConsejos: function() {
        return `
            <ul>
                <li><strong>Nombres descriptivos:</strong> Usa descripciones claras para encontrar grupos fácilmente</li>
                <li><strong>Filtro por estado:</strong> Por defecto muestra solo "Activos", cambia a "Todos" si necesitas ver inactivos</li>
                <li><strong>Buscar antes de crear:</strong> Usa los filtros para verificar que no existe un grupo similar</li>
                <li><strong>Revisar antes de guardar:</strong> Verifica la lista de artículos antes de confirmar</li>
                <li><strong>Estados útiles:</strong> Usa "Inactivo" para grupos temporalmente no utilizados</li>
            </ul>
        `;
    },
    
    getContentFlujo: function() {
        return `
            <p><strong>Para usar eficientemente la herramienta:</strong></p>
            <ol>
                <li><strong>Preparar:</strong> Ten listos los códigos de artículos que vas a agrupar</li>
                <li><strong>Verificar:</strong> Usa "Buscar por artículo" para ver si ya están en algún grupo</li>
                <li><strong>Crear:</strong> Si no existen, crea un nuevo grupo con "Nuevo Grupo"</li>
                <li><strong>Completar:</strong> Agrega todos los artículos de una vez para no olvidar ninguno</li>
                <li><strong>Verificar resultado:</strong> Usa los filtros para encontrar tu grupo y confirmar que esté correcto</li>
            </ol>
        `;
    },
    
    getContentFaq: function() {
        return `
            <div style="display: grid; gap: 15px;">
                <details style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #007bff;">¿Cómo sé si un artículo ya está en un grupo?</summary>
                    <p style="margin-top: 10px;">Usa "Buscar por artículo" en los filtros superiores. Si aparece algún resultado, ya está asociado.</p>
                </details>
                
                <details style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #007bff;">¿Puedo cambiar un artículo de grupo?</summary>
                    <p style="margin-top: 10px;">Sí. Primero elimínalo del grupo actual (editando el grupo y usando la papelera roja), luego agrégalo al nuevo grupo.</p>
                </details>
                
                <details style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #007bff;">¿Qué pasa si cierro sin guardar?</summary>
                    <p style="margin-top: 10px;">Se pierden todos los cambios. Siempre haz clic en "Guardar" antes de cerrar o salir.</p>
                </details>
                
                <details style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #007bff;">¿Puedo deshacer una eliminación?</summary>
                    <p style="margin-top: 10px;">Si eliminaste un grupo, puedes reactivarlo editándolo y cambiando el estado a "Activo".</p>
                </details>
                
                <details style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #007bff;">¿Por qué no aparecen algunos artículos en la búsqueda?</summary>
                    <p style="margin-top: 10px;">Solo aparecen artículos que no están ya asociados a otros grupos para evitar duplicados.</p>
                </details>
                
                <details style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #007bff;">¿Cómo borro un artículo de un grupo?</summary>
                    <p style="margin-top: 10px;">Edita el grupo, busca el artículo en la tabla y haz clic en el ícono de papelera roja junto a él.</p>
                </details>
            </div>
        `;
    },
    
    // === FUNCIONALIDAD DE INTERACCIÓN ===
    
    // Vincular eventos
    bindEvents: function() {
        const helpButton = document.getElementById('helpButton');
        const helpModal = document.getElementById('helpModal');
        const helpClose = document.getElementById('helpClose');
        
        // Abrir modal
        helpButton.addEventListener('click', () => this.openModal());
        
        // Cerrar modal
        helpClose.addEventListener('click', () => this.closeModal());
        helpModal.addEventListener('click', (e) => {
            if (e.target === helpModal) this.closeModal();
        });
        
        // Navegación por teclado
        if (this.config.enableKeyboardNavigation) {
            this.setupKeyboardNavigation();
        }
        
        // Delegación de eventos para pestañas y secciones
        document.addEventListener('click', (e) => this.handleClicks(e));
    },
    
    // Configurar navegación por teclado
    setupKeyboardNavigation: function() {
        document.addEventListener('keydown', (e) => {
            // F1 para abrir ayuda
            if (e.key === 'F1') {
                e.preventDefault();
                this.openModal();
                return;
            }
            
            // ESC para cerrar
            if (e.key === 'Escape' && this.state.isModalOpen) {
                this.closeModal();
                return;
            }
            
            // Solo procesar otras teclas si el modal está abierto
            if (!this.state.isModalOpen) return;
            
            // Tab entre pestañas (Ctrl + Tab)
            if (e.ctrlKey && e.key === 'Tab') {
                e.preventDefault();
                this.switchToNextTab();
                return;
            }
            
            // Enter o Espacio para expandir/contraer secciones
            if ((e.key === 'Enter' || e.key === ' ') && 
                e.target.classList.contains('help-section-header')) {
                e.preventDefault();
                const section = e.target.closest('.help-section');
                this.toggleSection(section);
            }
        });
    },
    
    // Manejar clicks en pestañas y secciones
    handleClicks: function(e) {
        // Cambio de pestaña
        if (e.target.classList.contains('help-tab')) {
            this.switchTab(e.target.dataset.tab);
            return;
        }
        
        // Toggle de sección
        const sectionHeader = e.target.closest('.help-section-header');
        if (sectionHeader) {
            const section = sectionHeader.closest('.help-section');
            this.toggleSection(section);
        }
    },
    
    // Cambiar pestaña activa
    switchTab: function(tabName) {
        // Validar que la pestaña existe
        const targetTab = document.querySelector(`[data-tab="${tabName}"]`);
        const targetContent = document.querySelector(`[data-content="${tabName}"]`);
        
        if (!targetTab || !targetContent) {
            console.warn(`Pestaña "${tabName}" no encontrada`);
            return;
        }
        
        // Desactivar todas las pestañas
        document.querySelectorAll('.help-tab').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        
        document.querySelectorAll('.help-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Activar pestaña seleccionada
        targetTab.classList.add('active');
        targetTab.setAttribute('aria-selected', 'true');
        targetContent.classList.add('active');
        
        // Actualizar estado
        this.state.currentTab = tabName;
        this.saveState();
        
        // Auto-expandir primera sección si está configurado
        if (this.config.autoExpandFirstSection) {
            setTimeout(() => {
                const firstSection = targetContent.querySelector('.help-section:not(.expanded)');
                if (firstSection) {
                    this.toggleSection(firstSection);
                }
            }, 100);
        }
        
        // Enfocar la pestaña para accesibilidad
        targetTab.focus();
    },
    
    // Cambiar a la siguiente pestaña (para navegación por teclado)
    switchToNextTab: function() {
        const tabs = Array.from(document.querySelectorAll('.help-tab'));
        const currentIndex = tabs.findIndex(tab => tab.classList.contains('active'));
        const nextIndex = (currentIndex + 1) % tabs.length;
        
        this.switchTab(tabs[nextIndex].dataset.tab);
    },
    
    // Expandir/contraer sección
    toggleSection: function(section) {
        const isExpanded = section.classList.contains('expanded');
        const sectionId = section.dataset.section;
        
        // Alternar clase
        section.classList.toggle('expanded');
        
        // Actualizar atributos ARIA
        const header = section.querySelector('.help-section-header');
        if (header) {
            header.setAttribute('aria-expanded', (!isExpanded).toString());
        }
        
        // Actualizar estado
        if (!isExpanded) {
            this.state.expandedSections.add(sectionId);
        } else {
            this.state.expandedSections.delete(sectionId);
        }
        
        this.saveState();
        
        // Scroll suave hacia la sección si se está expandiendo
        if (!isExpanded) {
            setTimeout(() => {
                section.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }, this.config.animationDuration);
        }
    },
    
    // Abrir modal
    openModal: function() {
        const helpModal = document.getElementById('helpModal');
        
        if (!helpModal) {
            console.error('Modal de ayuda no encontrado');
            return;
        }
        
        // Mostrar modal
        helpModal.style.display = 'block';
        helpModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Actualizar estado
        this.state.isModalOpen = true;
        
        // Enfocar el modal para accesibilidad
        setTimeout(() => {
            const firstFocusable = helpModal.querySelector('.help-close');
            if (firstFocusable) {
                firstFocusable.focus();
            }
        }, 100);
        
        // Expandir secciones según estado guardado
        this.restoreExpandedSections();
        
        // Registrar apertura para estadísticas (opcional)
        this.trackUsage('modal_opened');
    },
    
    // Cerrar modal
    closeModal: function() {
        const helpModal = document.getElementById('helpModal');
        
        if (!helpModal) return;
        
        // Ocultar modal
        helpModal.style.display = 'none';
        helpModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = 'auto';
        
        // Actualizar estado
        this.state.isModalOpen = false;
        
        // Devolver foco al botón de ayuda
        const helpButton = document.getElementById('helpButton');
        if (helpButton) {
            helpButton.focus();
        }
        
        // Guardar estado actual
        this.saveState();
        
        // Registrar cierre para estadísticas (opcional)
        this.trackUsage('modal_closed');
    },
    
    // Restaurar secciones expandidas
    restoreExpandedSections: function() {
        this.state.expandedSections.forEach(sectionId => {
            const section = document.querySelector(`[data-section="${sectionId}"]`);
            if (section && !section.classList.contains('expanded')) {
                section.classList.add('expanded');
                
                const header = section.querySelector('.help-section-header');
                if (header) {
                    header.setAttribute('aria-expanded', 'true');
                }
            }
        });
    },
    
    // === GESTIÓN DE ESTADO ===
    
    // Guardar estado en localStorage
    saveState: function() {
        if (!this.config.rememberLastTab) return;
        
        try {
            const state = {
                currentTab: this.state.currentTab,
                expandedSections: Array.from(this.state.expandedSections),
                timestamp: Date.now()
            };
            
            localStorage.setItem('helpModalState', JSON.stringify(state));
        } catch (error) {
            console.warn('No se pudo guardar el estado de la ayuda:', error);
        }
    },
    
    // Cargar estado desde localStorage
    loadState: function() {
        if (!this.config.rememberLastTab) return;
        
        try {
            const saved = localStorage.getItem('helpModalState');
            if (!saved) return;
            
            const state = JSON.parse(saved);
            
            // Verificar que el estado no sea muy antiguo (más de 24 horas)
            const now = Date.now();
            const dayInMs = 24 * 60 * 60 * 1000;
            
            if (now - state.timestamp > dayInMs) {
                localStorage.removeItem('helpModalState');
                return;
            }
            
            // Restaurar estado
            this.state.currentTab = state.currentTab || 'uso';
            this.state.expandedSections = new Set(state.expandedSections || []);
            
        } catch (error) {
            console.warn('No se pudo cargar el estado de la ayuda:', error);
            localStorage.removeItem('helpModalState');
        }
    },
    
    // === FUNCIONES AUXILIARES ===
    
    // Expandir todas las secciones de la pestaña actual
    expandAllSections: function() {
        const currentContent = document.querySelector('.help-tab-content.active');
        if (!currentContent) return;
        
        const sections = currentContent.querySelectorAll('.help-section:not(.expanded)');
        sections.forEach(section => this.toggleSection(section));
    },
    
    // Contraer todas las secciones de la pestaña actual
    collapseAllSections: function() {
        const currentContent = document.querySelector('.help-tab-content.active');
        if (!currentContent) return;
        
        const sections = currentContent.querySelectorAll('.help-section.expanded');
        sections.forEach(section => this.toggleSection(section));
    },
    
    // Buscar contenido en el manual
    searchContent: function(searchTerm) {
        if (!searchTerm || searchTerm.length < 2) return [];
        
        const results = [];
        const contents = document.querySelectorAll('.help-section-content');
        
        contents.forEach(content => {
            const section = content.closest('.help-section');
            const sectionTitle = section.querySelector('h3').textContent;
            const sectionId = section.dataset.section;
            
            const text = content.textContent.toLowerCase();
            const term = searchTerm.toLowerCase();
            
            if (text.includes(term)) {
                results.push({
                    sectionId: sectionId,
                    title: sectionTitle,
                    section: section
                });
            }
        });
        
        return results;
    },
    
    // Registrar uso para estadísticas (opcional)
    trackUsage: function(action) {
        try {
            // Aquí puedes implementar tu sistema de estadísticas
            console.log(`Ayuda: ${action}`, {
                timestamp: new Date().toISOString(),
                page: window.location.pathname,
                userAgent: navigator.userAgent.substring(0, 100)
            });
            
            // Ejemplo de implementación con servidor:
            /*
            fetch('/api/help-analytics', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: action,
                    page: window.location.pathname,
                    timestamp: new Date().toISOString()
                })
            }).catch(err => console.warn('Error enviando estadísticas:', err));
            */
        } catch (error) {
            console.warn('Error en trackUsage:', error);
        }
    },
    
    // Obtener información de diagnóstico
    getDiagnosticInfo: function() {
        return {
            version: '2.0.0',
            state: this.state,
            config: this.config,
            elementsPresent: {
                button: !!document.getElementById('helpButton'),
                modal: !!document.getElementById('helpModal'),
                tabs: document.querySelectorAll('.help-tab').length,
                sections: document.querySelectorAll('.help-section').length
            },
            browserSupport: {
                localStorage: typeof Storage !== 'undefined',
                details: 'HTMLDetailsElement' in window,
                css: window.CSS && CSS.supports('display', 'grid')
            }
        };
    }
};

// Funciones públicas para uso externo
window.HelpSystem = {
    open: () => HelpButton.openModal(),
    close: () => HelpButton.closeModal(),
    switchTab: (tab) => HelpButton.switchTab(tab),
    search: (term) => HelpButton.searchContent(term),
    expandAll: () => HelpButton.expandAllSections(),
    collapseAll: () => HelpButton.collapseAllSections(),
    diagnostics: () => HelpButton.getDiagnosticInfo()
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    try {
        HelpButton.init();
    } catch (error) {
        console.error('Error inicializando sistema de ayuda:', error);
    }
});