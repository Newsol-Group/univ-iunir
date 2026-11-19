kernel.renderer.registrar_pagelet('detalle', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			$('.js-ficha_alumno').on("click", function ()
			{
				var url = $(this).data('url-ficha');
				kernel.ui.pagelet_modal(url);
			});
		}
	}
})