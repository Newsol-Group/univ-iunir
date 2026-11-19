kernel.renderer.registrar_pagelet('filtro', function (info) {
	var id = '#' + info.id;
	
	return {
        onload: function () {
			
			$('#boton_buscar').on('click', function () {
                var $filtro  = $('#formulario_filtro');
                var action  = $filtro.attr('action');
                
				guarani.cambiar_op({
					href: action + '?' + $filtro.serialize()
				});
				return false;
			});
			
            $('#formulario_filtro-anio_academico').change(function(){
				buscarPeriodos($(this).val());
			});
            if (info.anio_academico_hash !== ""){
                $("#formulario_filtro-anio_academico option[value="+ info.anio_academico_hash +"]").attr("selected",true);
                $('#formulario_filtro-anio_academico').val(info.anio_academico_hash);
            }
            
            buscarPeriodos($('#formulario_filtro-anio_academico').val());
        }
    };
    
	function buscarPeriodos(anio_academico){
		$.ajax({
			url: info.url_buscar_periodos,
			dataType: 'json',
			data: {anio_academico: anio_academico},
			type: 'get',
			success: function(data) {
				var $elem_periodos = $('#formulario_filtro-periodo');
				$elem_periodos.children().remove();
				$elem_periodos.append(
					$('<option></option>').val('').html(info.mensaje_seleccione)
				);
				$.each(data, function(key, value) {
					if (value['periodo_lectivo'] == info.periodo){
						$elem_periodos.append($('<option selected="selected"></option>').val(value['periodo_lectivo']).html(value['periodo_lectivo_nombre']));
					} else {
						$elem_periodos.append($('<option></option>').val(value['periodo_lectivo']).html(value['periodo_lectivo_nombre']));
					}
				});
				
                if (info.periodo) {
                    $("#formulario_filtro-periodo option[value=" + info.periodo + "]").attr("selected", true);
                }
			}
		});
	}
});