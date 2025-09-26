# Sistema de Supervisión Comercial - Guía para AI

## Arquitectura General
- Sistema web PHP para control de gastos de supervisión comercial
- Base de datos SQL Server (conexión 'central')
- Frontend moderno con Bootstrap 5.1.3 y jQuery 3.6.0
- Módulos principales: gastos, presupuestos y premios

## Estructura del Proyecto y Responsabilidades
```
supervision/
├── Class/              # Clases de negocio y lógica principal
│   ├── Gasto.php      # Operaciones base de gastos (legacy)
│   ├── GastoNew.php   # Nueva implementación con validaciones
│   └── Email.php      # Servicio de notificaciones
├── Controller/         # Endpoints AJAX y procesamiento
├── js/                # Lógica frontend modular
└── [módulos]/         # Subsistemas específicos
    ├── presupuesto/   # Gestión de presupuestos y métricas
    └── premios/       # Sistema de premios (módulo independiente)
```

## Flujos de Datos Críticos

### Ciclo de Vida de Gastos
1. Carga inicial (cargaGastosNew.php)
   - Validación contra presupuesto semanal
   - Soporte dual tarjeta/efectivo
   - Adjuntos de comprobantes

2. Proceso de autorización (autorizarGastos.php)
   - Validación manual de supervisores
   - Notificaciones automáticas de excesos
   - Control de adelantos

3. Control y seguimiento (controlarGastos.php)
   - Métricas por supervisora
   - Estados: Pendiente → Autorizado/Rechazado → Controlado

## Patrones de Implementación

### Backend (PHP)
- Clases base extendidas: `Gasto` → `GastoNew`
- Transacciones SQL obligatorias para inserciones múltiples
- Validación de sesión en cada endpoint: `session_start()`
- Retorno estandarizado en JSON con estructura:
  ```json
  {
    "success": boolean,
    "message": string,
    "data": object|null,
    "debug": object|null  // Solo en desarrollo
  }
  ```

### Frontend (JavaScript)
- Módulos específicos por funcionalidad:
  - `cargaGastosNew.js`: Lógica de carga y validación
  - `verificacion-dinamica.js`: Validaciones en tiempo real
  - `mostrarNew.js`: UI y renderizado
- SweetAlert2 para notificaciones de usuario
- DataTables para grillas interactivas
- FancyBox 5.0 para visualización de comprobantes

## Debugging y Desarrollo

### Entorno Local
- XAMPP con PHP 7.4+
- SQL Server con base 'central'
- Logs en xampp/php/logs

### Puntos de Control
- Validar siempre respuesta de AJAX en Network
- Verificar transacciones en SQL Server
- Revisar logs PHP para errores de sesión

### Buenas Prácticas
- Mantener separación MVC
- Documentar cambios en validaciones
- Usar transacciones SQL
- Implementar manejo de errores consistente

## Integración con Otros Módulos

### Sistema de Presupuestos
- Validación cruzada via `Presupuesto.php`
- Dashboard de métricas en tiempo real
- Observaciones y ajustes temporales

### Sistema de Emails (PHPMailer)
- Notificaciones automáticas de:
  - Excesos de presupuesto
  - Rechazos de gastos
  - Autorizaciones pendientes