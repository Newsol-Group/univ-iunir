kernel.renderer.registrar_pagelet('formacion_acad', function(info) {
    var id = '#' + info.id;

	return {
        onload: function() {
			//---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults( $.datepicker.regional["es"] );
			$(id).find('input.date').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
				yearRange: "-70:+0"
			});
			
			$('.js-btnEliminar').on('click', function() {
				if (confirm(info.msj_confirmar_eliminar)) {
					submit();
					return true;
				}
				return false;
			});
        }
    }
	
});