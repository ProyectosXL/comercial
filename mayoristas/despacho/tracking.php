<?php

	$hoy = date("Y-m-d");
	if(!isset($_GET['cliente'])){	$cliente = '%';}else{	$cliente = '%'.$_GET['cliente'].'%';}
	if(!isset($_GET['vended'])){	$vended = '%';}else{	$vended = '%'.$_GET['vended'].'%';}
	if(!isset($_GET['transp'])){	$transp = '%';}else{	$transp = '%'.$_GET['transp'].'%';}
	if(!isset($_GET['estado'])){	$estado = '%';}else{	 $estado = '%'.$_GET['estado'].'%'; }
	if(!isset($_GET['guia'])){	$guia = '0';}elseif($_GET['guia']==''){$guia = '%';}else{$guia = $_GET['guia'];}
	//if(!isset($_GET['guia'])){	$guia = '%';}else{	$guia = $_GET['guia'];}

?>

<!DOCTYPE html>
    <html lang="en">
		<head>
			<title>Tracking Comprobantes</title>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="shortcut icon" href="../../assets/css/images/icono.jpg" />
			
			<!-- Custom CSS -->
			<link rel="stylesheet" href="css/tracking.css">
		</head>
		<body>
			<div class="container-fluid">
				<!-- Loading Overlay -->
				<div id="boxLoading">
					<div class="loading-spinner"></div>
				</div>
				
				<!-- Header -->
				<div class="tracking-header">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<h3><i class="bi bi-truck"></i> Tracking Pedidos Mayoristas</h3>
						</div>
						<div class="status-legend">
							<span class="badge badge-danger">Rojo</span> Excede los 7 días de fecha de contacto
							<span class="badge badge-warning">Amarillo</span> Excede los 2 días de fecha de guía
						</div>
						<div>
							<button type="button" class="btn btn-back" onclick="window.location.href='index.php'">
								<i class="bi bi-arrow-90deg-left"></i> Volver
							</button>
						</div>
					</div>
				</div>

				<!-- Formulario de Búsqueda -->
				<form method="GET" action="" class="search-form">
					<!-- Primera fila: Fechas, Estado, Vendedor, Cliente, Transporte -->
					<div class="form-row-custom">
						
						<div class="form-group-inline col-date">
							<label for="desde">Desde:</label>
							<input type="date" class="form-control form-control-custom" name="desde" id="desde" value="<?php if(!isset($_GET['desde'])){	echo $hoy;}else{ echo $_GET['desde'] ;}?>">
						</div>
					
						<div class="form-group-inline col-date">
							<label for="hasta">Hasta:</label>
							<input type="date" class="form-control form-control-custom" name="hasta" id="hasta" value="<?php if(!isset($_GET['hasta'])){	echo $hoy;}else{ echo $_GET['hasta'] ;}?>">
						</div>

						<div class="form-group-inline col-estado">
							<label for="estado">Estado:</label>
							<select class="form-control form-control-custom" name="estado" id="estado">
								<option value="" selected>Todos</option>
								<option value="PENDIENTE" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'PENDIENTE') ? 'selected' : ''; ?>>PENDIENTE</option>
								<option value="GUIA" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'GUIA') ? 'selected' : ''; ?>>GUÍA</option>
								<option value="CONTACTADO" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'CONTACTADO') ? 'selected' : ''; ?>>CONTACTADO</option>
								<option value="DESPACHADO" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'DESPACHADO') ? 'selected' : ''; ?>>DESPACHADO</option>
							</select>
						</div>
						
						<div class="form-group-inline col-vendedor">
							<label for="vendedor">Vendedor:</label>
							<select class="form-control form-control-custom" name="vended" id="vendedor">
								<option value="" selected>Todos</option>
								<option value="Z2" <?php echo (isset($_GET['vended']) && $_GET['vended'] == 'Z2') ? 'selected' : ''; ?>>Beatriz Gavalda</option>
								<option value="Z3" <?php echo (isset($_GET['vended']) && $_GET['vended'] == 'Z3') ? 'selected' : ''; ?>>Valeria Villarreal</option>
								<option value="Z4" <?php echo (isset($_GET['vended']) && $_GET['vended'] == 'Z4') ? 'selected' : ''; ?>>Sergio Lopez</option>
								<option value="Z5" <?php echo (isset($_GET['vended']) && $_GET['vended'] == 'Z5') ? 'selected' : ''; ?>>Cristian Nacke</option>
								<option value="Z6" <?php echo (isset($_GET['vended']) && $_GET['vended'] == 'Z6') ? 'selected' : ''; ?>>Gabriela Bonapasta</option>
								<option value="ZZ" <?php echo (isset($_GET['vended']) && $_GET['vended'] == 'ZZ') ? 'selected' : ''; ?>>Empresa</option>
							</select>
						</div>

					</div>

					<!-- Segunda fila: Cliente, Transporte, Guía, Botones -->
					<div class="form-row-custom">
						
						<div class="form-group-inline col-cliente">
							<label for="cliente">Cliente:</label>
							<select class="form-control form-control-custom" name="cliente" id="cliente" style="width: 100%;">
								<option value="">Seleccionar cliente...</option>
								<?php if(isset($_GET['cliente']) && $_GET['cliente'] != ''): ?>
									<option value="<?php echo $_GET['cliente']; ?>" selected><?php echo $_GET['cliente']; ?></option>
								<?php endif; ?>
							</select>
						</div>
						
						<div class="form-group-inline col-transporte">
							<label for="transp">Transporte:</label>
							<select class="form-control form-control-custom" name="transp" id="transp" style="width: 100%;">
								<option value="">Seleccionar transporte...</option>
								<?php if(isset($_GET['transp']) && $_GET['transp'] != ''): ?>
									<option value="<?php echo $_GET['transp']; ?>" selected><?php echo $_GET['transp']; ?></option>
								<?php endif; ?>
							</select>
						</div>
						
						<div class="form-group-inline col-guia">
							<label for="guia">N° Guía:</label>
							<input type="text" class="form-control form-control-custom" name="guia" id="guia" value="<?php echo isset($_GET['guia']) ? $_GET['guia'] : ''; ?>" placeholder="Número de guía...">
						</div>
						
						<div class="form-group-inline col-buttons">
							<label>&nbsp;</label>
							<div class="btn-group-custom">
								<button type="button" class="btn btn-search" onclick="traerData()">
									<i class="bi bi-search"></i> Buscar
								</button>
								<button type="button" class="btn btn-clear" onclick="limpiarFiltros()" title="Limpiar filtros">
									<i class="bi bi-x-circle"></i> Limpiar
								</button>
							</div>
						</div>
					
					</div>
				</form>

				<!-- Tabla de Resultados -->
				<div class="table-responsive">
					<table class="table table-striped table-tracking" id="tablaTracking">
						<thead class="thead-dark">
							<tr>
								<th class="h6">FECHA<br>COMP</th>
								<th class="h6">N COMP</th>
								<th class="h6">VEND</th>
								<th class="h6">TRANSPORTE</th>
								<th class="h6">RAZÓN<br>SOCIAL</th>
								<th class="h6">FECHA<br>GUÍA</th>
								<th class="h6">NÚMERO<br>GUÍA</th>
								<th class="h6">BULTOS</th>
								<th class="h6">IMPORTE</th>
								<th class="h6">FECHA<br>DESPACHO</th>
								<th class="h6">ESTADO</th>
							</tr>
						</thead>
						<tbody id="tableBody">
							<tr>
								<td colspan="11" class="text-center py-4">
									<i class="bi bi-info-circle"></i> Use los filtros para buscar pedidos
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</body>
		
		<!-- INCLUDE JS FILES -->
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
		<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
		<!-- Popper.js requerido por Bootstrap 4 para tooltips y popovers -->
		<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
		
		<!-- Custom JS -->
		<script src="js/tracking.js"></script>

</html>	