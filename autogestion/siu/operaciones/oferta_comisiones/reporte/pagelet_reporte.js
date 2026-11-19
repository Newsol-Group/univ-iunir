kernel.renderer.registrar_pagelet('reporte', function(info) {
	var id = '#' + info.id;
	var periodo = '';
	var actividad = '';
	var docente = '';
	var ubicacion = '';
	var dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo', 'sin_definir'];

	return {
		onload: function() {
			$(id).on('click', '#js-colapsar-filtro', function() {
				$('.filtros').toggle(500);
			});

			$(id).on('click', '.js-filtro_dias', function() {
				var dia = $(this);
				if (dia.hasClass('active'))
				{
					dia.removeClass('active');
					dias.splice($.inArray(dia.attr('dia'), dias), 1);
				}
				else
				{
					dia.addClass('active');
					dias.push(dia.attr('dia'));
				}
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			});

			function filtro_docente(id, value) {
				docente = id;
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			}

			$('#js-filtro_docente').plugin_autocompletar({
				url: info.url_buscar_docentes,
				select: filtro_docente,
				valor_default: {
					id: '',
					value: 'Todos'
				}
			});

			$('#js-limpiar_docente').on('click', function() {
				$('#js-filtro_docente').val('Todos');
				$('#js-filtro_docente-autocomplete').val('');
				filtro_docente('', 'Todos');
			});

			$(id).on('change', '#js-filtro_ubicacion', function() {
				ubicacion = $(this).val();
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			});

			function filtro_actividades(id, value) {
				actividad = id;
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			}

			$('#js-filtro_actividades').plugin_autocompletar({
				url: info.url_buscar_actividades,
				select: filtro_actividades,
				valor_default: {
					id: '',
					value: 'Todas'
				}
			});

			$('#js-limpiar_actividades').on('click', function() {
				$('#js-filtro_actividades').val('Todas');
				$('#js-filtro_actividades-autocomplete').val('');
				filtro_actividades('', 'Todas');
                                $('#js-filtro_actividades-autocomplete').trigger('change');
			});

                        (function() {
                            var ev = new $.Event('style'),
                            orig = $.fn.css;
                            $.fn.css = function() {
                                $(this).trigger(ev);
                                return orig.apply(this, arguments);
                            }
                        })();

                        $('#js-filtro_actividades').on('style', function(e) {
                            $('#js-filtro_actividades-autocomplete').trigger('change');
                        });
                        
                        $('#js-filtro_actividades-autocomplete').on('change', function ()
			{
                            if($(this).val() != '') { 
                                $('#js-filtro_actividades').prop('readonly', true); }
                            else { 
                                $('#js-filtro_actividades').prop('readonly', false); }
			});
 
			$(id).on('change', '#js-filtro_periodo', function() {
				periodo = $(this).val();
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			});

			function filtrar_dias(dias_horarios) {
				var visible = false;
				dias_horarios.each(function() {
					var dia = $(this);
					if ($.inArray(dia.attr('dia_sem'), dias) != -1)
					{
						dia.show();
						visible = true;
					}
					else
						dia.hide();
				});
				return visible;
			}

			function filtrar_subcomisiones(subcomisiones) {
				if (subcomisiones.length == 0)
					return true;
				var visible = false;
				subcomisiones.each(function() {
					var subcomision = $(this);
					if (	(docente == '' || $.inArray(docente, subcomision.attr('docentes').split(', ')) != -1)
						&&	filtrar_dias(subcomision.nextUntil('tr.subcomision', 'tr.js-dia'))	)
					{
						subcomision.show();
                        $('tr.horarios[cod_subcomision="'+$(subcomision).attr("subcomision")+'"], tr.horarios[subcomision="'+$(subcomision).attr("subcomision")+'"]').show();
						visible = true;
					}
					else{
                        subcomision.hide();
                        $('tr.horarios[cod_subcomision="'+$(subcomision).attr("subcomision")+'"], tr.horarios[subcomision="'+$(subcomision).attr("subcomision")+'"]').hide();
                    }
				});
				return visible;
			}

			function filtrar_comisiones(comisiones) {
				var visible = false;
				comisiones.each(function() {
					var comision = $(this);
					var tbody = comision.children('tbody');
					if (	(ubicacion == '' || comision.attr('sede') == ubicacion)
                        //si tiene subcomisiones que filtre por los docentes de la subcomision y NO por los de la comision
						&&	((tbody.children('tr.subcomision').length == 0) ? (docente == '' || $.inArray(docente, comision.attr('docentes').split(', ')) != -1) : true)
                        //si tiene subcomisiones que filtre por los dias de la subcomision y NO por los de la comision
						&&	((tbody.children('tr.subcomision').length == 0) ? filtrar_dias(tbody.children('tr.js-dia[comision="'+comision.attr('comision')+'"]')) : true)
						&&	filtrar_subcomisiones(tbody.children('tr.subcomision'))	)
					{
						comision.show();
						visible = true;
					}
					else
						comision.hide();
				});
				return visible;
			}

			function filtrar_actividades(actividades) {
				var visible = false;
				actividades.each(function() {
					var activ = $(this);
					if (	(actividad == '' || activ.attr('actividad') == actividad)
						&&	filtrar_comisiones(activ.children('table.comision'))	)
					{
						activ.show();
						visible = true;
					}
					else
						activ.hide();
				});
				return visible;
			}

			function filtrar_periodos(periodos) {
				var visible = false;
				periodos.each(function() {
					var perlec = $(this);
					if (	(periodo == '' || perlec.attr('periodo') == periodo)
						&&	filtrar_actividades(perlec.children('.js-recuadro_actividad'))	)
					{
						perlec.show();
						visible = true;
					}
					else
						perlec.hide();
				});
				return visible;
			}

			function filtrar()
			{
				if (filtrar_periodos($('.js-recuadro_periodo')))
					$('#js-reporte-vacio').hide();
				else
					$('#js-reporte-vacio').show();
				kernel.ui.hide_loading();
			}
                        
                        // Si es perfil docente filtro por el docente actual
                        if (info.es_perfil_docente) {
                            $('#js-filtro_docente').val(info.docente_autocomplete_label);
                            $('#js-filtro_docente-autocomplete').val(info.docente_autocomplete_id);
                            filtro_docente(info.docente_autocomplete_id, info.docente_autocomplete_label);
                        }
                        
        }
    };
});