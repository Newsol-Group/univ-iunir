kernel.renderer.registrar_pagelet('comisiones', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			$(id + ' #js-ver_mas').on('click', function ()
			{
				var periodo_lectivo = $('#js-comisiones').data('per_lec');
				kernel.ajax.call(info.url_ver_mas, {
					type: 'get',
					data: {
						periodo_lectivo: periodo_lectivo
					},
					success: function (paquete)
					{
						$('#js-comisiones').append(paquete.cont);
						if (paquete.periodo > 0) {
							$('#js-comisiones').data('per_lec', paquete.periodo);
							console.log(paquete.periodo);
						} else {
							$('#js-ver_mas').prop('disabled', true);
						}
					}
				});
			})
		}
	};
});