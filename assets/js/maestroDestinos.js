$(document).ready(function () {
    $('#inputRubro, #inputTemp, #inputNovedades').select2({
        dropdownParent: $('body'),
        width: '100%',
        minimumResultsForSearch: 0
    });
});

const mostrarSpinner = () => {
    let spinner = document.querySelector('.boxLoading')
      spinner.classList.add('loading')
}
    
const filtrar = () => {
    mostrarSpinner();
    let rubro = document.querySelector('#inputRubro').value
    let temporada = document.querySelector('#inputTemp').value
    let novedades = document.querySelector('#inputNovedades').value
  
    if(rubro == ''){
      rubro = '%'
    }
  
    if(temporada == ''){
      temporada = '%'
    }
 
  
    $.ajax({
      url: '../Controller/maestroDestinosController.php?accion=filtrarDestinos',
      method: 'POST',
      data: {
        rubro: rubro,
        temporada: temporada,
        novedades: novedades
      },
      success: function (response) {
        data = JSON.parse(response)

        var tableBody = document.querySelector("#tableBody");
  
        $('#tableMaestro').DataTable().destroy();
  
        tableBody.innerHTML = ''; 
    
  
        
  
        data.forEach(function (v) {
    
    
  
  
            var row = document.createElement("tr");
  
            var codArticuCell = document.createElement("td");
            codArticuCell.textContent = v["COD_ARTICU"];
            row.appendChild(codArticuCell);

            var sinonimoCell = document.createElement("td");
            sinonimoCell.textContent = v["SINONIMO"];
            row.appendChild(sinonimoCell);
  
            var descripcionCell = document.createElement("td");
            descripcionCell.textContent = v["DESCRIPCION"];
            row.appendChild(descripcionCell);
  
            var categoriaCell = document.createElement("td");
            categoriaCell.textContent = v["CATEGORIA"] || "";
            row.appendChild(categoriaCell);
  
            var destinoCell = document.createElement("td");
            destinoCell.classList.add("destino");
            var selectDestino = document.createElement("select");
            selectDestino.classList.add("form-control");
            selectDestino.classList.add("form-control-sm");
            selectDestino.setAttribute("name", "destino");

            // Iterar sobre las opciones posibles (por ejemplo, "continuo" y "discontinuo")
            ["CONTINUO", "DISCONTINUO","DISCONTINUO VERANO","DISCONTINUO INVIERNO","GUARDAR","GUARDAR VERANO","GUARDAR INVIERNO"].forEach(function (optionValue) {
                var option = document.createElement("option");
                option.value = optionValue;
                option.textContent = optionValue;
                
                // Establecer como seleccionado si coincide con el valor de la base de datos
                if (optionValue === v["DESTINO"]) {
                    option.selected = true;
                }

                selectDestino.appendChild(option);
            });

            selectDestino.style.textAlign = "center";
            selectDestino.setAttribute("data-prev", v["DESTINO"] || "");
            selectDestino.setAttribute("onchange", "cambiarDestino(this)")
            destinoCell.appendChild(selectDestino);
            row.appendChild(destinoCell);

            var destinoAntCell = document.createElement("td");
            destinoAntCell.textContent = v["DESTINO_ANT"] || "";
            row.appendChild(destinoAntCell);

            var temporadaCell = document.createElement("td");
            temporadaCell.textContent = v["TEMPORADA"];
            row.appendChild(temporadaCell);
  
            var rubroCell = document.createElement("td");
            rubroCell.textContent = v["FAMILIA"];
            row.appendChild(rubroCell);

            var liquidacionoCell = document.createElement("td");
            liquidacionoCell.classList.add("liquidacion");
            var liquidacionDestino = document.createElement("select");
            liquidacionDestino.classList.add("form-control");
            liquidacionDestino.setAttribute("name", "liquidacion")
            ;

            // Iterar sobre las opciones posibles (por ejemplo, "continuo" y "discontinuo")
            ["", "SI"].forEach(function (optionValue) {
                var option = document.createElement("option");
                option.value = optionValue;
                option.textContent = optionValue;
                
                if(optionValue == ""){



                  if(v["LIQUIDACION"] == null){

                    option.selected = true;

                  }

                }else{

                  if (optionValue === v["LIQUIDACION"]) {

                      option.selected = true;
                      
                  }

                }
               
                // Establecer como seleccionado si coincide con el valor de la base de datos

                liquidacionDestino.appendChild(option);
            });
            liquidacionDestino.style.textAlign = "center";
            liquidacionDestino.setAttribute("onchange", "cambiarLiquidacion(this)")
            liquidacionoCell.appendChild(liquidacionDestino);
            row.appendChild(liquidacionoCell);

            var ultimaModCell = document.createElement("td");
            if(v["FECHA_MOD"] == null){

              ultimaModCell.textContent = "";
              
            }else{

              var fechaParseada = new Date(v["FECHA_MOD"].date);
              var año = fechaParseada.getFullYear();
              var mes = ('0' + (fechaParseada.getMonth() + 1)).slice(-2);  // Agregar 1 ya que los meses van de 0 a 11
              var dia = ('0' + fechaParseada.getDate()).slice(-2);
              var fechaFormateada = año + '-' + mes + '-' + dia;
              ultimaModCell.textContent = fechaFormateada;
            }
            row.appendChild(ultimaModCell);

            var excluirCell = document.createElement("td");
            var excluirCheck = document.createElement("input");
            excluirCheck.classList.add("excluir");
            excluirCheck.setAttribute("type","checkbox")
            if(v['EXCLUIR'] == 1){
              excluirCheck.setAttribute("checked","true")
            }

            excluirCheck.setAttribute("onclick","excluirArticulo(this)")

            excluirCell.appendChild(excluirCheck);
            row.appendChild(excluirCell);
  
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
            "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "paginate": {
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "sSearch": "Busqueda rapida:",
            "sSearchPlaceholder": "Sobre cualquier campo"
        },
        "bInfo": false,
        "order": [],
        "autoWidth": false,
        "scrollY": "calc(100vh - 280px)",
        "scrollCollapse": true,
        'columnDefs': [
            {
                "targets": "_all",
                "className": "text-center"
            },
            {
                // DESTINO (select), LIQUIDACION (select), EXCLUIR (checkbox)
                "targets": [4, 8, 10],
                "orderable": false
            }
        ],
    });

    $('.destino select').select2({
        dropdownParent: $('body'),
        width: '100%',
        minimumResultsForSearch: 0,
        placeholder: '-- Seleccionar --'
    });

  }

  
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
  

  const liquidar = () => {
    Swal.fire({
      icon:"question",
      title: "Desea dar por finalizada la liquidación?",
      showDenyButton: true,
      confirmButtonText: "Confirmar",
      denyButtonText: "Cancelar"
      }).then((result) => {
      /* Read more about isConfirmed, isDenied below */
      if (result.isConfirmed) {
    
        let table = document.querySelector("#tableBody").querySelectorAll("tr");
        let liquidacionSiString = "("
        let liquidacionNoString = "("

        table.forEach((tr) => {

          if(tr.querySelectorAll("td")[8].querySelector("select").value == "SI"){

            liquidacionSiString += "'" + tr.querySelectorAll("td")[0].textContent + "',"

          }else{

            liquidacionNoString += "'" + tr.querySelectorAll("td")[0].textContent + "',"

          }
        
        });
        liquidacionSiString = liquidacionSiString.slice(0,-1)
        liquidacionSiString += ")"
        liquidacionNoString = liquidacionNoString.slice(0,-1)
        liquidacionNoString += ")"

        $.ajax ({
          url: "../Controller/maestroDestinosController.php?accion=liquidar",
          method: "POST",
          data: {
            liquidacionSiString: liquidacionSiString,
            liquidacionNoString: liquidacionNoString
          },
          success: function (response) {
            console.log(response)
              Swal.fire("Liquidación finalizada!", "", "success");
          }
        
        })
      // console.log(table)
      } else if (result.isDenied) {
      Swal.fire("No se realizaron cambios", "", "info");
      }
      });
  }

  const cambiarDestino = (t) => {

    let codArticu = t.parentElement.parentElement.querySelectorAll("td")[0].textContent
    let destinoAnt = t.getAttribute("data-prev") || ""
    let destino = t.value
    $.ajax ({
      url: "../Controller/maestroDestinosController.php?accion=cambiarDestino",
      method: "POST",
      data: {
        codArticu: codArticu,
        destino: destino
      },
      success: function (response) {

        var fechaHoy = new Date();
        var año = fechaHoy.getFullYear();
        var mes = ('0' + (fechaHoy.getMonth() + 1)).slice(-2);
        var dia = ('0' + fechaHoy.getDate()).slice(-2);
        var fechaFormateada = año + '-' + mes + '-' + dia;

        let tds = t.parentElement.parentElement.querySelectorAll("td");
        tds[5].textContent = destinoAnt;
        tds[9].innerHTML = fechaFormateada;
        t.setAttribute("data-prev", destino);
      }

    })
  }

  const cambiarLiquidacion = (t) => {
      
      let codArticu = t.parentElement.parentElement.querySelectorAll("td")[0].textContent
      let liquidacion = t.value
      $.ajax ({
        url: "../Controller/maestroDestinosController.php?accion=cambiarLiquidacion",
        method: "POST",
        data: {
          codArticu: codArticu,
          liquidacion: liquidacion
        },
        success: function (response) {
          var fechaHoy = new Date();
          var año = fechaHoy.getFullYear();
          var mes = ('0' + (fechaHoy.getMonth() + 1)).slice(-2);
          var dia = ('0' + fechaHoy.getDate()).slice(-2);
          var fechaFormateada = año + '-' + mes + '-' + dia;
          t.parentElement.parentElement.querySelectorAll("td")[9].innerHTML = fechaFormateada
        }

      })

  }

  const mostrarModalImport = () => {
    resetearImport();
    $("#modalCA").modal("toggle");
  }

  const resetearImport = () => {
    document.querySelector("#modalCA").innerHTML = `
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" id="modalDoc">
            <div class="modal-header" style="background:#044f8e; color:white; padding:14px 20px;">
                <div>
                    <h5 class="modal-title mb-0" id="titleModal">
                        <i class="bi bi-file-earmark-excel"></i> Importar archivo Excel
                    </h5>
                    <small id="modal-subtitle" style="opacity:.75; font-size:.8rem;"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white;opacity:1;font-size:1.4rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalImportBody" style="padding:24px;">
                <div id="upload-area"
                     style="border:2px dashed #b8d0ed; border-radius:10px; padding:52px 24px; text-align:center; background:#f0f6ff; cursor:pointer; transition:border-color .2s, background .2s;"
                     onclick="document.getElementById('formFile').click()"
                     onmouseover="this.style.borderColor='#044f8e';this.style.background='#e4eef9';"
                     onmouseout="this.style.borderColor='#b8d0ed';this.style.background='#f0f6ff';">
                    <i class="bi bi-cloud-arrow-up" style="font-size:3.2rem; color:#044f8e;"></i>
                    <p style="margin-top:14px; font-size:1rem; color:#333; font-weight:500; margin-bottom:4px;">
                        Haga clic para seleccionar un archivo
                    </p>
                    <p style="font-size:.82rem; color:#999; margin-bottom:0;">Formatos aceptados: .xlsx, .xls</p>
                    <input type="file" id="formFile" style="display:none;" accept=".xlsx,.xls" onchange="displayFileName(this)">
                </div>
                <div style="text-align:center; margin-top:14px;">
                    <a href="#" onclick="descargarPlantillaExcel(); return false;" style="color:#198754; font-size:.87rem;">
                        <i class="bi bi-file-earmark-excel"></i> Descargar plantilla Excel
                    </a>
                </div>
            </div>
            <div class="modal-footer" style="background:#f8f9fa; border-top:1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
    `;
  }
  const mostrarModalTemp = () =>{
    $("#modalTemporadas").modal("toggle")
}
const descargarPlantillaExcel = () => {
 
    // Contenido del archivo Excel con dos celdas
    const contenido = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8,' +
                      encodeURIComponent('ARTICULO\tDESTINO\tLIQUIDACION\n'); // Usar '\t' para separar celdas
  
    // Crear un enlace de descarga
    const link = document.createElement('a');
    link.href = contenido;
    link.target = '_blank';
    link.download = 'archivo_excel.xls';
  
    // Agregar el enlace al DOM y hacer clic para descargar
    document.body.appendChild(link);
    link.click();
  
    // Limpiar el enlace
    document.body.removeChild(link);
  }

  const excluirArticulo = (check) =>{

    let articulo = check.parentElement.parentElement.querySelector("td").textContent
    let valor = check.checked

    if(valor == true){
      valor = 1
    }else{
      valor = 0
    }
   
    $.ajax ({
      url: "../Controller/maestroDestinosController.php?accion=excluirArticulo",
      method: "POST",
      data: {
        codArticu: articulo,
        valor: valor
      },
      success: function (response) {
       
      }
    
    })

  }


  const excluirMasivo = (check) => {

    let allCheck = document.querySelectorAll(".excluir")
    let valor = check.checked

    if(valor == true){
      valor = 1
    }else{
      valor = 'null'
    }


    let stringParaSql = "("

    allCheck.forEach(element => {

      if(valor == true){

        element.checked = true

      }else{

        element.checked = false

      }

      let articulo = element.parentElement.parentElement.querySelector("td").textContent

      stringParaSql += "'" + articulo + "',"
      
    });

    stringParaSql = stringParaSql.slice(0,-1)

    stringParaSql += ")"

    $.ajax ({
      url: "../Controller/maestroDestinosController.php?accion=excluirMasivo",
      method: "POST",
      data: {
        stringParaSql: stringParaSql,
        valor: valor
      },
      success: function (response) {
       
      }
    
    })
    
  }