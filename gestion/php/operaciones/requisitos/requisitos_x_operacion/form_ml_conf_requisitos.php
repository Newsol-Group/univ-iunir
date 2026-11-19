<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class form_ml_conf_requisitos extends guarani_pers_ei_formulario_ml
{
	/**
	 * Se redefine el método que genera el encabezado.
	 * No se incluyen los nombres de columnas del EF fijo interfaz que se ocultan en el layout de la fila.
	 */
	function generar_formulario_encabezado()
	{
		echo "<thead id='cabecera_{$this->objeto_js}'>\n";
		echo "<tr>\n";
		$primera = true;
		foreach ($this->_lista_ef_post as $ef) {
			if ($ef == 'interfaz') {
				continue;
			}
			$id_form = $this->_elemento_formulario[$ef]->get_id_form_orig();
			$extra = '';
			if ($primera) {
				$extra = 'colspan="'.($this->_colspan + 1).'"';
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
	 * Esto se hace para poder ocultar el EF fijo interfaz que no se define como EF oculto porque se necesita acceder 
	 * su valor desde JS.
	 */
	function generar_layout_fila($clave_fila)
	{
		foreach ($this->_lista_ef_post as $ef) {
			$ocultar_campo = '';
			if ($ef == 'interfaz') { 
				$ocultar_campo = "style='display:none'";
			}
			$this->_elemento_formulario[$ef]->ir_a_fila($clave_fila);
			$id_form = $this->_elemento_formulario[$ef]->get_id_form();
			echo "<td class='{$this->estilo_celda_actual}' id='cont_$id_form' $ocultar_campo>\n";
			$this->generar_input_ef($ef);
			echo "</td>\n";
		}
	}
}
?>