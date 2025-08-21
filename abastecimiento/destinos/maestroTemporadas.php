<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coeficiente De Ajuste</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
        /* Estilo para el contenedor de la tabla */
        #tableTemporadaContainer {
            max-height: 400px; /* Establece la altura máxima */
            overflow-y: scroll; /* Activa el desbordamiento vertical con scrollbar */
            width: 100%;
        }
</style>

</head>

<body>

    <div class="modal fade" id="modalTemporadas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" id="modalDoc">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel"><span id="titleModal"><i class="bi bi-calendar3" aria-hidden="true" style="font-size: 25px;"></i> Administrar temporadas</h4></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button> 
                </div>
                    <div class="modal-body" id="modalTemporadaBody">
                        <div class="row">
                            <div class="col-11">     <div id="tableTemporadaContainer">
                            <table class="table table-hover table-condensed table-striped text-center" id="tableTemporada">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>TEMPORADA</th>
                                        <th>CODIGO</th>
                                        <th>EXCLUIR</th>

                                    </tr>
                                </thead>
                                <tbody id="tableTemporadaBody">
                                </tbody>
                            </table> 
                        </div>
                        </div>
                            <div class="col-1">
                                <div style="margin-top:360px">
                                    <i class="bi bi-plus-square-fill" style="margin-top:360px;font-size: 25px;" onclick="agregar()"></i>
                                </div>
                            </div>
                        </div>
                   
                    </div>
                    
                <div class="modal-footer">
                    
                    <button type="button" class="btn btn-primary"onclick="guardar()" hidden id="btn-guardar">Guardar <i class="bi bi-floppy"></i></button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js" integrity="sha512-TPh2Oxlg1zp+kz3nFA0C5vVC6leG/6mm1z9+mA81MI5eaUVqasPLO8Cuk4gMF4gUfP5etR73rgU/8PNMsSesoQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>



</body>    
<script>
      $(document).ready(function () {
            let tabla = document.querySelector("#tableTemporadaBody")

            $.ajax({
                url: "../Controller/maestroDestinosController.php?accion=traerTemporadas",
                type: "GET",
                success: function (response) {
                    let data = JSON.parse(response)
                    data = data.reverse();
                    data.forEach(element => {
                        
                        let excluir = `onclick="excluir(this,'1')"`;
                        if(element.EXCLUIR != null){
                            excluir = `checked onclick="excluir(this,'NULL')"` 
                        }
                        let row = document.createElement("tr")

                        let codigo = element.COD_TEMP
                        if(element.COD_TEMP == null){
                            codigo = ""
                        }

                        let textHtml =  `
                        <td>${element.NOMBRE_TEMP}</td>
                        <td>${codigo}</td>`;
                        textHtml += `<td><input type="checkbox" style="width:20px;height:20px" ${excluir} ></td>`
                
                  
                        row.innerHTML = textHtml;
                        tabla.appendChild(row)
                    });
                }
            })
        });
        const agregar = () => {
            let tabla = document.querySelector("#tableTemporadaBody")
            let row = document.createElement("tr")
            
            row.setAttribute("class","newRow")

            row.innerHTML = `
                <td><input type="text" style="width: 106.5px"></td>
                <td><input type="text" style="width: 56.5px"></td>
                <td><input type="checkbox" style="width:20px;height:20px"></td>
            `
            tabla.appendChild(row)

            document.querySelector("#btn-guardar").removeAttribute("hidden")
        }
        const excluir = (div,val) => {
          

            let nombreTemp = div.parentElement.parentElement.querySelector("td").textContent
            
            $.ajax({
                url: "../Controller/maestroDestinosController.php?accion=excluirTemporada",
                type: "POST",
                data: {nombreTemp:nombreTemp,excluir:val},
                success: function (response) {

                    if(val == 1){
                       
                        div.setAttribute("onclick","excluir(this,'NULL')")

                   
                    }else{

                        div.setAttribute("onclick","excluir(this,'1')")  

                    }

                }

            })
        }


        
       const guardar = () => {

        let allRows = document.querySelectorAll(".newRow")
        let stringParaSql = ""
        allRows.forEach(element => {
            let nombreTemp = element.querySelector("td").querySelector("input").value
            let codTemp = element.querySelector("td").nextElementSibling.querySelector("input").value
            let excluir = 'NULL'
            if( element.querySelector("td").nextElementSibling.nextElementSibling.querySelector("input").checked == true){
                excluir = 1
            }

            if(nombreTemp != "" && codTemp != ""){
                stringParaSql += `('${nombreTemp}','${codTemp}',${excluir}),`
            }
        });
        stringParaSql = stringParaSql.slice(0, -1);
        $.ajax({
            url: "../Controller/maestroDestinosController.php?accion=guardarTemporada",
            type: "POST",
            data: {stringParaSql:stringParaSql},
            success: function (response) {
      
                allRows.forEach(element => {
                    element.querySelectorAll("td")[0].innerHTML = element.querySelectorAll("td")[0].querySelector("input").value
                    element.querySelectorAll("td")[1].innerHTML = element.querySelectorAll("td")[1].querySelector("input").value
                    if(element.querySelectorAll("td")[2].querySelector("input").checked == true){
                        element.querySelectorAll("td")[2].innerHTML = `<input type="checkbox" style="width:20px;height:20px" checked onclick="excluir(this,'NULL')">`
                    }else{

                        element.querySelectorAll("td")[2].innerHTML = `<input type="checkbox" style="width:20px;height:20px" onclick="excluir(this,'1')">`
                    }
                    element.classList.remove("newRow")
                    document.querySelector("#btn-guardar").setAttribute("hidden","true")
                });
            }
        })
       }
   
</script>
