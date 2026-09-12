# Reapertura manual de períodos de gastos de supervisión

## Descripción

Permite que desde `supervision/controlarGastos.php` se reabra manualmente un período
(semana + año) para una supervisora puntual. La reapertura es de **un solo uso**: al
guardarse la carga vuelve a quedar cerrado. El comportamiento normal no cambia para
el resto de las supervisoras ni para los demás períodos.

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
-- La columna nueva, en 1 para todo lo existente
SELECT VIGENTE, COUNT(*) FROM RO_T_GASTOS_SUPERVISION GROUP BY VIGENTE;
SELECT VIGENTE, COUNT(*) FROM FU_T_ADELANTOS_SUPERVISION GROUP BY VIGENTE;

-- El índice único viejo ya no está y el filtrado sí
SELECT name, is_unique, has_filter, filter_definition
FROM sys.indexes WHERE object_id = OBJECT_ID('RO_T_GASTOS_SUPERVISION');

-- La tabla de reaperturas, vacía
SELECT * FROM RO_T_REAPERTURAS_GASTOS_SUP;
```

En `RO_T_GASTOS_SUPERVISION` tienen que quedar `UQ_GASTO_SUPERVISION_VIGENTE`
(único, con filtro `([VIGENTE]=(1))`) e `IX_GASTO_SUPERVISION_PERIODO`, y **no**
debe seguir existiendo `UQ_GASTO_SUPERVISION`.

## Por qué se reemplaza el índice único

`RO_T_GASTOS_SUPERVISION` ya tenía `UQ_GASTO_SUPERVISION`, un índice **único** sobre
`(SEMANA, ANIO, ID_CONC, SUPERVISORA)`. Por eso una segunda carga sobre el mismo
período nunca generó filas duplicadas: fallaba con violación de clave única (error
2601) y la transacción hacía rollback.

Eso significa que **la anulación lógica sola no alcanza**. Marcar las filas previas
como `VIGENTE = 0` no libera la clave, porque `VIGENTE` no forma parte del índice: el
INSERT de la segunda carga seguiría chocando contra las filas anuladas.

La solución es reemplazarlo por un índice único **filtrado** por `VIGENTE = 1`. Las
filas anuladas dejan de ocupar la clave y se sigue garantizando una única fila
vigente por concepto y período, que es la invariante que interesa.

`UQ_GASTO_SUPERVISION` está implementado como **restricción UNIQUE**, no como
índice suelto. Aparece igual en `sys.indexes`, pero `DROP INDEX` falla con error
3723 (*"It is being used for UNIQUE KEY constraint enforcement"*): hay que sacarlo
con `ALTER TABLE ... DROP CONSTRAINT`. El script contempla las dos formas.

El paso 5 del script verifica el estado final y falla con `RAISERROR` si algo
quedó a medias. Hace falta porque los pasos van en batches separados por `GO`: si
uno falla, los siguientes se ejecutan igual y el script terminaría diciendo
"finalizada" con la migración rota.

### Requisito de SET options

Los índices filtrados exigen `ANSI_NULLS ON` y `QUOTED_IDENTIFIER ON` en toda
sentencia que haga INSERT/UPDATE/DELETE sobre la tabla. El driver ODBC 17 que usa
`sqlsrv` los deja en ON por defecto, así que la aplicación no necesita cambios. Si
alguna vez se inserta desde un job de SQL Agent o desde `sqlcmd`, hay que verificarlo:
con esas opciones en OFF la escritura falla con error 1934.

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
2. La supervisora entra a `cargaGastosNew.php` y ve esa semana editable, aunque ya
   tenga gastos cargados.
3. Al guardar, dentro de una única transacción:
   - se consume la reapertura (`ABIERTA` → `CONSUMIDA`) verificando `rows_affected = 1`;
   - se anulan las filas previas del período (`VIGENTE = 1` → `0`), gastos y adelantos;
   - se insertan las filas nuevas.
   Si algo falla, rollback: no se consume la reapertura ni se anula nada.
4. El período vuelve a quedar cerrado.

## Consulta útil: historial de un período

```sql
SELECT * FROM RO_T_REAPERTURAS_GASTOS_SUP
WHERE SEMANA = 36 AND ANIO = 2026 AND SUPERVISORA = 'SONIA PACIFICO'
ORDER BY FECHA_HABILITACION DESC;

-- Filas anuladas de ese período (lo que había antes de la recarga)
SELECT * FROM RO_T_GASTOS_SUPERVISION
WHERE SEMANA = 36 AND ANIO = 2026 AND SUPERVISORA = 'SONIA PACIFICO'
ORDER BY VIGENTE DESC, ID;
```

## Notas

- Todas las lecturas y los UPDATE de `RO_T_GASTOS_SUPERVISION` en la aplicación
  filtran por `VIGENTE = 1`. Si se agrega una consulta nueva sobre esta tabla,
  **tiene que llevar el filtro**: omitirlo hace sobrecontar, sumando la fila anulada
  junto con la vigente.
- Las filas anuladas se conservan: son el historial de lo que había antes de la
  recarga. No se borran nunca.
- `revertir_reapertura_gastos.sql` deshace la migración completa. Solo tiene sentido
  si no se consumió ninguna reapertura todavía (ver el aviso dentro del script).
