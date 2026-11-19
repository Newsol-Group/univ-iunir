kernel.renderer.registrar_pagelet('cabecera', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			$(id).find('#js-colapsar-info-clase').on("click", function ()
			{
				var detalle = $('.js-detalle-clase');

				if (detalle.is(':visible')) {
					detalle.slideUp();
				} else {
					detalle.slideDown();
				}
				return false;
			});


			$(id + ' .js-combo-clase').on('change', function ()
			{
				var url = $(this).find(':selected').data('link');
				guarani.cambiar_op({href: url});
			});

		}
	}
})