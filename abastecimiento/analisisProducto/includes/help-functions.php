<?php
/* ==============================================
   FUNCIONES PHP PARA EL BOTÓN DE AYUDA
   ============================================== */

/**
 * Incluir los archivos CSS y JS del botón de ayuda
 * Debe ser llamada en el <head> de la página
 */
function incluir_archivos_ayuda() {
    echo '<link rel="stylesheet" href="css/help-button.css">' . "\n";
    echo '<script src="js/help-button.js"></script>' . "\n";
}

/**
 * Renderizar el botón de ayuda directamente en HTML
 * Alternativa si no se quiere usar JavaScript para crear el botón
 */
function renderizar_boton_ayuda() {
    ?>
    <button id="helpButton" class="help-button" title="Manual de Usuario">
        ?
    </button>
    <?php
}

/**
 * Renderizar el modal de ayuda completo
 * Útil para generar el HTML desde PHP si se prefiere
 */
function renderizar_modal_ayuda() {
    ?>
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
                <div class="help-tab-content active" data-content="uso">
                    <?php echo crear_seccion_desplegable('buscar', '🔍 Buscar y Filtrar Grupos', obtener_contenido_buscar()); ?>
                    <?php echo crear_seccion_desplegable('crear', '➕ Crear un Nuevo Grupo', obtener_contenido_crear()); ?>
                    <?php echo crear_seccion_desplegable('editar', '✏️ Editar un Grupo Existente', obtener_contenido_editar()); ?>
                    <?php echo crear_seccion_desplegable('eliminar', '🗑️ Eliminar un Grupo', obtener_contenido_eliminar()); ?>
                    <?php echo crear_seccion_desplegable('tabla', '📊 Entender la Tabla Principal', obtener_contenido_tabla()); ?>
                    <?php echo crear_seccion_desplegable('buscador', '🔧 Funciones del Buscador de Artículos', obtener_contenido_buscador()); ?>
                    <?php echo crear_seccion_desplegable('validaciones', '⚠️ Mensajes y Validaciones', obtener_contenido_validaciones()); ?>
                </div>
                <div class="help-tab-content" data-content="consejos">
                    <?php echo crear_seccion_desplegable('consejos', '💡 Consejos de Uso', obtener_contenido_consejos()); ?>
                    <?php echo crear_seccion_desplegable('flujo', '🚀 Flujo de Trabajo Recomendado', obtener_contenido_flujo()); ?>
                    <?php echo crear_seccion_desplegable('faq', '❓ Preguntas Frecuentes de Uso', obtener_contenido_faq()); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Obtener el contenido HTML del manual de ayuda
 * @return string HTML del contenido de ayuda
 */
function obtener_contenido_ayuda() {
    return '
        <div class="help-tabs">
            <button class="help-tab active" data-tab="uso">📖 Guía de Uso</button>
            <button class="help-tab" data-tab="consejos">💡 Consejos y FAQ</button>
        </div>
        <div class="help-modal-body">
            <div class="help-tab-content active" data-content="uso">
                ' . crear_seccion_desplegable('buscar', '🔍 Buscar y Filtrar Grupos', obtener_contenido_buscar()) . '
                ' . crear_seccion_desplegable('crear', '➕ Crear un Nuevo Grupo', obtener_contenido_crear()) . '
                ' . crear_seccion_desplegable('editar', '✏️ Editar un Grupo Existente', obtener_contenido_editar()) . '
                ' . crear_seccion_desplegable('eliminar', '🗑️ Eliminar un Grupo', obtener_contenido_eliminar()) . '
                ' . crear_seccion_desplegable('tabla', '📊 Entender la Tabla Principal', obtener_contenido_tabla()) . '
                ' . crear_seccion_desplegable('buscador', '🔧 Funciones del Buscador de Artículos', obtener_contenido_buscador()) . '
                ' . crear_seccion_desplegable('validaciones', '⚠️ Mensajes y Validaciones', obtener_contenido_validaciones()) . '
            </div>
            <div class="help-tab-content" data-content="consejos">
                ' . crear_seccion_desplegable('consejos', '💡 Consejos de Uso', obtener_contenido_consejos()) . '
                ' . crear_seccion_desplegable('flujo', '🚀 Flujo de Trabajo Recomendado', obtener_contenido_flujo()) . '
                ' . crear_seccion_desplegable('faq', '❓ Preguntas Frecuentes de Uso', obtener_contenido_faq()) . '
            </div>
        </div>
    ';
}

/**
 * Crear una sección desplegable
 */
function crear_seccion_desplegable($id, $titulo, $contenido) {
    return '
        <div class="help-section" data-section="' . $id . '">
            <div class="help-section-header">
                <h3>' . $titulo . '</h3>
                <span class="help-section-toggle">▶</span>
            </div>
            <div class="help-section-content">
                ' . $contenido . '
            </div>
        </div>
    ';
}

/**
 * Contenido de cada sección
 */
function obtener_contenido_buscar() {
    return '
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
    ';
}

function obtener_contenido_crear() {
    return '
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
    ';
}

function obtener_contenido_editar() {
    return '
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
    ';
}

function obtener_contenido_eliminar() {
    return '
        <p><strong>Para desactivar un grupo:</strong></p>
        <ol>
            <li>Encuentra el grupo en la tabla</li>
            <li>Haz clic en el botón "Eliminar" (ícono de papelera roja)</li>
            <li>Confirma la acción en el cuadro que aparece</li>
        </ol>
        <div class="help-highlight">
            <strong>Nota:</strong> El grupo se desactiva pero no se borra permanentemente. Puedes reactivarlo editándolo y cambiando el estado a "Activo".
        </div>
    ';
}

function obtener_contenido_tabla() {
    return '
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
    ';
}

function obtener_contenido_buscador() {
    return '
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
    ';
}

function obtener_contenido_validaciones() {
    return '
        <p><strong>Errores comunes y soluciones:</strong></p>
        <ul>
            <li><strong>"La descripción del grupo es obligatoria":</strong> Debes escribir un nombre para el grupo</li>
            <li><strong>"Debe seleccionar un rubro":</strong> Elige una categoría del menú desplegable</li>
            <li><strong>"Debe agregar al menos un artículo":</strong> El grupo necesita tener productos asociados</li>
            <li><strong>"Este artículo ya está en el grupo":</strong> No puedes agregar el mismo código dos veces</li>
            <li><strong>"Artículo eliminado del grupo":</strong> Confirmación de que se quitó correctamente</li>
        </ul>
    ';
}

function obtener_contenido_consejos() {
    return '
        <ul>
            <li><strong>Nombres descriptivos:</strong> Usa descripciones claras para encontrar grupos fácilmente</li>
            <li><strong>Filtro por estado:</strong> Por defecto muestra solo "Activos", cambia a "Todos" si necesitas ver inactivos</li>
            <li><strong>Buscar antes de crear:</strong> Usa los filtros para verificar que no existe un grupo similar</li>
            <li><strong>Revisar antes de guardar:</strong> Verifica la lista de artículos antes de confirmar</li>
            <li><strong>Estados útiles:</strong> Usa "Inactivo" para grupos temporalmente no utilizados</li>
        </ul>
    ';
}

function obtener_contenido_flujo() {
    return '
        <p><strong>Para usar eficientemente la herramienta:</strong></p>
        <ol>
            <li><strong>Preparar:</strong> Ten listos los códigos de artículos que vas a agrupar</li>
            <li><strong>Verificar:</strong> Usa "Buscar por artículo" para ver si ya están en algún grupo</li>
            <li><strong>Crear:</strong> Si no existen, crea un nuevo grupo con "Nuevo Grupo"</li>
            <li><strong>Completar:</strong> Agrega todos los artículos de una vez para no olvidar ninguno</li>
            <li><strong>Verificar resultado:</strong> Usa los filtros para encontrar tu grupo y confirmar que esté correcto</li>
        </ol>
    ';
}

function obtener_contenido_faq() {
    return '
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
    ';
}

/**
 * Verificar si el usuario tiene permisos para ver la ayuda
 * @return bool True si puede ver la ayuda, False si no
 */
function usuario_puede_ver_ayuda() {
    // Por ahora retorna true para todos los usuarios autenticados
    // Puedes agregar tu lógica de permisos aquí
    return true;
}

/**
 * Registrar que el usuario abrió la ayuda (para estadísticas)
 * @param int $usuario_id ID del usuario que abrió la ayuda
 * @param string $pagina Página desde donde se abrió la ayuda
 */
function registrar_uso_ayuda($usuario_id, $pagina = 'gestion_equivalencias') {
    try {
        // Aquí puedes agregar código para registrar en base de datos
        // Por ejemplo:
        /*
        $conn = obtener_conexion_bd();
        $stmt = $conn->prepare("INSERT INTO logs_ayuda (usuario_id, pagina, fecha_acceso) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $usuario_id, $pagina);
        $stmt->execute();
        */
        
        // Por ahora solo registra en un log de archivos si la carpeta existe
        if (is_dir('logs')) {
            $log_entry = date('Y-m-d H:i:s') . " - Usuario ID: $usuario_id abrió ayuda en página: $pagina\n";
            file_put_contents('logs/ayuda_usage.log', $log_entry, FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        // Silenciosamente falla para no interrumpir la funcionalidad principal
        error_log("Error registrando uso de ayuda: " . $e->getMessage());
    }
}

/**
 * Obtener configuración personalizada para la ayuda
 * @return array Configuración de la ayuda
 */
function obtener_config_ayuda() {
    return [
        'mostrar_botón' => true,
        'posicion' => 'top-right', // top-right, top-left, bottom-right, bottom-left
        'color_primario' => '#007bff',
        'mostrar_animaciones' => true,
        'idioma' => 'es',
        'version_ayuda' => '1.0'
    ];
}

/**
 * Generar el JavaScript de configuración personalizada
 * @return string JavaScript con la configuración
 */
function generar_config_js_ayuda() {
    $config = obtener_config_ayuda();
    return '<script>window.helpConfig = ' . json_encode($config) . ';</script>';
}
?>