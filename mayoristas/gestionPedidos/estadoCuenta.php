<?php
/* $cliente = new PPP();
$todosLosClientes = $cliente->traerCuenta($cliente); */
/* $todosLosClientes = json_decode($todosLosClientes); */
// var_dump($todosLosClientes);
// echo $todosLosClientes[0]->CUPO_CRED;
?>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div>
            <h5 class="modal-title" id="exampleModalLabel">Estado de cuenta </h5>
            <h6 class="text-secondary" id='codigoCliente'></h6>
        </div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <ul class="list-group">

        <li class="list-group-item list-group-item-secondary">
            Detalle de CC
            
        </li>
        <li class="list-group-item">
            Cupo credito 
            <span class="badge badge-primary badge-pill" id="spanCupoCred"></span>
        </li>
        <li class="list-group-item">
            Saldo CC
            <span class="badge badge-primary badge-pill" id="saldo"></span>
        </li>
        <li class="list-group-item">
            Vencidas
            <span class="badge badge-primary badge-pill" id="vencidas"></span>
        </li>
        <li class="list-group-item">
            Pedidos abiertos
            <span class="badge badge-primary badge-pill" id="montoPedidos"></span>
        </li>
        <li class="list-group-item">
            Total cheques
            <span class="badge badge-primary badge-pill" id="cheque"></span>
        </li>
        <li class="list-group-item">
            Cheques 10 dias
            <span class="badge badge-primary badge-pill" id="cheques10Dias"></span>
        </li>
        <li class="list-group-item">
            Total deuda
            <span class="badge badge-primary badge-pill" id="totalDeuda"></span>
        </li>
        <li class="list-group-item">
            Disponible $
            <span class="badge badge-primary badge-pill" id="totalDisponible"></span>
        </li>
        
        </ul>	
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>

      </div>
    </div>
  </div>
</div>

<script>
  /* console.log(datosCliente); */
  valoresNegativo();
  function valoresNegativo()
  {
  e=document.querySelectorAll('.badge');
  e.forEach(el=>{if(el.innerText.includes('-')){el.style.background='red';}});
}
</script>
