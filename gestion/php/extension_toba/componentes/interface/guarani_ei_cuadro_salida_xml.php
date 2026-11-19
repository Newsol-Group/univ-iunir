<?php
class guarani_ei_cuadro_salida_xml extends toba_ei_cuadro_salida_xml
{
	function generar_layout_fila($columnas, $datos_cuadro, $id_fila, $formateo)
	{
		$cols = $datos_cuadro[$id_fila];
		foreach ($cols as $id => $val) {
			$valor = "";
			if (isset($val)) {
				// Escape a caracteres especiales xml que generan error en el parser de jasper.
				$valor = guarani_texto::reemplazar_caracteres_especiales_xml($val);
			}
			
			$this->_objeto_toba_salida .= '<' . $this->xml_ns . 'dato clave="' . $id . '" valor="' . guarani_texto::parsear_caracteres_xml($valor) . '"/>';
		}
	}

	/**
	 * Se redefine la vista xml de la cabecera de los cortes de control para eliminar caracteres inválidos en xml que
	 * jasper no interpreta correctamente y genera un fatal error.
	 * Grafica el contenido de la cabecera del corte de control
	 * @param array $nodo
	 */
	protected function xml_cabecera_cc_contenido(&$nodo)
	{
		$indice_cortes = $this->_cuadro->get_indice_cortes();
		$descripcion = $indice_cortes[$nodo['corte']]['descripcion'];
		$valor = implode(", ",$nodo['descripcion']);
		$this->_objeto_toba_salida .= '<'.$this->xml_ns.'cc>';
		if (trim($descripcion) != '') {
			$this->_objeto_toba_salida .= guarani_texto::reemplazar_caracteres_especiales_xml(strip_tags($descripcion.' '.$valor));
		} else {
			$this->_objeto_toba_salida .= guarani_texto::reemplazar_caracteres_especiales_xml(strip_tags($valor));
		}
		$this->_objeto_toba_salida .= '</'.$this->xml_ns.'cc>';
	}
	
	/**
	 * Se extiende la función para que en el caso de haber seteado un msj eof del cuadro, con etiquetas, o en negrita, no
	 * retorne dicho mensaje en caso de que el cuadro no tenga datos, al imprimir con jasper, ya que esto produce un xml
	 * que no se levanta correctamente.
	 */
	function xml_mensaje_cuadro_vacio($texto)
	{
		return "";
	}

}