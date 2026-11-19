<?php
class form_ml_notas extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- SALIDA -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se redefine el método que genera el encabezado.
	 * No se incluyen los nombres de columnas de la instancia promocionar.
	 */
	function generar_formulario_encabezado()
	{
		echo "<thead id='cabecera_{$this->objeto_js}'>\n";
		echo "<tr>\n";
		$primera = true;
		foreach ($this->_lista_ef_post as $ef) {
			if ($ef == 'alumno') {
				continue;
			}
			if ($ef == 'instancia_cursada') {
				continue;
			}
			if ($ef == 'instancia_promocion') {
				break;
			}
			$id_form = $this->_elemento_formulario[$ef]->get_id_form_orig();
			$extra = '';
			if ($primera) {
				$extra = 'colspan="' . ($this->_colspan + 1) . '"';
			}
			echo "<th $extra id='nodo_$id_form' class='ei-ml-columna'>\n";
			$this->generar_etiqueta_columna($ef);
			echo "</th>\n";
			$primera = false;
		}
		echo "</tr>\n";
		echo "</thead>\n";
	}

	function generar_botones($clase = '', $extra = '')
	{
		if ($this->hay_botones()) {
			echo "<div class='ei-botonera ei-botonera-centrada'>";
			$this->generar_boton('primera', false);
			$this->generar_boton('anterior', false);
			echo "<div style='padding-top:6px; display:inline-block;'>Página <strong>{$this->controlador()->get_pagina_actual()}</strong> de <strong>{$this->controlador()->get_cantidad_paginas()}</strong></div>";
			$this->generar_boton('siguiente', false, false);
			$this->generar_boton('ultima', false, false);
			echo "</div>";
		}
	}

	function generar_layout($ancho = 'auto')
	{
		if ($this->controlador()->get_cantidad_paginas() == 1) {
			$this->evento('primera')->set_en_botonera(false);
			$this->evento('anterior')->set_en_botonera(false);
			$this->evento('siguiente')->set_en_botonera(false);
			$this->evento('ultima')->set_en_botonera(false);
		}
		parent::generar_layout();
	}

	/**
	 * Se redefine el método que genera la lista de EFs de cada fila.
	 * Se muestran u ocultan los EFs de las instancias cursar o promocionar dependiendo de las inscripciones del alumno.
	 * Si ambas instancias son válidas, cada fila se divide en dos.
	 */
	function generar_layout_fila($clave_fila)
	{
		$instancia_cursada = $this->_elemento_formulario['instancia_cursada']->get_estado();
		$instancia_promocion = $this->_elemento_formulario['instancia_promocion']->get_estado();

		$extra = '';
		$nueva_fila = '';
		if ($instancia_cursada && $instancia_promocion) {
			$extra = 'rowspan=2';
			$nueva_fila = "</tr><tr>";
		}

		$oculto_cursada = '';
		if (!$instancia_cursada) {
			$oculto_cursada = "style='display:none'";
		}

		$oculto_promocion = '';
		if (!$instancia_promocion) {
			$oculto_promocion = "style='display:none'";
		}

		$i = 0;
		foreach ($this->_lista_ef_post as $ef) {
			if ($i == 0) { // El EF 'alumno' siempre es invisible. No se usa un 'ef_oculto' porque se necesita acceder a su valor desde JS.
				echo "<td $extra style='display:none'>";
			} elseif ($i < 4) { // Datos del alumno: ROWSPAN.
				echo "<td $extra>";
			} elseif ($i == 4) { // El EF 'instancia_cursar' siempre es invisible. No se usa un 'ef_oculto' porque se necesita acceder a su valor desde JS.
				echo "<td style='display:none'>";
			} elseif ($i < 12) { // Instancia cursar: ocultar.
				echo "<td $oculto_cursada>";
			} elseif ($i == 12) { // El EF 'instancia_promocion' siempre es invisible. No se usa un 'ef_oculto' porque se necesita acceder a su valor desde JS.
				echo "<td style='display:none'>";
			} else { // Instancia promocionar: ocultar.
				echo "<td $oculto_promocion>";
			}
			$this->generar_input_ef($ef);
			echo '</td>';
			if ($i == 11) { // Último EF de la instancia cursar: nueva fila.
				echo $nueva_fila;
			}
			if ($i == 15) { // Condición para la instancia promocionar: columna vacía.
				echo "<td $oculto_promocion>&nbsp;</td>";
			}
			$i++;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$date = guarani_fecha::get_hoy_js();

		$fecha_inicio_periodo = $this->controlador()->get_fecha_inicio_periodo_comision();
		$dia = guarani_fecha::get_parte($fecha_inicio_periodo, 'dia');
		$mes = guarani_fecha::get_parte($fecha_inicio_periodo, 'mes') - 1;
		$anio = guarani_fecha::get_parte($fecha_inicio_periodo, 'año');

		$msj_fecha_regular_actual = guarani_mensajes::get('800_control_fechas', array('de regular', 'mayor', 'actual.'));
		$msj_fecha_regular_periodo = guarani_mensajes::get('800_control_fechas', array('de regular', 'menor', 'inicio del período lectivo de la comisión.'));
		$msj_fecha_promocion_actual = guarani_mensajes::get('800_control_fechas', array('de promoción', 'mayor', 'actual.'));
		$msj_fecha_promocion_periodo = guarani_mensajes::get('800_control_fechas', array('de promoción', 'menor', 'inicio del período lectivo de la comisión.'));

		echo "
		//---- Validación de EFs -----------------------------------

		var hoy = new $date;
		var fecha_inicio_periodo = new Date($anio, $mes, $dia);

		/**
		 * Verifica que la fecha de regular sea menor o igual a la fecha actual.
	 	 */
		{$this->objeto_js}.evt__fecha_regular__validar = function(fila)
		{
			if (this.ef('fecha_regular').ir_a_fila(fila).tiene_estado()) {
				var fecha_regular = this.ef('fecha_regular').ir_a_fila(fila).fecha();
				if (fecha_regular > hoy) {
					this.ef('fecha_regular').ir_a_fila(fila).set_error('$msj_fecha_regular_actual');
					return false;
				}
				if (fecha_regular < fecha_inicio_periodo) {
					this.ef('fecha_regular').ir_a_fila(fila).set_error('$msj_fecha_regular_periodo');
					return false;
				}
			}
			return true;
		}

		/**
		 * Verifica que la fecha de promoción sea menor o igual a la fecha actual.
	 	 */
		{$this->objeto_js}.evt__fecha_promocion__validar = function(fila)
		{
			if (this.ef('fecha_promocion').ir_a_fila(fila).tiene_estado()) {
				var fecha_promocion = this.ef('fecha_promocion').ir_a_fila(fila).fecha();
				if (fecha_promocion > hoy) {
					this.ef('fecha_promocion').ir_a_fila(fila).set_error('$msj_fecha_promocion_actual');
					return false;
				}
				if (fecha_promocion < fecha_inicio_periodo) {
					this.ef('fecha_promocion').ir_a_fila(fila).set_error('$msj_fecha_promocion_periodo');
					return false;
				}
			}
			return true;
		}
		";

		$msj_nota_invalida = guarani_mensajes::get('800_nota_invalida');
		$msj_condicion_invalida = toba::proyecto()->get_mensaje_proyecto('800CUR_error_resultado_condicion');
		$msj_condicion_invalida = $msj_condicion_invalida['m'];
		$msj_resultados_invalidos = toba::proyecto()->get_mensaje_proyecto('800CUR_error_resultado_instancia');
		$msj_resultados_invalidos = $msj_resultados_invalidos['m'];

		$instancia_cursar = comision::inst_cursar;
		$instancia_promocionar = comision::inst_promocionar;

		$resultado_aprobado = escalas_notas::resultado_aprobado;
		$resultado_reprobado = escalas_notas::resultado_reprobado;
		$resultado_ausente = escalas_notas::resultado_ausente;

		$mapeo_instancia_escala = $this->controlador()->generar_mapeo_js_instancia_escala();
		$mapeo_resultado_condicion = $this->controlador()->generar_mapeo_js_resultado_condicion();

		$js_validacion_nota = $this->controlador()->get_js_validar_nota_escalas();

		echo "
		$js_validacion_nota
		var seteo_resultado_cursada_defecto = false;
		var seteo_resultado_promocion_defecto = false;
		var escalas = $mapeo_instancia_escala;
		var condiciones_posibles = {$mapeo_resultado_condicion['posibles']};
		var condiciones_defecto = {$mapeo_resultado_condicion['defecto']};

		//---- Validacion de Datos --------------------------------

		/**
		 * Verifica la correspondencia entre los resultados de cursada y promoción.
	 	 */
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var filas = this.filas();
			for (id_fila in filas) {
				if (this.ef('resultado_cursada').ir_a_fila(filas[id_fila]).tiene_estado() && this.ef('resultado_promocion').ir_a_fila(filas[id_fila]).tiene_estado()) {
					var resultado_cursada = this.ef('resultado_cursada').ir_a_fila(filas[id_fila]).get_estado();
					var resultado_promocion = this.ef('resultado_promocion').ir_a_fila(filas[id_fila]).get_estado();

					// Si en la instancia CURSAR se ingresó el resultado DESAPROBADO, en la instancia PROMOCIONAR NO puede cargarse el resultado APROBADO.
					if (resultado_cursada == '$resultado_reprobado' && resultado_promocion == '$resultado_aprobado') {
						var alumno = this.ef('apellido_y_nombre').ir_a_fila(filas[id_fila]).get_estado();
						var msj = '$msj_resultados_invalidos';
						notificacion.agregar(msj.replace('%1%', alumno));
						return false;
					}
				}
			}
			return true;
		}

		//---- Procesamiento de EFs --------------------------------

		/**
		 * Valida la nota de cursada en la escala correspondiente.
		 * Si es válida se formatea y se autocompleta el resultado de cursada.
	 	 */
		{$this->objeto_js}.evt__nota_cursada__procesar = function(es_inicial, fila)
		{
			if (! es_inicial && this.ef('nota_cursada').ir_a_fila(fila).activo()) {
				if (this.ef('nota_cursada').ir_a_fila(fila).tiene_estado()) {
					var nota_cursada = this.ef('nota_cursada').ir_a_fila(fila).get_estado();

					if (! eval('es_nota_valida_para_escala_'+escalas[$instancia_cursar]+'(nota_cursada)')) {
						var alumno = this.ef('apellido_y_nombre').ir_a_fila(fila).get_estado();
						notificacion.limpiar();
						notificacion.agregar('$msj_nota_invalida' + alumno + '.');
						notificacion.mostrar();
						this.ef('nota_cursada').ir_a_fila(fila).resetear_estado();
						this.ef('resultado_cursada').ir_a_fila(fila).resetear_estado();
					} else {
						var resultado_cursada = eval('get_resultado_para_escala_'+escalas[$instancia_cursar]+'(nota_cursada)');
						seteo_resultado_cursada_defecto = true;
						this.ef('resultado_cursada').ir_a_fila(fila).set_estado(resultado_cursada);
						var nota_cursada_formateada = eval('formatear_nota_escala_'+escalas[$instancia_cursar]+'(nota_cursada)');
						this.ef('nota_cursada').ir_a_fila(fila).set_estado(nota_cursada_formateada);

						if (this.ef('instancia_promocion').ir_a_fila(fila).get_estado() &&
							this.ef('nota_promocion').ir_a_fila(fila).activo() &&
							! this.ef('nota_promocion').ir_a_fila(fila).tiene_estado()) { // Si no hay nota de promoción...
							if (eval('es_nota_valida_para_escala_'+escalas[$instancia_promocionar]+'(nota_cursada)')) { // Si la nota de cursada es válida también para promoción...
								var nota_promocion_formateada = eval('formatear_nota_escala_'+escalas[$instancia_promocionar]+'(nota_cursada)');
								this.ef('nota_promocion').ir_a_fila(fila).set_estado(nota_promocion_formateada); // Se completa la nota de promoción.
								this.autocompletar_resultado_promocion(nota_cursada, fila); // Se autocompleta el resultado de promoción.
							}
						}
					}
				} else {
					if (this.ef('resultado_cursada').ir_a_fila(fila).tiene_estado() && 
						this.ef('resultado_cursada').ir_a_fila(fila).get_estado() != '$resultado_ausente')
						this.ef('resultado_cursada').ir_a_fila(fila).resetear_estado();
				}
			}
		}

		/**
		 * Al cambiar el resultado de cursada (siempre que el mismo NO haya sido autocompletado), se resetea la nota de cursada.
		 * Se autocompleta la condición de regularidad.
	 	 */
		{$this->objeto_js}.evt__resultado_cursada__procesar = function(es_inicial, fila)
		{
			if (! es_inicial) {
				if (! seteo_resultado_cursada_defecto) {
					this.ef('nota_cursada').ir_a_fila(fila).resetear_estado();
				} else {
					seteo_resultado_cursada_defecto = false;
				}

				if (this.ef('resultado_cursada').ir_a_fila(fila).tiene_estado()) {
					var resultado_cursada = this.ef('resultado_cursada').ir_a_fila(fila).get_estado();
					if (!this.ef('cond_regularidad').ir_a_fila(fila).tiene_estado() ||
						!in_array(this.ef('cond_regularidad').ir_a_fila(fila).get_estado(), condiciones_posibles[resultado_cursada])) {
						this.ef('cond_regularidad').ir_a_fila(fila).set_estado(condiciones_defecto[resultado_cursada]);
					}
				} else {
					this.ef('cond_regularidad').ir_a_fila(fila).resetear_estado();
				}
			}
		}

		/**
		 * Verifica que la condición de regularidad sea válida respecto al resultado.
	 	 */
		{$this->objeto_js}.evt__cond_regularidad__procesar = function(es_inicial, fila)
		{
			if (! es_inicial && this.ef('resultado_cursada').ir_a_fila(fila).tiene_estado() && this.ef('cond_regularidad').ir_a_fila(fila).tiene_estado()) {
				var resultado_cursada = this.ef('resultado_cursada').ir_a_fila(fila).get_estado();
				var cond_regularidad = this.ef('cond_regularidad').ir_a_fila(fila).get_estado();

				if (! in_array(cond_regularidad, condiciones_posibles[resultado_cursada])) {
					var alumno = this.ef('apellido_y_nombre').ir_a_fila(fila).get_estado();
					var msj = '$msj_condicion_invalida';
					notificacion.limpiar();
					notificacion.agregar(msj.replace('%1%', alumno));
					notificacion.mostrar();
					this.ef('cond_regularidad').ir_a_fila(fila).resetear_estado();
				}
			}
		}

		/**
		 * Al ingresar una fecha de regular válida, si la fecha de promoción está vacía se completa con el mismo valor.
		 */
		{$this->objeto_js}.evt__fecha_regular__procesar = function(es_inicial, fila)
		{
			if (! es_inicial && this.ef('fecha_regular').ir_a_fila(fila).tiene_estado()) {
				if (this.ef('instancia_promocion').ir_a_fila(fila).get_estado() &&
					this.ef('fecha_promocion').ir_a_fila(fila).activo() &&
					! this.ef('fecha_promocion').ir_a_fila(fila).tiene_estado()) {
					if (this.ef('fecha_regular').ir_a_fila(fila).fecha() <= hoy) {
						this.ef('fecha_promocion').ir_a_fila(fila).set_estado(this.ef('fecha_regular').ir_a_fila(fila).get_estado());
					}
				}
			}
		}

		/**
		 * Valida la nota de promoción en la escala correspondiente.
		 * Si es válida se formatea y se autocompleta el resultado de promoción.
	 	 */
		{$this->objeto_js}.evt__nota_promocion__procesar = function(es_inicial, fila)
		{
			if (! es_inicial && this.ef('nota_promocion').ir_a_fila(fila).activo()) {
				if (this.ef('nota_promocion').ir_a_fila(fila).tiene_estado()) {
					var nota_promocion = this.ef('nota_promocion').ir_a_fila(fila).get_estado();
					var alumno = this.ef('apellido_y_nombre').ir_a_fila(fila).get_estado();

					var es_nota_valida = eval('es_nota_valida_para_escala_'+escalas[$instancia_promocionar]+'(nota_promocion)');

					if (! es_nota_valida) {
						notificacion.limpiar();
						notificacion.agregar('$msj_nota_invalida' + alumno + '.');
						notificacion.mostrar();
						this.ef('nota_promocion').ir_a_fila(fila).resetear_estado();
						this.ef('resultado_promocion').ir_a_fila(fila).resetear_estado();
					} else {
						this.autocompletar_resultado_promocion(nota_promocion, fila);
						var nota_promocion_formateada = eval('formatear_nota_escala_'+escalas[$instancia_promocionar]+'(nota_promocion)');
						this.ef('nota_promocion').ir_a_fila(fila).set_estado(nota_promocion_formateada);
					}
				} else {
					this.ef('resultado_promocion').ir_a_fila(fila).resetear_estado();
				}
			}
		}

		/**
		 * Al cambiar el resultado de promoción (siempre que el mismo NO haya sido autocompletado y el resultado NO sea reprobado), se resetea la nota de promoción.
	 	 */
		{$this->objeto_js}.evt__resultado_promocion__procesar = function(es_inicial, fila)
		{
			if (! es_inicial) {
				if (this.ef('resultado_promocion').ir_a_fila(fila).tiene_estado()) {
					var resultado_promocion = this.ef('resultado_promocion').ir_a_fila(fila).get_estado();
					if (! seteo_resultado_promocion_defecto && resultado_promocion != '$resultado_reprobado') {
						this.ef('nota_promocion').ir_a_fila(fila).resetear_estado();
					} else {
						seteo_resultado_promocion_defecto = false;
					}
				}
			}
		}

		//---- Auxiliares ------------------------------------------

		{$this->objeto_js}.autocompletar_resultado_promocion = function(nota_promocion, fila)
		{
			var resultado_promocion = eval('get_resultado_para_escala_'+escalas[$instancia_promocionar]+'(nota_promocion)');
			seteo_resultado_promocion_defecto = true;
			this.ef('resultado_promocion').ir_a_fila(fila).set_estado(resultado_promocion);
		}
		";
	}

}
?>