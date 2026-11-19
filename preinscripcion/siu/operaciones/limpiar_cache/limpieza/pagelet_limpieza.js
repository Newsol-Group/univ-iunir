kernel.renderer.registrar_pagelet('limpieza', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#form_limpieza_cache').submit(function() {
				var $form = $(this);
					kernel.ajax.load($form.attr('action'), id, {
						historia: false,
						type: 'POST',
						data: $form.serializeArray(),
						success: function() {
						}
					});
				return false;
			});
		}
	}	
})
