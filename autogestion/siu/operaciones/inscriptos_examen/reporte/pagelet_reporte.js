kernel.renderer.registrar_pagelet('reporte', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			var $filtro = $('form').formulario();

			$filtro.formulario('elemento', 'periodo_lectivo').on('change', function ()
			{
				var $periodo_lectivo = $(this);
				var anio = $periodo_lectivo.val() ?	$periodo_lectivo.find('option:selected').text().split(' - ')[0] : '';
				$filtro.formulario('elemento', 'anio_academico').val(anio);

			});

			$('#limpiar_actividad').on('click', function ()
			{
				$('#actividad').val('Todas');
				$('#actividad-autocomplete').val('');
			});

			$('#btn_filtro').on('click', function ()
			{
				guarani.cambiar_op({
					href: info.url_filtrar_comisiones + '?' + $filtro.serialize()
				});
				return false;
			});

			$('#btn_pdf').on('click', function ()
			{
				$filtro.formulario('submit_blank_get', info.url_generar_pdf);
				return false;
			});

			$('#btn_xls').on('click', function ()
			{
				$filtro.formulario('submit_blank_get', info.url_generar_xls);
				return false;
			});
		}
	};
});