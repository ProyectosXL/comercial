<?php
// public/index.php - Front Controller
session_start();

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar las variables de entorno
require_once __DIR__ . '/../classEnv.php';
Env::load(__DIR__ . '/../../../.env');

// Cargar la configuración de base de datos
require_once __DIR__ . '/../src/Config/Database.php';

// Sistema de Enrutamiento
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'home';
$url = filter_var($url, FILTER_SANITIZE_URL);
$params = explode('/', $url);

$controllerName = !empty($params[0]) ? ucwords($params[0]) . 'Controller' : 'HomeController';
$methodName = isset($params[1]) ? $params[1] : 'index';

$controllerFile = __DIR__ . '/../src/Controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;

    if (class_exists($controllerName)) {
        $controller = new $controllerName();

        if (method_exists($controller, $methodName)) {
            unset($params[0], $params[1]);
            call_user_func_array([$controller, $methodName], array_values($params));
        } else {
            die("Error 404: La acción solicitada no fue encontrada.");
        }
    } else {
        die("Error 404: El controlador solicitado no fue encontrado.");
    }
} else {
    require_once __DIR__ . '/../src/Controllers/HomeController.php';
    $controller = new HomeController();
    $controller->index();
}
