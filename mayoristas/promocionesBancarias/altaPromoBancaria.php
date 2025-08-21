<?php

?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Alta Promociones Bancarias</title>
        <?php 
            require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/assets/css/css.php';
        ?>
        

        </link>
    </head>
    <style>
  

    </style>
    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Alta Promociones Bancarias</h6></div>
                    <div class="card card-1">
                        <div class="row" style = "height:845px;width:100%;margin-left:10px;margin-bottom:5px">

                            <div class="col-3" style="border:solid 1px;">
                                <form action="">
                                    <div class="row">

                                        <label style="margin-left:20px"><i class="bi bi-bank2"></i> <strong>Promoción</strong></label>

                                        <select id="selectPromocion"  name="selectPromocion[]" style="width:100%;margin-left:20px" class="js-states form-control select2-hidden-accessible" multiple="" data-select2-id="selectRangoEtario" tabindex="-1" aria-hidden="true">                   

                                        </select>
                                    </div>
                                    <div style="margin-top:30px">
                                        <button type="button"  name="submit"class="btn btn-primary btn-block submit" style="height:40px" onclick="filtrar()">Filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                    </div>


                                    <div class="row" style="margin-top:5px">

                                        <label style="margin-left:20px"><i class="bi bi-people-fill"></i> <strong>Grupos</strong></label>

                                        <select id="selectGrupos"  name="selectGrupos[]" style="width:100%;margin-left:20px" class="js-states form-control select2-hidden-accessible" multiple="" data-select2-id="selectPromocion" tabindex="-1" aria-hidden="true">

                                        </select>
                                    </div>
                                </form>

                            </div>

                            <div class="col"  style="border:solid 1px;margin-left:20px;margin-right:20px">
                        
                                    <div class="row" style="margin-left:50px;margin-top: 20px;">
                                        <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                                            <img src="../../image/home-button.png" style="width:50px;height:45px;transition: transform 0.3s; margin-right: 1rem;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                        </a>
                                        <h3><i class="bi bi-cash-coin" style="margin-right: 20px; font-size: 50px"></i><strong>Alta promociones Bancarias</strong></h3>
                                    </div>
                                    <div class="row" style="margin-left:5px;margin-right:30px;;margin-top:30px">

                                        <div class="table-responsive" id="tableIndex">
                                            <div class="table-wrapper" id="tableIndex">
                                                <table class="table table-hover table-condensed table-striped text-center" id="tablaSucursales" style="width: 80%; margin-left: 25px;" cellspacing="0" data-page-length="10">
                                                    
                                                    <thead class="thead-dark" style="font-size: small;">
                                                        <th scope="col" style="width: 6%">Nro. Sucursal</th>
                                                        <th scope="col" style="width: 15%">Sucursal</th>
                                                        <!-- <th scope="col" style="width: 15%">Cod. Promo</th> -->

                                                        <th scope="col" style="width: 8%"></th>

                                                    </thead>

                                                    <tbody id="tablePB" style="font-size: small;">
                                                      
                                                    </tbody>

                                                </table>
                                            </div>
                                        </div>

                                    </div>
                        
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php 

            require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/assets/js/js.php';
        ?>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
       
    </body>

    </html>
    <script>    
    

    $(document).ready(function () {

        $('#selectGrupos').select2();
        $('#selectPromocion').select2();

    });


    // document.querySelector("#selectBanco").selectedOptions[0].value
    </script>
