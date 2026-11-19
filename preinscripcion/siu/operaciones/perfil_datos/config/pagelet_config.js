kernel.renderer.registrar_pagelet('config', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			
                        
                    $('.js-seleccionar-checkbox-todos-ninguno').on('change', function () {
                        
                        // Si checked = true marca a todos, si checked = false desmarca a todos
                        var checked = $(this).is(':checked');
                        $(this).parents('.js-grupo-checkboxes').find('.js-contenedor-checkboxes input[type="checkbox"]').prop('checked', checked);
                        
                        // Cambio el label según el estado
                        var label = checked ? info.mensajes.ninguno : info.mensajes.todos;
                        $(this).next('span').text(label);
                    });
                    
		}
	};
});
