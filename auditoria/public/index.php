<?php
// 1. Iniciar la sesión. Esto es crucial y debe ser la primera línea de código.
session_start();

// AÑADE ESTA LÍNEA AQUÍ
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Cargar las dependencias y la configuración del entorno.
// Carga la clase para leer el archivo .env
require_once __DIR__ . '/../classEnv.php';
// Lee las variables del archivo .env2 y las pone a disposición de la aplicación.
Env::load(__DIR__ . '/../../../.env');
// Carga la clase para gestionar las conexiones a la base de datos.
require_once __DIR__ . '/../src/Config/Database.php';

// 3. Sistema de Enrutamiento (Router)
// Este bloque de código analiza la URL para decidir qué controlador y qué método ejecutar.

// Obtiene la URL desde el parámetro 'url' (ej: 'sucursal/pendientes').
// Si no hay parámetro, el valor por defecto es 'home'.
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'home';
// Limpia la URL de caracteres no válidos.
$url = filter_var($url, FILTER_SANITIZE_URL);
// Divide la URL en partes (ej: ['sucursal', 'pendientes']).
$params = explode('/', $url);

// Define el nombre del controlador. El primer parámetro de la URL, o 'HomeController' por defecto.
// Ej: 'sucursal' se convierte en 'SucursalController'.
$controllerName = !empty($params[0]) ? ucwords($params[0]) . 'Controller' : 'HomeController';

// Define el nombre del método. El segundo parámetro de la URL, o 'index' por defecto.
// Ej: 'pendientes'.
$methodName = isset($params[1]) ? $params[1] : 'index';

// Construye la ruta completa al archivo del controlador.
$controllerFile = __DIR__ . '/../src/Controllers/' . $controllerName . '.php';

// 4. Ejecución del Controlador y Método

// Verifica si el archivo del controlador existe.
if (file_exists($controllerFile)) {
    require_once $controllerFile;

    // Verifica si la clase del controlador existe dentro del archivo.
    if (class_exists($controllerName)) {
        // Crea una instancia del controlador.
        // NOTA: Esto activa automáticamente el chequeo de seguridad en BaseController.
        $controller = new $controllerName();

        // Verifica si el método existe en el controlador.
        if (method_exists($controller, $methodName)) {
            // Limpia los dos primeros parámetros de la URL (que ya usamos para el controlador y el método).
            unset($params[0], $params[1]);

            // Llama al método y le pasa los parámetros restantes.
            // Ej: para 'dashboard/detalle/3', llama a $controller->detalle(3).
            call_user_func_array([$controller, $methodName], array_values($params));
        } else {
            // Si el método no existe, muestra un error.
            die("Error 404: La acción solicitada no fue encontrada.");
        }
    } else {
        // Si la clase no existe, muestra un error.
        die("Error 404: El controlador solicitado no fue encontrado.");
    }
} else {
    // Si el archivo del controlador no existe, asumimos que es la página de inicio.
    require_once __DIR__ . '/../src/Controllers/HomeController.php';
    $controller = new HomeController();
    $controller->index();
}