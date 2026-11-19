kernel.renderer.registrar_pagelet('reporte', function (info) {
	var id = '#' + info.id;

	return {
		onload: function () {

			var $filtro = $('form').formulario();
		$('#docente').prop('disabled', true);

            $filtro.find("#desde").datepicker( "destroy" );
            $filtro.find("#hasta").datepicker( "destroy" );

            $filtro.find("#desde").datepicker({
                changeMonth: true,
                changeYear: true,
                onClose: function( selectedDate ) {
                    $filtro.find("#hasta").datepicker( "option", "minDate", selectedDate );
                }
            });

            $filtro.find("#hasta").datepicker({
                changeMonth: true,
                changeYear: true,
                onClose: function( selectedDate ) {
                    $filtro.find("#desde").datepicker( "option", "maxDate", selectedDate );
                }
            });

            $filtro.find("#desde").datepicker( "option", "maxDate", $filtro.find("#hasta").datepicker( "getDate" ) );
            $filtro.find("#hasta").datepicker( "option", "minDate", $filtro.find("#desde").datepicker( "getDate" ) );

			$filtro.formulario('elemento', 'periodo_lectivo').on('change', function () {
				var $periodo_lectivo = $(this);
				var anio = $periodo_lectivo.val() ?
					$periodo_lectivo.find('option:selected').text().split(' - ')[0] :
					'';
				$filtro.formulario('elemento', 'anio_academico').val(anio);

			})

			$('#limpiar_actividad').on('click', function () {
				$('#actividad').val('Todas');
				$('#actividad-autocomplete').val('');
                                $('#actividad-autocomplete').trigger('change');
			});
                        
                        (function() {
                            var ev = new $.Event('style'),
                            orig = $.fn.css;
                            $.fn.css = function() {
                                $(this).trigger(ev);
                                return orig.apply(this, arguments);
                            }
                        })();

                        $('#actividad').on('style', function(e) {
                            $('#actividad-autocomplete').trigger('change');
                        });
                        
                        $('#actividad-autocomplete').on('change', function ()
			{
                            if($(this).val() != '') { 
                                $('#actividad').prop('readonly', true); }
                            else { 
                                $('#actividad').prop('readonly', false); }
			});
                        
			$('#btn_filtro').on('click', function () {
				var valido = true;
				$('#desde').parents('.control-group').removeClass('error');
				$('#hasta').parents('.control-group').removeClass('error');
				
				if ($('#desde').val() == ''){
					$('#desde').parents('.control-group').addClass('error');
					valido = false;
				}
				if ($('#hasta').val() == ''){
					$('#hasta').parents('.control-group').addClass('error');
					valido = false;
				}
                                
				if ($('#docente').val() == ''){
					valido = false;
				}
				
				if (valido){
					guarani.cambiar_op({
						href: info.url_filtrar_mesas + '?' + $filtro.serialize()
					});
				}
				return false;
			});
                                                      
			$('#btn_pdf').on('click', function () {
				$filtro.formulario('submit_blank_get', info.url_generar_pdf);
				return false;
			});

			$('#btn_xls').on('click', function () {
				$filtro.formulario('submit_blank_get', info.url_generar_xls);
				return false;
			});
		}
	};
});
