kernel.renderer.registrar_pagelet('filtro', function(info) {
	var id = '#' + info.id;


	function registrar_eventos() 
	{
		var filtro_actividades = function (id, value){

			$('#filtder').formulario('postear_y_reemplazar', '#mesas', function(){});
			return false;
		}

		var $filtro = $('#filtder').formulario();

		$filtro.formulario('elemento','actividad')
						.plugin_autocompletar('change_settings',{
								select: filtro_actividades
							});
	}

	return {
		onload: function() {
			registrar_eventos();
		}
	}
})
