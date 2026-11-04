const toggleCargando = (cargando) => {
  const btnBuscar = document.querySelector("#btnBuscar");
  const iconoBuscar = document.querySelector("#iconoBuscar");
  const spinnerBuscar = document.querySelector("#spinnerBuscar");
  const textoCargando = document.querySelector("#textoCargando");
  const selectArticulo = document.querySelector("#selectArticulo");
  const loadingIndicator = document.querySelector("#loadingIndicator");
  const resultadoArticulo = document.querySelector("#resultadoArticulo");

  if (cargando) {
    if (btnBuscar) btnBuscar.disabled = true;
    if (selectArticulo) selectArticulo.disabled = true;
    if (iconoBuscar) iconoBuscar.classList.add("d-none");
    if (spinnerBuscar) spinnerBuscar.classList.remove("d-none");
    if (textoCargando) textoCargando.classList.remove("d-none");
    if (loadingIndicator) loadingIndicator.classList.remove("d-none");
    if (resultadoArticulo) resultadoArticulo.style.opacity = "0.3";
  } else {
    if (btnBuscar) btnBuscar.disabled = false;
    if (selectArticulo) selectArticulo.disabled = false;
    if (iconoBuscar) iconoBuscar.classList.remove("d-none");
    if (spinnerBuscar) spinnerBuscar.classList.add("d-none");
    if (textoCargando) textoCargando.classList.add("d-none");
    if (loadingIndicator) loadingIndicator.classList.add("d-none");
    if (resultadoArticulo) resultadoArticulo.style.opacity = "1";
  }
};

const traerArticulo = (div, usuarioUy = null) => {
  const codArticulo = div.value ? div.value.trim() : "";
  if (!codArticulo) {
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'Por favor, ingrese un código de artículo.',
    });
    return;
  }

  toggleCargando(true);

  $.ajax({
      url: "controller/StockPrecioController.php?accion=traerArticulos",
      type: "POST",
      data: {
        codArticulo: codArticulo,
        usuarioUy: usuarioUy
      },
      dataType: 'json',
      success: function (data) {
          if (data && data.error) {
              borrar();
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error,
              });
              return;
          }
          if(Array.isArray(data) && data.length > 0){
              document.querySelector("#articulo").value = data[0]['COD_ARTICU'] || '';
              document.querySelector("#descripcion").value = data[0]['DESCRIPCIO'] || '';
              document.querySelector("#destino").value = data[0]['DESTINO'] || '';
              document.querySelector("#stock").value = parseInt(data[0]['CANT_STOCK'] || 0);
              
              // Precio sin IVA
              document.querySelector("#precio_sin_iva").value = "$" + parseNumber(data[0]['PRECIO_S_IVA'], 2);
              
              // Calcular el IVA (diferencia entre precio con IVA y sin IVA)
              const precioConIva = parseFloat(data[0]['PRECIO'] || 0);
              const precioSinIva = parseFloat(data[0]['PRECIO_S_IVA'] || 0);
              const ivaValor = precioConIva - precioSinIva;
              document.querySelector("#iva").value = "$" + parseNumber(ivaValor, 2);
              
              // Precio con IVA
              document.querySelector("#precio").value = "$" + parseNumber(data[0]['PRECIO'] || 0);

              aplicarEstadoLiquidacion(data[0]['PRECIO_ANTERIOR'], data[0]['LIQUIDACION']);

              traerVariantes(codArticulo, usuarioUy);
          } else {
              borrar();
              Swal.fire({
                icon: 'info',
                title: 'Sin resultados',
                text: 'No se encontraron datos para este artículo.',
              });
          }
      },
      error: function(jqXHR, textStatus, errorThrown) {
          console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al obtener los datos del artículo. Por favor, intente de nuevo.',
          });
          borrar();
      },
      complete: function() {
          toggleCargando(false);
      }
  });
}

const aplicarEstadoLiquidacion = (precioAnterior, liquidacion) => {
  const badgeElement = document.querySelector('.estado-badge');
  const filaPrecioAnterior = document.querySelector('#filaPrecioAnterior');
  const filaPrecioFinal = document.querySelector('#filaPrecioFinal');

  // El precio anterior sale de comparar la lista 95 (base) contra la 20 (actual)
  if (precioAnterior !== null && precioAnterior !== undefined) {
    document.querySelector("#precio_anterior").value = "$" + parseNumber(precioAnterior);
    filaPrecioAnterior.classList.remove('oculto');
    filaPrecioFinal.classList.add('en-liquidacion');
  } else {
    filaPrecioAnterior.classList.add('oculto');
    filaPrecioFinal.classList.remove('en-liquidacion');
  }

  // El badge SALE depende de LIQUIDACION del maestro de destinos, no de las listas
  if (liquidacion === 'SI') {
    badgeElement.style.display = 'inline-block';
    badgeElement.textContent = 'SALE';
  } else {
    badgeElement.style.display = 'none';
  }
}

const traerVariantes = (codArticulo, usuarioUy) => {
  $.ajax({
    url: "controller/StockPrecioController.php?accion=traerVariantes",
    type: "POST",
    data: {
      codArticulo: codArticulo,
      usuarioUy: usuarioUy
    },
    dataType: 'json',
    success: function (variantes) {
      // Verificar si hay error
      if (variantes.error) {
        console.error("Error del servidor:", variantes.error);
        return;
      }
      
      // Verificar si es un array
      if (!Array.isArray(variantes)) {
        console.error("Las variantes no son un array:", variantes);
        return;
      }
      
      let tbodyStockPrecio = document.querySelector("#tbodyStockPrecio");
      tbodyStockPrecio.innerHTML = '';
      
      variantes.forEach(element => {
        // Solo agregar a la tabla si no es el artículo original
        if (element.COD_ARTICU !== codArticulo) {

          let tr = document.createElement("tr");
          let tdArticulo = document.createElement("td");
          let tdColor = document.createElement("td");
          let tdDestino = document.createElement("td");
          let tdStock = document.createElement("td");
          let tdPrecioSinIva = document.createElement("td");
          let tdIva = document.createElement("td");
          let tdPrecio = document.createElement("td");

          // Calcular el IVA como la diferencia entre precio con IVA y sin IVA
          const precioConIva = parseFloat(element.PRECIO);
          const precioSinIva = parseFloat(element.PRECIO_S_IVA);
          const ivaValor = precioConIva - precioSinIva;

          tdArticulo.textContent = element.COD_ARTICU;
          tdColor.textContent = element.COLOR;
          tdDestino.textContent = element.DESTINO || '';
          tdStock.textContent = element.CANT_STOCK;
          tdPrecioSinIva.textContent = "$" + parseNumber(element.PRECIO_S_IVA, 2);
          tdIva.textContent = "$" + parseNumber(ivaValor, 2);

          const enLiquidacion = element.PRECIO_ANTERIOR !== null && element.PRECIO_ANTERIOR !== undefined;
          if (enLiquidacion) {
            let spanAnterior = document.createElement("span");
            spanAnterior.className = "precio-anterior d-block";
            spanAnterior.textContent = "$" + parseNumber(element.PRECIO_ANTERIOR);

            let spanActual = document.createElement("span");
            spanActual.className = "en-liquidacion";
            spanActual.textContent = "$" + parseNumber(element.PRECIO);

            tdPrecio.appendChild(spanAnterior);
            tdPrecio.appendChild(spanActual);
            tr.classList.add('fila-liquidacion');
          } else {
            tdPrecio.textContent = "$" + parseNumber(element.PRECIO);
          }

          if (parseInt(element.CANT_STOCK) === 0) {
            tr.classList.add('fila-sin-stock');
          }

          tr.appendChild(tdArticulo);
          tr.appendChild(tdColor);
          tr.appendChild(tdDestino);
          tr.appendChild(tdStock);
          tr.appendChild(tdPrecioSinIva);
          tr.appendChild(tdIva);
          tr.appendChild(tdPrecio);

          tbodyStockPrecio.appendChild(tr);
          console.log("Fila agregada al DOM:", tr);
        }
      });
    },
    error: function(jqXHR, textStatus, errorThrown) {
      console.error("Error al obtener variantes:", textStatus, errorThrown);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Hubo un error al obtener las variantes del artículo. Por favor, intente de nuevo.',
      });
    }
  });
}

const parseNumber = (number, decimals = 0) => {
  // Si es un string, convertir a número
  number = typeof number === 'string' ? parseFloat(number) : number;

  // Un artículo del maestro puede no tener precio cargado en la lista
  if (number === null || number === undefined || isNaN(number)) {
    number = 0;
  }

  const newNumber = number.toLocaleString('de-De', {
      style: 'decimal',
      maximumFractionDigits: decimals,
      minimumFractionDigits: decimals
  });

  return newNumber;
}

const borrar = () => {
  document.querySelector("#selectArticulo").value = "";
  document.querySelector("#articulo").value = "";
  document.querySelector("#descripcion").value = "";
  document.querySelector("#destino").value = "";
  document.querySelector("#stock").value = "";
  document.querySelector("#precio").value = "";
  document.querySelector("#precio_sin_iva").value = "";
  document.querySelector("#iva").value = ""; // Limpiar también el campo de IVA
  document.querySelector("#precio_anterior").value = "";
  document.querySelector("#selectArticulo").focus();

  // Ocultar el estado de liquidación (badge, precio anterior, resaltado) al borrar
  aplicarEstadoLiquidacion(null, 'NO');

  // Limpiar la tabla de variantes
  let tbodyStockPrecio = document.querySelector("#tbodyStockPrecio");
  if (tbodyStockPrecio) {
    tbodyStockPrecio.innerHTML = '';
  }
}

// Agregar evento de tecla Enter al campo de búsqueda
document.getElementById('selectArticulo').addEventListener('keydown', function(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    document.querySelector('.btn-primary').click();
  }
});