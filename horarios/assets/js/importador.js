$(document).ready(function () {
    const apiExportarQPURL = 'api/exportar_quickpass.php';

    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7;
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    $('#imp_btnDescargar').on('click', function () {
        const semana = $('#imp_semanaSelector').val();

        if (!semana) {
            alert("Por favor, seleccione una semana para exportar.");
            return;
        }

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);

        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);

        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        // Redirigimos a la API de exportación (que es global por defecto)
        window.location.href = `${apiExportarQPURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
    });
});
