
// Check masivo //
const checkAll = document.querySelectorAll(".check");

checkAll.forEach((select) => {
  select.addEventListener("change", (e) => {
    guardarCheck(0, e);
  });
});


function guardarCheck(datoMasivo = 0, e) {
  let check;
  let dato = datoMasivo != 0 ? datoMasivo : e.target;
  if (dato.checked == true) {
    check = 1;
  } else {
    check = 0;
  }
}

function checkMasivo(source) {

  var checkboxes = document.querySelectorAll(".check");
  
  for (var i = 0; i < checkboxes.length; i++) {

      if (checkboxes[i] != source) checkboxes[i].checked = source.checked;

      guardarCheck(checkboxes[i]); 
      
  }
  contar();
}



document.getElementById("save").addEventListener("click", guardar);
let articulos = [];
//Importar excel//
let descripcion;
let b = 0;
let articulosAImportar = [];
var ExcelToJSON = function () {
  this.parseExcel = function (file) {
    var reader = new FileReader();
    reader.onload = function (e) {
      var data = e.target.result;
      var workbook = XLSX.read(data, {
        type: "binary",
      });
      workbook.SheetNames.forEach(function (sheetName) {
        var XL_row_object = XLSX.utils.sheet_to_row_object_array(
          workbook.Sheets[sheetName]
        );
        var productList = JSON.parse(JSON.stringify(XL_row_object));

        var rows = $("#tblItems tbody");

        for (i = 0; i < productList.length; i++) {
          let columns = Object.values(productList[i]);
          columns[0] =
            columns[0] != undefined && columns != "" ? columns[0] : " ";
          columns[1] =
            columns[1] != undefined && columns != "" ? columns[1] : " ";
          columns[2] =
            columns[2] != undefined && columns != "" ? columns[2] : " ";
          buscarCodigo(columns[0]);
          columns.push(descripcion); //agregar descripción al objeto
          if (articulos.includes(columns[0])) {
            /* columns[3]='ARTICULO EXISTENTE'; */
            descripcion='ARTICULO YA EXCLUIDO';
          }
          console.log(columns);
          articulosAImportar.push(columns); //arreglo con objetos para guardar en la db
          console.log("art a imp: " + articulosAImportar);
          rows.append(`
                              <tr>
                                  <td>${columns[0]}</td>
                                  <td>${columns[1]}</td>
                                  <td>${columns[2]}</td>
                                  <td id=${columns[0]}>${descripcion}</td>
                              </tr>
                          `);
          if (descripcion.includes("CODIGO INEXISTENTE")||descripcion.includes('ARTICULO YA EXCLUIDO')) {
            document.getElementById(columns[0]).style.color = "red";
          }
        
        }

        //  console.log(articulosAImportar);
      });
    };
    reader.onerror = function (ex) {
      // console.log(ex);
    };

    reader.readAsBinaryString(file);
  };
};

function handleFileSelect(evt) {
  var files = evt.target.files; // FileList object
  var xl2json = new ExcelToJSON();
  xl2json.parseExcel(files[0]);
}

document
  .getElementById("fileupload")
  .addEventListener("change", handleFileSelect, false);

//Recargar modal importar en botón Cerrar//

document.getElementById("close").addEventListener("click", () => {
  location.reload();
});

//Funciones botón Guardar//

document.getElementById("save").addEventListener("click", () => {
  if (b == 1) {
    Swal.fire({
      icon: "error",
      title: "Oops...",
      text: "No se puede guardar si existen codigos inválidos.",
    });
  }

  /*  location.reload(); */
});

//buscar codigo
let resultado;
function buscarCodigo(art) {
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      resultado = conexion.responseText;
      valores(resultado);
    }
  };

  conexion.open("GET", "articulos.php?codigo=" + art, false);
  conexion.send();
}

function valores(valor) {
  if (valor == "error") {
    /*  console.log("no existe"); */
    descripcion = "CODIGO INEXISTENTE";
    b = 1;
    Swal.fire({
      icon: "error",
      title: "Oops...",
      text: "Existen codigos incorrectos",
    });
  } else {
    /* console.log('descripcion: '+valor); */
    descripcion = valor;
  }
}

function modificarTabla() {
  let tabla = document.getElementsByTagName("table");
  let rows = tabla[1].rows;
}

function guardar() {
  if (b == 0) {
    //traer el json con las filas del excel
    console.log(articulosAImportar);
    conexion = new XMLHttpRequest();
    conexion.onreadystatechange = () => {
      if (conexion.readyState == 4 && conexion.status == 200) {
        Swal.fire({
          position: "top-end",
          icon: "success",
          title: "Cambios guardados",
          showConfirmButton: false,
          timer: 2000,
          x: setTimeout(function () {
            location.reload();
          }, 2500),
        });
      } else {
        Swal.fire({
          position: "top-end",
          icon: "error",
          title: "Ocurrio un error: " + conexion.responseText,
          showConfirmButton: false,
          timer: 1500,
        });
      }
    };
    conexion.open("POST", "./Controller/articuloController.php?action=insert", false);
    conexion.setRequestHeader(
      "Content-Type",
      "application/x-www-form-urlencoded"
    );
    let articulos =
      "articulos=" + encodeURIComponent(JSON.stringify(articulosAImportar));
    conexion.send(articulos);
  }
}

(function () {
  let table = document.getElementById("table");
  for (let i = 0; i < table.children.length; i++) {
    articulos.push(table.children[i].children[3].innerHTML);
  }
})();

// AGREGAR al final de main2.js:

// Enhanced visual feedback for import process
function updateImportTableStyle() {
  $('#tblItems tbody tr').each(function() {
    const statusCell = $(this).find('td').eq(3); // Description column
    const statusText = statusCell.text();
    
    if (statusText.includes('CODIGO INEXISTENTE') || statusText.includes('ARTICULO YA EXCLUIDO')) {
      $(this).addClass('table-danger');
      statusCell.prepend('<i class="bi bi-exclamation-triangle text-danger me-1"></i>');
    } else {
      $(this).addClass('table-success');
      statusCell.prepend('<i class="bi bi-check-circle text-success me-1"></i>');
    }
  });
}

// Enhanced guardar function with modern UI
function guardar() {
  if (b == 0) {
    // Show confirmation dialog
    const validArticles = articulosAImportar.filter(art => 
      !art[3].includes('CODIGO INEXISTENTE') && 
      !art[3].includes('ARTICULO YA EXCLUIDO')
    ).length;

    if (validArticles === 0) {
      Swal.fire({
        icon: 'error',
        title: 'Sin artículos válidos',
        text: 'No hay artículos válidos para importar',
        confirmButtonColor: '#3498db'
      });
      return;
    }

    Swal.fire({
      title: 'Confirmar importación',
      html: `Se importarán <strong>${validArticles}</strong> artículo(s) válido(s).<br><small class="text-muted">¿Desea continuar?</small>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#27ae60',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="bi bi-upload"></i> Importar',
      cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        executeImport();
      }
    });
  } else {
    Swal.fire({
      icon: 'error',
      title: 'Artículos inválidos',
      text: 'No se puede importar mientras existan códigos inválidos.',
      confirmButtonColor: '#e74c3c'
    });
  }
}

function executeImport() {
  // Show loading
  Swal.fire({
    title: 'Importando artículos...',
    text: 'Por favor espere mientras se procesan los datos',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    }
  });

  $.ajax({
    url: "./Controller/articuloController.php?action=insert",
    method: "POST",
    data: {
      articulos: JSON.stringify(articulosAImportar.filter(art => 
        !art[3].includes('CODIGO INEXISTENTE') && 
        !art[3].includes('ARTICULO YA EXCLUIDO')
      ))
    },
    success: function(response) {
      Swal.fire({
        icon: 'success',
        title: '¡Importación exitosa!',
        text: 'Los artículos han sido importados correctamente',
        confirmButtonColor: '#27ae60',
        confirmButtonText: '<i class="bi bi-check-lg"></i> Continuar'
      }).then(() => {
        location.reload();
      });
    },
    error: function(xhr, status, error) {
      Swal.fire({
        icon: 'error',
        title: 'Error en la importación',
        text: 'Ocurrió un error al importar los artículos: ' + error,
        confirmButtonColor: '#e74c3c'
      });
    }
  });
}

// Override the original Excel parsing to add modern styling
const originalHandleFileSelect = handleFileSelect;
function handleFileSelect(evt) {
  const files = evt.target.files;
  if (files.length === 0) return;
  
  // Clear previous data
  $('#tblItems tbody').empty();
  articulosAImportar = [];
  
  originalHandleFileSelect(evt);
  
  // Add styling after parsing
  setTimeout(() => {
    updateImportTableStyle();
  }, 1000);
}

