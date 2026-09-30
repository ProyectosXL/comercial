<?php

/**
 * PremioEcommerce
 * Gestión de las personas de Premios Ecommerce y de sus escalas de premio (las "PAUTAS").
 * El dashboard /bi/premios/ (pestaña Premios Ecommerce) solo LEE estas tablas; se editan
 * únicamente desde acá.
 *
 * Tablas (POWER_BI_CONTROL, conexión 'power'), ver bi/premios/sql/setup_premios_ecommerce.sql:
 *   BI_T_PREMIOS_ECOM_PERSONAS / _CONCEPTOS / _ESCALAS          lo que se liquida
 *   BI_T_PREMIOS_ECOM_PUESTOS / _PUESTO_CONCEPTOS / _PUESTO_ESCALAS   plantilla del alta
 *
 * El PUESTO es la plantilla de qué se le mide a una persona: al darla de alta se le COPIAN
 * los conceptos y tramos del puesto. De ahí en más la persona tiene los suyos propios, que
 * son los que se editan en "Escalas". Por eso el puesto no se cambia después del alta.
 */
class PremioEcommerce
{
    private $cid;
    private $cid_power;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_power = $this->cid->conectar('power');
        if (!$this->cid_power) {
            throw new RuntimeException('No se pudo conectar a POWER_BI_CONTROL');
        }
    }

    /** sqlsrv_query que tira excepción con contexto, para no repetir el chequeo en cada paso. */
    private function ejecutar($sql, $params, $contexto)
    {
        $stmt = sqlsrv_query($this->cid_power, $sql, $params);
        if ($stmt === false) {
            throw new RuntimeException("Error $contexto: " . print_r(sqlsrv_errors(), true));
        }
        return $stmt;
    }

    /* ─────────────────────────────────────────────────────────
     * Escalas
     * ───────────────────────────────────────────────────────── */

    /**
     * Personas activas → conceptos activos → tramos (de mayor a menor umbral).
     */
    public function catalogo()
    {
        $stmt = $this->ejecutar(
            "SELECT ID_CONCEPTO, UMBRAL, IMPORTE
             FROM BI_T_PREMIOS_ECOM_ESCALAS
             ORDER BY ID_CONCEPTO, UMBRAL DESC",
            [], 'consultando BI_T_PREMIOS_ECOM_ESCALAS');
        $escalasPorConcepto = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $escalasPorConcepto[(int) $r['ID_CONCEPTO']][] = [
                'umbral'  => (float) $r['UMBRAL'],
                'importe' => (float) $r['IMPORTE'],
            ];
        }
        sqlsrv_free_stmt($stmt);

        $stmt = $this->ejecutar(
            "SELECT p.ID AS P_ID, p.NOMBRE, c.ID AS C_ID, c.CANAL, c.ETIQUETA, c.TIPO_UMBRAL
             FROM BI_T_PREMIOS_ECOM_PERSONAS p
             LEFT JOIN BI_T_PREMIOS_ECOM_CONCEPTOS c
                    ON c.ID_PERSONA = p.ID AND c.ACTIVO = 1
             WHERE p.ACTIVO = 1
             ORDER BY p.ORDEN, p.NOMBRE, c.ORDEN, c.ETIQUETA",
            [], 'consultando el catálogo de premios ecommerce');

        $personas = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $pid = (int) $r['P_ID'];
            if (!isset($personas[$pid])) {
                $personas[$pid] = ['id' => $pid, 'nombre' => (string) $r['NOMBRE'], 'conceptos' => []];
            }
            if ($r['C_ID'] === null) continue; // persona activa sin conceptos asignados
            $cid = (int) $r['C_ID'];
            $personas[$pid]['conceptos'][] = [
                'id'          => $cid,
                'canal'       => $r['CANAL'] !== null ? (string) $r['CANAL'] : null,
                'etiqueta'    => (string) $r['ETIQUETA'],
                'tipo_umbral' => (string) $r['TIPO_UMBRAL'],
                'escalas'     => $escalasPorConcepto[$cid] ?? [],
            ];
        }
        sqlsrv_free_stmt($stmt);

        return array_values($personas);
    }

    /** IDs de conceptos activos — para validar el payload de guardarEscalas(). */
    public function idsConceptos()
    {
        $ids = [];
        foreach ($this->catalogo() as $p) {
            foreach ($p['conceptos'] as $c) $ids[] = $c['id'];
        }
        return $ids;
    }

    /**
     * Reemplaza los tramos de los conceptos recibidos: borra y reinserta por concepto dentro
     * de una transacción, así no queda una escala mitad vieja / mitad nueva si se corta.
     * Un concepto que no venga en $tramosPorConcepto queda intacto.
     *
     * @param array $tramosPorConcepto id_concepto => [[umbral, importe], ...]
     */
    public function guardarEscalas($tramosPorConcepto, $usuario)
    {
        if (!$tramosPorConcepto) return;

        if (!sqlsrv_begin_transaction($this->cid_power)) {
            throw new RuntimeException('No se pudo iniciar la transacción: ' . print_r(sqlsrv_errors(), true));
        }
        try {
            $sqlInsert = "INSERT INTO BI_T_PREMIOS_ECOM_ESCALAS
                              (ID_CONCEPTO, UMBRAL, IMPORTE, ACTUALIZADO_POR, FECHA_ACTUALIZACION)
                          VALUES (?, ?, ?, ?, GETDATE())";
            foreach ($tramosPorConcepto as $idConcepto => $tramos) {
                $this->ejecutar('DELETE FROM BI_T_PREMIOS_ECOM_ESCALAS WHERE ID_CONCEPTO = ?',
                    [(int) $idConcepto], 'limpiando la escala del concepto ' . $idConcepto);
                foreach ($tramos as $t) {
                    $this->ejecutar($sqlInsert, [(int) $idConcepto, $t['umbral'], $t['importe'], $usuario],
                        'insertando un tramo del concepto ' . $idConcepto);
                }
            }
            sqlsrv_commit($this->cid_power);
        } catch (Throwable $e) {
            sqlsrv_rollback($this->cid_power);
            throw $e;
        }
    }

    /* ─────────────────────────────────────────────────────────
     * Personas y puestos
     * ───────────────────────────────────────────────────────── */

    /** Puestos activos con las etiquetas de su plantilla. */
    public function puestos()
    {
        $stmt = $this->ejecutar(
            "SELECT pu.ID, pu.NOMBRE, pc.ETIQUETA, pc.CANAL
             FROM BI_T_PREMIOS_ECOM_PUESTOS pu
             LEFT JOIN BI_T_PREMIOS_ECOM_PUESTO_CONCEPTOS pc ON pc.ID_PUESTO = pu.ID
             WHERE pu.ACTIVO = 1
             ORDER BY pu.ORDEN, pu.NOMBRE, pc.ORDEN",
            [], 'consultando los puestos (¿falta correr la sección PUESTOS de bi/premios/sql/setup_premios_ecommerce.sql?)');

        $puestos = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = (int) $r['ID'];
            $puestos[$id] = $puestos[$id] ?? ['id' => $id, 'nombre' => (string) $r['NOMBRE'], 'conceptos' => []];
            if ($r['ETIQUETA'] !== null) {
                $puestos[$id]['conceptos'][] = $r['ETIQUETA'] . ' (' . ($r['CANAL'] ?? 'VTEX + ML') . ')';
            }
        }
        sqlsrv_free_stmt($stmt);
        return array_values($puestos);
    }

    /** Todas las personas, activas e inactivas, con mail y puesto. */
    public function personasConfig()
    {
        $stmt = $this->ejecutar(
            "SELECT p.ID, p.NOMBRE, p.EMAIL, p.ACTIVO, pu.NOMBRE AS PUESTO
             FROM BI_T_PREMIOS_ECOM_PERSONAS p
             LEFT JOIN BI_T_PREMIOS_ECOM_PUESTOS pu ON pu.ID = p.ID_PUESTO
             ORDER BY p.ACTIVO DESC, p.ORDEN, p.NOMBRE",
            [], 'consultando las personas');

        $personas = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $personas[] = [
                'id'     => (int) $r['ID'],
                'nombre' => (string) $r['NOMBRE'],
                'email'  => $r['EMAIL'] !== null ? (string) $r['EMAIL'] : null,
                'activo' => (bool) $r['ACTIVO'],
                'puesto' => $r['PUESTO'] !== null ? (string) $r['PUESTO'] : null,
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $personas;
    }

    /**
     * El UNIQUE (NOMBRE) de PERSONAS ya lo impide, pero así el error se entiende en pantalla.
     * @throws InvalidArgumentException si $nombre ya lo usa otra persona (activa o no).
     */
    private function validarNombreLibre($nombre, $exceptoId = null)
    {
        $stmt = $this->ejecutar(
            "SELECT ID, ACTIVO FROM BI_T_PREMIOS_ECOM_PERSONAS WHERE NOMBRE = ?",
            [$nombre], 'validando el nombre');
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        if ($r && (int) $r['ID'] !== $exceptoId) {
            throw new InvalidArgumentException($r['ACTIVO']
                ? "Ya existe una persona llamada \"$nombre\"."
                : "Ya existe una persona llamada \"$nombre\", dada de baja. Reactivala desde la lista en vez de darla de alta de nuevo.");
        }
    }

    /**
     * Alta: inserta la persona y le copia la plantilla de conceptos + tramos de su puesto,
     * todo en una transacción (no puede quedar una persona sin conceptos o sin tramos).
     */
    public function crearPersona($nombre, $email, $idPuesto, $usuario)
    {
        $this->validarNombreLibre($nombre);

        $stmt = $this->ejecutar(
            "SELECT COUNT(*) AS N FROM BI_T_PREMIOS_ECOM_PUESTO_CONCEPTOS pc
             INNER JOIN BI_T_PREMIOS_ECOM_PUESTOS pu ON pu.ID = pc.ID_PUESTO AND pu.ACTIVO = 1
             WHERE pc.ID_PUESTO = ?",
            [$idPuesto], 'validando el puesto');
        $n = (int) (sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['N'] ?? 0);
        sqlsrv_free_stmt($stmt);
        if ($n === 0) {
            throw new InvalidArgumentException('El puesto elegido no existe o no tiene conceptos cargados.');
        }

        if (!sqlsrv_begin_transaction($this->cid_power)) {
            throw new RuntimeException('No se pudo iniciar la transacción: ' . print_r(sqlsrv_errors(), true));
        }
        try {
            $stmt = $this->ejecutar(
                "INSERT INTO BI_T_PREMIOS_ECOM_PERSONAS
                     (NOMBRE, EMAIL, ID_PUESTO, ORDEN, ACTIVO, ACTUALIZADO_POR, FECHA_ACTUALIZACION)
                 OUTPUT INSERTED.ID
                 SELECT ?, ?, ?, ISNULL(MAX(ORDEN), 0) + 1, 1, ?, GETDATE()
                 FROM BI_T_PREMIOS_ECOM_PERSONAS",
                [$nombre, $email, $idPuesto, $usuario], 'dando de alta la persona');
            $idPersona = (int) sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['ID'];
            sqlsrv_free_stmt($stmt);

            $this->ejecutar(
                "INSERT INTO BI_T_PREMIOS_ECOM_CONCEPTOS
                     (ID_PERSONA, CANAL, ETIQUETA, METRICA, TIPO_UMBRAL, ORIGEN_REAL, ORIGEN_OBJETIVO, ORDEN)
                 SELECT ?, CANAL, ETIQUETA, METRICA, TIPO_UMBRAL, ORIGEN_REAL, ORIGEN_OBJETIVO, ORDEN
                 FROM BI_T_PREMIOS_ECOM_PUESTO_CONCEPTOS WHERE ID_PUESTO = ?",
                [$idPersona, $idPuesto], 'copiando los conceptos del puesto');

            // El concepto de la persona se empareja con el de la plantilla por ETIQUETA
            // (única por persona y por puesto).
            $this->ejecutar(
                "INSERT INTO BI_T_PREMIOS_ECOM_ESCALAS
                     (ID_CONCEPTO, UMBRAL, IMPORTE, ACTUALIZADO_POR, FECHA_ACTUALIZACION)
                 SELECT c.ID, pe.UMBRAL, pe.IMPORTE, ?, GETDATE()
                 FROM BI_T_PREMIOS_ECOM_CONCEPTOS c
                 INNER JOIN BI_T_PREMIOS_ECOM_PUESTO_CONCEPTOS pc
                         ON pc.ID_PUESTO = ? AND pc.ETIQUETA = c.ETIQUETA
                 INNER JOIN BI_T_PREMIOS_ECOM_PUESTO_ESCALAS pe ON pe.ID_PUESTO_CONCEPTO = pc.ID
                 WHERE c.ID_PERSONA = ?",
                [$usuario, $idPuesto, $idPersona], 'copiando los tramos del puesto');

            sqlsrv_commit($this->cid_power);
        } catch (Throwable $e) {
            sqlsrv_rollback($this->cid_power);
            throw $e;
        }
        return $idPersona;
    }

    public function editarPersona($id, $nombre, $email, $usuario)
    {
        $this->validarNombreLibre($nombre, $id);
        $stmt = $this->ejecutar(
            "UPDATE BI_T_PREMIOS_ECOM_PERSONAS
             SET NOMBRE = ?, EMAIL = ?, ACTUALIZADO_POR = ?, FECHA_ACTUALIZACION = GETDATE()
             WHERE ID = ?",
            [$nombre, $email, $usuario, $id], 'editando la persona');
        if (sqlsrv_rows_affected($stmt) === 0) {
            throw new InvalidArgumentException('La persona no existe.');
        }
    }

    /** Baja/reactivación lógica: los conceptos y tramos quedan, así vuelve igual que estaba. */
    public function cambiarActivoPersona($id, $activo, $usuario)
    {
        $stmt = $this->ejecutar(
            "UPDATE BI_T_PREMIOS_ECOM_PERSONAS
             SET ACTIVO = ?, ACTUALIZADO_POR = ?, FECHA_ACTUALIZACION = GETDATE()
             WHERE ID = ?",
            [$activo ? 1 : 0, $usuario, $id], 'cambiando el estado de la persona');
        if (sqlsrv_rows_affected($stmt) === 0) {
            throw new InvalidArgumentException('La persona no existe.');
        }
    }
}
