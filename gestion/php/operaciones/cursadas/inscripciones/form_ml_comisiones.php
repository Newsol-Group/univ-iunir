<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class form_ml_comisiones extends guarani_pers_ei_formulario_ml
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
			if ($ef == 'inscripcion_cerrada_codigo') {
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
			if ($ef == 'inscripcion_cerrada_codigo') { //El ef 'inscripcion_cerrada_codigo' siempre es invisible.
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
		$si = comunes::si_desc;
		$msj_codigo_invalido_comision = toba::proyecto()->get_mensaje_proyecto('800CUR_insc_cursada_codigo_inv');
		$msj_codigo_invalido_comision = $msj_codigo_invalido_comision['m'];
		$msj_obligatorio = guarani_mensajes::get('800_error_es_obligatorio');
		
		echo "
		
		//---- Validacion de EFs -----------------------------------	
		
		{$this->objeto_js}.evt__codigo_validacion__validar = function(fila)
		{
			var codigo_ok = true;
			// Si es una de las comisiones seleccionadas para la inscripción (se seleccionó prioridad).
			if (this.ef('prioridad').ir_a_fila(fila).tiene_estado()) {
				if (this.ef('inscripcion_cerrada_desc').ir_a_fila(fila).get_estado() == '$si' &&
					this.ef('inscripcion_cerrada_codigo').ir_a_fila(fila).tiene_estado()) {
					var cod_comision = this.ef('inscripcion_cerrada_codigo').ir_a_fila(fila).get_estado();
					if (this.ef('codigo_validacion').ir_a_fila(fila).tiene_estado()) {
						// Se valida que el código introducido por el usuario coincida con el de la comisión, para 
						// comisiones con inscripción cerrada.
						var cod_introducido = this.ef('codigo_validacion').ir_a_fila(fila).get_estado();
						if (cod_comision != cod_introducido) {
							var comision_descr = this.ef('comision_nombre').ir_a_fila(fila).get_estado();
							var msj = '$msj_codigo_invalido_comision';
							var msj_aux = msj.replace('%1%', comision_descr);
							this.ef('codigo_validacion').ir_a_fila(fila).set_error(msj_aux);
							codigo_ok = false;
						}
					} else {
						// Si la comisión es de inscripción cerrada, es obligatorio ingresar el código.
						this.ef('codigo_validacion').ir_a_fila(fila).set_error('$msj_obligatorio'); 
						codigo_ok = false;
					}
				}
			}
		
			return codigo_ok;
		} 
		
		//---- Procesamiento de ef's ------------------------------
		
		{$this->objeto_js}.evt__inscripcion_cerrada_desc__procesar = function(es_inicial, fila)
		{
			// Si la Comisión es no es de inscripción cerrada, el ef con el código de validación se 
			// pone como de solo lectura, en caso contrario se setea como obligatorio.
			// Se hace solo en el caso inicial ya que el valor de Insc. Cerrada no cambia (ef fijo).
			if (es_inicial) {	
				if (this.ef('inscripcion_cerrada_desc').ir_a_fila(fila).get_estado() != '$si') {
					this.ef('codigo_validacion').ir_a_fila(fila).resetear_estado();
					this.ef('codigo_validacion').ir_a_fila(fila).set_solo_lectura(true);
				} 
			}
		} 
		
		{$this->objeto_js}.evt__prioridad__procesar = function(es_inicial, fila)
		{
			// Si se deselecciona la comisión (se saca la prioridad), se resetea el valor insertado parpa
			// el código de validación (y el estado de error en caso de haber alguno).
			if (!es_inicial) {	
				if (!this.ef('prioridad').ir_a_fila(fila).tiene_estado()) {
					this.ef('codigo_validacion').ir_a_fila(fila).resetear_error();
					this.ef('codigo_validacion').ir_a_fila(fila).resetear_estado();
				} 
			}
		} 
	
		";
	}

}
?>