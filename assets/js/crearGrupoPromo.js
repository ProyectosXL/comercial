$(document).ready(function () {
    traerLocales();

    document.querySelector("#tableIndex").style.height= "620px";

});

const traerLocales = () => {
    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/promoBancaria/locales',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            let array = data.body;
            let tableBody = document.querySelector("#bodyAsignar");

            $('#tablaClientes').DataTable().destroy();
            // Limpiar el contenido actual de la tabla
            tableBody.innerHTML = "";
           
            // Recorrer los datos y agregar filas a la tabla
            array.forEach(element => {
                let row = tableBody.insertRow();
                let cell1 = row.insertCell(0);
        
                cell1.innerHTML = element.NRO_SUCURSAL +' - '+element.DESC_SUCURSAL;
                cell1.setAttribute("onclick", "marcarAgregar(this)");
              
            });


        }

    });
};

const marcarAgregar = (div) => {
    
    if(div.getAttribute("class") == 'marcadoAgregar'){

        div.classList.remove("marcadoAgregar");

    }else{

        div.classList.add("marcadoAgregar");

    }
}

const marcarQuitar = (div) => {
    
    if(div.getAttribute("class") == 'marcadoQuitar'){

        div.classList.remove("marcadoQuitar");

    }else{

        div.classList.add("marcadoQuitar");

    }
}

const agregar = () => {

    allSucursalesAgregar = document.querySelectorAll('.marcadoAgregar')
    let tablaAgregados = document.querySelector("#bodyAsignado")

    allSucursalesAgregar.forEach(element => {
        
        element.classList.remove('marcadoAgregar')
        element.setAttribute("onclick","marcarQuitar(this)")
        tr = element.parentElement
        tablaAgregados.append(tr);


    });

    ordernarTabla('tablaAsignado')

}

const quitar = () => {

    allSucursalesQuitar = document.querySelectorAll('.marcadoQuitar')
    let tablaAgregados = document.querySelector("#bodyAsignar")

    allSucursalesQuitar.forEach(element => {
        
        element.classList.remove('marcadoQuitar')
        element.setAttribute("onclick","marcarAgregar(this)")
        tr = element.parentElement
        tablaAgregados.append(tr);


    });

    ordernarTabla('tablaAsignar')
   

}

const ordernarTabla = (tabla) => {

    var filas = $(`#${tabla} tbody > tr`).get();
    filas.sort(function (a, b) {
      var numA = parseInt($(a).find('td').text().split(' ')[0]);
      var numB = parseInt($(b).find('td').text().split(' ')[0]);
      return numA - numB;
    });
    // Reorganizar las filas en la tabla
    $.each(filas, function (index, fila) {
      $(`#${tabla}`).append(fila);
    });

}

const crear = () => {

    let error = false ;
    let tablaAsignado = document.querySelectorAll("#bodyAsignado tr")

    let array = []

    

    if(document.querySelector("#nombreGrupo").value == ''){
        Swal.fire({
            icon: 'warning',
            title: 'Atención!',
            text: 'Debe establecer el nombre del grupo'
        })
        error = true
    }
 
    if(tablaAsignado.length < 1){
        Swal.fire({
            icon: 'warning',
            title: 'Atención!',
            text: 'Debe incluir al menos una sucursal'
        })
            error = true
    }

    if(!error){

        let nombreGrupo = document.querySelector("#nombreGrupo").value

        tablaAsignado.forEach(element => {

            array.push(element.querySelector("td").textContent.split(' ')[0])
            
        });

        const arrayTransformado = array.map(elemento => [elemento, nombreGrupo]);
        
    

        $.ajax({ 
            url: ' http://app.xl.com.ar:6002/api/promoBancaria/grupos',
            method: 'POST',
            contentType: "application/json",
            dataType: "json",
            headers: {
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                nombreGrupo:nombreGrupo,
                sucursales:arrayTransformado
            }),
            success: function (data) {
               
                Swal.fire({
                    icon: 'success',
                    title: 'Grupo creado exitosamente!',
                    showConfirmButton: false,
                    timer: 2500
                }).then(function () {
                    location.reload()
                }); 
    
            }
    
        });
    
        
    }
    


}