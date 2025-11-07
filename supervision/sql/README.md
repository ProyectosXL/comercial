# Script de Base de Datos - Rotación de Imágenes

## Descripción
Este script crea la tabla necesaria para guardar las rotaciones de las imágenes de comprobantes de supervisión.

## Instrucciones de Instalación

### 1. Ejecutar el script SQL
Abre SQL Server Management Studio (SSMS) y ejecuta el archivo:
```
crear_tabla_rotacion_imagenes.sql
```

### 2. Base de Datos
El script debe ejecutarse en la base de datos **CENTRAL** (o la base de datos donde están las tablas `RO_T_*`).

### 3. Verificación
Después de ejecutar el script, verifica que la tabla fue creada correctamente:

```sql
SELECT * FROM RO_T_ROTACION_IMAGENES_SUP
```

## Estructura de la Tabla

La tabla `RO_T_ROTACION_IMAGENES_SUP` contiene:

- **ID**: Identificador único (auto-incremental)
- **NOMBRE_IMAGEN**: Nombre del archivo de imagen (único)
- **ROTACION**: Grados de rotación (0, 90, 180, 270, -90, -180, -270)
- **ID_CONC**: ID del concepto de gasto
- **SEMANA**: Número de semana
- **ANIO**: Año
- **SUPERVISORA**: Nombre de la supervisora
- **TIPO**: Tipo de comprobante ('tarjeta' o 'efectivo')
- **FECHA_CREACION**: Fecha de creación del registro
- **FECHA_MODIFICACION**: Fecha de última modificación

## Funcionalidad

Cuando un usuario rota una imagen en el visor:
1. La rotación se guarda automáticamente en la base de datos
2. La próxima vez que se abra esa imagen, se mostrará con la rotación guardada
3. Cada imagen mantiene su propia rotación independiente
4. La rotación se actualiza cada vez que el usuario la modifica

## Notas
- La tabla tiene un índice en `NOMBRE_IMAGEN` para búsquedas rápidas
- El campo `NOMBRE_IMAGEN` es único para evitar duplicados
- Si ya existe la tabla, el script no hace nada y muestra un mensaje
