<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* Dashboard Layout Improvements */
.config-container {
    max-width: 1100px;
    margin: 0 auto;
}

.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

/* Card Styling */
.config-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
    height: 100%;
    max-height: 600px;
}
.config-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.config-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fcfcfd;
    border-radius: 16px 16px 0 0;
}
.config-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.config-title i {
    width: 32px;
    height: 32px;
    background: var(--primary-bg);
    color: var(--primary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

/* List Style */
.config-list-container {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    background: #fff;
}
.config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    border-radius: 10px;
    margin-bottom: 4px;
    transition: all 0.2s;
}
.config-item:hover {
    background: var(--gray-50);
}
.item-text {
    font-size: 13.5px;
    font-weight: 500;
    color: var(--gray-700);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

/* Buttons and Badges */
.status-pill {
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    border: none;
    transition: 0.2s;
}
.status-pill.active { background: #ecfdf5; color: #10b981; }
.status-pill.inactive { background: #fef2f2; color: #ef4444; }
.status-pill:hover { filter: brightness(0.95); opacity: 0.8; }

.btn-icon-del {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: var(--gray-400);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}
.btn-icon-del:hover {
    background: #fee2e2;
    color: #ef4444;
}

/* Footer / Add Input */
.config-footer {
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid var(--gray-100);
    border-radius: 0 0 16px 16px;
}
.add-box {
    display: flex;
    gap: 8px;
}
.input-minimal {
    flex: 1;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    outline: none;
    transition: all 0.2s;
}
.input-minimal:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.empty-info {
    text-align: center;
    padding: 30px 10px;
    color: var(--gray-400);
    font-size: 13px;
}

/* Scrollbar Customization */
.config-list-container::-webkit-scrollbar { width: 5px; }
.config-list-container::-webkit-scrollbar-track { background: transparent; }
.config-list-container::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 10px; }
</style>

<div class="config-container">
    <div class="page-header">
        <div>
            <div class="breadcrumb-modern">
                <a href="/comercial/SolicitudesTransferencias/"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                <span>Configuración</span>
            </div>
            <h1 style="margin-top:10px;">Administración de Listas</h1>
            <p style="color:var(--gray-500); margin-top: 4px;">Gestione los valores que aparecen en los desplegables de la aplicación</p>
        </div>
    </div>

    <div class="config-grid">
        <?php
        $sections = [
            'TIPO_TRANSFERENCIA' => ['title' => 'Tipos de Movimiento', 'icon' => 'fas fa-exchange-alt', 'placeholder' => 'Ej: Devolución'],
            'USUARIO' => ['title' => 'Usuarios Solicitantes', 'icon' => 'fas fa-user-edit', 'placeholder' => 'Nombre del usuario'],
            'SUCURSAL' => ['title' => 'Locales / Depósitos', 'icon' => 'fas fa-warehouse', 'placeholder' => 'Ej: 15-Tienda Online']
        ];

        foreach ($sections as $key => $info):
            $items = array_filter($config, function($i) use ($key) { return $i['CATEGORIA'] === $key; });
            usort($items, function($a, $b) { return strcmp($a['VALOR'], $b['VALOR']); });
        ?>
        <div class="config-card">
            <div class="config-header">
                <h3 class="config-title">
                    <i><i class="<?php echo $info['icon']; ?>"></i></i>
                    <?php echo $info['title']; ?>
                </h3>
                <span style="font-size: 11px; font-weight: 700; color: var(--gray-400);"><?php echo count($items); ?> ITEMS</span>
            </div>

            <div class="config-list-container">
                <?php if (empty($items)): ?>
                    <div class="empty-info">No hay registros</div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <div class="config-item">
                        <span class="item-text" title="<?php echo htmlspecialchars($item['VALOR']); ?>">
                            <?php echo htmlspecialchars($item['VALOR']); ?>
                        </span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button class="status-pill <?php echo $item['ACTIVO'] ? 'active' : 'inactive'; ?>" 
                                    onclick="toggleConfig(<?php echo $item['ID']; ?>)">
                                <?php echo $item['ACTIVO'] ? 'Sí' : 'No'; ?>
                            </button>
                            <button class="btn-icon-del" onclick="eliminarConfig(<?php echo $item['ID']; ?>)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="config-footer">
                <div class="add-box">
                    <input type="text" class="input-minimal" id="input-<?php echo $key; ?>" placeholder="<?php echo $info['placeholder']; ?>">
                    <button class="btn-modern btn-primary-modern btn-sm-modern" onclick="guardarConfig('<?php echo $key; ?>')">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php 
$pageScripts = '<script>
function guardarConfig(categoria) {
    const valor = $("#input-" + categoria).val().trim();
    if (!valor) return;

    $.ajax({
        url: "/comercial/SolicitudesTransferencias/public/config/guardar",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ categoria: categoria, valor: valor }),
        success: function(resp) {
            if (resp.success) {
                location.reload();
            } else {
                showToast(resp.error || "Error al guardar", "error");
            }
        }
    });
}

function toggleConfig(id) {
    $.ajax({
        url: "/comercial/SolicitudesTransferencias/public/config/toggle",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ id: id }),
        success: function(resp) {
            if (resp.success) {
                location.reload();
            }
        }
    });
}

function eliminarConfig(id) {
    if (!confirm("¿Desea eliminar esta opción?")) return;

    $.ajax({
        url: "/comercial/SolicitudesTransferencias/public/config/eliminar",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ id: id }),
        success: function(resp) {
            if (resp.success) {
                location.reload();
            }
        }
    });
}
</script>';

require_once __DIR__ . '/../layouts/footer.php'; 
?>
