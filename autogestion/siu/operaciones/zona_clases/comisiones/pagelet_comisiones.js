kernel.renderer.registrar_pagelet('comisiones', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			//si hubo error del lado del servidor
			if (info.hubo_error_validacion)
			{
				kernel.ui.show_mensaje(info.mensajes.error_periodo_lectivo_invalido, {tipo: 'alert-error', until_interaction: true});
			}

			var $filtro = $('form').formulario();

			$('#btn_filtro').on('click', function ()
			{
				guarani.cambiar_op({
					href: info.url_filtrar_comisiones + '?' + $filtro.serialize()
				});
				return false;
			});
		}
	};
});