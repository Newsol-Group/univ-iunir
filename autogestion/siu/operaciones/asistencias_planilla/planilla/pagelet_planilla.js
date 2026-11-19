kernel.renderer.registrar_pagelet('planilla', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			$filtro = $('form').formulario();

			var $form = $('#formulario_filtro');
			$("#boton_pdf").on('click', function ()
			{
				$('#formulario_filtro-flg_pdf').val(1);
				$form.attr('target', "_blank");
				$form.submit();
				return false;
			});

			$('#boton_buscar').on('click', function ()
			{
				$('#formulario_filtro-flg_pdf').val(0);
				$form.attr('target', "_self");
				$form.submit();
			});

			$("#formulario_filtro-fecha").datepicker("destroy");
			$('#formulario_filtro-fecha').datepicker();
		}
	};
});