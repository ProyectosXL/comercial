/* ==============================================
   FUNCIONALIDAD DEL BOTÓN DE AYUDA
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
        const modalHTML = `
            <div id="helpModal" class="help-modal">
                <div class="help-modal-content">
                    <div class="help-modal-header">
                        <h2>Manual de Usuario - Gestión de Equivalencias</h2>
                        <button class="help-close" id="helpClose">&times;</button>
                    </div>
                    <div class="help-tabs">
                        <button class="help-tab active" data-tab="uso">📖 Guía de Uso</button>
                        <button class="help-tab" data-tab="consejos">💡 Consejos y FAQ</button>
                    </div>
                    <div class="help-modal-body">
                        ${this.getTabContent('uso')}
                        ${this.getTabContent('consejos')}
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },
    
    // Obtener contenido de pestañas
    getTabContent: function(tab) {
        if (tab === 'uso') {
            return `
                <div class="help-tab-content active" data-content="uso">
                    ${this.createCollapsibleSection('buscar', '🔍 Buscar y Filtrar Grupos', this.getBuscarContent())}
                    ${this.createCollapsibleSection('crear', '➕ Crear un Nuevo Grupo', this.getCrearContent())}
                    ${this.createCollapsibleSection('editar', '✏️ Editar un Grupo Existente', this.getEditarContent())}
                    ${this.createCollapsibleSection('eliminar', '🗑️ Eliminar un Grupo', this.getEliminarContent())}
                    ${this.createCollapsibleSection('tabla', '📊 Entender la Tabla Principal', this.getTablaContent())}
                    ${this.createCollapsibleSection('buscador', '🔧 Funciones del Buscador de Artículos', this.getBuscadorContent())}
                    ${this.createCollapsibleSection('validaciones', '⚠️ Mensajes y Validaciones', this.getValidacionesContent())}
                </div>
            `;
        } else {
            return `
                <div class="help-tab-content" data-content="consejos">
                    ${this.createCollapsibleSection('consejos', '💡 Consejos de Uso', this.getConsejosContent())}
                    ${this.createCollapsibleSection('flujo', '🚀 Flujo de Trabajo Recomendado', this.getFlujoContent())}
                    ${this.createCollapsibleSection('faq', '❓ Preguntas Frecuentes de Uso', this.getFaqContent())}
                </div>
            `;
        }
    },
    
    // Crear sección desplegable
    createCollapsibleSection: function(id, title, content) {
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
    
    // Contenido de cada sección
    getBuscarContent: function() {
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
    
    getCrearContent: function() {
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
    
    getEditarContent: function() {
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
    
    getEliminarContent: function() {
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
    
    getTablaContent: function() {
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
    
    getBuscadorContent: function() {
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
    
    getValidacionesContent: function() {
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
    
    getConsejosContent: function() {
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
    
    getFlujoContent: function() {
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
    
    getFaqContent: function() {
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
    
    // Vincular eventos
    bindEvents: function() {
        const helpButton = document.getElementById('helpButton');
        const helpModal = document.getElementById('helpModal');
        const helpClose = document.getElementById('helpClose');
        
        // Abrir modal
        helpButton.addEventListener('click', function() {
            HelpButton.openModal();
        });
        
        // Cerrar modal con X
        helpClose.addEventListener('click', function() {
            HelpButton.closeModal();
        });
        
        // Cerrar modal haciendo clic fuera
        helpModal.addEventListener('click', function(e) {
            if (e.target === helpModal) {
                HelpButton.closeModal();
            }
        });
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && helpModal.style.display === 'block') {
                HelpButton.closeModal();
            }
        });
        
        // Event listeners para las pestañas
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('help-tab')) {
                HelpButton.switchTab(e.target.dataset.tab);
            }
            
            if (e.target.classList.contains('help-section-header') || e.target.closest('.help-section-header')) {
                const header = e.target.classList.contains('help-section-header') ? e.target : e.target.closest('.help/* ==============================================
   FUNCIONALIDAD DEL BOTÓN DE AYUDA
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
        const modalHTML = `
            <div id="helpModal" class="help-modal">
                <div class="help-modal-content">
                    <div class="help-modal-header">
                        <h2>Manual de Usuario - Gestión de Equivalencias</h2>
                        <button class="help-close" id="helpClose">&times;</button>
                    </div>
                    <div class="help-modal-body">
                        ${this.getHelpContent()}
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },
    
    // Contenido del manual
    getHelpContent: function() {
        return `
            <div class="help-section">
                <h3>📋 Cómo usar esta herramienta</h3>
                <p>Esta pantalla te permite gestionar grupos de artículos equivalentes. Puedes crear nuevos grupos, editar existentes, buscar específicos y controlar su estado.</p>
                
                <div class="help-highlight">
                    <strong>Objetivo:</strong> Administrar de forma sencilla las asociaciones entre artículos equivalentes.
                </div>
            </div>
            
            <div class="help-section">
                <h3>🔍 Buscar y Filtrar Grupos</h3>
                <p><strong>Panel de filtros (parte superior):</strong></p>
                <ul>
                    <li><strong>Buscar por artículo:</strong> Escribe el código de un artículo para encontrar en qué grupo está</li>
                    <li><strong>Buscar por grupo:</strong> Ingresa el número de código de equivalencia directamente</li>
                    <li><strong>Rubro:</strong> Filtra solo los grupos de una categoría específica</li>
                    <li><strong>Estado:</strong> Muestra grupos activos, inactivos o todos</li>
                </ul>
                <p><strong>Tip:</strong> Puedes combinar varios filtros. Haz clic en "Buscar" para aplicar los filtros seleccionados.</p>
            </div>
            
            <div class="help-section">
                <h3>➕ Crear un Nuevo Grupo</h3>
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
            </div>
            
            <div class="help-section">
                <h3>✏️ Editar un Grupo Existente</h3>
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
            </div>
            
            <div class="help-section">
                <h3>🗑️ Eliminar un Grupo</h3>
                <p><strong>Para desactivar un grupo:</strong></p>
                <ol>
                    <li>Encuentra el grupo en la tabla</li>
                    <li>Haz clic en el botón "Eliminar" (ícono de papelera roja)</li>
                    <li>Confirma la acción en el cuadro que aparece</li>
                </ol>
                
                <div class="help-highlight">
                    <strong>Nota:</strong> El grupo se desactiva pero no se borra permanentemente. Puedes reactivarlo editándolo y cambiando el estado a "Activo".
                </div>
            </div>
            
            <div class="help-section">
                <h3>📊 Entender la Tabla Principal</h3>
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
            </div>
            
            <div class="help-section">
                <h3>🔧 Funciones del Buscador de Artículos</h3>
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
            </div>
            
            <div class="help-section">
                <h3>⚠️ Mensajes y Validaciones</h3>
                <p><strong>Errores comunes y soluciones:</strong></p>
                <ul>
                    <li><strong>"La descripción del grupo es obligatoria":</strong> Debes escribir un nombre para el grupo</li>
                    <li><strong>"Debe seleccionar un rubro":</strong> Elige una categoría del menú desplegable</li>
                    <li><strong>"Debe agregar al menos un artículo":</strong> El grupo necesita tener productos asociados</li>
                    <li><strong>"Este artículo ya está en el grupo":</strong> No puedes agregar el mismo código dos veces</li>
                    <li><strong>"Artículo eliminado del grupo":</strong> Confirmación de que se quitó correctamente</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3>💡 Consejos de Uso</h3>
                <ul>
                    <li><strong>Nombres descriptivos:</strong> Usa descripciones claras para encontrar grupos fácilmente</li>
                    <li><strong>Filtro por estado:</strong> Por defecto muestra solo "Activos", cambia a "Todos" si necesitas ver inactivos</li>
                    <li><strong>Buscar antes de crear:</strong> Usa los filtros para verificar que no existe un grupo similar</li>
                    <li><strong>Revisar antes de guardar:</strong> Verifica la lista de artículos antes de confirmar</li>
                    <li><strong>Estados útiles:</strong> Usa "Inactivo" para grupos temporalmente no utilizados</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3>🚀 Flujo de Trabajo Recomendado</h3>
                <p><strong>Para usar eficientemente la herramienta:</strong></p>
                <ol>
                    <li><strong>Preparar:</strong> Ten listos los códigos de artículos que vas a agrupar</li>
                    <li><strong>Verificar:</strong> Usa "Buscar por artículo" para ver si ya están en algún grupo</li>
                    <li><strong>Crear:</strong> Si no existen, crea un nuevo grupo con "Nuevo Grupo"</li>
                    <li><strong>Completar:</strong> Agrega todos los artículos de una vez para no olvidar ninguno</li>
                    <li><strong>Verificar resultado:</strong> Usa los filtros para encontrar tu grupo y confirmar que esté correcto</li>
                </ol>
            </div>
            
            <div class="help-section">
                <h3>❓ Preguntas Frecuentes de Uso</h3>
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
            </div>
        `;
    },
    
    // Vincular eventos
    bindEvents: function() {
        const helpButton = document.getElementById('helpButton');
        const helpModal = document.getElementById('helpModal');
        const helpClose = document.getElementById('helpClose');
        
        // Abrir modal
        helpButton.addEventListener('click', function() {
            HelpButton.openModal();
        });
        
        // Cerrar modal con X
        helpClose.addEventListener('click', function() {
            HelpButton.closeModal();
        });
        
        // Cerrar modal haciendo clic fuera
        helpModal.addEventListener('click', function(e) {
            if (e.target === helpModal) {
                HelpButton.closeModal();
            }
        });
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && helpModal.style.display === 'block') {
                HelpButton.closeModal();
            }
        });
    },
    
    // Abrir modal
    openModal: function() {
        const helpModal = document.getElementById('helpModal');
        helpModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevenir scroll del body
    },
    
    // Cerrar modal
    closeModal: function() {
        const helpModal = document.getElementById('helpModal');
        helpModal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restaurar scroll del body
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    HelpButton.init();
});