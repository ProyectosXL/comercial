<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coeficiente De Ajuste</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"
        integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">


    <style>

    </style>
</head>

<body>

    <div class="modal fade" id="modalCA" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"
        integrity="sha512-TPh2Oxlg1zp+kz3nFA0C5vVC6leG/6mm1z9+mA81MI5eaUVqasPLO8Cuk4gMF4gUfP5etR73rgU/8PNMsSesoQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>



</body>
<script>

    const displayFileName = (input) => {
        var fileName = input.files[0].name;
        input.parentNode.parentNode.querySelector('input[type="text"]').value = fileName;

        document.querySelector('#Examinar').hidden = true;

        document.querySelector(".modal-footer").innerHTML = `
                <button type="button" class="btn btn-danger" onclick="clearFileInput()">Borrar</button>
                <button type="button" class="btn btn-success" onclick="readExcelFile()">Subir<i class="bi bi-box-arrow-up"></i></button>
            `;
    }
    const readExcelFile = () => {
        let input = document.querySelector("#formFile")
        var file = input.files[0];
        var reader = new FileReader();

        reader.onload = function (e) {

            var data = new Uint8Array(e.target.result);
            var workbook = XLSX.read(data, { type: 'array' });

            // Asumimos que solo hay una hoja en el libro
            var sheet = workbook.Sheets[workbook.SheetNames[0]];
            document.querySelector('#modalDoc').style.width = "600px";
            document.querySelector('#titleModal').innerHTML = "<i class='bi bi-arrow-repeat' aria-hidden='true' style='font-size: 25px;'></i>Actualizar maestro de destinos";

            // Convertir la hoja de cálculo a un arreglo de objetos
            var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });
            let arrayDeError = [];
            let arrayDeErrorLiquidacion = [];

            jsonData.forEach((element, x) => {
                if (x == 0) {
                    return 1;
                }
                if (element[1] != 'CONTINUO' && element[1] != 'DISCONTINUO' && element[1] != 'DISCONTINUO VERANO' && element[1] != 'DISCONTINUO INVIERNO' && element[1] != 'GUARDAR') {
                    arrayDeError.push(element[0]);
                }

                if (element[2] != 'SI' && element[2] != null) {
                    arrayDeErrorLiquidacion.push(element[0]);
                }

            });

            if (arrayDeError.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en el archivo',
                    text: `Los siguientes articulos tienen un error en la columna de destino: ${arrayDeError.join(', ')}`
                })
                return 1;
            }

            if (arrayDeErrorLiquidacion.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en el archivo',
                    text: `Los siguientes articulos tienen un error en la columna de liquidacion: ${arrayDeErrorLiquidacion.join(', ')}, Solo se permiten los valores SI o vacio`
                })
                return 1;
            }

            var tableHtml = `<table class="table table-hover table-condensed table-striped text-center" id="tableImportExcel">
                <thead class="thead-dark">
                    <tr>
                        <th>Articulo</th>
                        <th>Descripcion</th>
                        <th>Destino</th>
                        <th>Liquidacion</th>
                        <th></th>

                    </tr>
                </thead>
                <tbody>`;

            $.ajax({

                type: "POST",
                url: "../Controller/maestroDestinosController.php?accion=traerDescripcion",
                data: {
                    data: JSON.stringify(jsonData)
                },
                success: function (res) {

                    res = JSON.parse(res);

                    jsonData.forEach((element, x) => {

                        element[0] = element[0] || '';

                        let descripcion = '';

                        res.forEach((element2, y) => {
                            if (element[0] == element2[0]) {
                                descripcion = element2[1];
                            }
                        });

                        element[1] = element[1] || '';
                        element[2] = element[2] || '';

                        if (x > 0) {
                            tableHtml += `
                                    <tr>
                                        <td>${element[0]}</td>
                                        <td>${descripcion}</td>
                                        <td>${element[1]}</td>
                                        <td>${element[2]}</td>
                                        <td></td>
                                    </tr>`;
                        }

                    });

                    tableHtml += `</tbody></table>`;
                    document.querySelector('#modalImportBody').innerHTML = tableHtml;
                    document.querySelector(".modal-footer").innerHTML = `
                            <button type="button" class="btn btn-success" onclick="actualizar()">Actualizar <i class='bi bi-arrow-repeat'></i></button>
                        `;


                }
            })

            return 1;
            // Ahora jsonData contiene los datos del archivo Excel



        };

        reader.readAsArrayBuffer(file);


    }

    const clearFileInput = () => {
        var fileInput = document.querySelector('#formFile');
        fileInput.value = ''; // Esto limpia el valor del input de archivo
        document.querySelector('#Examinar').hidden = false;
        document.querySelector('#nombreInput').value = ""
    }

    const actualizar = () => {

        let allRows = document.querySelectorAll('#tableImportExcel tbody tr');
        let newArray = [];

        allRows.forEach(element => {
            let articulo = element.querySelectorAll('td')[0].textContent;
            let descripcion = element.querySelectorAll('td')[1].textContent;
            let destino = element.querySelectorAll('td')[2].textContent;
            let liquidacion = element.querySelectorAll('td')[3].textContent;

            newArray.push([articulo, descripcion, destino, liquidacion]);
        });

        $.ajax({
            type: "POST",
            url: "../Controller/maestroDestinosController.php?accion=comprobarArticulos",
            data: {
                data: JSON.stringify(newArray)
            },
            success: function (data) {
                data = JSON.parse(data);
                let puedeContinuar = true;
                allRows.forEach(element => {
                    let errores = '';
                    if (element.querySelectorAll('td')[0].textContent == '') {
                        errores += 'Articulo (Requerido) <br>';
                        puedeContinuar = false;
                    }

                    if (element.querySelectorAll('td')[2].textContent == '') {
                        errores += 'Destino (Requerido) <br>';
                        puedeContinuar = false;
                    }

                    data.forEach(articulo => {
                        if (element.querySelectorAll('td')[0].textContent == articulo[0]) {
                            console.log(element.querySelectorAll('td')[4]);
                            if (articulo[1] == 1) {
                                // element.querySelectorAll('td')[4].style.backgroundColor = "red";
                            } else {
                                errores += 'Articulo no existe En el Maestro <br>';
                                puedeContinuar = false;
                                // element.querySelectorAll('td')[4].style.backgroundColor = "green";
                            }

                        }
                    });
                    if (errores != '') {

                        element.querySelectorAll('td')[4].innerHTML = `<i class="bi bi-exclamation-circle-fill" style="color:red;width" title="${errores.replace(/<br>/g, '\n')}" data-toggle="tooltip" ></i>`;
                    }

                });
                if (puedeContinuar) {

                    console.log('puede continuar')

                    $.ajax({
                        type: "POST",
                        url: "../Controller/maestroDestinosController.php?accion=actualizarMaestro",
                        data: {
                            data: JSON.stringify(newArray)
                        },
                        success: function (data) {


                            Swal.fire({
                                icon: 'success',
                                title: 'Actualizado correctamente',
                                showConfirmButton: false,
                                timer: 1500
                            }).then((result) => {
                                location.reload();
                            })

                        }
                    });

                } else {
                    console.log('no puede continuar')

                }
                // if(data == "success"){
                //     Swal.fire({
                //         icon: 'success',
                //         title: 'Actualizado correctamente',
                //         showConfirmButton: false,
                //         timer: 1500
                //     }).then((result) => {
                //         location.reload();
                //     })
                // }else{
                //     Swal.fire({
                //         icon: 'error',
                //         title: 'Error al actualizar',
                //         showConfirmButton: false,
                //         timer: 1500
                //     })
                // }
            }

        })


        $('[data-toggle="tooltip"]').tooltip();
    }


</script>