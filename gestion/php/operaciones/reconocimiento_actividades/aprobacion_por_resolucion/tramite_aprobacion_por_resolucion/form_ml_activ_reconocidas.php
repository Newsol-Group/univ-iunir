<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class form_ml_activ_reconocidas extends guarani_pers_ei_formulario_ml
{
	/**
	 * Se redefine el método que genera el encabezado.
	 * No se incluyen los nombres de columnas de los EFs fijos que se ocultan en el layout de la fila.
	 */
	function generar_formulario_encabezado()
	{
		echo "<thead id='cabecera_{$this->objeto_js}'>\n";
		echo "<tr>\n";
		$primera = true;
		foreach ($this->_lista_ef_post as $ef) {
			if ($ef == 'elemento' || $ef == 'elemento_tipo' || $ef == 'creditos_max' || $ef == 'creditos_min') {
				continue;
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

	/**
	 * Se redefine el método que genera la lista de EFs de cada fila.
	 * Esto se hace para poder ocultar algunos EFs fijos
	 * que no se definen como EFs ocultos porque se necesita acceder su valor desde JS.
	 */
	function generar_layout_fila($clave_fila)
	{
		foreach ($this->_lista_ef_post as $ef) {
			$ocultar_campo = '';
			if ($ef == 'elemento' || $ef == 'elemento_tipo' || $ef == 'creditos_max' || $ef == 'creditos_min') { // Los EFs 'elemento', 'elemento_tipo', 'creditos_max' y 'creditos_min'  siempre son invisibles.
				$ocultar_campo = "style='display:none'";
			}
			$this->_elemento_formulario[$ef]->ir_a_fila($clave_fila);
			$id_form = $this->_elemento_formulario[$ef]->get_id_form();
			echo "<td class='{$this->estilo_celda_actual}' id='cont_$id_form' $ocultar_campo>\n";
			$this->generar_input_ef($ef);
			echo "</td>\n";
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$js_validacion_nota = $this->controlador()->get_js_validar_nota_escala();
		$resultado_aprobado = escalas_notas::resultado_aprobado;
		$elemento_tipo_modulo = elemento::tipo_modulo;
		$msj_nota_invalida_escala = toba::proyecto()->get_mensaje_proyecto('800EQU_nota_invalida_escala');
		$msj_nota_invalida_escala = $msj_nota_invalida_escala['m'];
		$msj_nota_resultado_invalido = toba::proyecto()->get_mensaje_proyecto('800EQU_nota_invalida_resultado');
		$msj_nota_resultado_invalido = $msj_nota_resultado_invalido['m'];
		$msj_cred_max_invalidos = toba::proyecto()->get_mensaje_proyecto('800EQU_actividad_creditos_max_invalidos');
		$msj_cred_max_invalidos = $msj_cred_max_invalidos['m'];
		$msj_cred_min_invalidos = toba::proyecto()->get_mensaje_proyecto('800EQU_actividad_creditos_min_invalidos');
		$msj_cred_min_invalidos = $msj_cred_min_invalidos['m'];
		
		echo "
		$js_validacion_nota
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__nota__validar = function(fila)
		{
			var nota_ok = true;
			if (this.ef('nota').ir_a_fila(fila).tiene_estado()) {
				// Se valida que la nota pertenezca a la escala de notas seleccionada.
				var escala = this.ef('escala_nota').ir_a_fila(fila).get_estado();
				var nota = this.ef('nota').ir_a_fila(fila).get_estado();
				var es_nota_valida_escala = eval('es_nota_valida_para_escala_' + escala + '(nota)');
				if (!es_nota_valida_escala) {
					// La nota no pertenece a la escala, se retorna el msj de error
					var actividad_descr = this.ef('actividad_descr').ir_a_fila(fila).get_estado();
					var msj = '$msj_nota_invalida_escala';
					this.ef('nota').ir_a_fila(fila).set_error(msj.replace('%1%', actividad_descr));
					return false;
				}  else {
					this.ef('nota').ir_a_fila(fila).resetear_error();
				}
				
				// Valida que la nota seleccionada tenga asociada un resultado aprobado (de otra forma
				// tiene sentido para el reconocimiento de la actividad).
				var resultado = eval('get_resultado_para_escala_' + escala + '(nota)');
				if (resultado != '$resultado_aprobado') {
					var actividad_descr = this.ef('actividad_descr').ir_a_fila(fila).get_estado();
					var msj_result = '$msj_nota_resultado_invalido';
					this.ef('nota').ir_a_fila(fila).set_error(msj_result.replace('%1%', actividad_descr));
					nota_ok = false;
				} else {
					this.ef('nota').ir_a_fila(fila).resetear_error();
				}
			}	
			
			return nota_ok;
		}
		
		
		{$this->objeto_js}.evt__creditos__validar = function(fila)
		{
			var creditos_ok = true;
			if (this.ef('creditos').ir_a_fila(fila).tiene_estado()) {
				var cred = this.ef('creditos').ir_a_fila(fila).get_estado();
				if (this.ef('creditos_max').ir_a_fila(fila).tiene_estado()) {
					// Se valida que si están seteados los créditos para la actividad, no superen
					// la cantidad de créditos máximos definidos para la misma (si es que existe ese límite).
					var max = this.ef('creditos_max').ir_a_fila(fila).get_estado();
					if (cred > max) {
						var actividad_descr = this.ef('actividad_descr').ir_a_fila(fila).get_estado();
						var msj = '$msj_cred_max_invalidos';
						var msj_aux = msj.replace('%1%', actividad_descr);
						this.ef('creditos').ir_a_fila(fila).set_error(msj_aux.replace('%2%', max));
						creditos_ok = false;
					}
				}
				if (this.ef('creditos_min').ir_a_fila(fila).tiene_estado()) {
					// Se valida que si están seteados los créditos para la actividad, no sean
					// inferiores a la cantidad de créditos mínimos definidos para la misma (si es que existe ese límite).
					var min = this.ef('creditos_min').ir_a_fila(fila).get_estado();
					if (cred < min) {
						var actividad_descr = this.ef('actividad_descr').ir_a_fila(fila).get_estado();
						var msj = '$msj_cred_min_invalidos';
						var msj_aux = msj.replace('%1%', actividad_descr);
						this.ef('creditos').ir_a_fila(fila).set_error(msj_aux.replace('%2%', min));
						creditos_ok = false;
					}
				}
			} else if (this.ef('reconocimiento_total').ir_a_fila(fila).get_estado() == 'N') {
				// Si se reconoce un módulo de forma parcial es obligatorio ingresar créditos.
				this.ef('creditos').ir_a_fila(fila).set_error('" . guarani_mensajes::get('800_error_es_obligatorio') . "'); 
				creditos_ok = false;
			}			
		
			return creditos_ok;
		} 
		
		//---- Procesamiento de ef's ------------------------------
		
		{$this->objeto_js}.evt__nota__procesar = function(es_inicial, fila)
		{
			// Se formatea el valor de la nota ingresado para unificar notaciones decimales.
			if (!es_inicial) {	
				if (this.ef('escala_nota').ir_a_fila(fila).tiene_estado()) {
					var escala	= this.ef('escala_nota').ir_a_fila(fila).get_estado();
					var nota	= this.ef('nota').ir_a_fila(fila).get_estado();
					var es_nota_valida_escala = eval('es_nota_valida_para_escala_' + escala + '(nota)');
					if (es_nota_valida_escala) {
						var nota_format = eval('formatear_nota_escala_'+escala+'(nota)');
						this.ef('nota').ir_a_fila(fila).set_estado(nota_format);
					}
				}
			}
		} 
		
		{$this->objeto_js}.evt__reconocimiento_total__procesar = function(es_inicial, fila) 
		{
			var habilitar_notas = false;
			// Si el reconocimiento es total y el elemento es de Tipo Actividad se habilitan los campos Escala y Nota.
		 	if (this.ef('reconocimiento_total').ir_a_fila(fila).get_estado() == 'S') {				
				var tipo_elemento = this.ef('elemento_tipo').ir_a_fila(fila).get_estado();
				if (tipo_elemento != '$elemento_tipo_modulo') {	// Elemento de Tipo Actividad.
					habilitar_notas = true;
				}
		 	}		 	
		} 
				
		
		{$this->objeto_js}.evt__homologada__procesar = function(es_inicial, fila)
		{
			var tipo_elemento = this.ef('elemento_tipo').ir_a_fila(fila).get_estado();			
			if (tipo_elemento == '$elemento_tipo_modulo') {	// Elemento de Tipo Actividad.
				this.ef('homologada').ir_a_fila(fila).desactivar();
			}
			if (this.ef('homologada').ir_a_fila(fila).chequeado()) {
				this.ef('creditos').ir_a_fila(fila).resetear_estado();
				this.ef('creditos').ir_a_fila(fila).desactivar();
				//this.ef('escala_nota').ir_a_fila(fila).resetear_estado();
		 		this.ef('escala_nota').ir_a_fila(fila).desactivar();
		 		this.ef('nota').ir_a_fila(fila).resetear_estado();
		 		this.ef('nota').ir_a_fila(fila).desactivar();
			} else {
				this.ef('escala_nota').ir_a_fila(fila).activar();
			 	this.ef('nota').ir_a_fila(fila).activar();
				this.ef('creditos').ir_a_fila(fila).activar();
			}
		}
		
		";
	}

}
?>