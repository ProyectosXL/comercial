


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
        url: 'http://app.xl.com.ar:6002/api/despacho/tracking',
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