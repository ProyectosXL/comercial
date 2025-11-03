<?php
/**
 * Configuración centralizada de conexiones de base de datos
 * para el sistema de franquicias - Usa la clase Conexion del proyecto
 */

class DatabaseConfig {
    
    private static $conexion = null;
    private static $envVars = null;
    
    /**
     * Inicializar la clase Conexion del proyecto
     */
    private static function initConexion() {
        if (self::$conexion === null) {
            // Usar la clase Conexion que está en /Class (fuera de franquicias)
            require_once __DIR__ . '/../../Class/Conexion.php';
            self::$conexion = new Conexion();
        }
        return self::$conexion;
    }
    
    /**
     * Obtener variables de entorno usando la clase DotEnv del proyecto
     * Detecta automáticamente si existe .env (localhost) o usa configuración del servidor
     */
    private static function getEnvConfig() {
        if (self::$envVars === null) {
            try {
                // Ruta al archivo .env (desde franquicias/class subir 2 niveles)
                $envPath = __DIR__ . '/../../.env';
                
                error_log("DatabaseConfig: Buscando .env en: $envPath");
                error_log("DatabaseConfig: file_exists: " . (file_exists($envPath) ? 'SI' : 'NO'));
                error_log("DatabaseConfig: is_readable: " . (is_readable($envPath) ? 'SI' : 'NO'));
                
                // DETECCIÓN AUTOMÁTICA: verificar si existe .env
                if (file_exists($envPath)) {
                    // LOCALHOST/DESARROLLO/PRODUCCIÓN: Cargar desde .env
                    $envPathReal = realpath($envPath);
                    error_log("DatabaseConfig: ✅ Detectado .env en: $envPathReal - cargando configuración");
                    
                    // Cargar el .env directamente sin usar listVars()
                    if (!is_readable($envPath)) {
                        error_log("DatabaseConfig: ❌ ERROR - Archivo .env existe pero NO es legible");
                        throw new Exception("El archivo .env no es legible: $envPath");
                    }
                    
                    // Leer el archivo .env
                    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    error_log("DatabaseConfig: Archivo .env tiene " . count($lines) . " líneas");
                    
                    foreach ($lines as $line) {
                        if (strpos(trim($line), '#') === 0) {
                            continue; // Saltar comentarios
                        }
                        
                        if (strpos($line, '=') === false) {
                            continue; // Saltar líneas sin =
                        }
                        
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        
                        // Establecer en entorno
                        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                            putenv(sprintf('%s=%s', $name, $value));
                            $_ENV[$name] = $value;
                            $_SERVER[$name] = $value;
                        }
                    }
                    
                    // Ahora leer las variables del entorno
                    self::$envVars = [
                        'HOST_CENTRAL' => getenv('HOST_CENTRAL'),
                        'DATABASE_CENTRAL' => getenv('DATABASE_CENTRAL'),
                        'HOST_APPS' => getenv('HOST_APPS'),
                        'DATABASE_APPS' => getenv('DATABASE_APPS'),
                        'USER' => getenv('USER'),
                        'PASS' => getenv('PASS'),
                        'CHARACTER' => getenv('CHARACTER'),
                        'ENV' => getenv('ENV')
                    ];
                    
                    error_log("DatabaseConfig: ✅ Variables cargadas - HOST_CENTRAL: " . self::$envVars['HOST_CENTRAL'] . ", HOST_APPS: " . self::$envVars['HOST_APPS']);
                    
                    // Validar que se cargaron valores
                    if (empty(self::$envVars['HOST_CENTRAL']) || empty(self::$envVars['HOST_APPS'])) {
                        error_log("DatabaseConfig: ⚠️ ADVERTENCIA - Algunas variables están vacías");
                        error_log("DatabaseConfig: HOST_CENTRAL = '" . self::$envVars['HOST_CENTRAL'] . "'");
                        error_log("DatabaseConfig: HOST_APPS = '" . self::$envVars['HOST_APPS'] . "'");
                    }
                } else {
                    // SIN .ENV: Esto NO debería ocurrir en producción
                    // Si llegamos aquí, hay un problema de configuración del servidor
                    error_log("❌ CRÍTICO: Archivo .env NO encontrado en: " . __DIR__ . '/../../.env');
                    error_log("❌ La aplicación requiere el archivo .env para funcionar correctamente");
                    
                    throw new Exception(
                        "Configuración incompleta: Se requiere el archivo .env en la raíz del proyecto. " .
                        "Por favor contacte al administrador del sistema."
                    );
                }
                
            } catch (Exception $e) {
                error_log("DatabaseConfig ERROR: " . $e->getMessage());
                throw new Exception("No se pudo cargar la configuración de base de datos: " . $e->getMessage());
            }
        }
        return self::$envVars;
    }
    
    private static function getLakerSAConfig() {
        $config = self::getEnvConfig();
        return [
            'driver' => 'sqlsrv',
            'server' => $config['HOST_CENTRAL'],
            'database' => $config['DATABASE_CENTRAL'],
            'username' => $config['USER'],
            'password' => $config['PASS'],
            'charset' => 'utf8'
        ];
    }
    
    private static function getSistemasConfig() {
        $config = self::getEnvConfig();
        return [
            'driver' => 'sqlsrv',
            'server' => $config['HOST_APPS'],
            'database' => $config['DATABASE_APPS'],
            'username' => $config['USER'],
            'password' => $config['PASS'],
            'charset' => 'utf8'
        ];
    }
    
    public static function conectarLakerSA() {
        try {
            // Obtener configuración
            $config = self::getLakerSAConfig();
            
            // Logging detallado para debug
            error_log("DEBUG DB LAKER_SA:");
            error_log("  - Servidor: " . $config['server']);
            error_log("  - Base de datos: " . $config['database']);
            error_log("  - Usuario: " . $config['username']);
            
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("✅ Conexión exitosa a LAKER_SA");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("❌ Error conectando a LAKER_SA: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function conectarSistemas() {
        try {
            $config = self::getSistemasConfig();
            
            // Logging detallado para debug
            error_log("DEBUG DB SISTEMAS:");
            error_log("  - Servidor: " . $config['server']);
            error_log("  - Base de datos: " . $config['database']);
            error_log("  - Usuario: " . $config['username']);
            
            // Asegurar que tenemos los datos necesarios
            if (empty($config['database'])) {
                // Fallback a 'sistemas' si no se carga desde .env
                $config['database'] = 'sistemas';
                error_log("⚠️ DATABASE_APPS no configurado, usando 'sistemas' por defecto");
            }
            
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // **FORZAR USO DE LA BASE DE DATOS CORRECTA**
            $pdo->exec("USE [{$config['database']}]");
            
            // Verificar que estamos en la base correcta
            $stmt = $pdo->query("SELECT @@SERVERNAME as servidor, DB_NAME() as db_actual");
            $dbActual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dbActual['db_actual'] !== $config['database']) {
                error_log("❌ Se esperaba '{$config['database']}' pero está en '{$dbActual['db_actual']}'");
                throw new Exception("No se pudo cambiar a la base de datos correcta");
            }
            
            error_log("✅ Conexión exitosa a SISTEMAS - Servidor: {$dbActual['servidor']}, DB: {$dbActual['db_actual']}");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("❌ Error conectando a SISTEMAS: " . $e->getMessage());
            throw new Exception("No se pudo establecer conexión con base de datos APPS: " . $e->getMessage());
        }
    }
    
    public static function obtenerConfigLakerSA() {
        try {
            $pdo = self::conectarLakerSA();
            $pdo = null; // Cerrar conexión de prueba
            
            // Si llegamos aquí, la conexión funcionó
            // Retornar la configuración que funcionó (esto es solo para información)
            return self::getLakerSAConfig();
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
