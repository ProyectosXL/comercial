const armarTabla = () =>{
    $.ajax({
        url: 'http://app.xl.com.ar:6002/api/promoBancaria/promociones',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            let array = data.body;
         
            $.ajax({
                url: 'http://app.xl.com.ar:6002/api/promoBancaria/promosPorSucursal',
                method: 'GET',
                dataType: 'json',
                success: function (response) {

                    $.ajax({
                        url: 'http://app.xl.com.ar:6002/api/promoBancaria/sucursales',
                        method: 'GET',
                        dataType: 'json',
                        success: function (sucursales) {
                            sucursales = sucursales.body

                            let arrayResponse = response.body
                            let arraySucursales = []
                            let numeroSucursal = []
                            let tabla = document.querySelector("#bodyPromo")
                        
             
                                    
                                
                                array.forEach(element => {
                                    var tr = document.createElement('tr')
                                    
                                    var td1 = document.createElement('td')
                                    var td2 = document.createElement('td')
        
        
                                    var contenidoTexto1 = document.createTextNode(element.COD_PROMOCION_TARJETA);
                                    var contenidoTexto2 = document.createTextNode(element.DESC_PROMOCION_TARJETA);
        
                                    td1.appendChild(contenidoTexto1);
                                    td2.appendChild(contenidoTexto2);
        
                                    tr.appendChild(td1)
                                    tr.appendChild(td2)
        
                                    sucursales.forEach(e => {
                                
                        
                                        if(!(arraySucursales.includes(e.DESC_SUCURSAL))){
                                            arraySucursales.push(e.DESC_SUCURSAL)
                                            numeroSucursal.push(e.NRO_SUCURSAL)
                                            var nuevoTh = document.createElement('th')
        
                                            var contenidoTexto = document.createTextNode(e.DESC_SUCURSAL);
                                            nuevoTh.appendChild(contenidoTexto);
                                            nuevoTh.style.width = '20px'
                                            document.querySelector("#trPromosActivas").appendChild(nuevoTh)
                                        
        
                                            
                                            
                                            
                                        }
                                        
                                        
                                    });
        
                                    numeroSucursal.forEach(sucursal =>{
                                        let td =  document.createElement('td')
                                        td.setAttribute("id",'A'+sucursal+'-'+element.COD_PROMOCION_TARJETA)
                                        td.style.textAlign='center'
                                        tr.appendChild(td)
                                    })
        
                                    tabla.appendChild(tr)
        
                            
        
                                });
        
                         
                            
        
                            arrayResponse.forEach(e => {
                                
        
                                // console.log(e)
                                document.querySelector(`#A${e.NRO_SUCURSAL}-${e.COD_PROMOCION}`).innerHTML = '<i class="bi bi-check-circle-fill" style="color:green"></i>'
                            
        
                            })

                        }
                    })
                

             
                    // console.log(arraySucursales)
                }
            })
   
        }
    })



}
armarTabla()
