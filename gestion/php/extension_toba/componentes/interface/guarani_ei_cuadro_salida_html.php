<?php
class guarani_ei_cuadro_salida_html extends toba_ei_cuadro_salida_html
{
	/**
	 * Retorna el texto que sumariza la cantidad de filas de un nivel de corte
	 * @param integer $profundidad Nivel de profundidad actual
	 * @return string
	 */
	protected function etiqueta_cantidad_filas_cuadro(&$nodo)
	{
		$etiqueta = $this->_cuadro->get_etiqueta_cantidad_filas_profundidad($nodo['profundidad']);
		if (! empty($etiqueta)) {
			$valor = implode(', ', $nodo['descripcion']);
			$etiqueta .= " $valor: ";
		} else {
			$etiqueta = 'Cantidad de filas: ';
		}
		return $etiqueta;
	}

	/**
	 * Genera el PIE del corte de control
	 * Estaria bueno que esto consuma primitivas para:
	 * 	- no pisarse con el contenido anidado.
	 * 	- reutilizar en la regeneracion completa.
	 * @ignore
	 */
	function html_pie_corte_control(&$nodo, $es_ultimo)
	{
		if($this->_cuadro->get_cortes_modo() == apex_cuadro_cc_tabular){				//MODO TABULAR
			$indice = $this->_cuadro->get_indice_cortes();
			$total_columnas = $this->_cuadro->get_cantidad_columnas_total();
			

			echo toba::output()->get('CuadroSalidaHtml')->getInicioPieCC($this->_cuadro->tabla_datos_es_general());

			//-----  Cabecera del PIE --------
			$this->html_cabecera_pie($indice, $nodo, $total_columnas);
			$nivel_css = $this->get_nivel_css($nodo['profundidad']);
			$css_pie = 'ei-cuadro-cc-pie-nivel-' . $nivel_css;
			//----- Totales de columna -------
			if (isset($nodo['acumulador'])) {
				$titulos = false;
				if($indice[$nodo['corte']]['pie_mostrar_titulos']) {
					$titulos = true;
				}
				$this->html_cuadro_totales_columnas($nodo['acumulador'],
													'ei-cuadro-cc-sum-nivel-'.$nivel_css,
													$titulos,
													$css_pie);
			}
			//------ Sumarizacion AD-HOC del usuario --------
			$this->html_sumarizacion_usuario($nodo, $total_columnas);
			//----- Contar Filas
			if($indice[$nodo['corte']]['pie_contar_filas']) {
				echo "<tr><td  class='$css_pie' colspan='$total_columnas'>\n";
				echo $this->etiqueta_cantidad_filas_cuadro($nodo) . guarani_arreglos::count($nodo['filas']);
				echo "</td></tr>\n";
			}
			//----- Contenido del usuario al final del PIE
			$this->html_pie_pie($nodo, $total_columnas, $es_ultimo);
			echo toba::output()->get('CuadroSalidaHtml')->getFinPieCC($this->_cuadro->tabla_datos_es_general());
		}else{																//MODO ANIDADO
			echo "</li>\n";
		}
	}
	
	/**
	 * @ignore
	 * @param <type> $nodo
	 */
	function html_sumarizacion_usuario($nodo, $total_columnas)
	{
		if(isset($nodo['sum_usuario'])) {
			$datos = array();
			$acumulador_usuario = $this->_cuadro->get_acumulador_usuario();
			$nivel_css = $this->get_nivel_css($nodo['profundidad']);
			$css = 'ei-cuadro-cc-sum-nivel-'.$nivel_css;
			$css_pie = 'ei-cuadro-cc-pie-nivel-' . $nivel_css;
			foreach($nodo['sum_usuario'] as $id => $valor) {
				$desc = $acumulador_usuario[$id]['descripcion'];
				$datos[$desc] = $valor;
			}
			echo toba::output()->get('CuadroSalidaHtml')->getSumarizacion($datos,null,null,$css,$nivel_css,  "<tr><td  class='$css_pie' colspan='$total_columnas'>\n","</td></tr>\n");
		}
	}
}
?>