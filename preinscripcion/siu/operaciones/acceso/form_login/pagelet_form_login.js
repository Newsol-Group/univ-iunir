kernel.renderer.registrar_pagelet('form_login', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#usuario').focus();
			$('#frameRC').contents().on('keypress', function(e) {
				 if (e.which === 13) {
					$('#guarani_form_login').submit();
				}
			});
                        
                        // Cuando cambia "País emisor del documento"
                        $('#pais_documento').on('change', function ()
			{
                            cambio_pais_documento();
			});
                        
                        //---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults( $.datepicker.regional["es"] );
			$(id).find('input#fecha_nacimiento').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay: 1,
				yearRange: "-150:+0",
                                maxDate: 0
			});
		}
	};
        
        function cambio_pais_documento()
        {
            var valor = $('#pais_documento').val();
            // Si no se seleccionó "País emisor del documento"
            if (valor === null) {
                $('#tipo_documento').children().remove();
            } else {// Si se seleccionó "País emisor del documento"
                buscar_tipo_documento_paises(valor);
            }
        }
        
        function buscar_tipo_documento_paises(_pais_documento)
        {
            kernel.ui.show_loading();
            
            $.ajax({
                    url: info.url_tipo_documentos,
                    dataType: 'json',
                    data: {pais: _pais_documento},
                    type: 'POST',
                    success: function (data)
                    {
                        kernel.ui.hide_loading();
                        
                        $('#tipo_documento').children().remove();
                        $.each(data, function (key, value)
                        {
                            $('#tipo_documento').append(
                                $('<option></option>').val(key).html(value)
                            );
                        });
                        ordenar_combo('tipo_documento');
                    }
            });
        }
});
