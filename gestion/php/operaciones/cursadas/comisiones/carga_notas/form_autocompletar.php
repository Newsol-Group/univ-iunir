<?php
class form_autocompletar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table>
					<tr>
						<td>'; $this->generar_html_ef('campo_completar', '100px'); echo '</td>
						<td>'; $this->generar_html_ef('valor_nota_cursada', '80px'); echo '</td>
						<td>'; $this->generar_html_ef('valor_nota_promocion', '80px'); echo '</td>
						<td>'; $this->generar_html_ef('valor_resultado_cursada', '80px'); echo '</td>
						<td>'; $this->generar_html_ef('valor_resultado_promocion', '80px'); echo '</td>
						<td>'; $this->generar_html_ef('valor_fecha', '80px'); echo '</td>
						<td>'; $this->generar_html_ef('valor_observaciones', '80px'); echo '</td>
						<td>'; $this->generar_html_ef('para', '45px'); echo '</td>
						<td>'; $this->generar_botones(); echo '</td>
					</tr>
				</table>';

		$this->evento('completar')->set_en_botonera(false);
		$this->evento('completar_todos')->set_en_botonera(false);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		";

		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('para autocompletar', 'mayor', 'actual'));

		$msj_nota = guarani_mensajes::get('800_nota_invalida', array('autocompletar.'));

		$campo_nota_cursada = comision::autocompletar_campo_nota_cursada;
		$campo_nota_promocion = comision::autocompletar_campo_nota_promocion;
		$campo_resultado_cursada = comision::autocompletar_campo_resultado_cursada;
		$campo_resultado_promocion = comision::autocompletar_campo_resultado_promocion;
		$campo_fecha = comision::autocompletar_campo_fecha;
		$campo_observaciones = comision::autocompletar_campo_observaciones;

		$autocompletar_para_todos = comision::autocompletar_para_todos;
		$autocompletar_para_todos_pagina = comision::autocompletar_para_todos_pagina;
		$autocompletar_para_sin_datos = comision::autocompletar_para_sin_datos;

		$instancia_cursar = comision::inst_cursar;
		$instancia_promocionar = comision::inst_promocionar;
		
		$mapeo_instancia_escala = $this->controlador()->controlador()->generar_mapeo_js_instancia_escala();
		$mapeo_resultado_condicion = $this->controlador()->controlador()->generar_mapeo_js_resultado_condicion();

		echo "
		var escalas = $mapeo_instancia_escala;
		var condiciones_defecto = {$mapeo_resultado_condicion['defecto']};

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__campo_completar__procesar = function(es_inicial)
		{
			var completar = this.ef('campo_completar').get_estado();

			switch (completar) {
			case '$campo_nota_cursada':
				this.ef('valor_nota_cursada').mostrar(true);
				this.ef('valor_nota_promocion').ocultar(true);
				this.ef('valor_resultado_cursada').ocultar(true);
				this.ef('valor_resultado_promocion').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_observaciones').ocultar(true);
				break;
			case '$campo_nota_promocion':
				this.ef('valor_nota_cursada').ocultar(true);
				this.ef('valor_nota_promocion').mostrar(true);
				this.ef('valor_resultado_cursada').ocultar(true);
				this.ef('valor_resultado_promocion').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_observaciones').ocultar(true);
				break;
			case '$campo_resultado_cursada':
				this.ef('valor_nota_cursada').ocultar(true);
				this.ef('valor_nota_promocion').ocultar(true);
				this.ef('valor_resultado_cursada').mostrar(true);
				this.ef('valor_resultado_promocion').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_observaciones').ocultar(true);
				break;
			case '$campo_resultado_promocion':
				this.ef('valor_nota_cursada').ocultar(true);
				this.ef('valor_nota_promocion').ocultar(true);
				this.ef('valor_resultado_cursada').ocultar(true);
				this.ef('valor_resultado_promocion').mostrar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_observaciones').ocultar(true);
				break;
			case '$campo_fecha':
				this.ef('valor_nota_cursada').ocultar(true);
				this.ef('valor_nota_promocion').ocultar(true);
				this.ef('valor_resultado_cursada').ocultar(true);
				this.ef('valor_resultado_promocion').ocultar(true);
				this.ef('valor_fecha').mostrar(true);
				this.ef('valor_observaciones').ocultar(true);
				break;
			case '$campo_observaciones':
				this.ef('valor_nota_cursada').ocultar(true);
				this.ef('valor_nota_promocion').ocultar(true);
				this.ef('valor_resultado_cursada').ocultar(true);
				this.ef('valor_resultado_promocion').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_observaciones').mostrar(true);
				break;
			}
		}

		{$this->objeto_js}.evt__valor_nota_cursada__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('valor_nota_cursada').tiene_estado()) {
				var nota_cursada = this.ef('valor_nota_cursada').get_estado();
				if (! eval('es_nota_valida_para_escala_'+escalas[$instancia_cursar]+'(nota_cursada)')) {
					notificacion.limpiar();
					notificacion.agregar('$msj_nota');
					notificacion.mostrar();
					this.ef('valor_nota_cursada').resetear_estado();
				} else {
					var nota_cursada_formateada = eval('formatear_nota_escala_'+escalas[$instancia_cursar]+'(nota_cursada)');
					this.ef('valor_nota_cursada').set_estado(nota_cursada_formateada);
				}
			}
		}

		{$this->objeto_js}.evt__valor_nota_promocion__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('valor_nota_promocion').tiene_estado()) {
				var nota_promocion = this.ef('valor_nota_promocion').get_estado();
				if (! eval('es_nota_valida_para_escala_'+escalas[$instancia_promocionar]+'(nota_promocion)')) {
					notificacion.limpiar();
					notificacion.agregar('$msj_nota');
					notificacion.mostrar();
					this.ef('valor_nota_promocion').resetear_estado();
				} else {
					var nota_promocion_formateada = eval('formatear_nota_escala_'+escalas[$instancia_promocionar]+'(nota_promocion)');
					this.ef('valor_nota_promocion').set_estado(nota_promocion_formateada);
				}
			}
		}

		{$this->objeto_js}.evt__para__procesar = function(es_inicial)
		{
			var para = this.ef('para').get_estado();
			if (para == '$autocompletar_para_todos' || para == '$autocompletar_para_sin_datos') {
				this.ocultar_boton('completar');
				this.mostrar_boton('completar_todos');
			} else {
				this.mostrar_boton('completar');
				this.ocultar_boton('completar_todos');
			}
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			// Validación sobre el campo fecha.
			var hoy = new $date;
			if (this.ef('valor_fecha').tiene_estado()) {
				var fecha = this.ef('valor_fecha').fecha();
				if (fecha > hoy) {
					notificacion.agregar('$msj_fecha');
					return false;
				}
			}
			return true;
		}

		//---- Eventos ---------------------------------------------

		{$this->objeto_js}.evt__completar = function()
		{
			var form_ml = this.controlador.controlador.dep('form_ml_notas');
			var filas = form_ml.filas();

			var criterio = this.ef('para').get_estado();

			var campos;
			switch (this.ef('campo_completar').get_estado()) {
			case '$campo_nota_cursada':
				if (this.ef('valor_nota_cursada').tiene_estado()) {
					var nota_cursada = this.ef('valor_nota_cursada').get_estado();
					var resultado_cursada = eval('get_resultado_para_escala_'+escalas[$instancia_cursar]+'(nota_cursada)');
					var cond_regularidad = condiciones_defecto[resultado_cursada];
					campos = {	'resultado_cursada': {'campo_control': 'instancia_cursada', 'valor': resultado_cursada},
								'cond_regularidad': {'campo_control': 'instancia_cursada', 'valor': cond_regularidad},
								'nota_cursada': {'campo_control': 'instancia_cursada', 'valor': nota_cursada}
					};
				}
				break;
			case '$campo_nota_promocion':
				if (this.ef('valor_nota_promocion').tiene_estado()) {
					var nota_promocion = this.ef('valor_nota_promocion').get_estado();
					var resultado_promocion = eval('get_resultado_para_escala_'+escalas[$instancia_promocionar]+'(nota_promocion)');
					campos = {	'resultado_promocion': {'campo_control': 'instancia_promocion', 'valor': resultado_promocion},
								'nota_promocion': {'campo_control': 'instancia_promocion', 'valor': nota_promocion}
					};
				}
				break;
			case '$campo_resultado_cursada':
				var resultado_cursada = this.ef('valor_resultado_cursada').get_estado();
				campos = {'resultado_cursada': {'campo_control': 'instancia_cursada', 'valor': resultado_cursada}};
				break;
			case '$campo_resultado_promocion':
				var resultado_promocion = this.ef('valor_resultado_promocion').get_estado();
				campos = {'resultado_promocion': {'campo_control': 'instancia_promocion', 'valor': resultado_promocion}};
				break;
			case '$campo_fecha':
				var fecha = this.ef('valor_fecha').get_estado();
				campos = {	'fecha_regular': {'campo_control': 'instancia_cursada', 'valor': fecha},
							'fecha_promocion': {'campo_control': 'instancia_promocion', 'valor': fecha}
				};
				break;
			case '$campo_observaciones':
				var observaciones = this.ef('valor_observaciones').get_estado();
				campos = {	'observacion_cursada': {'campo_control': 'instancia_cursada', 'valor': observaciones},
							'observacion_promocion': {'campo_control': 'instancia_promocion', 'valor': observaciones}
				};
				break;
			}

			var ef_campo;
			var ef_campo_control;
			for (id_fila in filas) {
				for (campo in campos) {
					ef_campo = form_ml.ef(campo).ir_a_fila(filas[id_fila]);
					ef_campo_control = form_ml.ef(campos[campo]['campo_control']).ir_a_fila(filas[id_fila]);
					if (ef_campo.activo() &&
						ef_campo_control.get_estado() &&
						(criterio == '$autocompletar_para_todos' || criterio == '$autocompletar_para_todos_pagina' || ! ef_campo.tiene_estado())) {
									ef_campo.set_estado(campos[campo]['valor']);
									
									// Si el campo a autocompletar es 'Resultado de Regularidad' entonces vacio la nota
									if (campo == 'resultado_cursada') {
										form_ml.ef('nota_cursada').ir_a_fila(filas[id_fila]).set_estado('');
									}
									
									// Si el campo a autocompletar es 'Resultado de Promoción' entonces vacio la nota
									if (campo == 'resultado_promocion') {
										form_ml.ef('nota_promocion').ir_a_fila(filas[id_fila]).set_estado('');
									}
								}
							}
						}

			return false;
		}

		{$this->objeto_js}.evt__completar_todos = function()
		{
			var params = [];
			params['campo_completar'] = this.ef('campo_completar').get_estado();
			params['escala_cursada'] = escalas[$instancia_cursar];
			params['valor_nota_cursada'] = this.ef('valor_nota_cursada').get_estado();
			params['escala_promocion'] = escalas[$instancia_promocionar];
			params['valor_nota_promocion'] = this.ef('valor_nota_promocion').get_estado();
			params['valor_resultado_cursada'] = this.ef('valor_resultado_cursada').get_estado();
			params['valor_resultado_promocion'] = this.ef('valor_resultado_promocion').get_estado();
			params['valor_fecha'] = this.ef('valor_fecha').get_estado();
			params['valor_observaciones'] = this.ef('valor_observaciones').get_estado();
			params['para'] = this.ef('para').get_estado();
			this.controlador.ajax('completar_todos', params, this, this.respuesta_completar_todos);
			return false;
		}

		{$this->objeto_js}.respuesta_completar_todos = function(respuesta)
		{
			this.evt__completar(); 
		}
		";
	}
}