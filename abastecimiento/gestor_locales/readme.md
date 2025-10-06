# Gestión de M² de Locales

Esta aplicación web interna permite gestionar de forma centralizada las **medidas en metros cuadrados (M²)** de cada sucursal. Registrar las dimensiones de las áreas de Venta, Depósito y Baulera es fundamental para la planificación y optimización del espacio.

La aplicación está construida en PHP siguiendo una estructura inspirada en el patrón MVC (Modelo-Vista-Controlador).

## ✨ Características

*   **Interfaz Clara**: Diseño limpio y responsivo basado en Bootstrap 4.
*   **Gestión de Datos (CRUD)**: Funcionalidades para agregar, editar y eliminar las medidas de cada local a través de una interfaz modal.
*   **Filtrado por Sucursal**: Permite buscar y visualizar rápidamente un local específico.
*   **Importación Masiva**: Capacidad para crear y actualizar registros en lote subiendo un archivo Excel.
*   **Exportación de Plantillas**: Generación de plantillas Excel con una hoja de referencia de sucursales para facilitar la importación.
*   **Guía de Usuario Integrada**: Un modal de ayuda explica todas las funcionalidades de la aplicación.
*   **Feedback Visual**: Alertas de éxito/error y una pantalla de carga para la importación.

## 🔧 Stack Tecnológico

*   **Backend**: PHP 7.4+
*   **Frontend**: HTML5, CSS3, JavaScript (con jQuery)
*   **Base de Datos**: Microsoft SQL Server
*   **Servidor Web**: Apache (configurado a través de XAMPP)
*   **Dependencias (Composer)**: `phpoffice/phpspreadsheet` para la manipulación de archivos Excel.
*   **Librerías Frontend**: Bootstrap 4, Font Awesome 5.

## 🚀 Instalación y Configuración

Sigue estos pasos para configurar el proyecto en un entorno de desarrollo local.

### 1. Prerrequisitos

*   **XAMPP**: Instalado con Apache y PHP.
*   **Composer**: Necesario para instalar las dependencias de PHP.
*   **Driver SQL Server**: La extensión `php_pdo_sqlsrv` debe estar habilitada en `php.ini`.
*   **Acceso a la Base de Datos**: Credenciales y acceso de red al servidor de MS SQL Server.

### 2. Pasos de Instalación

1.  **Clonar/Copiar el Proyecto**: Asegúrate de que esta carpeta del proyecto (`gestor_locales`) esté dentro de tu `htdocs`, por ejemplo: `C:/xampp/htdocs/comercial/abastecimiento/gestor_locales/`.

2.  **Instalar Dependencias**: Abre una terminal en esta carpeta y ejecuta:
    ```bash
    composer install
    ```

3.  **Configurar el Entorno (`.env`)**:
    *   Esta aplicación utiliza un archivo `.env` centralizado, ubicado en `C:/xampp/htdocs/comercial/.env`.
    *   Asegúrate de que este archivo exista y contenga las variables para la base de datos `sistemas` (ej. `HOST_APPS`, `DATABASE_APPS`, `USER`, `PASS`).

4.  **Configurar Apache (`httpd.conf`)**:
    *   Para que las URLs limpias funcionen, la directiva `AllowOverride` debe estar en `All`.
    *   Abre `C:/xampp/apache/conf/httpd.conf`, busca `<Directory "C:/xampp/htdocs">` y cambia `AllowOverride None` a `AllowOverride All`.
    *   **Reinicia el servidor Apache** desde el panel de control de XAMPP.

5.  **Crear Tabla en la Base de Datos**:
    *   Conéctate al servidor SQL, selecciona la base de datos `sistemas` y ejecuta el siguiente script:
    ```sql
    CREATE TABLE FP_T_SUCURSALES_MEDIDAS (
        NRO_SUCURS INT PRIMARY KEY NOT NULL,
        M2_VENTA DECIMAL(10, 2) NULL,
        M2_DEPOSITO DECIMAL(10, 2) NULL,
        M2_BAULERA DECIMAL(10, 2) NULL,
        FECHA_REGISTRO DATETIME NULL DEFAULT GETDATE()
    );

## 📂 Estructura del Proyecto
/gestor_locales/
├── public/ <-- Raíz web pública (CSS, JS, index.php)
├── src/ <-- Código fuente de la aplicación (Controladores, Modelos, Vistas)
├── vendor/ <-- Dependencias de Composer
├── .htaccess <-- Reglas de reescritura de URL
└── classEnv.php <-- Clase para leer el archivo .env

## 🖥️ Acceso a la Aplicación

Una vez configurado, puedes acceder a la aplicación a través de la siguiente URL:

*   `http://localhost/comercial/abastecimiento/gestor_locales/`