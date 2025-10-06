<?php
// public/index.php
session_start();
require_once '../vendor/autoload.php';
require_once '../src/core/Database.php';
require_once '../src/Controllers/StockController.php';

$pdo = Database::connect();
$controller = new StockController($pdo);

// Enrutador simple
$route = $_GET['route'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['excel_file'])) {
        $controller->handleImport();
    } else {
        $controller->handlePost();
    }
} elseif ($route === 'export-template') {
    $controller->exportTemplate();
} else {
    $controller->index();
}