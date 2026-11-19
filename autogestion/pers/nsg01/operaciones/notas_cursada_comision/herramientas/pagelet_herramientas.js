kernel.renderer.registrar_pagelet('herramientas', function (info)
{
	var id = '#' + info.id;
	var fecha_inicio;
	var fecha_fin;
	var datosAnterioresRegular = [];
	var datosAnterioresPromocion = [];

	function get_escala_regularidad()
	{
		return guarani.escalas().recuperar(info.id_escala_regularidad);
	}

	function get_escala_promocion()
	{
		return guarani.escalas().recuperar(info.id_escala_promocion);
	}

	function create_date(date_string)
	{
		var fecha_split = date_string.split('/');
		return new Date(fecha_split[2], fecha_split[1] - 1, fecha_split[0]);
	}

	function es_renglon_bloqueado_asistencia(row)
	{
		return $(row).is('[data-bloqueada-asistencia="1"]');
	}

	function hay_alumnos_bloqueados_asistencia()
	{
		return $('.js-bloqueada-asistencia[data-bloqueada-asistencia="1"]').length > 0;
	}

	function campo_bloqueado_por_asistencia(campo)
	{
		return $.inArray(campo, [
			info.autocompletar_campo_nota_cursada,
			info.autocompletar_campo_nota_promocion,
			info.autocompletar_campo_resultado_cursada,
			info.autocompletar_campo_resultado_promocion
		]) !== -1;
	}

	function para_es_global(para)
	{
		return $.inArray(para, [
			info.autocompletar_para_todos,
			info.autocompletar_para_sin_datos
		]) !== -1;
	}

	function mapear_para_a_pagina(para)
	{
		if (para === info.autocompletar_para_sin_datos) {
			return info.autocompletar_para_sin_datos_pagina;
		}
		return info.autocompletar_para_todos_pagina;
	}

	function mensaje_autocompletar_global_bloqueado()
	{
		return 'Para conservar la condicion de libre por asistencia, este autocompletar solo puede aplicarse sobre la pagina actual.';
	}

	function mensaje_calculo_global_bloqueado()
	{
		return 'Para conservar la condicion de libre por asistencia, este calculo solo puede aplicarse sobre la pagina actual.';
	}

	function mensaje_omitidos(omitidos)
	{
		if (omitidos === 1) {
			return 'Se omitio 1 alumno libre por asistencia.';
		}
		return 'Se omitieron ' + omitidos + ' alumnos libres por asistencia.';
	}

	function mostrar_omitidos(omitidos)
	{
		if (omitidos > 0) {
			kernel.ui.show_mensaje(mensaje_omitidos(omitidos), {tipo: 'alert-info'});
		}
	}

	function controlar_btn_completar()
	{
		var campo = $('#preset_campo').val();
		var valor = $.trim($('#preset_form').find('#js-valor-' + campo).val());

		if (valor === '') {
			$('#btnCompletar').prop('disabled', true);
		} else {
			$('#btnCompletar').prop('disabled', false);
		}
	}

	function setup_unload()
	{
		$(window).on('beforeunload', function ()
		{
			if (sessionStorage.hay_cambios === 'true') {
				return '¿Esta usted seguro de que desea salir de esta pagina?';
			}
		});
	}

	function setup_links()
	{
		$('a').addClass('no-ajax');
	}

	function cambiar_preset_valor(campo)
	{
		$('#preset_form').find('.preset_valor').prop('disabled', true).hide();
		$('#preset_form').find('#js-valor-' + campo).prop('disabled', false).show();
		$('#preset_form').find('#js-label-preset-valor').attr('for', 'js-valor-' + campo);
	}

	function setup_preset_valor()
	{
		cambiar_preset_valor($('#preset_campo').val());
	}

	function actualizar_opciones_globales_autocompletar()
	{
		var campo = $('#preset_campo').val();
		var restringir = hay_alumnos_bloqueados_asistencia() && campo_bloqueado_por_asistencia(campo);
		var $para = $('#autocompletar_para');
		var valor_actual = $para.val();

		$para.find('option').each(function ()
		{
			var es_global = para_es_global($(this).val());
			$(this).prop('disabled', restringir && es_global);
		});

		if (restringir && para_es_global(valor_actual)) {
			$para.val(mapear_para_a_pagina(valor_actual));
		}
	}

	function actualizar_opciones_globales_calculo()
	{
		var restringir = hay_alumnos_bloqueados_asistencia();
		var $para = $('#aplicar_a');
		var valor_actual = $para.val();

		$para.find('option').each(function ()
		{
			var es_global = para_es_global($(this).val());
			$(this).prop('disabled', restringir && es_global);
		});

		if (restringir && para_es_global(valor_actual)) {
			$para.val(mapear_para_a_pagina(valor_actual));
		}
	}

	function fecha_valida(val)
	{
		var fecha = create_date(val);
		return fecha.getTime() >= fecha_inicio.getTime() && fecha.getTime() <= fecha_fin.getTime();
	}

	function validar_valor_preset_actual()
	{
		var campo = $('#preset_campo').val();
		var val = $.trim($('#preset_form').find('#js-valor-' + campo).val());

		controlar_btn_completar();

		if (val === '') {
			return false;
		}

		if (campo === info.autocompletar_campo_nota_cursada && !get_escala_regularidad().validar(val)) {
			$('#preset_form').find('#js-valor-' + campo).val('');
			kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
			return false;
		}

		if (campo === info.autocompletar_campo_nota_promocion && !get_escala_promocion().validar(val)) {
			$('#preset_form').find('#js-valor-' + campo).val('');
			kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
			return false;
		}

		if (campo === info.autocompletar_campo_fecha && !fecha_valida(val)) {
			$('#preset_form').find('#js-valor-' + campo).val('');
			kernel.ui.show_mensaje(info.mensajes.fecha_invalida, {tipo: 'alert-error'});
			return false;
		}

		return true;
	}

	function guardar_datos_anteriores()
	{
		datosAnterioresRegular = [];
		datosAnterioresPromocion = [];

		$('.js-renglon-regular').each(function ()
		{
			if ($(this).find("[data-tipo='nota']").length > 0) {
				datosAnterioresRegular[$(this).data('renglon')] = {
					fecha: $(this).find("[data-tipo='fecha']").val(),
					nota: $(this).find("[data-tipo='nota']").val(),
					resultado: $(this).find("[data-tipo='resultado']").val(),
					condicion: $(this).find("[data-tipo='cond_regularidad']").val(),
					observacion: $(this).find("[data-tipo='observacion']").val()
				};
			}
		});

		$('.js-renglon-promocion').each(function ()
		{
			if ($(this).find("[data-tipo='nota']").length > 0) {
				datosAnterioresPromocion[$(this).data('renglon')] = {
					fecha: $(this).find("[data-tipo='fecha']").val(),
					nota: $(this).find("[data-tipo='nota']").val(),
					resultado: $(this).find("[data-tipo='resultado']").val(),
					observacion: $(this).find("[data-tipo='observacion']").val()
				};
			}
		});
	}

	function recargar_datos_anteriores()
	{
		$('.js-renglon-regular').each(function ()
		{
			var renglon = $(this).data('renglon');
			var datos_regular = datosAnterioresRegular[renglon];

			if (renglon && datos_regular) {
				$(this).find("[data-tipo='fecha']").val(datos_regular.fecha).change().keyup();
				$(this).find("[data-tipo='nota']").val(datos_regular.nota).change().keyup();
				$(this).find("[data-tipo='resultado']").val(datos_regular.resultado).change().keyup();
				$(this).find("[data-tipo='cond_regularidad']").val(datos_regular.condicion).change().keyup();
				$(this).find("[data-tipo='observacion']").val(datos_regular.observacion).change().keyup();
			}
		});

		$('.js-renglon-promocion').each(function ()
		{
			var renglon = $(this).data('renglon');
			var datos_promocion = datosAnterioresPromocion[renglon];

			if (renglon && datos_promocion) {
				$(this).find("[data-tipo='fecha']").val(datos_promocion.fecha).change().keyup();
				$(this).find("[data-tipo='nota']").val(datos_promocion.nota).change().keyup();
				$(this).find("[data-tipo='resultado']").val(datos_promocion.resultado).change().keyup();
				$(this).find("[data-tipo='observacion']").val(datos_promocion.observacion).change().keyup();
			}
		});

		datosAnterioresRegular = [];
		datosAnterioresPromocion = [];
	}

	function autocompletar_pagina(campo, valor, para)
	{
		var selector_instancia = '';
		var selector_campo = '';
		var hubo_cambios = false;
		var omitidos = 0;

		switch (campo) {
			case info.autocompletar_campo_nota_cursada:
				selector_instancia = '.js-renglon-regular';
				selector_campo = "[data-tipo='nota']";
				break;
			case info.autocompletar_campo_nota_promocion:
				selector_instancia = '.js-renglon-promocion';
				selector_campo = "[data-tipo='nota']";
				break;
			case info.autocompletar_campo_resultado_cursada:
				selector_instancia = '.js-renglon-regular';
				selector_campo = "[data-tipo='resultado']";
				break;
			case info.autocompletar_campo_resultado_promocion:
				selector_instancia = '.js-renglon-promocion';
				selector_campo = "[data-tipo='resultado']";
				break;
			case info.autocompletar_campo_fecha:
				selector_instancia = '.js-renglon-regular, .js-renglon-promocion';
				selector_campo = "[data-tipo='fecha']";
				break;
			case info.autocompletar_campo_observaciones:
				selector_instancia = '.js-renglon-regular, .js-renglon-promocion';
				selector_campo = "[data-tipo='observacion']";
				break;
		}

		var solo_vacios = para === info.autocompletar_para_sin_datos_pagina;
		var bloquear_por_asistencia = campo_bloqueado_por_asistencia(campo);

		guardar_datos_anteriores();
		$('#btn_undo_autocompletar').prop('disabled', false);

		$(selector_instancia).each(function ()
		{
			var $fila = $(this);
			var $campo = $fila.find(selector_campo);
			var aplica = false;

			if ($campo.length === 0) {
				return;
			}

			aplica = !solo_vacios || $campo.val() === '';
			if (!aplica) {
				return;
			}

			if (bloquear_por_asistencia && es_renglon_bloqueado_asistencia($fila)) {
				omitidos++;
				return;
			}

			if ($campo.val() !== valor) {
				hubo_cambios = true;
			}

			$campo.val(valor).change().keyup();
		});

		if (hubo_cambios) {
			$('#notas_cursada_filtrar_acta').val('todas-todas').change();
			kernel.ui.show_mensaje(info.mensajes.msg_exito_autocompletar, {tipo: 'alert-success'});
		} else {
			kernel.ui.show_mensaje(info.mensajes.msg_no_cambios_autocompletar, {tipo: 'alert-info'});
		}

		mostrar_omitidos(omitidos);
	}

	function autocompletar()
	{
		var campo = $('#preset_campo').val();
		var campos_validos = [
			info.autocompletar_campo_nota_cursada,
			info.autocompletar_campo_nota_promocion,
			info.autocompletar_campo_resultado_cursada,
			info.autocompletar_campo_resultado_promocion,
			info.autocompletar_campo_fecha,
			info.autocompletar_campo_observaciones
		];

		if ($.inArray(campo, campos_validos) === -1) {
			kernel.ui.show_mensaje(info.mensajes.completar_invalido, {tipo: 'alert-error'});
			return false;
		}

		var para = $('#autocompletar_para').val();
		var para_validos = [
			info.autocompletar_para_todos_pagina,
			info.autocompletar_para_sin_datos_pagina,
			info.autocompletar_para_todos,
			info.autocompletar_para_sin_datos
		];

		if ($.inArray(para, para_validos) === -1) {
			kernel.ui.show_mensaje(info.mensajes.para_invalido, {tipo: 'alert-error'});
			return false;
		}

		var val = $.trim($('#preset_form').find('#js-valor-' + campo).val());

		if (val === '') {
			kernel.ui.show_mensaje(info.mensajes.valor_vacio, {tipo: 'alert-error'});
			return false;
		}

		if (campo === info.autocompletar_campo_nota_cursada && !get_escala_regularidad().validar(val)) {
			kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
			return false;
		}

		if (campo === info.autocompletar_campo_nota_promocion && !get_escala_promocion().validar(val)) {
			kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
			return false;
		}

		if (campo === info.autocompletar_campo_fecha && !fecha_valida(val)) {
			kernel.ui.show_mensaje(info.mensajes.fecha_invalida, {tipo: 'alert-error'});
			return false;
		}

		if ($.inArray(campo, [info.autocompletar_campo_resultado_cursada, info.autocompletar_campo_resultado_promocion]) !== -1) {
			var resultados = campo === info.autocompletar_campo_resultado_cursada ? info.resultados_regularidad : info.resultados_promocion;
			if ($.inArray(val, resultados) === -1) {
				kernel.ui.show_mensaje(info.mensajes.resultado_invalido, {tipo: 'alert-error'});
				return false;
			}
		}

		if (hay_alumnos_bloqueados_asistencia() && campo_bloqueado_por_asistencia(campo) && para_es_global(para)) {
			kernel.ui.show_mensaje(mensaje_autocompletar_global_bloqueado(), {tipo: 'alert-info'});
			return false;
		}

		if ($.inArray(para, [info.autocompletar_para_todos_pagina, info.autocompletar_para_sin_datos_pagina]) !== -1) {
			autocompletar_pagina(campo, val, para);
			return true;
		}

		kernel.ui.prompt({
			msg: info.mensajes.msg_prompt_modificara_todas_paginas,
			msg_ok: info.mensajes.aceptar,
			msg_cancel: info.mensajes.cancelar,
			ok: function ()
			{
				sessionStorage.hay_cambios = false;
				$('#preset_form').submit();
			}
		});

		return true;
	}

	function calcular_notas_pagina(instancia, para, regla_calculo)
	{
		var alumnos = [];
		var tipo_instancia = $();
		var solo_vacios = para === info.autocompletar_para_sin_datos_pagina;
		var omitidos = 0;

		guardar_datos_anteriores();
		$('#btn_undo_nota').prop('disabled', false);

		if (instancia === info.inst_cursar) {
			tipo_instancia = $('.js-renglon-regular');
		} else if (instancia === info.inst_promocionar) {
			tipo_instancia = $('.js-renglon-promocion');
		}

		tipo_instancia.each(function ()
		{
			var $fila = $(this);
			var $nota = $fila.find("[data-tipo='nota']");
			var aplica = false;

			if ($nota.length === 0) {
				return;
			}

			aplica = !solo_vacios || $nota.val() === '';
			if (!aplica) {
				return;
			}

			if (es_renglon_bloqueado_asistencia($fila)) {
				omitidos++;
				return;
			}

			alumnos.push($fila.data('renglon'));
		});

		if (alumnos.length === 0) {
			mostrar_omitidos(omitidos);
			kernel.ui.show_mensaje(info.mensajes.msg_sin_alumnos_calcular_nota, {tipo: 'alert-info'});
			return false;
		}

		kernel.ajax.call(info.url_calcular_notas, {
			type: 'POST',
			data: {
				instancia: instancia,
				regla_calculo: regla_calculo,
				alumnos: alumnos
			},
			success: function (response)
			{
				if (response.cont.hubo_error) {
					kernel.ui.show_mensaje(response.cont.msg_error, {tipo: 'alert-error'});
					return false;
				}

				var alumnosNotas = response.cont.notas || {};
				var error_calculo_nota = false;
				var error_numero = '';

				if (Object.keys(alumnosNotas).length > 0) {
					tipo_instancia.each(function ()
					{
						var $fila = $(this);
						var renglon = $fila.data('renglon');
						var $nota = $fila.find("[data-tipo='nota']");

						if ($nota.length === 0 || $.inArray(renglon, alumnos) === -1 || typeof alumnosNotas[renglon] === 'undefined') {
							return;
						}

						if (alumnosNotas[renglon] < 0) {
							error_calculo_nota = true;
							error_numero = alumnosNotas[renglon];
						} else {
							$nota.val(alumnosNotas[renglon]).change().keyup();
						}
					});
				}

				if (error_calculo_nota === true) {
					var mensaje_error = '';

					switch (error_numero) {
						case '-1':
							mensaje_error = info.mensajes.escalas_notas_deben_ser_numericas;
							break;
						case '-2':
							mensaje_error = info.mensajes.no_hay_evaluaciones_promediables;
							break;
						case '-3':
							mensaje_error = info.mensajes.escalas_notas_deben_ser_consistentes;
							break;
						case '-4':
							mensaje_error = info.mensajes.no_hay_evaluaciones_comision;
							break;
						default:
							mensaje_error = info.mensajes.error_desconocido;
					}

					$.facebox($("<div style='font-weight: bold; text-align: center;'>" + info.mensajes.no_pueden_calcular_notas + "</div><br /><div>" + mensaje_error + '</div>'));
				} else {
					$('#notas_cursada_filtrar_acta').val('todas-todas').change();
					kernel.ui.show_mensaje(info.mensajes.msg_exito_calcular_nota, {tipo: 'alert-success'});
				}

				mostrar_omitidos(omitidos);
			}
		});

		return true;
	}

	function calcular_notas()
	{
		var instancia = $('#instancia').val();
		var instancias_validas = [
			info.inst_cursar,
			info.inst_promocionar
		];

		if ($.inArray(instancia, instancias_validas) === -1) {
			kernel.ui.show_mensaje(info.mensajes.instancia_invalida, {tipo: 'alert-error'});
			return false;
		}

		var para = $('#aplicar_a').val();
		var para_validos = [
			info.autocompletar_para_todos_pagina,
			info.autocompletar_para_sin_datos_pagina,
			info.autocompletar_para_todos,
			info.autocompletar_para_sin_datos
		];

		if ($.inArray(para, para_validos) === -1) {
			kernel.ui.show_mensaje(info.mensajes.aplicar_a_invalido, {tipo: 'alert-error'});
			return false;
		}

		var regla_calculo = $('#regla_calculo').val();
		if ($.inArray(regla_calculo, info.reglas_calculo) === -1) {
			kernel.ui.show_mensaje(info.mensajes.regla_calculo_invalido, {tipo: 'alert-error'});
			return false;
		}

		if (hay_alumnos_bloqueados_asistencia() && para_es_global(para)) {
			kernel.ui.show_mensaje(mensaje_calculo_global_bloqueado(), {tipo: 'alert-info'});
			return false;
		}

		if ($.inArray(para, [info.autocompletar_para_todos_pagina, info.autocompletar_para_sin_datos_pagina]) !== -1) {
			return calcular_notas_pagina(instancia, para, regla_calculo);
		}

		kernel.ui.prompt({
			msg: info.mensajes.msg_prompt_modificara_todas_paginas,
			msg_ok: info.mensajes.aceptar,
			msg_cancel: info.mensajes.cancelar,
			ok: function ()
			{
				sessionStorage.hay_cambios = false;
				$('#calcular-notas').submit();
			}
		});

		return true;
	}

	return {
		onload: function ()
		{
			if (info.mensajes.exito_autocompletar) {
				kernel.ui.show_mensaje(info.mensajes.exito_autocompletar, {tipo: 'alert-success'});
			}

			if (info.mensajes.error_autocompletar) {
				kernel.ui.show_mensaje(info.mensajes.error_autocompletar, {tipo: 'alert-error'});
			}

			if (info.mensajes.no_cambios_autocompletar) {
				kernel.ui.show_mensaje(info.mensajes.no_cambios_autocompletar, {tipo: 'alert-info'});
			}

			if (info.mensajes.exito_calcular_nota) {
				kernel.ui.show_mensaje(info.mensajes.exito_calcular_nota, {tipo: 'alert-success'});
			}

			if (info.mensajes.sin_alumnos_calcular_nota) {
				kernel.ui.show_mensaje(info.mensajes.sin_alumnos_calcular_nota, {tipo: 'alert-info'});
			}

			if (info.mensajes.error_calcular_nota) {
				kernel.ui.show_mensaje(info.mensajes.error_calcular_nota, {tipo: 'alert-error'});
			}

			if (info.mensajes.error_regla_calculo) {
				$.facebox(info.mensajes.error_regla_calculo);
			}

			$('#btnCompletar').on('click', function (e)
			{
				e.preventDefault();
				autocompletar();
			});

			$('#btn_undo_autocompletar').on('click', function (e)
			{
				e.preventDefault();
				recargar_datos_anteriores();
				$('#btn_undo_autocompletar').prop('disabled', true);
			});

			$('#btn_completar_nota').on('click', function (e)
			{
				e.preventDefault();
				calcular_notas();
			});

			$('#btn_undo_nota').on('click', function (e)
			{
				e.preventDefault();
				recargar_datos_anteriores();
				$('#btn_undo_nota').prop('disabled', true);
			});

			fecha_inicio = create_date(info.fecha_inicio);
			fecha_fin = create_date(info.fecha_fin);

			$('#js-valor-' + info.autocompletar_campo_fecha).datepicker({
				minDate: fecha_inicio,
				maxDate: fecha_fin
			});

			$('#preset_campo').on('change', function ()
			{
				$('#preset_form').find('.preset_valor:not(.resultado)').val('');
				cambiar_preset_valor($(this).val());
				actualizar_opciones_globales_autocompletar();
				controlar_btn_completar();
			});

			$('.preset_valor').on('keyup', function ()
			{
				controlar_btn_completar();
			});

			$('#preset_form .preset_valor').on('change', function ()
			{
				validar_valor_preset_actual();
			});

			$('#preset_form').on('submit', function (e)
			{
				var campo = $('#preset_campo').val();
				var para = $('#autocompletar_para').val();

				if (hay_alumnos_bloqueados_asistencia() && campo_bloqueado_por_asistencia(campo) && para_es_global(para)) {
					e.preventDefault();
					kernel.ui.show_mensaje(mensaje_autocompletar_global_bloqueado(), {tipo: 'alert-info'});
					return false;
				}
			});

			$('#calcular-notas').on('submit', function (e)
			{
				if (hay_alumnos_bloqueados_asistencia() && para_es_global($('#aplicar_a').val())) {
					e.preventDefault();
					kernel.ui.show_mensaje(mensaje_calculo_global_bloqueado(), {tipo: 'alert-info'});
					return false;
				}
			});

			setup_preset_valor();
			actualizar_opciones_globales_autocompletar();
			actualizar_opciones_globales_calculo();
			controlar_btn_completar();
			setup_unload();
			setTimeout(setup_links, 5000);
		}
	};
});
