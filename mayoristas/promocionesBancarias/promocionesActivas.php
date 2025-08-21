<?php 
   
   
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Promociones Activas</title>
        <?php 
            require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/assets/css/css.php';
        ?>
     <style>
        input[type='search'] {
            margin-right: 45px;
        }

        .dataTables_length {
            margin-left: 50px;
        }

        .dataTables_info {
            margin-left: 50px;
        }

        #bodyPromo_paginate {
            margin-right: 42px;
        }

    

        #tablaPromos th, #tablaPromos tbody td {
            white-space: nowrap;
            position: sticky;
            top:0px;
        }
        
        #tablaPromos th {

            z-index: 120;

        }

    
        #tablaPromos tbody td:nth-child(1){
            left: 0;
            background-color: #f2f2f2;
            z-index: 100;
            width: 10%;
        }

        #tablaPromos tbody td:nth-child(2) {
            left: 80px;
            background-color: #f2f2f2;
            z-index: 99;
            width: 10%;
        }


    </style>
    </head>

    <body style="overflow-y: hidden;">

    <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w880"><div style="color:white; text-align:center"><h6>Promociones Activas</h6></div>
                    <div class="card card-1" >
                        <div id="periodo" hidden></div>
                        <div class="row" style="margin-left:50px; margin-top:30px">
                        <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                            <img src="../../image/home-button.png" style="width:50px;height:45px;transition: transform 0.3s; margin-right: 1rem;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        </a> 
                            <h3><strong><img src="../../assets/css/images/etiqueta.png" alt="" style="width:30px;margin-bottom:20px"> Promociones Activas </strong></h3>
                        </div>
                        <div style="height:650px; overflow-x: auto;overflow-y: auto;">
                            <table class="table table-bordered" id="tablaPromos" style="margin-left: 50px; font-size:small;" cellspacing="0" data-page-length="100">
                                
                            <thead class="thead-dark" style="" >
                            
                                <tr id="trPromosActivas">
                                    <th style="text-align:center;width:10%" >CODIGO</th>
                                    <th style="text-align:center;width:10%" >PROMOCION</th>
                           
                                </tr>
                            </thead>
                            <tbody id="bodyPromo">
                                
                            </tbody>
            
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/assets/js/js.php';
            ?>
            <script>
            
  
            </script>
    </body>

</html>



