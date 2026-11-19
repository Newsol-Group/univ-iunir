<?php

/**
 * Métodos relacionados al armado de información para mostrar en pantalla
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_ei_formulario extends toba_ei_formulario
{
	/**
	 *  Arma el html correspondiente a los ef de un formulario y los alinea en una tabla
	 * 
	 * @param $array = ef's a generar html
	 * @param $columnas = cantidad de columnas que tendra la tabla
	 * @return HTML generado
	 */
	function get_html_alineado($array, $columnas=1, $ancho_etiqueta= '100px')
	{
		if ($columnas == 1) {
			$html = '';
			foreach ($array as $ef) {
				$html .= $this->generar_html_ef($ef,$ancho_etiqueta);
			}
		} else {

				$html = "<table style= padding: 50px; margin:0px >";
				$esta_fila = 0;
				foreach ($array as $ef) {
					if (in_array($ef, $this->_lista_ef_post)) {
							$id_ef = $this->_elemento_formulario[$ef]->get_id_form();
							$id_ef_t = $id_ef."_t" ;
							if ($esta_fila == 0) {
								$esta_fila = $columnas;
								$html .= "<tr>";
							}
							$etiqueta = $this->ef($ef)->get_etiqueta();
							if (isset($etiqueta))
							{
								$html .= "<td align='left'>";
								$html .= "<div id='nodo_$id_ef'>\n";
								$html .= $this->get_etiqueta_ef($ef, $ancho_etiqueta);
								$html .= "</div>";
								$html .= "</td>";
							}
							$html .= "<td align='left'>";
							$html .= "<div id='nodo_$id_ef_t'>\n";
							$html .= $this->get_input_ef($ef);
							$html .= "</div>";
							$html .= "</td>";
							$esta_fila = $esta_fila - 1;
							if ($esta_fila == 0) {
								$html .= "</tr>";
							}
					}
				}
				if ($esta_fila <> 0) {
					$col_faltante = $esta_fila * 2;
					$html .= "<td colspan=$col_faltante>&nbsp;</td></tr>";
				}
				$html .= "</table>";
		}
		return $html;
	}
}

?>