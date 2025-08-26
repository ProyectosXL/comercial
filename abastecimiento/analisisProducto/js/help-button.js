/* ==============================================
   FUNCIONALIDAD DEL BOTÓN DE AYUDA - SIMPLIFICADO
   ============================================== */

// Objeto para manejar la funcionalidad del botón de ayuda
const HelpButton = {
    
    // Inicializar el botón de ayuda
    init: function() {
        this.createHelpButton();
        this.createHelpModal();
        this.bindEvents();
    },
    
    // Crear el botón flotante
    createHelpButton: function() {
        const helpButton = document.createElement('button');
        helpButton.id = 'helpButton';
        helpButton.className = 'help-button';
        helpButton.innerHTML = '?';
        helpButton.title = 'Manual de Usuario';
        
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
            <div id="helpModal" class="help-modal">
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
                <h2>Manual de Usuario - Gestión de Equivalencias</h2>
                <button class="help-close" id="helpClose">&times;</button>
            </div>
        `;
    },
    
    // Pestañas del modal
    getTabs: function() {
        return `
            <div class="help-tabs">
                <button class="help-tab active" data-tab="uso">📖 Guía de Uso</button>
                <button class="help-tab" data-tab="consejos">💡 Consejos y FAQ</button>
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
            <div class="help-tab-content active" data-content="uso">
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
            <div class="help-tab-content" data-content="consejos">
                ${this.createSection('consejos', '💡 Consejos de Uso', this.getContentConsejos())}
                ${this.createSection('flujo', '🚀 Flujo de Trabajo Recomendado', this.getContentFlujo())}
                ${this.createSection('faq', '❓ Preguntas Frecuentes de Uso', this.getContentFaq())}
            </div>
        `;
    },
    
    // Crear una sección desplegable
    createSection: function(id, title, content) {
        return `
            <div class="help-section" data-section="${id}">
                <div class="help-section-header">
                    <h3>${title}</h3>
                    <span class="help-section-toggle">▶</span>
                </div>
                <div class="help-section-content">
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
            <p><strong>¿Cómo sé si un artículo ya está en un grupo?</strong><br>
            Usa "Buscar por artículo" en los filtros superiores. Si aparece algún resultado, ya está asociado.</p>
            
            <p><strong>¿Puedo cambiar un artículo de grupo?</strong><br>
            Sí. Primero elimínalo del grupo actual (editando el grupo y usando la papelera roja), luego agrégalo al nuevo grupo.</p>
            
            <p><strong>¿Qué pasa si cierro sin guardar?</strong><br>
            Se pierden todos los cambios. Siempre haz clic en "Guardar" antes de cerrar o salir.</p>
            
            <p><strong>¿Puedo deshacer una eliminación?</strong><br>
            Si eliminaste un grupo, puedes reactivarlo editándolo y cambiando el estado a "Activo".</p>
            
            <p><strong>¿Por qué no aparecen algunos artículos en la búsqueda?</strong><br>
            Solo aparecen artículos que no están ya asociados a otros grupos para evitar duplicados.</p>
            
            <p><strong>¿Cómo borro un artículo de un grupo?</strong><br>
            Edita el grupo, busca el artículo en la tabla y haz clic en el ícono de papelera roja junto a él.</p>
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
        
        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && helpModal.style.display === 'block') {
                this.closeModal();
            }
        });
        
        // Delegación de eventos para pestañas y secciones
        document.addEventListener('click', (e) => this.handleClicks(e));
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
        // Desactivar todas las pestañas
        document.querySelectorAll('.help-tab').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.help-tab-content').forEach(content => content.classList.remove('active'));
        
        // Activar pestaña seleccionada
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        document.querySelector(`[data-content="${tabName}"]`).classList.add('active');
    },
    
    // Expandir/contraer sección
    toggleSection: function(section) {
        section.classList.toggle('expanded');
    },
    
    // Abrir modal
    openModal: function() {
        const helpModal = document.getElementById('helpModal');
        helpModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Expandir primera sección por defecto
        setTimeout(() => {
            const firstSection = document.querySelector('.help-tab-content.active .help-section');
            if (firstSection && !firstSection.classList.contains('expanded')) {
                this.toggleSection(firstSection);
            }
        }, 100);
    },
    
    // Cerrar modal
    closeModal: function() {
        const helpModal = document.getElementById('helpModal');
        helpModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Cerrar todas las secciones
        document.querySelectorAll('.help-section.expanded').forEach(section => {
            section.classList.remove('expanded');
        });
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    HelpButton.init();
});