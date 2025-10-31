<?php
session_start();
session_destroy();

// Opcional: Redirigir a la página principal de XL Gestión o mostrar un mensaje.
echo "Sesión cerrada correctamente. Puede cerrar esta ventana.";
// header('Location: https://app.xl.com.ar/'); // Descomenta esta línea para redirigir
exit();