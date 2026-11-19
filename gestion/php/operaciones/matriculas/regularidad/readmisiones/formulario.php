<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Regularidad
 */
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset title="Pérdida de regularidad"><legend>Pérdida de regularidad</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo "<td width='30%'>";
					$this->generar_html_ef('fecha_format', '120px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('anio_academico_perdida_reg', '300px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('documento_numero', '120px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef_causa('causa_nombre');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '<fieldset title="Readmisión"><legend>Readmisión</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo "<td width='50%'>";
					$this->generar_html_ef('tipo', '120px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('contabiliza', '120px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo "<td width='30%'>";
					$this->generar_html_ef('fecha', '120px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('anio_academico', '220px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('documento', '120px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('fecha_vencimiento', '120px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('observaciones', '120px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '<p>&nbsp;</p>';
	}

	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$reinc_por_res = 'R';
		
		$fecha_perd_reg = $this->controlador()->controlador()->get_fecha_perdida_regularidad_seleccionada();
		$fecha_perd_reg_partes = explode('-', $fecha_perd_reg);
		$fecha_perd_reg_js = 'Date(' . $fecha_perd_reg_partes[0] . ',' . ($fecha_perd_reg_partes[1] - 1) . ',' . $fecha_perd_reg_partes[2] . ')';
		
		$msj_error_fecha = guarani_mensajes::get('800_control_fechas', array('Readmisión', 'menor', 'de pérdida de regularidad'));
		$msj_error_fecha_vto = guarani_mensajes::get('800_control_fechas', array('Vencimiento readmisión', 'menor', 'Readmisión'));
		
		if ($this->controlador()->entidad_es_nueva()) {
			echo "
			
			{$this->objeto_js}.hay_cambios = function(ef_actual) { 
				return true;
			}
			
			";
		}
		
		echo "
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo__procesar = function(es_inicial)
		{
			var tipo = this.ef('tipo').get_estado();
			
			if (tipo == '$reinc_por_res') {
				this.ef('contabiliza').activar();
				this.ef('fecha_vencimiento').activar();
				this.ef('fecha_vencimiento').set_obligatorio(true);
			} else {
				this.ef('contabiliza').resetear_estado();
				this.ef('contabiliza').desactivar();
				this.ef('fecha_vencimiento').resetear_estado();
				this.ef('fecha_vencimiento').desactivar();
				this.ef('fecha_vencimiento').set_obligatorio(false);
			}
		}

		{$this->objeto_js}.evt__anio_academico__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('anio_academico').tiene_estado()) {
				var anio = this.ef('anio_academico').get_estado();
				var fecha_vencimiento = '31/03/' + (parseInt(anio) + 1);
				this.ef('fecha_vencimiento').cambiar_valor(fecha_vencimiento);
			}
		}

		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha__validar = function()
		{
			var ok = true;
			
			if (this.ef('fecha').tiene_estado()) {
				var fecha = this.ef('fecha').fecha();
				var fecha_per_reg = new $fecha_perd_reg_js;
				if (fecha < fecha_per_reg) {
					this.ef('fecha').set_error('$msj_error_fecha');
					ok = false;		
				}
			}
			
			return ok;
		}
		
		{$this->objeto_js}.evt__fecha_vencimiento__validar = function()
		{
			var ok = true;
			
			if (this.ef('fecha').tiene_estado() && this.ef('fecha_vencimiento').tiene_estado()) {
				var fecha = this.ef('fecha').fecha();
				var fecha_vto = this.ef('fecha_vencimiento').fecha();
				
				if (fecha > fecha_vto) {
					this.ef('fecha_vencimiento').set_error('$msj_error_fecha_vto');
					ok = false;		
				}
			}
			
			return ok;
		}
		
		";
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Se redefine la generación de la etiqueta y el componente HTML para el ef 
	 * 'causa_nombre', para mostrar la etiqueta arriba del elemento
	 * 
	 * @param string $ef Identificador del ef
	 * @param string $ancho_etiqueta Ancho de la etiqueta del ef. Si no se setea, usa la definida en el editor.
	 * Recordar incluír las medidas (px, %, etc.). 
	 */
	protected function generar_html_ef_causa($ef, $ancho_etiqueta=null)
	{
		echo $this->get_html_ef_causa($ef, '0px');
	}

	/**
	 * Retorna la etiqueta y el componente HTML para el ef 'causa_nombre' (en dos líneas)
	 * 
	 * @param string $ef Identificador del ef
	 * @param string $ancho_etiqueta Ancho de la etiqueta del ef. Si no se setea, usa la definida en el editor.
	 * Recordar incluír las medidas (px, %, etc.). 
	 */	
	protected function get_html_ef_causa($ef, $ancho_etiqueta=null, $con_etiqueta=true)
	{
		$salida = '';
		if (! in_array($ef, $this->_lista_ef_post)) {
			//Si el ef no se encuentra en la lista posibles, es probable que se alla quitado con una restriccion o una desactivacion manual
			return;
		}
		$clase = 'ei-form-fila';
		$estilo_nodo = "";
		$id_ef = $this->_elemento_formulario[$ef]->get_id_form();
		if (! $this->_elemento_formulario[$ef]->esta_expandido()) {
			$clase .= ' ei-form-fila-oculta';
			$estilo_nodo = "display:none";
		}
		if (isset($this->_info_formulario['resaltar_efs_con_estado']) 
				&& $this->_info_formulario['resaltar_efs_con_estado'] && $this->_elemento_formulario[$ef]->seleccionado()) {
			$clase .= ' ei-form-fila-filtrada';
		}
		$es_fieldset = ($this->_elemento_formulario[$ef] instanceof toba_ef_fieldset);
		if (! $es_fieldset) {							//Si es fieldset no puedo sacar el <div> porque el navegador cierra visualmente inmediatamente el ef.
			$salida .= "<div class='$clase' style='$estilo_nodo' id='nodo_$id_ef'>\n";
		}
		if ($this->_elemento_formulario[$ef]->tiene_etiqueta() && $con_etiqueta) {
			$salida .= $this->get_etiqueta_ef($ef, $ancho_etiqueta);
			$salida .= '<br/><br/>';
			//--- El margin-left de 0 y el heigth de 1% es para evitar el 'bug de los 3px'  del IE
			$ancho = isset($ancho_etiqueta) ? $ancho_etiqueta : $this->_ancho_etiqueta;
			$salida .= "<div id='cont_$id_ef' style='margin-left: $ancho;'>\n";
			$salida .= $this->get_input_ef($ef);
			$salida .= "</div>";
			if (isset($this->_info_formulario['expandir_descripcion']) && $this->_info_formulario['expandir_descripcion']) {
				$salida .= '<span class="ei-form-fila-desc">'.$this->_elemento_formulario[$ef]->get_descripcion().'</span>';
			}

		} else {		
			$salida .= $this->get_input_ef($ef);
		}
		if (! $es_fieldset) {
			$salida .= "</div>\n";
		}
		return $salida;		
	}
}
?>