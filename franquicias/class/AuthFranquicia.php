<?php
/**
 * Clase para manejar la autenticación de usuarios de franquicia
 * desde la base de datos LAKER_SA - Usa configuración del .env principal
 */

class AuthFranquicia {
    private $conexion_laker;
    private $conexion_sistemas;
    private $envVars;
    
    public function __construct() {
        $this->cargarConfiguracion();
        $this->conectarLaker();
        $this->conectarSistemas();
    }
    
    private function cargarConfiguracion() {
        try {
            // Usar ruta relativa desde franquicias/class/ hacia la raíz del proyecto
            $envPath = __DIR__ . '/../../.env';
            
            // Resolver la ruta completa para debugging
            $envPathResolved = realpath($envPath);
            if (!$envPathResolved) {
                throw new Exception("No se pudo resolver la ruta del archivo .env desde: " . $envPath);
            }
            
            if (!file_exists($envPathResolved)) {
                throw new Exception("Archivo .env no encontrado en: " . $envPathResolved);
            }
            
            $this->envVars = $this->parseEnvFile($envPathResolved);
        } catch (Exception $e) {
            error_log("Error cargando .env en AuthFranquicia: " . $e->getMessage());
            throw new Exception("No se pudo cargar la configuración del .env principal: " . $e->getMessage());
        }
    }
    
    /**
     * Parse del archivo .env de forma manual
     */
    private function parseEnvFile($filePath) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $vars = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Saltar comentarios
            if (strpos($line, '#') === 0 || empty($line)) {
                continue;
            }
            
            // Separar clave=valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $vars[trim($key)] = trim($value);
            }
        }
        
        return $vars;
    }
    
    private function conectarLaker() {
        try {
            $this->conexion_laker = new PDO(
                "sqlsrv:server={$this->envVars['HOST_CENTRAL']};Database={$this->envVars['DATABASE_CENTRAL']}", 
                $this->envVars['USER'], 
                $this->envVars['PASS']
            );
            $this->conexion_laker->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("DEBUG AUTH: Conexión a LAKER_SA exitosa");
        } catch (Exception $e) {
            error_log("ERROR AUTH: No se pudo conectar a LAKER_SA: " . $e->getMessage());
            throw new Exception("Error conectando a LAKER_SA: " . $e->getMessage());
        }
    }
    
    private function conectarSistemas() {
        try {
            $this->conexion_sistemas = new PDO(
                "sqlsrv:server={$this->envVars['HOST_APPS']};Database={$this->envVars['DATABASE_APPS']}", 
                $this->envVars['USER'], 
                $this->envVars['PASS']
            );
            $this->conexion_sistemas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("DEBUG AUTH: Conexión a sistemas exitosa");
        } catch (Exception $e) {
            error_log("ERROR AUTH: No se pudo conectar a sistemas: " . $e->getMessage());
            throw new Exception("Error conectando a sistemas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener todos los usuarios de franquicia y supervisión disponibles
     */
    public function obtenerUsuariosFranquicia() {
        try {
            $stmt = $this->conexion_laker->query(
                "SELECT NOMBRE, PASS, TIPO FROM SOF_USUARIOS WHERE (TIPO = 'FRANQUICIA' OR TIPO = 'SUPERVISION') ORDER BY TIPO, NOMBRE"
            );
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG AUTH: Obtenidos " . count($usuarios) . " usuarios (franquicia + supervisión)");
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error obteniendo usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Valida las credenciales del usuario contra LAKER_SA
     * Ahora incluye usuarios FRANQUICIA y SUPERVISION
     */
    public function validarCredenciales($usuario, $password) {
        try {
            $stmt = $this->conexion_laker->prepare(
                "SELECT NOMBRE, PASS, TIPO FROM SOF_USUARIOS WHERE (TIPO = 'FRANQUICIA' OR TIPO = 'SUPERVISION') AND NOMBRE = ?"
            );
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG AUTH: Validando credenciales para usuario: $usuario");
            
            if ($user) {
                error_log("DEBUG AUTH: Usuario encontrado (Tipo: " . $user['TIPO'] . "), verificando contraseña");
                if ($user['PASS'] === $password) {
                    error_log("DEBUG AUTH: Credenciales válidas para: $usuario (Tipo: " . $user['TIPO'] . ")");
                    return true;
                } else {
                    error_log("DEBUG AUTH: Contraseña incorrecta para: $usuario");
                }
            } else {
                error_log("DEBUG AUTH: Usuario no encontrado: $usuario");
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error validando credenciales: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el código de franquicia (usuario) directamente para compatibilidad
     */
    public function obtenerFranquiciaId($nombreUsuario) {
        try {
            // Buscar el ID numérico de la franquicia (NRO_SUCURS)
            $stmt = $this->conexion_laker->prepare(
                "SELECT NRO_SUCURS, NOMBRE, DESCRIPCION FROM SOF_USUARIOS WHERE TIPO = 'FRANQUICIA' AND NOMBRE = ?"
            );
            $stmt->execute([$nombreUsuario]);
            $franquicia = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($franquicia && isset($franquicia['NRO_SUCURS'])) {
                error_log("DEBUG AUTH: Franquicia encontrada en SOF_USUARIOS: " . $franquicia['NOMBRE'] . " - " . $franquicia['DESCRIPCION'] . " - NRO_SUCURS: " . $franquicia['NRO_SUCURS']);
                return $franquicia['NRO_SUCURS'];
            } else {
                error_log("DEBUG AUTH: Franquicia no encontrada en SOF_USUARIOS para: $nombreUsuario");
                throw new Exception("Franquicia no encontrada en el sistema");
            }

        } catch (Exception $e) {
            error_log("Error obteniendo NRO_SUCURS de franquicia: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la descripción/nombre completo de la franquicia
     */
    public function obtenerNombreFranquicia($nombreUsuario) {
        try {
            $stmt = $this->conexion_laker->prepare(
                "SELECT DESCRIPCION FROM SOF_USUARIOS WHERE TIPO = 'FRANQUICIA' AND NOMBRE = ?"
            );
            $stmt->execute([$nombreUsuario]);
            $franquicia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($franquicia && !empty($franquicia['DESCRIPCION'])) {
                return $franquicia['DESCRIPCION'];
            } else {
                // Si no hay descripción, devolver el nombre del usuario
                return $nombreUsuario;
            }
            
        } catch (Exception $e) {
            error_log("Error obteniendo nombre de franquicia: " . $e->getMessage());
            return $nombreUsuario; // Fallback al usuario
        }
    }

    /**
     * Obtiene el rol del usuario basado en su tipo en la base de datos
     */
    public function obtenerRolUsuario($usuario) {
        try {
            $stmt = $this->conexion_laker->prepare(
                "SELECT TIPO FROM SOF_USUARIOS WHERE (TIPO = 'FRANQUICIA' OR TIPO = 'SUPERVISION') AND NOMBRE = ?"
            );
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $rol = strtolower($user['TIPO']); // 'franquicia' o 'supervision'
                error_log("DEBUG AUTH: Rol determinado para $usuario: $rol");
                return $rol;
            }
            
            error_log("DEBUG AUTH: No se pudo determinar rol para: $usuario");
            return null;
        } catch (Exception $e) {
            error_log("Error obteniendo rol de usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si el usuario tiene acceso a una funcionalidad específica según su rol
     */
    public function tieneAccesoFuncionalidad($funcionalidad) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $rol = $_SESSION['user_role'] ?? null;
        
        switch ($funcionalidad) {
            case 'stock_loading':
            case 'historial':
                // Solo usuarios franquicia pueden cargar stock y ver historial
                return $rol === 'franquicia';
                
            case 'reports':
                // Solo usuarios supervisión pueden ver reportes
                return $rol === 'supervision';
                
            case 'article_consultation':
                // Nadie puede consultar artículos (funcionalidad removida)
                return false;
                
            default:
                return false;
        }
    }

    /**
     * Genera un nombre amigable para la franquicia basado en el código de usuario
     */
    private function generarNombreFranquicia($codigoUsuario) {
        // Mapeo completo de códigos conocidos a nombres más amigables
        $mapeoNombres = [
            'xlcentro' => 'XL Centro',
            'xlbahia' => 'XL Bahía Blanca',
            'xlbaulera' => 'XL Baulera',
            'xlberazategui' => 'XL Berazategui',
            'xlbrown' => 'XL Brown',
            'xlcastelar' => 'XL Castelar',
            'xlcentenario' => 'XL Centenario',
            'xldino' => 'XL Dino',
            'xlituzaingo' => 'XL Ituzaingo',
            'xljujuy' => 'XL Jujuy',
            'xlpampa' => 'XL La Pampa',
            'xlribera' => 'XL Ribera',
            'xllomas' => 'XL Lomas',
            'xlmartinez' => 'XL Martinez',
            'xlmaschwitz' => 'XL Maschwitz',
            'xlconstitucion' => 'XL Constitución',
            'xlmerlo' => 'XL Merlo',
            'xlcordoba' => 'XL Córdoba',
            'xlplazaoeste' => 'XL Plaza Oeste',
            'xlneuquen' => 'XL Neuquén',
            'xlposadas' => 'XL Posadas',
            'xlsalta' => 'XL Salta',
            'xlnoa' => 'XL NOA',
            'xlsaltap' => 'XL Salta Paseo',
            'xlsanisidro' => 'XL San Isidro',
            'xlsanjuan' => 'XL San Juan',
            'xlsanluis' => 'XL San Luis',
            'xlsanmiguel' => 'XL San Miguel',
            'xlsanfernando' => 'XL San Fernando',
            'xlsarmiento' => 'XL Sarmiento',
            'xlmalvinas' => 'XL Malvinas',
            'xltrelew' => 'XL Trelew',
            'xltucuman' => 'XL Tucumán',
            'xlvelez' => 'XL Vélez',
            'xlvillamaria' => 'XL Villa María',
            'xljockey' => 'XL Jockey',
            'xlsantafe' => 'XL Santa Fe',
            'xlcitybell' => 'XL City Bell',
            'xllaplata2' => 'XL La Plata 2',
            'xlconcordia' => 'XL Concordia',
            'xlzamora' => 'XL Zamora',
            'xllaplatac51' => 'XL La Plata C51',
            'xlquilmes' => 'XL Quilmes',
            'xlmendoza' => 'XL Mendoza',
            'xlsgoshopping' => 'XL SGO Shopping',
            'xlsgocentro' => 'XL SGO Centro',
            'xlramos' => 'XL Ramos',
            'xlushuaia' => 'XL Ushuaia',
            'xllaplata1' => 'XL La Plata 1',
            'xldevoto' => 'XL Devoto',
            'xlballester' => 'XL Ballester',
            'xlmoron3' => 'XL Morón 3',
            'xllaplatac9' => 'XL La Plata C9',
            'xlquilfact' => 'XL Quilmes Factory',
            'xlmdguemes' => 'XL MD Güemes',
            'xlpadua' => 'XL Padua',
            'xlayacucho' => 'XL Ayacucho',
            'xltucumancentro' => 'XL Tucumán Centro',
            'xlmadryn' => 'XL Puerto Madryn',
            'xlvillaurquiza' => 'XL Villa Urquiza',
            'xllanus' => 'XL Lanús',
            'xldelparque' => 'XL Del Parque',
            'xlramosmejia' => 'XL Ramos Mejía',
            'xlmoreno' => 'XL Moreno',
            'xlneuquencentro' => 'XL Neuquén Centro',
            'xlpalomar' => 'XL Palomar',
            'xlpalmares' => 'XL Palmares',
            'xlcabildo' => 'XL Cabildo',
            'xlmdpflorida' => 'XL MDP Florida',
            'xlmdpfloridaout' => 'XL MDP Florida Outlet',
            'xlliniers' => 'XL Liniers',
            'xlreconquista' => 'XL Reconquista',
            'xladrogue' => 'XL Adrogué',
            'xlarieta' => 'XL Arieta',
            'xlviedma' => 'XL Viedma',
            'xlcatamarca' => 'XL Catamarca'
        ];
        
        if (isset($mapeoNombres[$codigoUsuario])) {
            return $mapeoNombres[$codigoUsuario];
        }
        
        // Si no está en el mapeo, generar nombre a partir del código
        $nombre = str_replace('xl', 'XL ', $codigoUsuario);
        return ucwords($nombre);
    }
    
    /**
     * Inicia sesión para un usuario de franquicia
     */
    public function iniciarSesion($usuario, $password) {
        if (!$this->validarCredenciales($usuario, $password)) {
            error_log("DEBUG AUTH: Credenciales inválidas para: $usuario");
            return false;
        }
        
        try {
            // Obtener el rol del usuario
            $rol = $this->obtenerRolUsuario($usuario);
            if (!$rol) {
                error_log("DEBUG AUTH: No se pudo determinar el rol para: $usuario");
                return false;
            }
            
            // Incluir configuración de sesiones
            require_once __DIR__ . '/../config_sesiones_fixed.php';
            
            $_SESSION['usuario_franquicia'] = $usuario;
            $_SESSION['username'] = $usuario; // Compatibilidad para controladores que esperan 'username'
            $_SESSION['user_role'] = $rol; // Nuevo: rol del usuario
            $_SESSION['autenticado'] = true;
            $_SESSION['timestamp_login'] = time();
            
            // Solo para usuarios franquicia necesitamos franquicia_id
            if ($rol === 'franquicia') {
                $franquiciaId = $this->obtenerFranquiciaId($usuario);
                $nombreFranquicia = $this->generarNombreFranquicia($franquiciaId);
                $_SESSION['franquicia_id'] = $franquiciaId;
                $_SESSION['nombre_franquicia'] = $nombreFranquicia;
                $info_id = $franquiciaId;
            } else {
                $info_id = $usuario; // Para supervision usamos el usuario
            }
            
            // Log del login exitoso
            error_log("DEBUG AUTH: Login exitoso para $usuario, rol: $rol, info_id: $info_id");
            
            return [
                'success' => true,
                'usuario' => $usuario,
                'franquicia_id' => $info_id,
                'rol' => $rol
            ];
            
        } catch (Exception $e) {
            error_log("Error iniciando sesión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay una sesión activa válida (franquicia o supervisión)
     */
    public static function verificarSesion() {
        // Incluir configuración de sesiones
        require_once __DIR__ . '/../config_sesiones_fixed.php';
        
        $valida = isset($_SESSION['autenticado']) && 
               $_SESSION['autenticado'] === true && 
               isset($_SESSION['usuario_franquicia']) &&
               isset($_SESSION['user_role']);
               
        return $valida;
    }
    
    /**
     * Obtiene el ID de franquicia de la sesión actual
     */
    public static function obtenerFranquiciaIdSesion() {
        // Incluir configuración de sesiones
        require_once __DIR__ . '/../config_sesiones_fixed.php';
        
        return isset($_SESSION['franquicia_id']) ? $_SESSION['franquicia_id'] : null;
    }
    
    /**
     * Obtiene lista de franquicias autorizadas para acceso especial
     * JOIN entre SOF_USUARIOS.NRO_SUCURS y [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS.NRO_SUCURSAL
     * con filtro CANAL = 'FRANQUICIAS' AND HABILITADO = 1 AND NRO_SUC_MADRE IS NULL AND TANGO IS NULL
     */
    public function obtenerFranquiciasAutorizadas() {
        try {
            // Primero, verificar si las tablas existen y tienen datos
            error_log("DEBUG AUTH: Iniciando obtención de franquicias autorizadas");
            
            // Consulta más robusta con manejo de diferentes tipos de datos
            $sql = "
                SELECT DISTINCT 
                    u.NOMBRE AS codigo_usuario,
                    u.DESCRIPCION AS descripcion_usuario,
                    u.NRO_SUCURS AS nro_sucurs_usuario,
                    s.NRO_SUCURSAL AS nro_sucursal_tabla,
                    s.DESCRIPCION AS descripcion_sucursal,
                    s.CANAL,
                    s.HABILITADO,
                    s.NRO_SUC_MADRE,
                    s.TANGO
                FROM SOF_USUARIOS u
                INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s ON CAST(u.NRO_SUCURS AS VARCHAR) = CAST(s.NRO_SUCURSAL AS VARCHAR)
                WHERE u.TIPO = 'FRANQUICIA'
                AND UPPER(LTRIM(RTRIM(ISNULL(s.CANAL, '')))) = 'FRANQUICIAS'
                AND s.HABILITADO = 1
                AND s.NRO_SUC_MADRE IS NULL
                AND s.TANGO IS NULL
                ORDER BY u.NOMBRE
            ";
            
            error_log("DEBUG AUTH: Ejecutando consulta SQL para franquicias autorizadas");
            
            $stmt = $this->conexion_laker->query($sql);
            $franquicias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG AUTH: Consulta ejecutada. Resultados encontrados: " . count($franquicias));
            
            // Log detallado de los primeros resultados para debugging
            if (count($franquicias) > 0) {
                $primer_resultado = $franquicias[0];
                error_log("DEBUG AUTH: Primer resultado - Usuario: " . $primer_resultado['codigo_usuario'] . 
                         ", Canal: '" . $primer_resultado['CANAL'] . "'" . 
                         ", Habilitado: " . $primer_resultado['HABILITADO']);
            } else {
                error_log("DEBUG AUTH: No se encontraron resultados. Verificando con consulta de diagnóstico...");
                
                // Consulta de diagnóstico sin filtros para ver qué hay
                $sql_debug = "
                    SELECT COUNT(*) as total_usuarios
                    FROM SOF_USUARIOS 
                    WHERE TIPO = 'FRANQUICIA'
                ";
                $stmt_debug = $this->conexion_laker->query($sql_debug);
                $debug_result = $stmt_debug->fetch(PDO::FETCH_ASSOC);
                error_log("DEBUG AUTH: Total usuarios franquicia: " . $debug_result['total_usuarios']);
                
                // Verificar si existe la tabla [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS
                $sql_tabla = "SELECT COUNT(*) as total_sucursales FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS";
                $stmt_tabla = $this->conexion_laker->query($sql_tabla);
                $tabla_result = $stmt_tabla->fetch(PDO::FETCH_ASSOC);
                error_log("DEBUG AUTH: Total sucursales en [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS: " . $tabla_result['total_sucursales']);
            }
            
            return $franquicias;
            
        } catch (Exception $e) {
            error_log("Error obteniendo franquicias autorizadas: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Verifica si el usuario actual está autorizado para acceder a la página especial
     */
    public function tieneAccesoEspecial($nombreUsuario = null) {
        try {
            // Si no se proporciona usuario, usar el de la sesión actual
            if ($nombreUsuario === null) {
                $nombreUsuario = self::obtenerFranquiciaIdSesion();
            }
            
            if (!$nombreUsuario) {
                error_log("DEBUG AUTH: No se pudo obtener usuario para verificar acceso especial");
                return false;
            }
            
            $sql = "
                SELECT COUNT(*) as tiene_acceso
                FROM SOF_USUARIOS u
                INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s ON u.NRO_SUCURS = s.NRO_SUCURSAL
                WHERE u.TIPO = 'FRANQUICIA'
                AND u.NOMBRE = ?
                AND s.CANAL = 'FRANQUICIAS'
                AND s.HABILITADO = 1
                AND s.NRO_SUC_MADRE IS NULL
                AND s.TANGO IS NULL
            ";
            
            $stmt = $this->conexion_laker->prepare($sql);
            $stmt->execute([$nombreUsuario]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $tieneAcceso = $resultado['tiene_acceso'] > 0;
            
            error_log("DEBUG AUTH: Usuario $nombreUsuario " . ($tieneAcceso ? "SÍ" : "NO") . " tiene acceso especial");
            
            return $tieneAcceso;
            
        } catch (Exception $e) {
            error_log("Error verificando acceso especial: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método estático para verificar acceso especial desde cualquier lugar
     */
    public static function verificarAccesoEspecial() {
        try {
            // Incluir configuración de sesiones
            require_once __DIR__ . '/../config_sesiones_fixed.php';
            
            $auth = new self();
            // Obtener el nombre de usuario directamente de la sesión, no usar obtenerFranquiciaIdSesion()
            $usuarioFranquicia = $_SESSION['usuario_franquicia'] ?? $_SESSION['username'] ?? null;
            return $auth->tieneAccesoEspecial($usuarioFranquicia);
        } catch (Exception $e) {
            error_log("Error en verificación estática de acceso especial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cierra la sesión actual
     */
    public static function cerrarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_destroy();
    }
}
?>