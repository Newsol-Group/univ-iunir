kernel.renderer.registrar_pagelet('recuperar_pass', function(info) {
	var id = '#' + info.id;

                 //---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults( $.datepicker.regional["es"] );
			$('input.date').datepicker({
				changeMonth: true,
				changeYear: true,
                                dateFormat: 'dd/mm/yy',
				firstDay:0,
				yearRange: "-150:+0"
			});
                //----Fin Date Picker -------------------------------------------------
	return {
		onload: function() {
                $('#formulario_email').submit(function ()
			{
                        if( $("#e_mail").val() ==''){
                        
					kernel.ui.show_mensaje('Error en el formulario', {tipo: 'alert-error'});
					return false;
				}
			});
                $('#formulario_documento').submit(function ()
			{
                        if(( $("#pais").val() =='')   ||  ( $("#tipo_documento").val() =='')  ||  ( $("#numero_documento").val() =='')  ||  ( $("#fecha_nacimiento").val() =='')){
                        
					kernel.ui.show_mensaje('Error en el formulario', {tipo: 'alert-error'});
					return false;
				}
			});
			$('#frameRC').contents().on('keypress', function(e) {
				 if(e.which == 13) {
					$('#guarani_form_recuperar_pass').submit();
				}
			});
		
                $('#formulario_email').hide();
                $('#formulario_documento').hide();
                $('#radio_documento').click(function(){
                //oculto formulario email y muestro docuemento
                $('#formulario_email').hide(); 
                $('#formulario_documento').show();
                $('#radio_email').prop('checked', false);

                });
                $('#radio_email').click(function(){
                //oculto formulario email y muestro docuemento
                $('#formulario_documento').hide(); 
                $('#formulario_email').show();
                 $('#radio_documento').prop('checked', false);
               
                });
                buscar_tipo_documento_pais($('#pais').val())
                $('#pais').change(function(){
                buscar_tipo_documento_pais($('#pais').val())
                });

                function buscar_tipo_documento_pais(_pais)
                {
                    $.ajax({
                        url: info.url_tipo_documentos,
                        dataType: 'json',
                        async: false,
                        data: {pais: _pais},
                        type: 'post',
                        success: function (data) {
                            $('#tipo_documento').children().remove();
                            $.each(data, function (key, value) {
                                $('#tipo_documento').append(
                                        $('<option></option>').val(key).html(value)
                                        );  
                            });
                            ordenar_combo('tipo_documento');

                        }
                    });
                }
		}
	}
})
