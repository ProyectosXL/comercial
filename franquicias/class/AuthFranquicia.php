<?php
/**
 * Clase para manejar la autenticación de usuarios de franquicia
 * desde la base de datos LAKER_SA - VERSIÓN CORREGIDA
 */
class AuthFranquicia {
    private $conexion_laker;
    private $conexion_sistemas;
    
    public function __construct() {
        $this->conectarLaker();
        $this->conectarSistemas();
    }
    
    private function conectarLaker() {
        try {
            $this->conexion_laker = new PDO(
                "sqlsrv:server=SERVIDOR;Database=LAKER_SA", 
                "sa", 
                "Axoft1988"
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
                "sqlsrv:server=192.168.0.143;Database=sistemas", 
                "sa", 
                "Axoft1988"
            );
            $this->conexion_sistemas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("DEBUG AUTH: Conexión a sistemas exitosa");
        } catch (Exception $e) {
            error_log("ERROR AUTH: No se pudo conectar a sistemas: " . $e->getMessage());
            throw new Exception("Error conectando a sistemas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener todos los usuarios de franquicia disponibles
     */
    public function obtenerUsuariosFranquicia() {
        try {
            $stmt = $this->conexion_laker->query(
                "SELECT NOMBRE, PASS FROM SOF_USUARIOS WHERE TIPO = 'FRANQUICIA' ORDER BY NOMBRE"
            );
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG AUTH: Obtenidos " . count($usuarios) . " usuarios de franquicia");
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error obteniendo usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Valida las credenciales del usuario contra LAKER_SA
     */
    public function validarCredenciales($usuario, $password) {
        try {
            $stmt = $this->conexion_laker->prepare(
                "SELECT NOMBRE, PASS FROM SOF_USUARIOS WHERE TIPO = 'FRANQUICIA' AND NOMBRE = ?"
            );
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("DEBUG AUTH: Validando credenciales para usuario: $usuario");
            
            if ($user) {
                error_log("DEBUG AUTH: Usuario encontrado, verificando contraseña");
                if ($user['PASS'] === $password) {
                    error_log("DEBUG AUTH: Credenciales válidas para: $usuario");
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
     * Obtiene o crea la franquicia en la base sistemas
     */
    public function obtenerFranquiciaId($nombreUsuario) {
        try {
            // Primero buscar si ya existe
            $stmt = $this->conexion_sistemas->prepare(
                "SELECT id FROM franquicias WHERE codigo_usuario = ?"
            );
            $stmt->execute([$nombreUsuario]);
            $franquicia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($franquicia) {
                error_log("DEBUG AUTH: Franquicia existente encontrada para $nombreUsuario, ID: " . $franquicia['id']);
                return $franquicia['id'];
            }
            
            // Si no existe, crearla
            $nombreFranquicia = $this->generarNombreFranquicia($nombreUsuario);
            
            $stmt = $this->conexion_sistemas->prepare(
                "INSERT INTO franquicias (nombre, codigo_usuario, activa, fecha_registro) 
                 VALUES (?, ?, 1, GETDATE())"
            );
            $stmt->execute([$nombreFranquicia, $nombreUsuario]);
            
            // Obtener el ID recién insertado
            $stmt = $this->conexion_sistemas->prepare(
                "SELECT SCOPE_IDENTITY() as new_id"
            );
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $newId = $result['new_id'];
            
            error_log("DEBUG AUTH: Nueva franquicia creada para $nombreUsuario, ID: $newId");
            return $newId;
            
        } catch (Exception $e) {
            error_log("Error obteniendo/creando franquicia: " . $e->getMessage());
            throw $e;
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
            $franquiciaId = $this->obtenerFranquiciaId($usuario);
            
            // Incluir configuración de sesiones
            require_once __DIR__ . '/../config_sesiones_fixed.php';
            
            $_SESSION['usuario_franquicia'] = $usuario;
            $_SESSION['franquicia_id'] = $franquiciaId;
            $_SESSION['autenticado'] = true;
            $_SESSION['timestamp_login'] = time();
            
            // Log del login exitoso
            error_log("DEBUG AUTH: Login exitoso para $usuario, franquicia_id: $franquiciaId");
            
            return [
                'success' => true,
                'usuario' => $usuario,
                'franquicia_id' => $franquiciaId
            ];
            
        } catch (Exception $e) {
            error_log("Error iniciando sesión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay una sesión activa válida
     */
    public static function verificarSesion() {
        // Incluir configuración de sesiones
        require_once __DIR__ . '/../config_sesiones_fixed.php';
        
        $valida = isset($_SESSION['autenticado']) && 
               $_SESSION['autenticado'] === true && 
               isset($_SESSION['franquicia_id']);
               
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