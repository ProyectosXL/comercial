<?php
/* ==============================================
   FUNCIONES PHP MEJORADAS PARA EL BOTÓN DE AYUDA
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
    <button id="helpButton" class="help-button" title="Manual de Usuario - F1 o Click">
        ?
    </button>
    <?php
}

/**
 * Renderizar el modal de ayuda completo desde PHP
 * Útil para generar el HTML desde el servidor
 */
function renderizar_modal_ayuda_completo() {
    $contenido_primera_solapa = obtener_contenido_primera_solapa();
    $contenido_segunda_solapa = obtener_contenido_segunda_solapa();
    
    ?>
    <div id="helpModal" class="help-modal" role="dialog" aria-labelledby="helpModalTitle" aria-hidden="true">
        <div class="help-modal-content">
            <div class="help-modal-header">
                <h2 id="helpModalTitle">📚 Manual de Usuario - Gestión de Equivalencias</h2>
                <button class="help-close" id="helpClose" aria-label="Cerrar manual">&times;</button>
            </div>
            
            <div class="help-tabs" role="tablist">
                <button class="help-tab active" data-tab="uso" role="tab" aria-selected="true" aria-controls="tab-uso">
                    📖 Guía de Uso
                </button>
                <button class="help-tab" data-tab="consejos" role="tab" aria-selected="false" aria-controls="tab-consejos">
                    💡 Consejos y FAQ
                </button>
            </div>
            
            <div class="help-modal-body">
                <!-- Primera solapa: Guía de Uso -->
                <div class="help-tab-content active" data-content="uso" id="tab-uso" role="tabpanel">
                    <?php echo $contenido_primera_solapa; ?>
                </div>
                
                <!-- Segunda solapa: Consejos y FAQ -->
                <div class="help-tab-content" data-content="consejos" id="tab-consejos" role="tabpanel">
                    <?php echo $contenido_segunda_solapa; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Obtener contenido de la primera solapa (Guía de Uso)
 */
function obtener_contenido_primera_solapa() {
    $secciones = [
        'buscar' => ['🔍 Buscar y Filtrar Grupos', obtener_contenido_buscar()],
        'crear' => ['➕ Crear un Nuevo Grupo', obtener_contenido_crear()],
        'editar' => ['✏️ Editar un Grupo Existente', obtener_contenido_editar()],
        'eliminar' => ['🗑️ Eliminar un Grupo', obtener_contenido_eliminar()],
        'tabla' => ['📊 Entender la Tabla Principal', obtener_contenido_tabla()],
        'buscador' => ['🔧 Funciones del Buscador de Artículos', obtener_contenido_buscador()],
        'validaciones' => ['⚠️ Mensajes y Validaciones', obtener_contenido_validaciones()]
    ];
    
    $html = '';
    foreach ($secciones as $id => $data) {
        $html .= crear_seccion_desplegable($id, $data[0], $data[1]);
    }
    
    return $html;
}

/**
 * Obtener contenido de la segunda solapa (Consejos y FAQ)
 */
function obtener_contenido_segunda_solapa() {
    $secciones = [
        'consejos' => ['💡 Consejos de Uso', obtener_contenido_consejos()],
        'flujo' => ['🚀 Flujo de Trabajo Recomendado', obtener_contenido_flujo()],
        'faq' => ['❓ Preguntas Frecuentes de Uso', obtener_contenido_faq()]
    ];
    
    $html = '';
    foreach ($secciones as $id => $data) {
        $html .= crear_seccion_desplegable($id, $data[0], $data[1]);
    }
    
    return $html;
}

/**
 * Crear una sección desplegable
 */
function crear_seccion_desplegable($id, $titulo, $contenido) {
    return '
        <div class="help-section" data-section="' . $id . '">
            <div class="help-section-header" role="button" aria-expanded="false" aria-controls="section-' . $id . '">
                <h3>' . $titulo . '</h3>
                <span class="help-section-toggle" aria-hidden="true">▶</span>
            </div>
            <div class="help-section-content" id="section-' . $id . '" role="region">
                ' . $contenido . '
            </div>
        </div>
    ';
}

/**
 * Contenido de cada sección - Primera solapa
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

/**
 * Contenido de cada sección - Segunda solapa
 */
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
    ';
}

/**
 * Verificar si el usuario tiene permisos para ver la ayuda
 * @return bool True si puede ver la ayuda, False si no
 */
function usuario_puede_ver_ayuda() {
    // Puedes personalizar esta función según tus necesidades de permisos
    // Por ejemplo, verificar roles, permisos específicos, etc.
    
    // Verificar si hay sesión activa
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    
    // Verificar rol del usuario (ejemplo)
    /*
    if (isset($_SESSION['user_role'])) {
        $rolesConAcceso = ['admin', 'supervisor', 'usuario'];
        return in_array($_SESSION['user_role'], $rolesConAcceso);
    }
    */
    
    // Por defecto, permitir acceso a usuarios autenticados
    return isset($_SESSION['user_id']) || isset($_SESSION['usuario']);
}

/**
 * Registrar que el usuario abrió la ayuda (para estadísticas)
 * @param int $usuario_id ID del usuario que abrió la ayuda
 * @param string $pagina Página desde donde se abrió la ayuda
 */
function registrar_uso_ayuda($usuario_id = null, $pagina = 'gestion_equivalencias') {
    try {
        // Si no se proporciona usuario_id, intentar obtenerlo de la sesión
        if ($usuario_id === null) {
            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 'anonimo';
        }
        
        // Aquí puedes agregar código para registrar en base de datos
        // Ejemplo usando PDO:
        /*
        $conn = obtener_conexion_bd();
        $stmt = $conn->prepare("
            INSERT INTO logs_ayuda (usuario_id, pagina, fecha_acceso, ip_address, user_agent) 
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([
            $usuario_id, 
            $pagina, 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
        */
        
        // Registro en archivo de log si la carpeta existe
        $log_dir = __DIR__ . '/../../logs';
        if (is_dir($log_dir) && is_writable($log_dir)) {
            $log_entry = date('Y-m-d H:i:s') . " - Usuario: $usuario_id - Página: $pagina - IP: " . 
                        ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
            file_put_contents($log_dir . '/ayuda_usage.log', $log_entry, FILE_APPEND | LOCK_EX);
        }
        
        return true;
        
    } catch (Exception $e) {
        // Silenciosamente falla para no interrumpir la funcionalidad principal
        error_log("Error registrando uso de ayuda: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas de uso de la ayuda
 * @param int $dias_atras Número de días hacia atrás para las estadísticas
 * @return array Estadísticas de uso
 */
function obtener_estadisticas_ayuda($dias_atras = 30) {
    try {
        $estadisticas = [
            'total_aperturas' => 0,
            'usuarios_unicos' => 0,
            'paginas_mas_consultadas' => [],
            'horarios_pico' => [],
            'periodo' => $dias_atras . ' días'
        ];
        
        // Ejemplo usando base de datos:
        /*
        $conn = obtener_conexion_bd();
        
        // Total de aperturas
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM logs_ayuda 
            WHERE fecha_acceso >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$dias_atras]);
        $estadisticas['total_aperturas'] = $stmt->fetchColumn();
        
        // Usuarios únicos
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT usuario_id) as unicos 
            FROM logs_ayuda 
            WHERE fecha_acceso >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$dias_atras]);
        $estadisticas['usuarios_unicos'] = $stmt->fetchColumn();
        
        // Páginas más consultadas
        $stmt = $conn->prepare("
            SELECT pagina, COUNT(*) as total 
            FROM logs_ayuda 
            WHERE fecha_acceso >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY pagina 
            ORDER BY total DESC 
            LIMIT 10
        ");
        $stmt->execute([$dias_atras]);
        $estadisticas['paginas_mas_consultadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        */
        
        // Alternativa usando archivo de log
        $log_file = __DIR__ . '/../../logs/ayuda_usage.log';
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $fecha_limite = date('Y-m-d', strtotime("-$dias_atras days"));
            
            $usuarios = [];
            $paginas = [];
            
            foreach ($lines as $line) {
                if (strpos($line, $fecha_limite) !== false || strpos($line, date('Y-m-d')) !== false) {
                    $estadisticas['total_aperturas']++;
                    
                    // Extraer usuario y página del log
                    if (preg_match('/Usuario: ([^-]+)/', $line, $matches)) {
                        $usuarios[] = trim($matches[1]);
                    }
                    if (preg_match('/Página: ([^-]+)/', $line, $matches)) {
                        $pagina = trim($matches[1]);
                        $paginas[$pagina] = ($paginas[$pagina] ?? 0) + 1;
                    }
                }
            }
            
            $estadisticas['usuarios_unicos'] = count(array_unique($usuarios));
            arsort($paginas);
            $estadisticas['paginas_mas_consultadas'] = array_slice($paginas, 0, 10, true);
        }
        
        return $estadisticas;
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas de ayuda: " . $e->getMessage());
        return [
            'error' => 'No se pudieron obtener las estadísticas',
            'total_aperturas' => 0,
            'usuarios_unicos' => 0,
            'paginas_mas_consultadas' => [],
            'periodo' => $dias_atras . ' días'
        ];
    }
}

/**
 * Obtener configuración personalizada para la ayuda
 * @return array Configuración de la ayuda
 */
function obtener_config_ayuda() {
    return [
        'mostrar_boton' => usuario_puede_ver_ayuda(),
        'posicion' => 'bottom-right', // top-right, top-left, bottom-right, bottom-left
        'color_primario' => '#007bff',
        'mostrar_animaciones' => true,
        'recordar_ultima_pestana' => true,
        'expandir_primera_seccion' => true,
        'navegacion_teclado' => true,
        'idioma' => 'es',
        'version_ayuda' => '2.0',
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG, // Para WordPress
        'analytics_enabled' => true
    ];
}

/**
 * Generar el JavaScript de configuración personalizada
 * @return string JavaScript con la configuración
 */
function generar_config_js_ayuda() {
    $config = obtener_config_ayuda();
    ob_start();
    ?>
    <script type="text/javascript">
    // Configuración del sistema de ayuda
    window.helpConfig = <?php echo json_encode($config, JSON_UNESCAPED_UNICODE); ?>;
    
    // Aplicar configuración al sistema de ayuda cuando esté listo
    document.addEventListener('DOMContentLoaded', function() {
        if (window.HelpButton && window.helpConfig) {
            // Aplicar configuración
            Object.assign(window.HelpButton.config, {
                autoExpandFirstSection: window.helpConfig.expandir_primera_seccion,
                rememberLastTab: window.helpConfig.recordar_ultima_pestana,
                enableKeyboardNavigation: window.helpConfig.navegacion_teclado
            });
            
            // Aplicar estilos personalizados si es necesario
            if (window.helpConfig.color_primario !== '#007bff') {
                const style = document.createElement('style');
                style.textContent = `
                    .help-button { 
                        background: linear-gradient(135deg, ${window.helpConfig.color_primario}, ${adjustColor(window.helpConfig.color_primario, -20)}) !important; 
                    }
                    .help-modal-header { 
                        background: linear-gradient(135deg, ${window.helpConfig.color_primario}, ${adjustColor(window.helpConfig.color_primario, -20)}) !important; 
                    }
                    .help-tab.active { 
                        color: ${window.helpConfig.color_primario} !important; 
                        border-bottom-color: ${window.helpConfig.color_primario} !important; 
                    }
                `;
                document.head.appendChild(style);
            }
            
            console.log('Configuración de ayuda aplicada:', window.helpConfig);
        }
    });
    
    // Función auxiliar para ajustar colores
    function adjustColor(color, percent) {
        const num = parseInt(color.replace("#",""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 +
            (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255))
            .toString(16).slice(1);
    }
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Función para obtener conexión a base de datos (ejemplo)
 * Debes implementar esta función según tu sistema
 */
function obtener_conexion_bd() {
    // Ejemplo usando PDO
    /*
    try {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'tu_base_datos';
        $username = $_ENV['DB_USER'] ?? 'usuario';
        $password = $_ENV['DB_PASS'] ?? 'contraseña';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión a BD: " . $e->getMessage());
        throw new Exception("Error de conexión a la base de datos");
    }
    */
    
    // Si usas el sistema de conexión existente del proyecto
    require_once __DIR__ . '/../Class/Conexion.php';
    $conexion = new Conexion();
    return $conexion->conectar('central');
}

/**
 * Crear tabla de logs de ayuda si no existe
 */
function crear_tabla_logs_ayuda() {
    try {
        $conn = obtener_conexion_bd();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS logs_ayuda (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id VARCHAR(50) NOT NULL,
            pagina VARCHAR(100) NOT NULL,
            fecha_acceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            sesion_id VARCHAR(100),
            INDEX idx_usuario (usuario_id),
            INDEX idx_pagina (pagina),
            INDEX idx_fecha (fecha_acceso)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $conn->exec($sql);
        return true;
        
    } catch (Exception $e) {
        error_log("Error creando tabla logs_ayuda: " . $e->getMessage());
        return false;
    }
}

/**
 * Función de utilidad para mostrar información de debug
 */
function debug_sistema_ayuda() {
    if (!obtener_config_ayuda()['debug_mode']) {
        return '';
    }
    
    ob_start();
    ?>
    <script>
    console.log('=== DEBUG SISTEMA DE AYUDA ===');
    console.log('Config:', window.helpConfig);
    console.log('Usuario puede ver ayuda:', <?php echo json_encode(usuario_puede_ver_ayuda()); ?>);
    console.log('Sesión activa:', <?php echo json_encode(session_status() === PHP_SESSION_ACTIVE); ?>);
    
    // Mostrar diagnósticos cuando el sistema esté listo
    setTimeout(() => {
        if (window.HelpSystem) {
            console.log('Diagnósticos del sistema:', window.HelpSystem.diagnostics());
        }
    }, 1000);
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Función principal para incluir todo lo necesario de la ayuda
 * Esta es la función más fácil de usar - incluye todo automáticamente
 */
function incluir_sistema_ayuda_completo($mostrar_debug = false) {
    // Solo incluir si el usuario tiene permisos
    if (!usuario_puede_ver_ayuda()) {
        return;
    }
    
    // Incluir archivos CSS y JS
    incluir_archivos_ayuda();
    
    // Incluir configuración JavaScript
    echo generar_config_js_ayuda();
    
    // Incluir debug si está activado
    if ($mostrar_debug) {
        echo debug_sistema_ayuda();
    }
    
    // Registrar que se cargó la ayuda (opcional)
    if (obtener_config_ayuda()['analytics_enabled']) {
        // Este registro se puede hacer de forma asíncrona
        register_shutdown_function(function() {
            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
            $pagina = basename($_SERVER['PHP_SELF'], '.php');
            registrar_uso_ayuda($usuario_id, 'carga_' . $pagina);
        });
    }
}

/**
 * Shortcode para mostrar estadísticas de ayuda (si usas WordPress)
 */
function shortcode_estadisticas_ayuda($atts) {
    $atts = shortcode_atts([
        'dias' => 30,
        'formato' => 'tabla'
    ], $atts);
    
    if (!current_user_can('manage_options')) {
        return '<p>No tienes permisos para ver estas estadísticas.</p>';
    }
    
    $stats = obtener_estadisticas_ayuda($atts['dias']);
    
    ob_start();
    ?>
    <div class="estadisticas-ayuda">
        <h3>Estadísticas de Uso de Ayuda - Últimos <?php echo $atts['dias']; ?> días</h3>
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <div class="stat-box" style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                <h4 style="margin: 0 0 10px 0; color: #007bff;">Total de Aperturas</h4>
                <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo number_format($stats['total_aperturas']); ?></p>
            </div>
            <div class="stat-box" style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                <h4 style="margin: 0 0 10px 0; color: #28a745;">Usuarios Únicos</h4>
                <p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo number_format($stats['usuarios_unicos']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($stats['paginas_mas_consultadas'])): ?>
        <h4>Páginas Más Consultadas</h4>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Página</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #dee2e6;">Consultas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['paginas_mas_consultadas'] as $pagina => $total): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo htmlspecialchars($pagina); ?></td>
                    <td style="padding: 10px; text-align: right; border: 1px solid #dee2e6;"><?php echo number_format($total); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Registrar shortcode si WordPress está disponible
if (function_exists('add_shortcode')) {
    add_shortcode('estadisticas_ayuda', 'shortcode_estadisticas_ayuda');
}

?>