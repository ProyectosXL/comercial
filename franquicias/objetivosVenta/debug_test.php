<?php
// debug_test.php - Archivo temporal para diagnosticar problemas

// Habilitar reporte de errores completo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Debug Test - Objetivos Venta</h2>";
echo "<hr>";

// 1. Verificar que el archivo .env se puede cargar
echo "<h3>1. Test de .env</h3>";
try {
    // Verificar variables de entorno primero
    require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/classEnv.php';
    echo "✓ Clase DotEnv cargada correctamente<br>";
    
    $dotenv = new DotEnv($_SERVER['DOCUMENT_ROOT'] . '/comercial/.env');
    $vars = $dotenv->listVars();
    echo "✓ Variables de entorno cargadas<br>";
    echo "• HOST_CENTRAL: " . ($vars['HOST_CENTRAL'] ? 'Definido' : 'NO DEFINIDO') . "<br>";
    echo "• DATABASE_CENTRAL: " . ($vars['DATABASE_CENTRAL'] ? 'Definido' : 'NO DEFINIDO') . "<br>";
    echo "• USER: " . ($vars['USER'] ? 'Definido' : 'NO DEFINIDO') . "<br>";
    echo "• PASS: " . ($vars['PASS'] ? 'Definido' : 'NO DEFINIDO') . "<br>";
    
    require_once 'Class/objetivo.php';
    echo "✓ Clase Objetivo cargada correctamente<br>";
    
    $objetivo = new Objetivo();
    echo "✓ Instancia de Objetivo creada correctamente<br>";
} catch (Exception $e) {
    echo "✗ Error al cargar Objetivo: " . $e->getMessage() . "<br>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 2. Test de conexión a base de datos
echo "<h3>2. Test de Conexión DB</h3>";
try {
    require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
    echo "✓ Clase Conexion cargada<br>";
    
    $conexion = new Conexion();
    echo "✓ Instancia de Conexion creada<br>";
    
    // Probar conexión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Establecer entorno por defecto si no existe
    if (!isset($_SESSION['entorno'])) {
        $_SESSION['entorno'] = 'central';
        echo "• Estableciendo entorno por defecto: central<br>";
    }
    
    echo "• Entorno actual: " . ($_SESSION['entorno'] ?? 'NO DEFINIDO') . "<br>";
    
    $cid = $conexion->conectar('central');
    if ($cid) {
        echo "✓ Conexión a base de datos exitosa<br>";
    } else {
        echo "✗ Error de conexión a base de datos<br>";
        echo "Errores SQL: <pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
    }
} catch (Exception $e) {
    echo "✗ Error en conexión: " . $e->getMessage() . "<br>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 3. Test de carga de sucursales
echo "<h3>3. Test de Sucursales</h3>";
try {
    if (isset($objetivo)) {
        $sucursales = $objetivo->getSucursales();
        echo "✓ Método getSucursales() ejecutado<br>";
        echo "• Número de sucursales encontradas: " . count($sucursales) . "<br>";
        
        if (count($sucursales) > 0) {
            echo "• Primera sucursal: " . print_r($sucursales[0], true) . "<br>";
        } else {
            echo "• No se encontraron sucursales<br>";
        }
    } else {
        echo "✗ Objeto Objetivo no disponible<br>";
    }
} catch (Exception $e) {
    echo "✗ Error al cargar sucursales: " . $e->getMessage() . "<br>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 4. Test de carga de objetivos
echo "<h3>4. Test de Objetivos</h3>";
try {
    if (isset($objetivo)) {
        $objetivos = $objetivo->getObjetivos();
        echo "✓ Método getObjetivos() ejecutado<br>";
        echo "• Número de objetivos encontrados: " . count($objetivos) . "<br>";
        
        if (count($objetivos) > 0) {
            echo "• Primer objetivo: <pre>" . print_r($objetivos[0], true) . "</pre>";
        } else {
            echo "• No se encontraron objetivos<br>";
        }
    } else {
        echo "✗ Objeto Objetivo no disponible<br>";
    }
} catch (Exception $e) {
    echo "✗ Error al cargar objetivos: " . $e->getMessage() . "<br>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 5. Información del sistema
echo "<h3>5. Información del Sistema</h3>";
echo "• PHP Version: " . phpversion() . "<br>";
echo "• SQL Server Driver: " . (extension_loaded('sqlsrv') ? 'Instalado' : 'NO INSTALADO') . "<br>";
echo "• Sesión iniciada: " . (session_status() === PHP_SESSION_ACTIVE ? 'Sí' : 'No') . "<br>";
echo "• Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "• Archivo actual: " . __FILE__ . "<br>";

?>