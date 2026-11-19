kernel.renderer.registrar_pagelet('home', function (info) {
	var id = '#' + info.id;

	//--------------------------------------------------------------------------
			
	function filtro_autocomplete() {
		$('#cuadro_busqueda').autocomplete({
			source: info.url_autocomplete_filtro,
			appendTo: id,
			minLength: $("#js-ui-autocomplete-min-length").val(),
			search: function() {
				kernel.ui.show_loading();
			},
			open: function() {
			},
			response: function(event, ui) {
				kernel.ui.hide_loading();
				if (ui.content.length === 0) {
					kernel.ui.show_mensaje(info.mensajes.no_se_encontraron_alumnos, {tipo: 'alert-error'});
				}
			},
			select: function( event, ui ) {
				guarani.cambiar_op({
					href: ui.item.id
				});
			}
		});

		$('#formulario_filtro-cuadro_busqueda').keypress(function(event){
			if(event.keyCode == 13){
				event.preventDefault();
			}
		});
	}

	//--------------------------------------------------------------------------

    return {
		onload: function () {
			
			//---- Manejo de botones para mostrar Comisiones / Mesas / Actas FC 
			$('.g3-form-form').prepend(info.botones_filtro);
			
			$.each($('.js-btn-mostrar'), function() {
				inicializar_boton(this.id);
			});
			
			$('.js-btn-mostrar').on('click', function(event) {
				click_boton(this.id);
				event.preventDefault();
				return false;
			});
			
			function click_boton(id)
			{
				if (id === 'btn-comisiones') {
					$('#comisiones').slideToggle(200);
					toggle_boton(id);
				} else if (id === 'btn-mesas') {
					$('#mesas').slideToggle(200);
					toggle_boton(id);
				} else if (id === 'btn-actas_fc') {
					$('#actas_fc').slideToggle(200);
					toggle_boton(id);
				}
			}
			
			function toggle_boton(id)
			{
				if ($('#'+id).hasClass('active')) {
					$('#'+id).removeClass('active');
					localStorage.setItem('class-'+id, '');
				} else {
					$('#'+id).addClass('active');
					localStorage.setItem('class-'+id, 'active');
				}
			}
			
			function inicializar_boton(id)
			{
				if (!((localStorage.getItem('class-'+id) === null) || (localStorage.getItem('class-'+id) == ''))) {
					click_boton(id);
				}
			}
			
			//---- Manejo de filtro --------------------------------------------
			
			filtro_autocomplete();
			
			var $filtro = $('form').formulario();

			$filtro.formulario('elemento', 'periodo_lectivo').on('change', function () {
				var $periodo_lectivo = $(this);
				var anio = $periodo_lectivo.val() ?
					$periodo_lectivo.find('option:selected').text().split(' - ')[0] :
					'';
				$filtro.formulario('elemento', 'anio_academico').val(anio);

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
                        
                        $('#actividad-autocomplete').on('change', function () {
                            if($(this).val() != '') { 
                                $('#actividad').prop('readonly', true); }
                            else { 
                                $('#actividad').prop('readonly', false); }
			});

			$('#limpiar_actividad').on('click', function () {
				$('#actividad').val('Todas');
				$('#actividad-autocomplete').val('');
                                $('#actividad-autocomplete').trigger('change');
			});

			$('#btn_filtro').on('click', function () {
				guarani.cambiar_op({
					href: info.url_filtrar + '?' + $filtro.serialize()
				});
				return false;
			});
		}
	};
});