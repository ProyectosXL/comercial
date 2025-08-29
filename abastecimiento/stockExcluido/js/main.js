
//Búsqueda rápida table//
document.getElementById('btn_delete').addEventListener('click',matrizOrdenes);
/* document.getElementById('save').addEventListener('click',guardar); */
$('#altaModal').modal('toggle')
    
$('#altaModalImport').modal('toggle')

$("#btn_export").click(function() {

    $('input[type=number]').each(function(){
        this.setAttribute('value',$(this).val());
    });
        
    $("table").table2excel({

        // exclude CSS class
        exclude: ".noExl",
        name: "Worksheet Name",
        filename: "ArticulosExcluidos", //do not include extension
        fileext: ".xls", // file extension

    });
});

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

  //Matriz de artículos a eliminar//

  function matrizOrdenes()
  {
    let checked=document.querySelectorAll("#check");
    let matriz=[];

    for(let i=0;i<checked.length;i++)
    {
      if (checked[i].parentElement.parentElement.style.display == "" && checked[i].checked == true ){ //si el tr está visible
        matriz[i]=checked[i].parentElement.parentElement.childNodes[1].textContent;
      }
    }
  //console.log(matriz);
  return matriz;
  }

    //Count checkbox artículos a eliminar//

    var cont = 0;

    function contar() {
      var checkbox = document.querySelectorAll('#tableIndex input[type="checkbox"]'); //Array que contiene los checkbox

      for (var x=0; x < checkbox.length; x++) {
      if (checkbox[x].checked) {
        cont = cont + 1;
        //console.log(cont);
      }
      }

    }

    //Revisa que se haya seleccionado algún artículo y lo borra de la tabla//

    // Actualización de la función deleteArticulo para usar el nuevo diseño
    function deleteArticulo() {
    const matriz = matrizOrdenes();
    const selectedCount = matriz.filter(id => id).length;
    
    if (selectedCount === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin selección',
        text: 'No hay ningún artículo seleccionado para eliminar',
        confirmButtonColor: '#3498db'
      });
      return;
    }

    Swal.fire({
      title: '¿Está seguro?',
      html: `Se eliminarán <strong>${selectedCount}</strong> artículo(s) seleccionado(s).<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e74c3c',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="bi bi-trash3"></i> Sí, eliminar',
      cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading
        Swal.fire({
          title: 'Eliminando...',
          text: 'Por favor espere',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          }
        });
        postear(matriz);
      }
    });
}


    // Actualización de la función postear
  function postear(matriz) {
    let env = 1;
    let url = (env == 1) ? 'deleteArticulo.php' : 'PedidoTest.php';
    
    $.ajax({
      url: 'Controller/' + url,
      method: 'POST',
      data: {
        matriz: matriz,
      },
      success: function (data) {
        Swal.fire({
          icon: 'success',
          title: '¡Eliminado exitosamente!',
          text: 'Los artículos han sido eliminados correctamente',
          confirmButtonColor: '#27ae60',
          confirmButtonText: '<i class="bi bi-check-lg"></i> Continuar'
        }).then(function () {
          window.location = "index.php";
        });
      },
      error: function(xhr, status, error) {
        Swal.fire({
          icon: 'error',
          title: 'Error al eliminar',
          text: 'Ocurrió un error al intentar eliminar los artículos.',
          confirmButtonColor: '#e74c3c'
        });
      }
    });
  }

      //Verifica si el artículo existe en la STA11//

      function traerDescripcion() {
        $("#inputDescrip").val("");

        var codigoAbuscar = $("#codArticulo").val();

        $.ajax({
          url: "Controller/buscarCodigo.php",
          method: "POST",
          data: {
            codArticu: codigoAbuscar,
          },
          success: function (data) {
            let datos = JSON.parse(data);
            if (datos.code != 200) {
              $("#inputDescrip").val("NO EXISTE EL ARTICULO SELECCIONADO");
            } else {
              let codigo = JSON.parse(datos.msg);
              $("#inputDescrip").val(codigo[0].DESCRIPCIO);
            }
          },
        });
      }

    let conexion;

    // Actualización de la función insertarArticulo
    function insertarArticulo() {
      let articulo = document.getElementById("codArticulo").value;
      let cantidad = document.getElementById("inputCant").value;
      let observaciones = document.getElementById("inputObs").value;

      if (!articulo) {
        Swal.fire({
          icon: 'warning',
          title: 'Campo requerido',
          text: 'Por favor seleccione un artículo',
          confirmButtonColor: '#3498db'
        });
        return;
      }

      if (!cantidad || cantidad <= 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Cantidad inválida',
          text: 'Por favor ingrese una cantidad válida',
          confirmButtonColor: '#3498db'
        });
        return;
      }

      // Show loading
      Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
          Swal.showLoading();
        }
      });

      $.ajax({
        url: "Controller/articuloController.php?action=update",
        method: "POST",
        data: {
          articulo: articulo,
          cantidad: cantidad,
          observaciones: observaciones
        },
        success: function (data) {
          procesarEvento();
        },
        error: function(xhr, status, error) {
          Swal.fire({
            icon: 'error',
            title: 'Error al guardar',
            text: 'Ocurrió un error al intentar guardar los cambios.',
            confirmButtonColor: '#e74c3c'
          });
        }
      });
    }

      function guardar()
      {
        //traer el json con las filas del excel
        console.log(columns);
      }

      
      // Actualización de procesarEvento
      function procesarEvento() {
        Swal.fire({
          icon: 'success',
          title: '¡Guardado exitosamente!',
          text: 'Los cambios han sido guardados correctamente',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        }).then(() => {
          redireccionar();
        });
      }