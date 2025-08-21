$(document).ready(function () {
    traerLocales();
    traerPromos();
    traerGrupos();
});

const traerLocales = () => {
    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/promoBancaria/locales',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            let array = data.body;
            armarTabla(array)
        }

    });
};

const traerPromos = () => {
    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/promoBancaria/promocionesAll',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            let array = data.body;
            // Obtén una referencia al elemento select
            var selectPromocion = document.querySelector("#selectPromocion");

            // Limpia el select para evitar duplicados
            selectPromocion.innerHTML = "";

            // Agrega opciones al select
            for (var i = 0; i < array.length; i++) {
                var option = document.createElement("option");
                option.value = array[i].ID_PROMOCION_TARJETA;  // Asigna un valor a la opción (puedes ajustarlo según tus necesidades)
                option.text = array[i].COD_PROMOCION_TARJETA +' - '+ array[i].DESC_PROMOCION_TARJETA + ' - ' +  array[i].PORC_DESCUENTO +'%';    // Asigna el texto de la opción
                selectPromocion.add(option);
            }
            $('#selectPromocion').select2();
        }
    })

}

const traerGrupos = () => {
    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/promoBancaria/grupos',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            let array = data.body;
          
            // // Obtén una referencia al elemento select
            var selectGrupo = document.querySelector("#selectGrupos");

            // // Limpia el select para evitar duplicados
            selectGrupos.innerHTML = "";
            // // Agrega opciones al select
            for (var i = 0; i < array.length; i++) {
                
                var option = document.createElement("option");
                option.value = array[i].NOMBRE;  // Asigna un valor a la opción (puedes ajustarlo según tus necesidades)
                option.text = array[i].NOMBRE;    // Asigna el texto de la opción
                selectGrupos.add(option);
            }
            $('#selectGrupos').select2();
        }
    })

}

const filtrar = () => {
   
    // let selectedValues = $('#selectPromocion').val();
    let selectGrupos = $('#selectGrupos').val();

    if( selectGrupos != ''){

  
        // let cadena = '(' + selectedValues.map(value => `'${value}'`).join(',') + ')';

        let cadenaGrupos = '(' + selectGrupos.map(value => `'${value}'`).join(',') + ')';

        
        $.ajax({
            url: 'http://app.xl.com.ar:6002/api/promoBancaria/traerLocalesPorGrupo',
            method: 'POST',
            contentType: "application/json",
            dataType: "json",
            headers: {
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                // codPromocion:cadena,
                grupos:cadenaGrupos
            }),
            success: function (data) {

                let array = data.body;
                let table = document.querySelector("#tablePB")

                var tabla = $('#tablaSucursales').DataTable();   
                var filas = tabla.rows().nodes();

                filas.each(function (e) {
                    e.querySelectorAll("td")[2].querySelector('input').checked = false
                    e.querySelectorAll("td")[2].querySelector('input').disabled = false
                    // e.querySelectorAll("td")[2].textContent = ''
                })
                console.log(array)
                array.forEach(element => {
                    
                    filas.each(function (e) {

                            if(element.NRO_SUCURSAL == e.querySelector("td").textContent){

                                e.querySelectorAll("td")[2].querySelector('input').checked = true
                                e.querySelectorAll("td")[2].querySelector('input').disabled = true

                            }

                    });
                  
                    

                });
            }

        });

    }else{

        traerLocales();

    }

    
}

const aplicar = () => {
  
    let tabla = $('#tablaSucursales').DataTable();   
    let selectedValues = $('#selectPromocion').val();
    let filas = tabla.rows().nodes();
    let arraySucursales = [];
    let  arrayTransformado = [];
  
    if(selectedValues.length < 1){
        Swal.fire({
            icon: 'error',
            title: 'Debe seleccionar al menos una promocion!',
            showConfirmButton: false,
            timer: 2500
        })
        return 1
    }
    
    filas.each(function (e) {
        if(e.querySelectorAll("td")[2].querySelector('input').checked == true ){
            selectedValues.forEach(element => {
                arraySucursales.push([e.querySelector("td").getAttribute("id_suc"),element])
            });
        }
    })


    if(arraySucursales.length < 1){
        Swal.fire({
            icon: 'error',
            title: 'Debe marcar al menos una sucursal!',
            showConfirmButton: false,
            timer: 2500
        })
        return 1
    }


    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/promoBancaria/altaPromo',
        method: 'POST',
        contentType: "application/json",
        dataType: "json",
        headers: {
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            arraySucursales:arraySucursales
        }),
        success: function (data) {
         
            Swal.fire({
                icon: 'success',
                title: 'Alta de promociones exitosa!',
                showConfirmButton: false,
                timer: 2500
            }).then(function () {
                location.reload()
            }); 

        }})


   
}


const armarTabla = (array) => {

    let tableBody = document.querySelector("#tablePB");

    $('#tablaSucursales').DataTable().destroy();
    // Limpiar el contenido actual de la tabla
    tableBody.innerHTML = "";
   
    // Recorrer los datos y agregar filas a la tabla
    array.forEach(element => {
        let row = tableBody.insertRow();
        let cell1 = row.insertCell(0);
        let cell2 = row.insertCell(1);
        let cell3 = row.insertCell(2);
        // let cell4 = row.insertCell(3);

        cell1.innerHTML = element.NRO_SUCURSAL;
        cell1.setAttribute("id_suc", element.ID_SUCURSAL)
        cell2.innerHTML = element.DESC_SUCURSAL;
        // cell4.innerHTML = '';
        cell3.innerHTML = "<input type='checkbox' style='width: 20px;height: 20px;'></input>";
       
  
    });

    // Destruir la instancia anterior de DataTable si existe
  

    // Inicializar DataTable con la nueva configuración
    $('#tablaSucursales').DataTable({
        "bLengthChange": false,
        "bInfo": false,
        "paging": true,
        "aaSorting": false,
        "lengthMenu":   [[10, 25, 50, -1],
        [10, 25, 50, 'All']],
        "pageLength": 10,
        'columnDefs': [
            {
                "targets": "_all",
                "className": "text-center",
                "sortable": false,
            },
        ],
        "oLanguage": {
            "sSearch": "Busqueda rápida:",
            "sSearchPlaceholder": "Sobre cualquier campo..."
        },
    });
    document.querySelector("#tablaSucursales_filter").style.width = '550px'
    $("#tablaSucursales_filter").append('<button class="btn btn-success " style="margin-bottom:4px;margin-left:10px;height:40px;margin-right:5px" onclick ="aplicar()"> Aplicar <i class="bi bi-check-square"></i></button>');
    $('.dataTables_filter input[type="search"]').css({'height':'40px'});

    // document.querySelector("#tablaSucursales_next").style.marginRight = "30%"
    let div = document.querySelector("#tablaSucursales_filter");
    // console.log(div.parentElement.parentElement.childNodes);
    
    let padre = div.parentElement.parentElement.childNodes[0];
    padre.append(div);
    padre.classList.remove("col-md-6")
    padre.classList.add("col-md-5")

    // Selecciona el primer input de tipo "search" en el documento
    let inputSearch = document.querySelector('input[type="search"]');
    inputSearch.style.width = "220px";

}