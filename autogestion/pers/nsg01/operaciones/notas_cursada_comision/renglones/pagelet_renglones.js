kernel.renderer.registrar_pagelet('renglones', function (info)
{
	var id = '#' + info.id;
	var fecha_inicio, fecha_fin;

	var collapsed_class = 'icon-circle-arrow-down';
	var uncollapsed_class = 'icon-circle-arrow-up';

	var autocompletar_promocion = 'On';

	function get_tipo_row(row)
	{
		if (row.hasClass('js-renglon-regular')) {
			return 'regular';
		} else {
			return 'promocion';
		}
	}

	function get_escala(row)
	{
		var tipo = get_tipo_row(row);
		var id = info.id_escalas[tipo];
		return guarani.escalas().recuperar(id);
	}

	function create_date(date_string)
	{
		var fecha_split = date_string.split('/');
		if (fecha_split.length != 3) {
			return false;
		}
		return new Date(fecha_split[2], fecha_split[1] - 1, fecha_split[0]);
	}

	//--------------------------------------------------------------------------
	//---- UTIL
	//--------------------------------------------------------------------------

	function get_fecha(row)
	{
		return $.trim(row.find('[data-tipo=fecha]').val());
	}

	function get_nota(row)
	{
		var input = row.find('[data-tipo=nota]');
		if (input.length == 0) {
			return false;
		}

		return $.trim(input.val());
	}

	function get_nota_anterior(row)
	{
		return $.trim(row.find('[data-tipo=nota]').data('valor-anterior'));
	}

	function set_nota_anterior(row, nota)
	{
		return row.find('[data-tipo=nota]').data('valor-anterior', nota);
	}

	function get_res(row)
	{
		return $.trim(row.find('.resultado').val());
	}

	function get_cond(row)
	{
		return $.trim(row.find('.condicion').val());
	}

	function get_observacion(row)
	{
		return row.find('.observacion').val();
	}
        
	function get_cond_nota(row)
	{
		var nota = get_nota(row);
		if (nota == '') {
			return '';
		}
		var resultado = get_escala(row).get_resultado(nota);
		$.each(_condiciones, function ()
		{
			if (this.resultado == resultado) {
				condicion = this;
				return false;
			}
		});
	}

	function get_res_nota(row)
	{
		var nota = get_nota(row);
		if (nota == '') {
			return '';
		}
		return get_escala(row).get_resultado(nota);
	}

	function formatear_nota(row, event)
	{
		var escala = get_escala(row);
		if (escala.get_tipo() == 'continua') {
			var nota = get_nota(row);
			if (!event) {
				// si no viene el evento no se dispar� desde una entrada de input, no hay que formatear
				return;
			}

			var key = event.keyCode || event.which;
			key = String.fromCharCode(key);

			if (!/[0-9]|\.|,/.test(key) && event.type !== 'focusout') {
				// se formatea s�lo si se presion� alg�n nro, un pto o una coma
				return;
			}

			var nota_formateada = escala.formatear(nota);
			row.find('[data-tipo=nota]').val(nota_formateada);
			set_nota_anterior(row, nota_formateada);
		}
	}

	/**
	 * Setea el resultado v�lido para la nota actual.
	 */
	function update_resultado_nota(row)
	{
		var res = get_res_nota(row);
		row.find('.resultado').val(res);
	}
        
        /**/
        function update_resultado_condicion(row)
        {          
            //Si no existe resultado, entonces setear el resultado que corresponde con la condición de regularidad seleccionada.
            
            var resultado = get_res(row);
            var condicion = get_cond(row);
            
			
            if(resultado == '') {
                $.each(_condiciones, function ()
			{
				if ( this.cond == condicion) {
					res = this.resultado;
                                        row.find('.resultado').val(res);
				}
			}); 
            }else{// Si el resultado es otro, entonces setear el resultado que se corresponde con la condición de regularidad y limpiar la nota

			$.each(_condiciones, function ()
			{
				if ( this.resultado !== resultado && this.cond == condicion) {
					res = this.resultado;
                                        row.find('.resultado').val(res);
                                        //row.find('.nota_cursada').val('');
				}
			}); 
            }  
        }
	/**
	 * Setea la condici�n por defecto para el resultado actual.
	 */
	function update_condicion_resultado(row)
	{
		var res = get_res(row);
		var condicion = false;

		$.each(_condiciones, function ()
		{
			if (this.resultado == res && this.preset == 'S') {
				condicion = this;
				return false;
			}
		});

		if (condicion === false) { // no hay condici�n que matchee con la nota
			console.log('error de matcheo de resultado de condici�n con resultado de nota');
		}

		condicion.cond = res === 'R' ? 1 : condicion.cond; 
		row.find('.condicion').val(condicion.cond);
	}

	function fila_promocion_visible(fila_regular)
	{
		return fila_regular.next('.js-renglon-promocion').find('td:eq(1)').is(':visible');
	}

	function update_fila_promocion(fila_regular)
	{
		// Si no se debe asentar la nota de promocion que no autocomplete
		if(info.cur_asentar_notas_actas_promocion == info.ingreso_notas_sin_notas) return false;

		var tipo_row = get_tipo_row(fila_regular);

		// La fila origen debe ser regular
		if(tipo_row != 'regular') return false;

		var renglon = $(fila_regular).data("renglon");

		var fila_promocion = fila_regular.next(".js-renglon-promocion[data-renglon='"+renglon+"']");

		// Si no existe la fila de promoci�n se sale
		if (fila_promocion.length == 0) return false;

                var fila_prom_visible = fila_promocion_visible(fila_regular);
                var nota_regular = get_nota(fila_regular);
                var nota_promocion = get_nota(fila_promocion);
                
		// Si la fila de promoci�n esta oculta / colapsada se le autocompleta la nota siempre, 
		// tambi�n si est� visible / descolapsada pero no tiene nota asignada
                // siempre debe tener nota de regular para poder autocompletar
		if ((!fila_prom_visible && nota_regular != '') || (fila_prom_visible && nota_regular != '' && nota_promocion == '')) {
                        
			var elemento_nota_promocion = fila_promocion.find('[data-tipo=nota]');
			var existe_nota_promocion = true;

			// Si la nota de promoci�n es un select se verifica que la nota de regular se encuentre dentro de sus valores
			if (elemento_nota_promocion.is("select")) {
				existe_nota_promocion = elemento_nota_promocion.find("option[value='" + nota_regular + "']").length > 0;
			}

			elemento_nota_promocion.val(nota_regular);
			if (chequeo_nota_valida(fila_promocion) && existe_nota_promocion) {
				// Si la nota de promoci�n es v�lida y existe (en el caso que sea un select)
				update_resultado_nota(fila_promocion);
				$(id).plugin_renglones('remove_alerta', fila_promocion.find('[data-tipo=nota]'));
			} else {
				// Si la nota de promoci�n es inv�lida y no existe (en el caso que sea un select), se muestra mensaje de alerta
				$(id).plugin_renglones('set_alerta', fila_promocion.find('[data-tipo=nota]'), info.mensajes.nota_incompatible);
				// Se borran los campos nota y resultado
				fila_promocion.find('[data-tipo=nota]').val('').focus();
				fila_promocion.find('[data-tipo=resultado]').val('');
				// Se despliegan la fila de promoci�n para que el docente pueda ver el alerta (Columna "Acta" en adelante)
                                // Ver: https://api.jquery.com/addback/
                                fila_regular.find('td.col-nro-acta').nextAll('td').addBack().prop('rowspan', 1);
				fila_regular.find('td .js-toggle-acta-promocion i').removeClass(collapsed_class).addClass(uncollapsed_class);
				fila_promocion.find('td').show();
			}
		}

		// Si la fila de promoci�n esta oculta / colapsada, se le autocompleta la fecha siempre, 
		// tambi�n si est� visible / descolapsada pero no tiene fecha asignada
		if ((!fila_prom_visible) || (fila_prom_visible && get_fecha(fila_promocion) == '')) {
			fila_promocion.find('[data-tipo=fecha]').val(get_fecha(fila_regular));
		}
                
		// Reviso si cambiaron los siguientes campos en la fila de promoci�n:
		marcar_campo_modificado(fila_promocion, 'fecha');
		marcar_campo_modificado(fila_promocion, 'nota');
		marcar_campo_modificado(fila_promocion, 'resultado');
		marcar_campo_modificado(fila_promocion, 'observacion');
	}

        /**
	 * Setea la observaci�n de promoci�n.
	 */
	function update_observacion_promocion(row)
	{
            var fila_promocion = $(row).next("tr.js-renglon-promocion");
            var observacion_promocion = get_observacion(fila_promocion);
            // Solo autocompleto si la observaci�n de promoci� es vac�a
            if (observacion_promocion === "") {
                // Le copio el mismo valor al de promoci�n
                $(fila_promocion).find('.observacion').val(get_observacion(row)).keyup();
            }
	}

	//--------------------------------------------------------------------------
	//---- CHEQUEO DE ERRORES
	//--------------------------------------------------------------------------

	/**
	 * Una nota es v�lida si es vac�a o si existe en el mapeo de la escala.
	 */
	function chequeo_nota_valida(row)
	{
		var nota = get_nota(row);
		if (nota === '') {
			return true;
		} else {
			return get_escala(row).validar(nota);
		}
	}

	function chequeo_condicion_valida(row)
	{
		var res = get_res(row);
		var cond = get_cond(row);
		if (res === '' || cond === '') {
			return true;
		} else {
			var valido = false;
			$.each(_condiciones, function ()
			{
				if (this.resultado == res && this.cond == cond) {
					valido = true;
					return true;
				}
			});
			return valido;
		}
	}

        /**
	 * Marco si el campo fue modificado o no.
	 */
	function marcar_campo_modificado(row, campo)
	{
               var selector_campo = '[data-tipo='+campo+']';
               // Si el campo no existe salgo
               if (!row.find(selector_campo).length) {
                   return false;
               }
               var valor_actual = row.find(selector_campo).val();
               var valor_original = row.find(selector_campo).data('valorOriginal');
               // Si el valor fue modificado agrega la clase "js-modificado", sino se la quita.
               row.find(selector_campo).toggleClass("js-modificado", valor_actual != valor_original);
               
               // Si hay uno o mas campos modificados entonces hay cambios
               sessionStorage.hay_cambios = $(".js-modificado").length > 0;
               // Si hay cambios habilito el bot�n "Guardar", sino lo deshabilito
               if (sessionStorage.hay_cambios == 'true') {
                   $("#js-btn-guardar").prop('disabled', false);
               } else {
                   $("#js-btn-guardar").prop('disabled', true);
               }
	}
        
	//--------------------------------------------------------------------------
	//---- VALIDACIONES
	//--------------------------------------------------------------------------

	function validar_fecha(row)
	{
		var fecha = create_date(row.find('.fecha').val());
		if (row.find('.fecha').val() != '' && (fecha === false || fecha.getTime() < fecha_inicio.getTime() || fecha_fin.getTime() < fecha.getTime())) {
			$(id).plugin_renglones('set_error', row.find('.fecha'), info.mensajes.fecha_invalida);
		} else {
			if (autocompletar_promocion == 'On') update_fila_promocion(row);
			update_toggle_promocion_enabled();
			$(id).plugin_renglones('remove_error', row.find('.fecha'));
		}
                
                // Si hubo cambios marco la fecha como modificada
                marcar_campo_modificado(row, 'fecha');
               
	}
        
	function validar_nota(row, tipo, event)
	{
                // Si la nota es inv�lida
		if (!chequeo_nota_valida(row)) {
			$(id).plugin_renglones('remove_alerta', row.find('[data-tipo=nota]'));
			$(id).plugin_renglones('set_error', row.find('[data-tipo=nota]'), info.mensajes.nota_invalida);
		} else {
			// Si es v�lido se debe setear el resultado y la condici�n
			if (get_nota(row) != '') {
				// S�lo cambia el resultado si la nota no es vac�a
				formatear_nota(row, event);
				update_resultado_nota(row);
				
				update_condicion_resultado(row);
				//update_resultado_condicion(row);

				if (autocompletar_promocion == 'On') update_fila_promocion(row);
				$(id).plugin_renglones('remove_alerta', row.find('[data-tipo=nota]'));
			} else {
				// Si la nota es vac�a, se vac�an los valores de resultado y condici�n
				row.find('.resultado').val('');
				row.find('.condicion').val('');
			}

			update_toggle_promocion_enabled();
			$(id).plugin_renglones('remove_error', row.find('[data-tipo=nota]'));
		}
                
                // El campo "nota" tambi�n impacta en "resultado" y "condicion"
                marcar_campo_modificado(row, 'nota');
                marcar_campo_modificado(row, 'resultado');
                marcar_campo_modificado(row, 'cond_regularidad');
	}

	/*function validar_resultado(row)
	{
		row.find('[data-tipo=nota]').val('');
		var res = get_res(row);
		if (res == '') {
			row.find('.condicion').val('');
		} else {
			update_condicion_resultado(row);
			validar_condicion(row);
			if (autocompletar_promocion == 'On')
				update_fila_promocion(row);
		}
		update_toggle_promocion_enabled();
	}*/

	function validar_resultado(row)
	{
		var es_acta_regular = $(row).hasClass("js-renglon-regular");
		var es_acta_promocion = $(row).hasClass("js-renglon-promocion");
		var resultado = get_res(row);
                var resultado_nota = get_res_nota(row);
                
                // Si no coincide la nota con el resultado entonces vac�o la nota
		if(resultado !== resultado_nota) {
                    row.find('[data-tipo=resultado_cursada]').val('');
                }

		// Si el resultado no es vacio
		if (resultado != '') {
			// si es acta regular, actualizo la condicion y la valido
			if(es_acta_regular){
				update_condicion_resultado(row);
				validar_condicion(row);
				if (autocompletar_promocion == 'On') update_fila_promocion(row);
			}
		}
		update_toggle_promocion_enabled();

		//Seg�n: https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Cursadas_Notas_Resultados
		if(es_acta_regular){
			controlar_nota_acta_regular(row);
		}
		else if(es_acta_promocion){
			controlar_nota_acta_promocion(row);
		}
                
                // El campo "resultado" tambi�n impacta en "nota" y "condicion"
                marcar_campo_modificado(row, 'nota');
                marcar_campo_modificado(row, 'resultado');
                marcar_campo_modificado(row, 'cond_regularidad');
	}

	function controlar_nota_acta_regular(row){

		var resultado = get_res(row);
		var nota = get_nota(row);

		$(id).plugin_renglones('remove_error', row.find('.nota_cursada'));

		switch (resultado) {
			// Si el resultado es vac�o
			case '':
				// borro la nota y la condici�n
				row.find('.nota_cursada').val('');
				row.find('.condicion').val('');
				break;
			// Si el resultado es aprobado
			case info.resultado_nota_aprobado:

				switch (info.cur_asentar_notas_actas_regulares) {
					// Si la nota no es obligatoria
					case info.ingreso_notas_no_obligatorio:
						validar_nota_resultado_regular(row);
						break;
					// Si la nota es obligatoria
					case info.ingreso_notas_obligatorio:
						// si no se ingreso nota muestro error
						if(!nota) $(id).plugin_renglones('set_error', row.find('.nota_cursada'), info.mensajes.nota_obligatoria);
						validar_nota_resultado_regular(row);
						break;
					// Sin Notas - No debe ingresarse la nota
					case info.ingreso_notas_sin_notas:
						row.find('.nota_cursada').val('');
						break;
				}

				break;
			case info.resultado_nota_desaprobado:

				switch (info.cur_asentar_notas_actas_regulares) {
					// Si la nota no es obligatoria
					case info.ingreso_notas_no_obligatorio:
						validar_nota_resultado_regular(row);
						break;
					// Si la nota es obligatoria
					case info.ingreso_notas_obligatorio:
						// si no se ingreso nota muestro error
						if(!nota) $(id).plugin_renglones('set_error', row.find('.nota_cursada'), info.mensajes.nota_obligatoria);
						validar_nota_resultado_regular(row);
						break;
					// Sin Notas - No debe ingresarse la nota
					case info.ingreso_notas_sin_notas:
						row.find('.nota_cursada').val('');
						break;
				}

				break;
			case info.resultado_nota_ausente:
				row.find('.nota_cursada').val('');
				break;
		}

	}

	function validar_nota_resultado_regular(row){

		var nota = get_nota(row);
		var resultado = get_res(row);
		var resultado_nota = get_res_nota(row);

		if(!nota) return false;

		// si la nota es inv�lida o no coincide la nota con el resultado
		if((!chequeo_nota_valida(row)) || (resultado != resultado_nota)) $(id).plugin_renglones('set_error', row.find('.nota_cursada'), info.mensajes.nota_invalida);
                
	}

	function controlar_nota_acta_promocion(row){

		var resultado = get_res(row);
		var nota = get_nota(row);

		$(id).plugin_renglones('remove_error', row.find('.nota_promocion'));

		switch (resultado) {
			// Si el resultado es vac�o
			case '':
				row.find('.nota_promocion').val('');
				break;
			// Si el resultado es aprobado
			case info.resultado_nota_aprobado:

				switch (info.cur_asentar_notas_actas_promocion) {
					// Si la nota no es obligatoria
					case info.ingreso_notas_no_obligatorio:
						validar_nota_resultado_promocion(row);
						break;
					// Si la nota es obligatoria
					case info.ingreso_notas_obligatorio:
						// si no se ingreso nota muestro error
						if(!nota) $(id).plugin_renglones('set_error', row.find('.nota_promocion'), info.mensajes.nota_obligatoria);
						validar_nota_resultado_promocion(row);
						break;
					// Sin Notas - No debe ingresarse la nota
					case info.ingreso_notas_sin_notas:
						row.find('.nota_promocion').val('');
						break;
				}

				break;
			case info.resultado_nota_desaprobado:

				switch (info.cur_asentar_notas_actas_promocion) {
					// Si la nota no es obligatoria
					case info.ingreso_notas_no_obligatorio:
						validar_nota_resultado_promocion(row);
						break;
					// Si la nota es obligatoria
					case info.ingreso_notas_obligatorio:
						// si no se ingreso nota muestro error
						if(!nota) $(id).plugin_renglones('set_error', row.find('.nota_promocion'), info.mensajes.nota_obligatoria);
						validar_nota_resultado_promocion(row);
						break;
					// Sin Notas - No debe ingresarse la nota
					case info.ingreso_notas_sin_notas:
						row.find('.nota_promocion').val('');
						break;
				}

				break;
			case info.resultado_nota_ausente:
				row.find('.nota_promocion').val('');
				break;
		}

	}

	function validar_nota_resultado_promocion(row){

		var nota = get_nota(row);
		var resultado = get_res(row);
		var resultado_nota = get_res_nota(row);

		if(!nota) return false;
                
		if((resultado_nota == info.resultado_nota_aprobado) && ($.inArray(resultado, [info.resultado_nota_aprobado, info.resultado_nota_desaprobado]) == -1)){
                    $(id).plugin_renglones('set_error', row.find('[data-tipo=nota]'), info.mensajes.nota_invalida);
		} else if((resultado_nota == info.resultado_nota_desaprobado) && (resultado != info.resultado_nota_desaprobado)){
			$(id).plugin_renglones('set_error', row.find('[data-tipo=nota]'), info.mensajes.nota_invalida);
                }

	}

	function validar_condicion(row)
	{

            update_resultado_condicion(row);
            //validar_resultado(row);
            if (!chequeo_condicion_valida(row)) {
			$(id).plugin_renglones('set_error', row.find('.condicion'), info.mensajes.condicion_invalida);
		} else {
			$(id).plugin_renglones('remove_error', row.find('.condicion'));
		}
                
                // El campo "condicion" tambi�n impacta en "nota" y "resultado"
                marcar_campo_modificado(row, 'nota');
                marcar_campo_modificado(row, 'resultado');
                marcar_campo_modificado(row, 'cond_regularidad');
	}

	function update_toggle_promocion_enabled()
	{
		var hay_una_colapsable = false;
		$('.js-toggle-acta-promocion').each(function ()
		{
			var fila_regular = $(this).parents('tr');
			var fila_promocion = fila_regular.next('.js-renglon-promocion');
			var fecha_r = get_fecha(fila_regular);
			var fecha_p = get_fecha(fila_promocion);
			var nota_r = get_nota(fila_regular);
			var nota_p = get_nota(fila_promocion);
			var resultado_r = get_res(fila_regular);
			var resultado_p = get_res(fila_promocion);
                        var observacion_r = get_observacion(fila_regular);
			var observacion_p = get_observacion(fila_promocion);

			// Si todos los datos de cursada son iguales a los de promoci�n se habilita el boton para colapsar.
			var enabled = ((fecha_r === fecha_p) && (nota_r === nota_p) && (resultado_r === resultado_p) && (observacion_r === observacion_p));

			if (!enabled) {
				if (!fila_promocion.find('td').is(':visible')) {
					$(this).trigger('click');
				}
				$(this).addClass('disabled');
			} else {
				$(this).removeClass('disabled');
				hay_una_colapsable = true;
			}
		});
		if (hay_una_colapsable) {
			$('#js-toggle-acta-promocion').removeClass('disabled');
		} else {
			$('#js-toggle-acta-promocion').addClass('disabled');
		}

	}

	var force_toggle = false;
	function setup_toggle_actas_promocion()
	{
		update_toggle_promocion_enabled();
		$('#js-toggle-acta-promocion').on("click", function ()
		{
			if ($(this).hasClass('disabled')) {
				return false;
			}
			$(this).toggleClass('open');
			var is_open = $(this).hasClass('open');
			force_toggle = is_open ? 'open' : 'close';
			$(id).find('.js-toggle-acta-promocion').trigger('click');
			force_toggle = false;
			var collapsed_class = 'icon-circle-arrow-down';
			var uncollapsed_class = 'icon-circle-arrow-up';
			$(this).find('i').toggleClass(uncollapsed_class, is_open);
			$(this).find('i').toggleClass(collapsed_class, !is_open);
			$(this).attr('data-original-title', is_open ? info.mensajes.esconder_actas_promocion : info.mensajes.mostrar_actas_promocion)
					.tooltip('fixTitle')
					.tooltip('show');
		});

		$('#js-toggle-acta-promocion').tooltip();

		$(id + ' .js-toggle-acta-promocion').on("click", function ()
		{
			if ($(this).hasClass('disabled')) {
				return false;
			}

			var tr_regular = $(this).closest('tr');

			var tr_promocion = tr_regular.next('tr');
			var show = !tr_promocion.find('td:first').is(':visible');
			if (force_toggle !== false) {
				show = force_toggle === 'open';
			}

			if (show) {
				// Al desplegar la fila de promoci�n (Columna "Acta" en adelante)
                                // Ver: https://api.jquery.com/addback/
				tr_regular.find('td.col-nro-acta').nextAll('td').addBack().prop('rowspan', 1);
				tr_regular.find('td .js-toggle-acta-promocion i').removeClass(collapsed_class).addClass(uncollapsed_class);
				tr_promocion.find('td').show();
			} else {
				// Al colapsar la fila de promoci�n (Columna "Acta" en adelante)
                                // Ver: https://api.jquery.com/addback/
				tr_promocion.find('td').hide();
				tr_regular.find('td .js-toggle-acta-promocion i').removeClass(uncollapsed_class).addClass(collapsed_class);
				tr_regular.find('td.col-nro-acta').nextAll('td').addBack().prop('rowspan', 2);
			}
			return false;
		});
	}

	function setup_autocompletar_promocion()
	{
		// Si tiene dos instancias con escalas de notas diferentes
		if (info.tiene_dos_instancias && !info.tiene_instancias_con_misma_escala) {
			// Se recupera la cookie 'autocompletar_promocion'
			var cookie_autocompletar_promocion = readCookie('autocompletar_promocion');
			// Si la cookie contiene algo, se vuelca a la variable global 'autocompletar_promocion'
			if (cookie_autocompletar_promocion) {
				autocompletar_promocion = cookie_autocompletar_promocion;
			} else {
				// Si la cookie esta vac�a, se le carga el valor de la variable global 'autocompletar_promocion'
				createCookie('autocompletar_promocion', autocompletar_promocion);
			}

			if (autocompletar_promocion == 'On') {
				$(".js-autocompletar-promocion.on").addClass('active');
				$(".js-autocompletar-promocion.off").removeClass('active');
			} else {
				$(".js-autocompletar-promocion.on").removeClass('active');
				$(".js-autocompletar-promocion.off").addClass('active');
			}

			$(".js-autocompletar-promocion").on("click", function ()
			{
				autocompletar_promocion = $(this).data('value');
				createCookie('autocompletar_promocion', autocompletar_promocion);
			});

		}

	}

	function setup_deshabilitar_notas() {

		// Si el par�metro 'cur_asentar_notas_actas_regulares' es 'S' (Sin Notas) deshabilito la carga de notas
		if(info.cur_asentar_notas_actas_regulares == info.ingreso_notas_sin_notas){
			$('.nota_cursada').prop( "disabled", true );
		}

		// Si el par�metro 'cur_asentar_notas_actas_promocion' es 'S' (Sin Notas) deshabilito la carga de notas
		if(info.cur_asentar_notas_actas_promocion == info.ingreso_notas_sin_notas){
			$('.nota_promocion').prop( "disabled", true );
		}

	}
        
	function validar_observacion(row)
	{
		var observacion = row.find('.observacion').val();
                
                // La observaci�n no puede ser mayor a 100 caracteres
		if (observacion.length > 100) {
			$(id).plugin_renglones('set_error', row.find('.observacion'), info.mensajes.observacion_invalida);
		} else {
			update_toggle_promocion_enabled();
			$(id).plugin_renglones('remove_error', row.find('.observacion'));
		}
                
                // Si hubo cambios marco la observacion como modificada
                marcar_campo_modificado(row, 'observacion');
	}
        
        // Cuando cambiamos las "Observaciones" de "Cursada"
        $(id).on("change", "tr.js-renglon-regular input.observacion", function () {
            // Si hay que autocpmpletar "Promoci�n"
            if (autocompletar_promocion == 'On') {
                var row = $(this).parents("tr.js-renglon-regular");
                update_observacion_promocion(row);
            }
        });

	return {
		onload: function ()
		{
                        
                        fecha_inicio = create_date(info.fecha_inicio);
                        fecha_fin = create_date(info.fecha_fin);
                        
			$(id).plugin_renglones({
				validadores: [
                                        {
						tipo: ['fecha'],
						validar: validar_fecha
					}
					, {
						tipo: ['nota'],
						validar: validar_nota
					}
					, {
						tipo: ['resultado'],
						validar: validar_resultado
					}
					, {
						tipo: ['cond_regularidad'],
						validar: validar_condicion
					  }
                                        , {
						tipo: ['observacion'],
						validar: validar_observacion
					  }
				],

				highlight_renglon: info.highlight_renglon,
				datepicker_min_date: fecha_inicio,
				datepicker_max_date: fecha_fin,
				mostrar_prompt_perdera_cambios: true,
				msg_prompt_perdera_cambios: info.mensajes.msg_prompt_perdera_cambios,
				msg_ok_prompt_perdera_cambios: info.mensajes.aceptar,
				msg_cancel_prompt_perdera_cambios: info.mensajes.cancelar,
				guardar_cambio_pagina: false,
				success: function ()
				{
					setup_toggle_actas_promocion();
					setup_deshabilitar_notas();
					// Al guardar marco que no hay cambios
					sessionStorage.hay_cambios = false;
					kernel.ui.show_mensaje(info.mensajes.guardado_exitoso, {until_interaction: true});
				},
				error: function ()
				{
					setup_deshabilitar_notas();
					kernel.ui.show_mensaje(info.mensajes.guardado_error, {tipo: 'alert-error'});
				},
				autocomplete: {
					url: info.url_autocomplete,
					mensaje_vacio: info.mensajes.no_se_encontraron_alumnos,
					min_letras_busqueda: $("#js-ui-autocomplete-min-length").val()
				}
			});

			setup_toggle_actas_promocion();

			setup_autocompletar_promocion();

			// Si existe el bot�n de "Mostrar actas promoci�n" se dispara el evento 
			// click para que despliegue las actas de promoci�n
			if ($('#js-toggle-acta-promocion').length)
				$('#js-toggle-acta-promocion').trigger("click").trigger('blur');

			$(id).on("keypress", "input.nota_cursada, input.nota_promocion", function (e)
			{
				if (e.which == 13) {
					// Si presiona 'Enter'
					$(this).trigger('blur');
					return false;
				}
			});
                        
                        $('#notas_cursada_filtrar_acta').on("change", function ()
			{   
                            var clase = $(this).val();
                            var seleccion = '.js-'+clase ;
                            // Muestro todos los renglones
                            if (clase == 'todas-todas') {
                                $( ".js-renglon-regular" ).show(); 
                                $( ".js-renglon-promocion" ).show();
                            } else {// Muestro los renglones del acta filtrada
                                $( ".js-renglon-regular" ).hide();
                                $( ".js-renglon-promocion" ).hide();
                                $(seleccion).show();
                            }
                        });
                        
			setup_deshabilitar_notas();
		}
	};
});