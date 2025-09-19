
<?php

	$hoy = date("Y-m-d");

	if(!isset($_GET['cliente'])){	$cliente = '%';}else{	$cliente = '%'.$_GET['cliente'].'%';}
	if(!isset($_GET['estado'])){	$estado = '%';}else{	$estado = '%'.$_GET['estado'].'%';}
	if(!isset($_GET['transp'])){	$transp = '%';}else{	$transp = '%'.$_GET['transp'].'%';}
	if(!isset($_GET['guia'])){	$guia = '%';}else{	$guia = '%'.$_GET['guia'];}

?>

<!DOCTYPE html>
<html lang="es">
<head>
	<title>Gestión de Guías</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" href="../../assets/css/images/icono.jpg" />
	
	<!-- INCLUDE CSS FILES -->
	<?php
		require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/mayoristas/despacho/assets/css/css.php';
	?>
	
	<!-- Select2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	
	<!-- Custom CSS -->
	<link rel="stylesheet" href="css/guias.css">
</head>
<body>
	<div class="container-fluid">
		<!-- Loading Overlay -->
		<div id="boxLoading">
			<div class="loading-spinner"></div>
		</div>
		
		<!-- Header -->
		<div class="guias-header">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<h3><i class="bi bi-calendar-check"></i> Gestión de Guías</h3>
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
			<!-- Todos los filtros en una sola fila -->
			<div class="form-row-custom">
				<div class="form-group-inline col-date">
					<label for="desde">Desde:</label>
					<input type="date" class="form-control form-control-custom" name="desde" id="desde" value="<?php if(!isset($_GET['desde'])){	echo $hoy;}else{ echo $_GET['desde'] ;}?>">
				</div>
			
				<div class="form-group-inline col-date">
					<label for="hasta">Hasta:</label>
					<input type="date" class="form-control form-control-custom" name="hasta" id="hasta" value="<?php if(!isset($_GET['hasta'])){	echo $hoy;}else{ echo $_GET['hasta'] ;}?>">
				</div>
				
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
					<input type="text" class="form-control form-control-custom" name="guia" id="guia" value="<?php if(!isset($_GET['guia'])){	echo '';}else{ echo $_GET['guia'] ;}?>" autofocus placeholder="Número de guía...">
				</div>
				
				<div class="form-group-inline col-buttons">
					<label>&nbsp;</label>
					<div class="btn-group-custom">
						<button type="button" class="btn btn-search" onclick="buscar()">
							<i class="bi bi-search"></i> Buscar
						</button>
						<button type="button" class="btn btn-save" onclick="guardar()">
							<i class="bi bi-save"></i> Guardar
						</button>
					</div>
				</div>
			</div>
		</form>

		<!-- Tabla de Resultados -->
		<div class="table-responsive">
			<table class="table table-striped table-guias" id="tablaGuias">
				<thead class="thead-dark">
					<tr>
						<th class="h6">FECHA<br>GUÍA</th>
						<th class="h6">NÚMERO<br>GUÍA</th>
						<th class="h6">RAZÓN<br>SOCIAL</th>
						<th class="h6">BULTOS</th>
						<th class="h6">IMPORTE</th>
						<th class="h6">TRANSPORTE</th>
						<th class="h6">FECHA<br>CONTACTO</th>
						<th class="h6">CONTACTO</th>
						<th class="h6">MEDIO</th>
						<th class="h6">FECHA<br>RETIRO</th>
						<th class="h6">OBSERVACIONES</th>
					</tr>
				</thead>
				<tbody id="tableBody">
					<!-- Fila inicial vacía para evitar errores de DataTables -->
				</tbody>
			</table>
		</div>
	</div>
</body>

<!-- INCLUDE JS FILES -->
<?php
	require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/mayoristas/despacho/assets/js/js.php';
?>

<!-- Select2 CDN for dynamic selectors -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/guias.js"></script>

<script>

</script>

</html>