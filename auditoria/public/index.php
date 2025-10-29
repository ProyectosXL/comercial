<?php
// public/index.php
session_start();
ini_set('display_errors', 1);

require_once __DIR__ . '/../classEnv.php';

Env::load(__DIR__ . '/../../.env2'); 

require_once __DIR__ . '/../src/Config/Database.php';

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
            echo "Error: Método no encontrado.";
        }
    } else {
        echo "Error: Clase controladora no encontrada.";
    }
} else {
    require_once __DIR__ . '/../src/Controllers/HomeController.php';
    $controller = new HomeController();
    $controller->index();
}