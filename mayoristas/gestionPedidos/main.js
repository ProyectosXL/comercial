
function myFunction() {
    var input, filter, table, tr, td, td2, i, txtValue;
    input = document.getElementById("textbusq");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");
    //tr = document.getElementById('tr');
  
    for (i = 0; i < tr.length; i++) {
      visible = false;
      /* Obtenemos todas las celdas de la fila, no sólo la primera */
      td = tr[i].getElementsByTagName("td");
  
      for (j = 0; j < td.length; j++) {
        if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
          visible = true;
        }
      }
      if (visible === true) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }

        //Formato moneda de seleccionPedido.php//

        let importe = document.querySelectorAll('#importe');
        importe.forEach(el=>el.innerHTML=new Intl.NumberFormat("es-ar",{style: "currency", currency: "ARS", minimumFractionDigits: 0}).format(el.innerHTML))


  function selectCliente(){
    var tr = table.getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++){
        var x = document.getElementsByTagName("td")[1].innerHTML;
        console.log(x);
    }
  }
