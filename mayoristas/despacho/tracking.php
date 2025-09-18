
<?php

session_start();
if (1 ==2 ) {
	header("Location:login.php");
} else {


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
			
			<title>Tracking Comprobrantes</title>
			<meta charset="UTF-8"></meta>
			<link rel="shortcut icon" href="assets/css/images/icono.jpg" />
			<!-- INCLUDE CSS FILES -->
			<?php
				require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/css/css.php';
			?>

		</head>
		<body>
			<div class="container-fluid">
			<div id="boxLoading"></div>
			<div class="d-flex justify-content-between mt-3">

				<div ><h3>Tracking Pedidos Mayoristas</h3></div>
				<div>
					<span class="badge badge-danger">Rojo</span> Excede los 7 dias de fecha de contacto
					<span class="badge badge-warning">Amarillo</span> Excede los 2 dias de fecha de guia
				</div>
				<div>
					<button type="button" class="btn btn-warning"onclick="window.location.href='index.php'">Volver <i class="bi bi-arrow-90deg-left"></i></button>
				</div>

			</div>  

			<form method="GET" action="">

				<div class="row mb-2">
					
					<div class="ml-3">
					<label class="col-form-label">Desde:</label>
						<input type="date" class="form-control form-control-sm" name="desde" id="desde" value="<?php if(!isset($_GET['desde'])){	echo $hoy;}else{ echo $_GET['desde'] ;}?>">
					</div>
				
					<div class="ml-1">
					<label class="col-form-label">Hasta:</label>
						<input type="date" class="form-control form-control-sm" name="hasta"  id="hasta"  value="<?php if(!isset($_GET['hasta'])){	echo $hoy;}else{ echo $_GET['hasta'] ;}?>">
					</div>
					
					<div class="ml-1">
					<label class="col-form-label">Cliente:</label>
						<input type="text" class="form-control form-control-sm" name="cliente"  id="cliente"  value="<?php if(!isset($_GET['cliente'])){	echo '';}else{ echo $_GET['cliente'] ;}?>" placeholder="">
					</div>
					
					<div class="col-sm-1">
					<label class="col-sm col-form-label">Estado:</label>
						<select class="form-control form-control-sm" name="estado"  id="estado" >
						<option value="" selected></option>
						<option value="PENDIENTE">PENDIENTE</option>
						<option value="GUIA">GUIA</option>
						<option value="CONTACTADO">CONTACTADO</option>
						<option value="DESPACHADO">DESPACHADO</option>
						</select >
					</div>
					
					<div class="ml-1">
					<label class="col-form-label">Vendedor:</label>
						<select class="form-control form-control-sm" name="vended"  id="vendedor" >
						<option value="" selected></option>
						<option value="Z2">Beatriz Gavalda</option>
						<option value="Z3">Valeria Villarreal</option>
						<option value="Z4">Sergio Lopez</option>
						<option value="Z5">Cristian Nacke</option>
						<option value="Z6">Gabriela Bonapasta</option>
						<option value="ZZ">Empresa</option>
						</select >
					</div>
					
					<div class="col-sm-1">
					<label class="col-form-label">Transp:</label>
						<input type="text" class="form-control form-control-sm" name="transp"  id="transp"  placeholder="">
					</div>
					
					<div class="col-sm-1">
					<label class="col-form-label">Guia:</label>
						<input type="text" class="form-control form-control-sm" name="guia"  id="guia"  autofocus placeholder="">
					</div>
					
					<div class="col-sm-1 " style="margin-top:2rem;">
						<button type="button" class="btn btn-primary" onclick="traerData()">Buscar <i class="bi bi-search"></i></button>
					</div>
				
				</div>
			
			</form>  

			<table class="table table-striped" id="tablaTracking">
				<thead class="thead-dark">
					<tr>
						<th width="9%" class="h6">FECHA<br>COMP</th>
						<th width="9%" class="h6">N COMP</th>
						<th width="9%" class="h6">VEND</th>
						<th width="9%" class="h6">TRANSPORTE</th>
						<th width="9%" class="h6">RAZON<br>SOCIAL</th>
						<th width="9%" class="h6">FECHA<br>GUIA</th>
						<th width="9%" class="h6">NUMERO<br>GUIA</th>
						<th width="9%" class="h6">BULTOS</th>
						<th width="9%" class="h6">IMPORTE</th>
						<th width="9%" class="h6">FECHA<br>DESPACHO</th>
						<th width="9%" class="h6">ESTADO</th>
					</tr>
				</thead>
				<tbody id="tableBody">
				</tbody>
			</table>		
		
		</body>
		<script>
			
			const traerData = () => {

				let spinner = document.getElementById("boxLoading");
				spinner.className = " loading";

				let desde = document.querySelector('#desde').value;
				let hasta = document.querySelector('#hasta').value;
				let cliente = document.querySelector('#cliente').value;
				let estado = document.querySelector('#estado').value;
				let vendedor = document.querySelector('#vendedor').value;
				let transp = document.querySelector('#transp').value;
				let guia = document.querySelector('#guia').value;
				let token = localStorage.getItem('token');


				cliente = cliente != '' ? cliente : '%' ;
				estado = estado != '' ? estado : '%' ;
				vendedor = vendedor != '' ? vendedor : '%' ;
				transp = transp != '' ? transp : '%' ;
				guia = guia != '' ? guia : '%' ;
				
				$.ajax({
					url: 'https://app.xl.com.ar/api/despacho/tracking',
					method: 'POST',
					contentType: "application/json",
					dataType: "json",
					headers: {
						'Authorization': `Bearer ${token}`,
						'Content-Type': 'application/json'
					},
					data: JSON.stringify({
						desde: desde,
						hasta: hasta,
						cliente: cliente,
						estado: estado,
						vendedor: vendedor,
						transp: transp,
						guia: guia
					}),
					success: function (data) {
				
						var table = document.querySelector("#tableBody");
						$('#tablaTracking').DataTable().destroy();

				

						if(data.length > 0){

							table.innerHTML = '';
						
							console.log(table.innerHTML);
							
							data.body.forEach(function(v) {
								
								var row = table.insertRow();


								if (v['DIAS'] > 7 && v['ESTADO'] == 'CONTACTADO') {

									row.style.fontWeight = "bold";
									row.style.color = "#FE2E2E";

								} else if (v['DIAS_GUIA'] > 2 && v['ESTADO'] != 'DESPACHADO') {

									row.style.fontWeight = "bold";
									row.style.color = "#FE9A2E";

								}

			
								var cell;
								cell = row.insertCell(); cell.innerHTML = v['FECHA_COMP'];
								cell = row.insertCell(); cell.innerHTML = v['N_COMP'];
								cell = row.insertCell(); cell.innerHTML = v['COD_VENDED'];
								cell = row.insertCell(); cell.innerHTML = v['NOMBRE_TRA'];
								cell = row.insertCell(); cell.innerHTML = v['RAZON_SOCI'];

								cell = row.insertCell();
								cell.innerHTML = (v['FECHA_GUIA'] === '1900-01-01 00:00:00') ? '' : v['FECHA_GUIA'];
							
								cell = row.insertCell();
								cell.innerHTML = (v['NRO_GUIA'] === '0') ? '' : v['NRO_GUIA'];
							
								cell = row.insertCell();
								cell.innerHTML = (v['BULTOS'] === '0') ? '' : v['BULTOS'];
							
								cell = row.insertCell();
								cell.innerHTML = '$ ' + parseNumber(v['IMPORTE']);
							
								cell = row.insertCell();
								cell.innerHTML = (v['FECHA_DESPACHO'] === '1900-01-01 00:00:00') ? '' : v['FECHA_DESPACHO'];
							
								
								cell = row.insertCell();
								if (v['ESTADO'] == 'CONTACTADO') {

									cell.innerHTML = v['ESTADO'];
									cell.setAttribute("title", `Fecha Contacto: ${v['FECHA_CONTACTO']}`);
									cell.setAttribute("data-toggle", "tooltip");

								}else{

									cell.innerHTML = v['ESTADO'];

								}

							
							});
							
					
							$('[data-toggle="tooltip"]').tooltip();
			
							$('#tablaTracking').DataTable({
								"bLengthChange": true,
								"language": {
											"lengthMenu": "mostrar _MENU_ registros",
											"info":           "Mostrando registros del _START_ al _END_ de un total de  _TOTAL_ registros",
											"paginate": {
												"next":       "Siguiente",
												"previous":   "Anterior"
											},
						
								},
							
								
								"bInfo": true,
								"aaSorting": false,
								'columnDefs': [
									{
										"targets": "_all", 
										"className": "text-center",
										"sortable": false,
								
									},
								],
								"oLanguage": {
							
									"sSearch": "Busqueda rapida:",
									"sSearchPlaceholder" : "Sobre cualquier campo"
									
							
								},
							});

						}else{

							table.innerHTML = '';
							alert('No se encontraron registros');

						}

						spinner.classList.remove("loading");

					}
				})
			
			}


			const parseNumber = (number) => {
				number = parseInt(number);

				let newNumber = number.toLocaleString('de-De', {
					style: 'decimal',
					maximumFractionDigits: 0,
					minimumFractionDigits: 0
				});

				return newNumber;
			}
		</script>
		<!-- INCLUDE JS FILES -->
		<?php
			require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/js/js.php';
		?>

</html>	
<?php
}
?>