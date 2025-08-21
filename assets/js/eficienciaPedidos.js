
  
const cambiarEntorno = (t) =>{
  
    let spinner = document.querySelector("#boxLoading");
  
    spinner.classList.add("loading");

    let entorno = 0;
  
    if(t.getAttribute("data-off") == "ARG" ){
      entorno = 0;
    }else{
      entorno = 1;
    }


    $.ajax({
      url: "../Controller/maestroDestinosController.php?accion=cambiarEntorno",
      method: "POST",
      data : {entorno: entorno},
      success: function (data) {
        location.reload();
      }
    });

  }
  
  const exportar = () => {

    $('#tableMaestro').DataTable().destroy();

    $("#tableMaestro").table2excel({
      // exclude CSS class
      exclude: ".imagen",
      name: "Detalle pedidos",
      filename: "Detalle notas de pedido", // do not include extension
      fileext: ".xls", // file extension
    });

    activarDatatable();
  }

  const activarDatatable = () => {

    $('#tableMaestro').DataTable({
      "bLengthChange": true,
      "lengthMenu": [ [100], [100] ],
      "language": {
                  "lengthMenu": "mostrar _MENU_ registros",
                  "info":           "Mostrando registros del _START_ al _END_ de un total de  _TOTAL_ registros",
                  "paginate": {
                      "next":       "Siguiente",
                      "previous":   "Anterior"
                  },

      },
  
      
      "bInfo": false,
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

}