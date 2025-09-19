
	// Inicializar DataTable vacía al cargar la página para evitar errores
	$(document).ready(function() {
		// Verificar si ya existe una instancia de DataTable
		if ($.fn.DataTable.isDataTable('#tablaGuias')) {
			$('#tablaGuias').DataTable().destroy();
		}
		
		// Agregar fila de mensaje inicial
		$('#tableBody').html('<tr><td colspan="11" class="text-center py-4"><i class="bi bi-info-circle"></i> Use los filtros para buscar guías</td></tr>');
		
		// Verificar que Select2 esté disponible antes de inicializar
		if (typeof $.fn.select2 !== 'undefined') {
			// Inicializar Select2 para cliente
			$('#cliente').select2({
				placeholder: 'Buscar cliente...',
				allowClear: true,
				language: {
					noResults: function() {
						return "No se encontraron clientes";
					},
					searching: function() {
						return "Buscando...";
					}
				}
			});
			
			// Cargar datos de clientes
			loadClientesData();
			
			// Inicializar Select2 para transporte
			$('#transp').select2({
				placeholder: 'Buscar transporte...',
				allowClear: true,
				language: {
					noResults: function() {
						return "No se encontraron transportes";
					},
					searching: function() {
						return "Buscando...";
					}
				}
			});
			
			// Cargar datos de transportes
			loadTransportesData();
			
		} else {
			console.warn('Select2 no está disponible. Los selectores funcionarán como campos de texto normales.');
		}
	});
	
	// Cargar datos de clientes desde API
	function loadClientesData() {
		let token = localStorage.getItem('token');
		
		$.ajax({
			url: 'https://app.xl.com.ar/api/despacho/clientes', 
			method: 'GET',
			headers: {
				'Authorization': `Bearer ${token}`,
				'Content-Type': 'application/json'
			},
			success: function(response) {
				console.log('Clientes cargados:', response);
				
				// Procesar datos según la estructura de la API
				let clientesData = response.body.map(cliente => ({
					id: cliente.RAZON_SOCI,
					text: cliente.RAZON_SOCI 
				}));
				
				// Actualizar el Select2 con los nuevos datos
				updateClienteSelect2(clientesData);
			},
			error: function(xhr, status, error) {
				console.warn('Error cargando clientes:', error);
				// Usar datos de ejemplo si falla la API
				let clientesData = [
					{ id: 'CLIENTE001', text: 'CLIENTE EJEMPLO 1 S.A.' },
					{ id: 'CLIENTE002', text: 'COMERCIAL DEL SUR LTDA.' },
					{ id: 'CLIENTE003', text: 'DISTRIBUIDORA NORTE S.R.L.' }
				];
				updateClienteSelect2(clientesData);
			}
		});
	}
	
	// Cargar datos de transportes desde API
	function loadTransportesData() {
		let token = localStorage.getItem('token');
		
		$.ajax({
			url: 'https://app.xl.com.ar/api/despacho/transportes',
			method: 'GET',
			headers: {
				'Authorization': `Bearer ${token}`,
				'Content-Type': 'application/json'
			},
			success: function(response) {
				console.log('Transportes cargados:', response);
				
				// Procesar datos según la estructura de la API
				let transportesData = response.body.map(transporte => ({
					id: transporte.NOMBRE_TRA,
					text: transporte.NOMBRE_TRA
				}));
				
				// Actualizar el Select2 con los nuevos datos
				updateTransporteSelect2(transportesData);
			},
			error: function(xhr, status, error) {
				console.warn('Error cargando transportes:', error);
				// Usar datos de ejemplo si falla la API
				let transportesData = [
					{ id: 'ANDREANI', text: 'ANDREANI' },
					{ id: 'OCA', text: 'OCA' },
					{ id: 'CORREO_ARG', text: 'CORREO ARGENTINO' },
					{ id: 'VIA_CARGO', text: 'VIA CARGO' },
					{ id: 'EXPRESO_SINGER', text: 'EXPRESO SINGER' }
				];
				updateTransporteSelect2(transportesData);
			}
		});
	}
	
	// Función auxiliar para actualizar Select2 de clientes
	function updateClienteSelect2(clientesData) {
		try {
			if ($('#cliente').hasClass('select2-hidden-accessible')) {
				$('#cliente').select2('destroy');
			}
			
			$('#cliente').select2({
				placeholder: 'Buscar cliente...',
				allowClear: true,
				data: clientesData,
				language: {
					noResults: function() {
						return "No se encontraron clientes";
					},
					searching: function() {
						return "Buscando...";
					}
				}
			});
		} catch (error) {
			console.log('Error inicializando Select2 para clientes:', error);
		}
	}
	
	// Función auxiliar para actualizar Select2 de transportes
	function updateTransporteSelect2(transportesData) {
		try {
			if ($('#transp').hasClass('select2-hidden-accessible')) {
				$('#transp').select2('destroy');
			}
			
			$('#transp').select2({
				placeholder: 'Buscar transporte...',
				allowClear: true,
				data: transportesData,
				language: {
					noResults: function() {
						return "No se encontraron transportes";
					},
					searching: function() {
						return "Buscando...";
					}
				}
			});
		} catch (error) {
			console.log('Error inicializando Select2 para transportes:', error);
		}
	}