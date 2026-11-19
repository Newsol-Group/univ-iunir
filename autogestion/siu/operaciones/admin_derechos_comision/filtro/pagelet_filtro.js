kernel.renderer.registrar_pagelet('filtro', function(info) {
	var id = '#' + info.id;


	function registrar_eventos() 
	{
		var filtro_actividades = function (id, value){

			$('#filtder').formulario('postear_y_reemplazar', '#comisiones', function(){});
			return false;
		}

		var $filtro = $('#filtder').formulario();

		$filtro.formulario('elemento', 'dias')
					.botonera('change_settings', {
				'on_change' : function(checkbox, $grupo){
									console.log("se cambiaron los dias " +$grupo.botonera('valores'));
								}
			});

		$filtro.formulario('elemento', 'turnos')
				.botonera('change_settings', {
					'on_change' : function(checkbox, $grupo){
							console.log("se cambiaron los turnos " +$grupo.botonera('valores'));
					}
			});

		$filtro.formulario('elemento','actividad')
						.plugin_autocompletar('change_settings',{
								select: filtro_actividades
							});
	}

	return {
		onload: function() {
			registrar_eventos();
//			$(id).find('.form-algo').guarani_form();
//			$(id).find('.form-algo').guarani_form('submit', function(response) {
//				$('#comisiones').html(response.cont);
//			});
		}
	}
})
