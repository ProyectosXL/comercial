<?php
session_start();

if(isset($_SESSION['username'])){
	session_destroy();
}

?>

<!doctype html>
<html charset="UTF-8">
<head>

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<!-- INCLUDE CSS FILES -->
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/css/css.php';
?>
<link href="../../assets/css/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">


<title>Login</title>

</head>

<body>
	<div id="contenedor">

	<header id="encabezado">
		<h1 align="center">XL GESTION</h1>

	</header>

	<aside id="logo">
		<img src="../../assets/css/images/logo.jpg">
	</aside>
	
	<section id="contenido">
		<article>
			<form>	
				<div class="container login-form">
					<h2 class="login-title">- Please Login -</h2>
					<div class="panel panel-default">
						<div class="panel-body">
							<form>
								<div class="input-group login-userinput">
									<span class="input-group-addon"><span class="fa fa-user mr-3" id="userIcon"></span></span>
									<input class="form-control" type="text" id="usuarioRegistrado" name="user" placeholder="Usuario" required autofocus>
								</div>
								<div class="input-group">
									<span class="input-group-addon"><span class="fa fa-lock mr-3" id="userIcon"></span></span>
									<input class="form-control" type="password" value="hunter2" id="contraseña" placeholder="Contraseña" name="pass" required>
									<span id="showPassword" class="input-group-btn">
							<button class="btn btn-default reveal" type="button"><i class="glyphicon glyphicon-eye-open"></i></button>
						</span>  
								</div>
								<button class="btn btn-primary btn-block login-button" type="button" onclick="login()"><i class="fa fa-sign-in"></i> Ingresar</button>
								<!-- spinner -->
								<div id="boxLoading"></div>
								<div class="checkbox login-options mt-2">
									<label for="">Conexión con el local</label>
									<select id="inputState" name="conecta" >
										<option selected value="si">Si</option>
										<option value="no">No</option>
									</select>	
								</div>		
							</form>			
						</div>
					</div>
				</div>
				<div class="row" style="justify-content:center">			
					<div class="col-md-3 col-md-offset-3 alert alert-danger mt-2" role="alert" id="alertError" style="display:none;" >
					<strong>Error!</strong><a> No existe el usuario </a>
					</div>
				</div>

			</form>
		</article>
	</section>
		
	</div>
	
</body>

<!-- INCLUDE JS FILES -->
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/js/js.php';
?>


<script>

window.onload = function(){$("#showPassword").hide();}

$("#txtPassword").on('change',function() {  
		if($("#txtPassword").val())
		{
			$("#showPassword").show();
		}
		else
		{
			$("#showPassword").hide();
		}
});

$(".reveal").on('click',function() {
    var $pwd = $("#txtPassword");
    if ($pwd.attr('type') === 'password') 
		{
        $pwd.attr('type', 'text');
    } 
		else 
		{
        $pwd.attr('type', 'password');
    }
});




</script>

</html>

