kernel.renderer.registrar_pagelet('controles', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#js-btn-test').on('click', function (){
					kernel.ajax.load(info.url_controles_test1, '#resultado_test1', {
					historia: false,
					type: 'GET',
					reemplazar_nodo: false,
					success: function(data) {
						//kernel.renderer.pagelet('controles').onload();
					},
					error: function() {
						//kernel.renderer.pagelet('controles').onload();
					}
				});
				
			});
			

			
		}
	}

	
})