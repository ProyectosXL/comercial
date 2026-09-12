# Reapertura manual de períodos de gastos de supervisión

## Descripción

Permite que desde `supervision/controlarGastos.php` se reabra manualmente un período
(semana + año) para una supervisora puntual. La reapertura es de **un solo uso**: al
guardarse la carga vuelve a quedar cerrado.

Es una reapertura **complementaria**: habilita a la supervisora a cargar los conceptos
que todavía **no tienen gasto** en ese período. **No modifica ni anula nada de lo ya
cargado** — los importes, los CAE y las marcas de cargado en Tango quedan intactos.

Un concepto que ya tiene carga no se puede volver a cargar ni corregir desde acá.

## Instrucciones de instalación

### 1. Ejecutar el script SQL

Abrí SQL Server Management Studio (SSMS) y ejecutá, sobre la base **CENTRAL**
(la misma donde viven las tablas `RO_T_*`):

```
crear_reapertura_gastos.sql
```

El script es idempotente: se puede correr más de una vez sin efecto adicional.

### 2. Verificación

```sql
-- La tabla nueva, vacía
SELECT * FROM RO_T_REAPERTURAS_GASTOS_SUP;

-- UQ_GASTO_SUPERVISION tiene que seguir existiendo: es lo que impide
-- cargar dos veces el mismo concepto en un período
SELECT name, is_unique, is_unique_constraint
FROM sys.indexes WHERE object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION');
```

El script ya verifica todo esto por su cuenta en el paso 5 y falla con `RAISERROR`
si algo quedó a medias. Hace falta porque los pasos van en batches separados por `GO`:
si uno falla, los siguientes se ejecutan igual y el script terminaría diciendo
"finalizada" con la migración rota.

## `UQ_GASTO_SUPERVISION`: la red de seguridad

`RO_T_GASTOS_SUPERVISION` tiene `UQ_GASTO_SUPERVISION`, una **restricción UNIQUE**
sobre `(SEMANA, ANIO, ID_CONC, SUPERVISORA)`. Por eso nunca hubo filas duplicadas por
concepto y período: un segundo intento falla con error 2601.

Esa restricción es justamente lo que hace cumplir la regla de la reapertura
complementaria: un concepto no puede recibir una segunda fila en el mismo período. El
código valida lo mismo antes de insertar, para poder nombrar los conceptos en
conflicto en vez de propagar un 2601 crudo, pero **la restricción es la red real**.

El paso 5 del script verifica que exista y **falla con `RAISERROR` si falta**.

### Deshacer la versión de reemplazo (pasos 3 y 4)

Una versión anterior de este script planteaba la reapertura como **reemplazo**: anular
lógicamente las filas previas (`VIGENTE = 0`) e insertar las nuevas. Eso obligaba a
cambiar `UQ_GASTO_SUPERVISION` por un índice único **filtrado** por `VIGENTE = 1`, ya
que marcar una fila como anulada no libera la clave.

Ese enfoque se descartó. Los pasos 3 y 4 lo revierten:

- **Paso 3** elimina la columna `VIGENTE` de las dos tablas y sus índices
  (`UQ_GASTO_SUPERVISION_VIGENTE`, que quedaría redundante, e
  `IX_GASTO_SUPERVISION_PERIODO`, que la incluye). Si aparecen filas con
  `VIGENTE = 0` — es decir, si el enfoque de reemplazo llegó a usarse — **no toca
  nada** y avisa, porque borrar la columna perdería la única marca de que esas filas
  estaban anuladas.
- **Paso 4** vuelve a crear `UQ_GASTO_SUPERVISION` si falta. Antes verifica que no
  haya conceptos repetidos: si los hubiera, los lista y no la crea, porque el `ALTER
  TABLE` fallaría.

Si nunca se ejecutó aquella versión, ninguno de los dos pasos hace nada.

> Nota de historia: al aplicar esto en producción, la versión de reemplazo se ejecutó
> parcialmente antes de descartarse. El `DROP` de la restricción falló en el primer
> intento (era una restricción UNIQUE, no un índice suelto, así que requería
> `ALTER TABLE ... DROP CONSTRAINT` en lugar de `DROP INDEX`) y funcionó en el
> segundo, dejando la tabla sin ninguna restricción única. El paso 4 existe para
> recuperar exactamente ese estado.

## Estructura de `RO_T_REAPERTURAS_GASTOS_SUP`

| Columna | Descripción |
|---|---|
| `ID` | Identificador único (auto-incremental) |
| `SEMANA` / `ANIO` | Período reabierto |
| `SUPERVISORA` | Nombre de la supervisora (string, igual que en `RO_T_GASTOS_SUPERVISION`) |
| `MOTIVO` | Motivo de la reapertura (obligatorio) |
| `USUARIO_HABILITA` | Usuario que habilitó |
| `FECHA_HABILITACION` | Cuándo se habilitó |
| `ESTADO` | `ABIERTA` \| `CONSUMIDA` \| `ANULADA` |
| `FECHA_USO` / `USUARIO_USO` | Cuándo y quién consumió la reapertura al guardar |
| `USUARIO_ANULA` / `FECHA_ANULACION` | Cuándo y quién la anuló a mano |

No hay vencimiento por tiempo: una reapertura se cierra al consumirse o al anularse
a mano.

El índice `UQ_REAPERTURAS_GASTOS_ABIERTA` es único y filtrado por `ESTADO = 'ABIERTA'`,
de modo que **no puede haber dos reaperturas abiertas para el mismo período y
supervisora**. Es lo que hace seguro el consumo con compare-and-swap: si dos pestañas
intentan guardar a la vez, ambas ejecutan el mismo `UPDATE ... WHERE ESTADO = 'ABIERTA'`
y solo una obtiene `rows_affected = 1`; la otra ve 0, hace rollback y falla.

## Funcionamiento

1. Desde `controlarGastos.php` se elige semana + supervisora y se habilita la
   reapertura con un motivo obligatorio. Se crea una fila en estado `ABIERTA`.
   Si el período ya tiene todos los conceptos cargados, la pantalla avisa que no
   hay nada para agregar y no deja habilitarla.
2. La supervisora entra a `cargaGastosNew.php` y ve un aviso con los períodos
   reabiertos. Al seleccionar esa semana, quedan editables **solo los conceptos sin
   carga**; los ya cargados se muestran atenuados y bloqueados.
3. Al guardar, dentro de una única transacción:
   - se consume la reapertura (`ABIERTA` → `CONSUMIDA`) verificando `rows_affected = 1`;
   - se valida que ningún concepto enviado tenga ya una fila en el período;
   - se insertan las filas nuevas.
   Si algo falla, rollback: no se consume la reapertura ni se inserta nada.
4. El período vuelve a quedar cerrado.

El **adelanto no se toca** en una reapertura. Es del período, no del concepto, y ya
se cargó en la carga original; como `insertarAdelanto` hace `INSERT` y `obtenerAdelanto`
hace `SUM`, volver a mandarlo duplicaría el importe. El campo se deshabilita en la
pantalla y el servidor lo ignora.

## Consulta útil: historial de un período

```sql
SELECT * FROM RO_T_REAPERTURAS_GASTOS_SUP
WHERE SEMANA = 36 AND ANIO = 2026 AND SUPERVISORA = 'SONIA PACIFICO'
ORDER BY FECHA_HABILITACION DESC;

-- Los gastos cargados por una reapertura son filas normales. Para ubicarlos,
-- comparar FECHA_MODIF contra la FECHA_USO de la reapertura.
SELECT SEMANA, ANIO, SUPERVISORA, ID_CONC, IMPORTE, IMPORTE_TARJETA, FECHA_MODIF
FROM RO_T_GASTOS_SUPERVISION
WHERE SEMANA = 36 AND ANIO = 2026 AND SUPERVISORA = 'SONIA PACIFICO'
ORDER BY FECHA_MODIF;
```

## Notas

- El usuario que habilita o anula llega en el payload de
  `Controller/reaperturasGastos.php`, no de la sesión PHP: a ese controller también
  lo consume otra aplicación que no comparte la sesión. Si no viene por ninguna vía
  se registra `SIN_IDENTIFICAR`.
- `revertir_reapertura_gastos.sql` elimina la tabla de reaperturas. Los gastos
  cargados mediante una reapertura se conservan: son filas normales.
