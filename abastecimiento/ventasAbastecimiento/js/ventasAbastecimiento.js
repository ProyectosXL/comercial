const cambiarEntorno = (t) => {
  
    let entorno = 0;
  
    if (t.getAttribute("data-off") == "ARG") {
      entorno = 0;
    } else {
      entorno = 1;
    }
  
    $.ajax({
      url: "../Controller/maestroDestinosController.php?accion=cambiarEntorno",
      method: "POST",
      data: { entorno: entorno },
      success: function (data) {
        location.reload();
      }
    });
    
}
  
  
  $(document).ready( function () {
        
    document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"


})


const buscar = () =>{

  let spinner = document.querySelector('.boxLoading')
  spinner.classList.add('loading')

  let rubros = document.querySelector("#rubro");
  let desde = document.querySelector("#desde").value;
  let hasta = document.querySelector("#hasta").value;
  let destino = document.querySelector("#destino").value
  let temporada = document.querySelector("#temporada").value
  let tipoSucursal = document.querySelector("#tipoSucursal").value
  
 let rubrosParaSql = "";
 var dt = $('#mytable').DataTable();
 dt.destroy();

 for (const option of rubros.options) {
    if (option.selected) {
      rubrosParaSql += "'" + option.value + "',";
    }
  }

  rubrosParaSql = rubrosParaSql.slice(0, -1);

  $.ajax({
    url: "Controller/ventasController.php",
    method: "POST",
    data: { 
      rubros: rubrosParaSql,
      desde: desde,
      hasta: hasta,
      destino: destino,
      temporada: temporada,
      tipoSucursal: tipoSucursal
     },
    success: function (data) {
      document.querySelector("#tbody").innerHTML = '';

      let tbody = document.querySelector("#tbody");

      let ventas = JSON.parse(data);
      let sum = 0;

      ventas.forEach(venta => {

        sum += venta.CANT_VEND;

        let tr = document.createElement("tr");

        let tdSucursal = document.createElement("td");
        tdSucursal.setAttribute("onClick", "sweet(this)");
        tdSucursal.innerHTML = venta.DESC_SUCURSAL;
        tr.appendChild(tdSucursal);

        let tdTipo = document.createElement("td");
        tdTipo.setAttribute("onClick", "sweet(this)");
        tdTipo.innerHTML = venta.TIPO_SUCURSAL;
        tdTipo.style.fontWeight = venta.TIPO_SUCURSAL === 'Franquicia' ? 'bold' : 'normal';
        tdTipo.style.color = venta.TIPO_SUCURSAL === 'Franquicia' ? '#0066cc' : '#006600';
        tr.appendChild(tdTipo);

        let tdArticulo = document.createElement("td");
        tdArticulo.setAttribute("onClick", "sweet(this)");
        tdArticulo.innerHTML = venta.ARTICULO;
        tr.appendChild(tdArticulo);

        let tdDescripcion = document.createElement("td");
        tdDescripcion.setAttribute("onClick", "sweet(this)");
        tdDescripcion.innerHTML = venta.DESCRIPCION;
        tr.appendChild(tdDescripcion);

        let tdCantVend = document.createElement("td");
        tdCantVend.setAttribute("onClick", "sweet(this)");
        tdCantVend.innerHTML = venta.CANT_VEND;
        tr.appendChild(tdCantVend);

        let tdRubro = document.createElement("td");
        tdRubro.setAttribute("onClick", "sweet(this)");
        tdRubro.innerHTML = venta.RUBRO;
        tr.appendChild(tdRubro);

        let tdCategoriaPadre = document.createElement("td");
        tdCategoriaPadre.setAttribute("onClick", "sweet(this)");
        tdCategoriaPadre.innerHTML = venta.CATEGORIA_PADRE;
        tr.appendChild(tdCategoriaPadre);

        let tdCategoria = document.createElement("td");
        tdCategoria.setAttribute("onClick", "sweet(this)");
        tdCategoria.innerHTML = venta.CATEGORIA;
        tr.appendChild(tdCategoria);

        let tdTemporada = document.createElement("td");
        tdTemporada.setAttribute("onClick", "sweet(this)");
        tdTemporada.innerHTML = venta.TEMPORADA;
        tr.appendChild(tdTemporada);

        let tdDestino = document.createElement("td");
        tdDestino.setAttribute("onClick", "sweet(this)");
        tdDestino.innerHTML = venta.DESTINO;
        tr.appendChild(tdDestino);

        let tdPrecio = document.createElement("td");
        tdPrecio.setAttribute("onClick", "sweet(this)");
        tdPrecio.innerHTML = "$" + new Intl.NumberFormat().format(venta.PRECIO);
        tr.appendChild(tdPrecio);

        tbody.appendChild(tr);

        

      });
      let cantidad = document.querySelector("#cantidad");

      cantidad.innerHTML = new Intl.NumberFormat().format(sum);

      spinner.classList.remove('loading')
      $('#mytable').DataTable( {
        dom: 'Bfrtip',
        ordering: true,
        orderClasses: false,
        buttons: [
            
      {
        extend: 'copy',
        footer: true,
        title: 'Venta locales',
        filename: 'Ventas',
        text: '<button class="btn btn-secondary ">Copiar <i class="fa fa-copy"></i></button>'
      }
            ,
          
      {
        //Botón para Excel
        extend: 'csv',
        footer: true,
        title: 'Venta locales',
        filename: 'Ventas',
        text: '<button class="btn btn-success ">CSV <i class="fa fa-file-excel-o"></i></button>'
      }
            ,
      
      {
        //Botón para Excel
        extend: 'excel',
        footer: true,
        title: 'Venta locales',
        filename: 'Ventas',
        text: '<button class="btn btn-success ">Excel <i class="fa fa-file-excel-o"></i></button>'
      }
            
            , 
            
      {
        extend: 'pdf',
        footer: true,
        title: 'Venta locales',
        filename: 'Ventas',
        text: '<button class="btn btn-danger eh">PDF <i class="fa fa-file-pdf-o"></i></button>'
      }
            
            , 
            
      // {
      //   extend: 'print',
      //   footer: true,
      //   title: 'Venta locales',
      //   filename: 'Ventas',
      //   text: '<button class="btn btn-info ">Imprimir <i class="fa fa-print"></i></button>'
      // }


        ]
      } );

    },
    error: function(xhr, status, error) {
      console.error('Error en AJAX:', error);
      console.error('Respuesta:', xhr.responseText);
      spinner.classList.remove('loading');
      alert('Error al cargar las ventas. Ver consola para detalles.');
    }
  });

}