/**
 * Script de diagnóstico para verificar problemas con la visualización de imágenes
 * Ejecutar en la consola del navegador: diagnosticarImagenes()
 */

window.diagnosticarImagenes = function() {
    console.log('🔧 === DIAGNÓSTICO DE SISTEMA DE IMÁGENES ===');
    console.log('');
    
    // 1. Verificar jQuery
    console.log('1️⃣ jQuery:', typeof $ !== 'undefined' ? '✅ Disponible (v' + $.fn.jquery + ')' : '❌ NO disponible');
    
    // 2. Verificar Fancybox
    console.log('2️⃣ Fancybox:', typeof Fancybox !== 'undefined' ? '✅ Disponible' : '❌ NO disponible');
    
    // 3. Verificar SweetAlert2
    console.log('3️⃣ SweetAlert2:', typeof Swal !== 'undefined' ? '✅ Disponible' : '❌ NO disponible');
    
    // 4. Verificar DataTables
    console.log('4️⃣ DataTables:', typeof $.fn.DataTable !== 'undefined' ? '✅ Disponible' : '❌ NO disponible');
    
    // 5. Verificar si la tabla existe y tiene datos
    const tableExists = $('#gastosTable').length > 0;
    console.log('5️⃣ Tabla #gastosTable:', tableExists ? '✅ Existe' : '❌ NO existe');
    
    if (tableExists && $.fn.DataTable.isDataTable('#gastosTable')) {
        const table = $('#gastosTable').DataTable();
        const rowCount = table.rows().count();
        console.log('   └─ Filas en tabla:', rowCount);
    }
    
    // 6. Verificar botones de ver fotos
    const buttonCount = $('.verFotos').length;
    console.log('6️⃣ Botones ".verFotos":', buttonCount > 0 ? `✅ ${buttonCount} encontrados` : '❌ Ninguno encontrado');
    
    // 7. Verificar selectors importantes
    console.log('7️⃣ Selectores:');
    console.log('   ├─ #semanaRetail:', $('#semanaRetail').length ? `✅ Valor: "${$('#semanaRetail').val()}"` : '❌ NO encontrado');
    console.log('   └─ #supervisoraSelect:', $('#supervisoraSelect').length ? `✅ Valor: "${$('#supervisoraSelect').val()}"` : '❌ NO encontrado');
    
    // 8. Probar acceso a una imagen de prueba
    console.log('8️⃣ Probando ruta de imágenes...');
    const testUrl = '/comercial/imagenes/compSupervision/';
    console.log('   └─ Ruta base:', testUrl);
    
    console.log('');
    console.log('🔍 Para probar la carga de imágenes manualmente, ejecuta:');
    console.log('   probarCargaImagenes(ID_CONC, "tarjeta" o "efectivo")');
    console.log('');
    console.log('📋 Ejemplo: probarCargaImagenes(1, "tarjeta")');
};

/**
 * Función para probar la carga de imágenes manualmente
 */
window.probarCargaImagenes = function(id_conc, type) {
    console.log('🧪 === PRUEBA DE CARGA DE IMÁGENES ===');
    console.log('ID_CONC:', id_conc);
    console.log('Type:', type);
    
    const semanaCompleta = $('#semanaRetail').val();
    const supervisora = $('#supervisoraSelect').val();
    
    if (!semanaCompleta) {
        console.error('❌ No hay semana seleccionada');
        return;
    }
    
    const [semana, anio] = semanaCompleta.split('|');
    
    const data = {
        id_conc: id_conc,
        semana: semana,
        anio: anio,
        supervisora: supervisora,
        type: type
    };
    
    console.log('📤 Datos a enviar:', data);
    console.log('🌐 URL:', 'Controller/obtenerImagenesSup.php');
    
    $.ajax({
        url: 'Controller/obtenerImagenesSup.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json'
    })
    .done(function(response) {
        console.log('✅ Respuesta exitosa:', response);
        
        if (response.success && response.imagenes && response.imagenes.length) {
            console.log(`🖼️ Imágenes encontradas (${response.imagenes.length}):`);
            response.imagenes.forEach((img, i) => {
                console.log(`   ${i + 1}. ${img}`);
                
                // Verificar si la imagen existe
                const testImg = new Image();
                testImg.onload = function() {
                    console.log(`   ✅ Imagen ${i + 1} carga correctamente`);
                };
                testImg.onerror = function() {
                    console.error(`   ❌ Imagen ${i + 1} NO se puede cargar`);
                };
                testImg.src = img;
            });
            
            console.log('');
            console.log('🔍 Debug info del servidor:');
            console.log(response.debug);
        } else {
            console.warn('⚠️ No se encontraron imágenes:', response.message);
        }
    })
    .fail(function(xhr, status, error) {
        console.error('❌ Error en la petición:', error);
        console.error('Status:', status);
        console.error('Response:', xhr.responseText);
    });
};

/**
 * Función para probar Fancybox con una imagen de prueba
 */
window.probarFancybox = function() {
    console.log('🎬 Probando Fancybox...');
    
    if (typeof Fancybox === 'undefined') {
        console.error('❌ Fancybox NO está disponible');
        return;
    }
    
    // Usar una imagen de prueba de internet
    const testImages = [
        {
            src: 'https://via.placeholder.com/800x600/FF0000/FFFFFF?text=Imagen+1',
            caption: 'Imagen de prueba 1'
        },
        {
            src: 'https://via.placeholder.com/800x600/00FF00/FFFFFF?text=Imagen+2',
            caption: 'Imagen de prueba 2'
        }
    ];
    
    try {
        Fancybox.show(testImages, {
            loop: false
        });
        console.log('✅ Fancybox funciona correctamente');
    } catch (error) {
        console.error('❌ Error al abrir Fancybox:', error);
    }
};

// Auto-ejecutar diagnóstico al cargar
console.log('%c💡 Sistema de diagnóstico cargado', 'color: #4CAF50; font-weight: bold;');
console.log('%cEjecuta diagnosticarImagenes() para ver el estado del sistema', 'color: #2196F3;');
