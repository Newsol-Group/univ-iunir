kernel.renderer.registrar_pagelet('edicion_temas', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			$(id).on('click', '#js-copiar_temas', function ()
			{
                            $('#temas_dictados_text').val($('#temas_planificados_text').val());
			});




			$(id).find('.form-renglones').submit(function ()
			{
				var $form = $(this);
                                
				kernel.ajax.call($form.attr('action'), {
					type: 'POST',
					data: $form.serializeArray(),
					success: function (response)
					{
						kernel.ui.show_mensaje(response.cont);
					}
				});
				return false;
			});

		}
	}
})