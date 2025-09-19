const buscar = () => {

    let spinner = document.getElementById("boxLoading");
    spinner.className = " loading";

    let desde = document.querySelector('#desde').value;
    let hasta = document.querySelector('#hasta').value;
    let guia = document.querySelector('#guia').value;
    let cliente = document.querySelector('#cliente').value;
    let transp = document.querySelector('#transp').value;
    let token = localStorage.getItem('token');

    guia = guia != '' ? guia : '%' ;
    cliente = cliente != '' ? cliente : '%' ;
    transp = transp != '' ? transp : '%' ;

    $.ajax({

        url: 'https://app.xl.com.ar/api/despacho/guias',
        type: 'POST',
        contentType: "application/json",
        dataType: "json",
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify ({
            desde: desde,
            hasta: hasta,
            guia: guia,
            cliente: cliente,
            transp: transp
        }),
        success: function (response) {

            var tableBody = document.querySelector("#tableBody");

            // Verificar si DataTable ya existe antes de destruirla
            if ($.fn.DataTable.isDataTable('#tablaGuias')) {
                $('#tablaGuias').DataTable().destroy();
            }

            tableBody.innerHTML = '';

            // Verificar si hay datos
            if (response.body && response.body.length > 0) {
                response.body.forEach(function (v) {

                var row = document.createElement("tr");

                var fechaGuiaCell = document.createElement("td");
                // fechaGuiaCell.textContent = v["FECHA_GUIA"];
                // row.appendChild(fechaGuiaCell);
                // Crear un objeto Date a partir de la cadena de fecha proporcionada
                var fechaGuia = new Date(v["FECHA_GUIA"]);

                // Obtener el año, mes y día de la fecha
                var year = fechaGuia.getFullYear();
                var month = String(fechaGuia.getMonth() + 1).padStart(2, "0"); // Suma 1 al mes ya que los meses comienzan en 0
                var day = String(fechaGuia.getDate()).padStart(2, "0");

                // Formatear la fecha en el formato YYYY-MM-DD
                var formattedDate = year + "-" + month + "-" + day;

                fechaGuiaCell.textContent = formattedDate;
                row.appendChild(fechaGuiaCell);


                var nroGuiaCell = document.createElement("td");
                nroGuiaCell.textContent = v["NRO_GUIA"];
                row.appendChild(nroGuiaCell);


                var razonSocialCell = document.createElement("td");
                razonSocialCell.textContent = v["RAZON_SOCI"];
                row.appendChild(razonSocialCell);

                var bultosCell = document.createElement("td");
                bultosCell.textContent = v["BULTOS"];
                row.appendChild(bultosCell);

                var impoteCell = document.createElement("td");
                impoteCell.textContent ="$"+  parseNumber(v["IMPORTE"]);
                row.appendChild(impoteCell);

                var transporteCell = document.createElement("td");
                transporteCell.textContent = v["TRANSPORTE"];
           
                if(v["E_MAIL"] != null && v["E_MAIL"] != ''){
                    
                    transporteCell.innerHTML = v["TRANSPORTE"] + '<button class="btn btn-primary btn-sm" style="margin-left:2%;width: 36px;height: 29px;"  data-email="' + v["E_MAIL"] + '" onclick="copiarEmail(this)"><i class="bi bi-envelope-exclamation email-icon"></i></button>';
                }
                if(v["TELEFONO1"] != null && v["TELEFONO1"] != ''){
                    transporteCell.setAttribute("data-toggle", "tooltip");
                    transporteCell.setAttribute("data-placement", "top");
                    transporteCell.setAttribute("title", `${v["TELEFONO"]}`);
                }
                row.appendChild(transporteCell);

                var fechaContactoCell = document.createElement("td");
                var fechaContactoInput = document.createElement("input");
                fechaContactoInput.type = "date";
                fechaContactoInput.className = "form-control form-control-sm";
                fechaContactoInput.value = v["FECHA_CONTACTO"];
                if(v["FECHA_CONTACTO"] != null && v["FECHA_CONTACTO"] != ''){
               
                    fechaContactoInput.disabled = true;
                }
   
                fechaContactoCell.appendChild(fechaContactoInput);
                fechaContactoCell.style.textAlign = "center";
                row.appendChild(fechaContactoCell);


                var contactoCell = document.createElement("td");
                var contactoInput = document.createElement("input");
                contactoInput.type = "text";
                contactoInput.className = "form-control form-control-sm";
                contactoInput.value = v["CONTACTO"];
                if(v["CONTACTO"] != null && v["CONTACTO"] != ''){
                    contactoInput.disabled = true;
                }
                contactoCell.appendChild(contactoInput);
                contactoCell.style.textAlign = "center";
                row.appendChild(contactoCell);

                var medioContactoCell = document.createElement("td");
                var medioContactoSelect = document.createElement("select");
                medioContactoSelect.className = "form-control form-control-sm";
                medioContactoSelect.style.textAlign = "center";

                var opciones = ["WHATSAPP", "MAIL", "TELEFONO"];
                for (var i = 0; i < opciones.length; i++) {
                    var option = document.createElement("option");
                    option.text = opciones[i];
                    option.value = opciones[i];
                    medioContactoSelect.appendChild(option);
                }

                medioContactoSelect.value = v["MEDIO_CONTACTO"];

                
                if (v["MEDIO_CONTACTO"] != null && v["MEDIO_CONTACTO"] != '') {
                    medioContactoSelect.disabled = true;
                }

                medioContactoCell.appendChild(medioContactoSelect);
                row.appendChild(medioContactoCell);


                var fechaRetiroCell = document.createElement("td");
                var fechaRetiroInput = document.createElement("input");
                fechaRetiroInput.type = "date";
                fechaRetiroInput.className = "form-control form-control-sm";
                fechaRetiroInput.value = v["FECHA_RETIRO"];
                if(v["FECHA_RETIRO"] != null && v["FECHA_RETIRO"] != ''){
                    fechaRetiroInput.disabled = true;
                }
                fechaRetiroCell.appendChild(fechaRetiroInput);
                fechaRetiroCell.style.textAlign = "center";
                row.appendChild(fechaRetiroCell);


                var observacionesCell = document.createElement("td");
             
                var observacionesInput = document.createElement("input");
                observacionesInput.type = "text";
                observacionesInput.className = "form-control form-control-sm";
                observacionesInput.name = "observaciones[]";
                if (v["OBSERVACIONES"] == null || v["OBSERVACIONES"] == '') {
                    observacionesCell.appendChild(observacionesInput);
                } else {

                    observacionesInput.value = v["OBSERVACIONES"];
                    observacionesInput.disabled = true;
                    observacionesCell.appendChild(observacionesInput);
                    
                }
          
                row.appendChild(observacionesCell);

                tableBody.appendChild(row);
            });

            $('[data-toggle="tooltip"]').tooltip();
 
            $('#tablaGuias').DataTable({
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
            
            } else {
                // No hay datos, mostrar mensaje
                tableBody.innerHTML = '<tr><td colspan="11" class="text-center py-4"><i class="bi bi-info-circle"></i> No se encontraron resultados</td></tr>';
            }
            
            spinner.classList.remove("loading");
        },
        error: function (error) {
            console.log(error);
        }

    });

}


const guardar = () => {

    let allTr = document.querySelectorAll('#tableBody tr');
    let error = false;
    let arrayFilas = [];
    let token = localStorage.getItem('token');

    allTr.forEach(element => {

        if(element.childNodes[6].querySelector("input").value == '' && element.childNodes[7].querySelector("input").value == '' &&
            element.childNodes[8].querySelector("select").value == ''){
            return; 
        }

        if( element.childNodes[6].querySelector("input").value == '' || element.childNodes[7].querySelector("input").value == '' ||
            element.childNodes[8].querySelector("select").value == ''
        ){

            error = true;

        }
        let fechaDeRetiro = (element.children[9].children[0].value != "") ? element.children[9].children[0].value : null

        arrayFilas.push({
            nroGuia : element.children[1].textContent,
            fechaContacto: element.children[6].children[0].value,
            contacto: element.children[7].children[0].value,
            medioContacto: element.children[8].children[0].value,
            fechaRetiro: fechaDeRetiro,
            observaciones: element.children[10].children[0].value

        });
    });
   
    if(error){
        
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Debe completar los campos contacto, medio y fecha de retiro!',
        })
        return 1

    }else{
        Swal.fire({
            icon: 'warning',
            title: 'Desea guardar los cambios?',
            showDenyButton: true,
            confirmButtonText: 'Guardar',
            denyButtonText: 'Cancelar',
            }).then((result) => {
            /\ *Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
              
                $.ajax({

                    url: 'https://app.xl.com.ar/api/despacho/update-guias',
                    type: 'POST',
                    contentType: "application/json",
                    dataType: "json",
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({data: arrayFilas}),
                    success: function (response) {
                        Swal.fire('Guardado!', '', 'success')
                    }
                });
            } else if (result.isDenied) {
            Swal.fire('Los cambios no fueron guardados', '', 'info')
            }
            })

    }

}

const copiarEmail = (div) => {

    let email = div.getAttribute("data-email");
    let texto = document.createElement("textarea");
    document.body.appendChild(texto);
    texto.value = email;
    texto.select();
    document.execCommand("copy");
    document.body.removeChild(texto);
    alert("Correo copiado: " + email);

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