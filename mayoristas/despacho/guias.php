
<?php

session_start();
if (!isset($_SESSION['username'])) {
	header("Location:login.php");
} else {



	$hoy = date("Y-m-d");

	if(!isset($_GET['cliente'])){	$cliente = '%';}else{	$cliente = '%'.$_GET['cliente'].'%';}
	if(!isset($_GET['estado'])){	$estado = '%';}else{	$estado = '%'.$_GET['estado'].'%';}
	if(!isset($_GET['transp'])){	$transp = '%';}else{	$transp = '%'.$_GET['transp'].'%';}
	if(!isset($_GET['guia'])){	$guia = '%';}else{	$guia = '%'.$_GET['guia'];}

?>

<!DOCTYPE html>
    <html lang="en">
		<head>
			<title>Gestión de Guías</title>
			<meta charset="UTF-8"></meta>
			<link rel="shortcut icon" href="assets/css/images/icono.jpg" />

			<!-- INCLUDE CSS FILES -->
			<?php
				require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/mayoristas/despacho/assets/css/css.php';
			?>

		</head>
		<body>
			<div class="container-fluid">
				<div id="boxLoading"></div>
				
				<div class="d-flex justify-content-between mt-3">

					<div ><h3><i class="bi bi-calendar-check"></i> Gestión de Guías</h3></div>
					<div ><button type="button" class="btn btn-warning"onclick="window.location.href='index.php'">Volver <i class="bi bi-arrow-90deg-left"></i></button></div>

				</div>  

				<div class="row mb-2">
				
					<div class="col-sm-2">
						<label class="col-sm col-form-label">Desde:</label>
							<input type="date" class="form-control form-control-sm" name="desde" id="desde" value="<?php if(!isset($_GET['desde'])){	echo $hoy;}else{ echo $_GET['desde'] ;}?>">
					</div>
				
					<div class="col-sm-2">
						<label class="col-sm col-form-label">Hasta:</label>
							<input type="date" class="form-control form-control-sm" name="hasta" id="hasta" value="<?php if(!isset($_GET['hasta'])){	echo $hoy;}else{ echo $_GET['hasta'] ;}?>">
					</div>
					
					<div class="">
						<label class="col-sm col-form-label">Cliente:</label>
							<input type="text" class="form-control form-control-sm" name="cliente" id="cliente" value="<?php if(!isset($_GET['cliente'])){	echo '';}else{ echo $_GET['cliente'] ;}?>" placeholder="">
					</div>
		
					<div class="" style="margin-left: 1rem">
						<label class="col-sm col-form-label">Transporte:</label>
							<input type="text" class="form-control form-control-sm" name="transp" id="transp" placeholder="">
					</div>
		
					<div class="" style="margin-left: 1rem">
						<label class="col-sm col-form-label">Nro Guia:</label>
							<input type="text" class="form-control form-control-sm" name="guia" id="guia" autofocus placeholder="">
					</div>
					
					<div class="col-sm-2" style="margin-top: 2rem">
						<button type="submit" class="btn btn-primary" onclick="buscar()">Buscar <i class="bi bi-search"></i></button>
						<button type="submit" class="btn btn-success" onclick="guardar()">Guardar <i class="bi bi-save"></i>
					</div>

				</div>

				<table class="table table-condensed table-striped" id="tablaGuias">

					<thead class="thead-dark">
						<tr>
							<th width="8%"  class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">FECHA<br>GUIA</th>
							<th width="2%"  class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">NUMERO<br>GUIA</th>
							<!-- <th width="2%"  class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">TIPO</th> -->
							<th width="10%" class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">RAZON<br>SOCIAL</th>
							<th width="3%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">BULTOS</th>
							<th width="4%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">IMPORTE</th>
							<th width="12%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">TRANSPORTE</th>
							<th width="5%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">FECHA<br>CONTACTO</th>
							<th width="7%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">CONTACTO</th>
							<th width="7%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">MEDIO</th>
							<th width="5%" 	class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">FECHA_RETIRO</th>
							<th width="15%" class="h6" style="position: sticky; top: 0; z-index: 10;text-align:center">OBSERVACIONES</th>
						</tr>
					</thead>
					<tbody id="tableBody">
					
					</tbody>

				</table>
	
			</div>
	</body>
	<!-- INCLUDE JS FILES -->
	<?php
		require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/mayoristas/despacho/assets/js/js.php';
	?>

</html>	

<?php

}
?>