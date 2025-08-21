//Búsqueda rápida table//

function myFunction() {
    var input, filter, table, tr, td, td2, i, txtValue;
    input = document.getElementById("textBox");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");
    //tr = document.getElementById('tr');
  
    for (i = 0; i < tr.length; i++) {
      visible = false;
      /* Obtenemos todas las celdas de la fila, no sólo la primera */
      td = tr[i].getElementsByTagName("td");
  
      for (j = 0; j < td.length; j++) {
        if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
          visible = true;
        }
      }
      if (visible === true) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }

 //Quitar el evento submit de la tecla enter//

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type=text]').forEach( node => node.addEventListener('keypress', e => {
      if(e.keyCode == 13) {
        e.preventDefault();
      }
    }))
  });

const mostrarSpinner = () => {
  let spinner = document.querySelector('.boxLoading')
	spinner.classList.add('loading')
}


const filtrar = () => {
  
  mostrarSpinner();
  let rubro = document.querySelector('#inputRubro').value
  let temporada = document.querySelector('#inputTemp').value

  if(rubro == ''){
    rubro = '%'
  }

  if(temporada == ''){
    temporada = '%'
  }


  $.ajax({
    url: '../Controller/maestroDestinosController.php?accion=filtrar',
    method: 'POST',
    data: {
      rubro: rubro,
      temporada: temporada
    },
    success: function (response) {
      data = JSON.parse(response)


      var tableBody = document.querySelector("#tableBody");

      $('#tableMaestro').DataTable().destroy();

      tableBody.innerHTML = ''; 
  

      

      data.forEach(function (v) {


          var row = document.createElement("tr");

        
  
          var imgCell = document.createElement("td");
          imgCell.classList.add("imagen");

          var imgLink = document.createElement("a");
          imgLink.setAttribute("data-toggle", "modal");
          imgLink.setAttribute("data-target", "#modalImageDiv");

          var imgElement = document.createElement("img");
          imgElement.setAttribute("src", "../../../Imagenes/" + v["COD_ARTICU"].substring(0, 13) + ".jpg");
          imgElement.setAttribute("alt", "Sin imagen");
          imgElement.setAttribute("height", "50");
          imgElement.setAttribute("width", "50");

          imgLink.appendChild(imgElement);


          imgCell.appendChild(imgLink);

          imgLink.addEventListener("click", function() {
            var modalImage = document.getElementById("modalImage");
            modalImage.setAttribute("src", "../../../Imagenes/" + v["COD_ARTICU"].substring(0, 13) + ".jpg");
            $("#exampleModala").modal("toggle");
          });
          row.appendChild(imgCell);


          var codArticuCell = document.createElement("td");
          codArticuCell.textContent = v["COD_ARTICU"];
         

          if(v["FECHA_MOD"] != null){

            var fechaOriginal = v["FECHA_MOD"].date; 
            
            var fechaObjeto = new Date(fechaOriginal);
            var options = { year: 'numeric', month: '2-digit', day: '2-digit' };
            var fechaFormateada = fechaObjeto.toLocaleDateString('es-ES', options);

          }
          codArticuCell.setAttribute("fecha", fechaFormateada);
          row.appendChild(codArticuCell);

          var descripcionCell = document.createElement("td");
          descripcionCell.textContent = v["DESCRIPCION"];
          row.appendChild(descripcionCell);

          var destinoCell = document.createElement("td");
          destinoCell.textContent = v["DESTINO"];
          row.appendChild(destinoCell);

          var temporadaCell = document.createElement("td");
          temporadaCell.textContent = v["TEMPORADA"];
          row.appendChild(temporadaCell);

          var rubroCell = document.createElement("td");
          rubroCell.textContent = v["RUBRO"];
          row.appendChild(rubroCell);

          tableBody.appendChild(row);
      });
      
      activarDatatable();
    
      let spinner = document.querySelector('.boxLoading')
      spinner.classList.remove('loading')
    }
  })

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

const exportNovedades = () => {

  $('#tableMaestro').DataTable().destroy();

  allTr = document.querySelectorAll('#tableBody tr');
  let fechaMasCercana;
  let trFechaMasCercana;
  

  allTr.forEach((tr) => {
    // Obtener el campo de fecha de cada fila
    let fechaCell = tr.querySelectorAll('td')[1].getAttribute("fecha"); // Reemplaza 'fecha-class' con la clase real de tu campo de fecha

    // Verificar si la celda de fecha existe y tiene un valor
    if (fechaCell && fechaCell !== '' && fechaCell !== ' ' && fechaCell !== null && fechaCell !== "undefined") {

      // Convertir la fecha al formato "YYYY/MM/DD" a un objeto Date
     // Convertir la fecha al formato "YYYY/MM/DD" a un objeto Date
     let fechaParts = fechaCell.split('/');
     let fechaFila = new Date(`${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`);

     let fechaActual = new Date(); // Obtener la fecha actual


      // Comparar las fechas
      if (!fechaMasCercana || Math.abs(fechaFila - fechaActual) < Math.abs(fechaMasCercana - fechaActual)) {
        fechaMasCercana = fechaFila;
        trFechaMasCercana = tr;
      }
    }
  });

  allTr.forEach((tr) => {

    let fecha = tr.querySelectorAll('td')[1].getAttribute("fecha");
    if(fecha != "undefined"){

      let fechaParts = fecha.split('/');

      let fechaFila = new Date(`${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`);

   

      if(fechaFila.getTime() != fechaMasCercana.getTime()){

          tr.classList.add('imagen');

      }

    }else{
      tr.classList.add('imagen');
    }

  });

  $("#tableMaestro").table2excel({
    // exclude CSS class
    exclude: ".imagen",
    name: "Detalle pedidos",
    filename: "Detalle notas de pedido", // do not include extension
    fileext: ".xls", // file extension
  });

  allTr.forEach((tr) => {
    tr.classList.remove('imagen');
  });
  
  activarDatatable();
}
