<?php
class guarani_ei_cuadro_salida_excel extends toba_ei_cuadro_salida_excel
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
	 * Genera el pie del corte de control
	 * @param array $nodo
	 * @param boolean $es_ultimo
	 */
	function excel_pie_corte_control( &$nodo, $es_ultimo )
	{
		$span = $this->_cuadro->get_cantidad_columnas();
		$indice_cortes = $this->_cuadro->get_indice_cortes();
		$acumulador = $this->_cuadro->get_acumulador_usuario();

		//-----  Cabecera del PIE --------
		$this->excel_cabecera_pie($indice_cortes, $nodo, $span);

		//----- Totales de columna -------
		if (isset($nodo['acumulador'])) {
			$titulos = false;
			if($indice_cortes[$nodo['corte']]['pie_mostrar_titulos']){
				$titulos = true;
			}
			$this->excel_cuadro_totales_columnas($nodo,
												$nodo['profundidad'],
												$titulos,
												false);
		}
		//------ Sumarizacion AD-HOC del usuario --------
		if(isset($nodo['sum_usuario'])){
			foreach($nodo['sum_usuario'] as $id => $valor){
				$desc = $acumulador[$id]['descripcion'];
				$datos[$desc] = $valor;
			}
			$this->excel_cuadro_sumarizacion($datos,null,300,$nodo['profundidad']);
		}
		//----- Contar Filas
		if($indice_cortes[$nodo['corte']]['pie_contar_filas']) {
			$rangos = $this->excel_get_rangos($nodo);
			$etiqueta = $this->etiqueta_cantidad_filas_cuadro($nodo);
			$cursor = $this->_objeto_toba_salida->get_cursor();

			$this->_objeto_toba_salida->texto($etiqueta, $this->_excel_contar_filas_op, $span-1);
			$cursor[0] = $cursor[0] + ($span-1);
   			$letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cursor[0]);
			$formula = '=ROWS'.implode(' + ROWS', $rangos);
			$this->_objeto_toba_salida->texto($formula, $this->_excel_contar_filas_op, 1, null, $cursor);
		}
		//----- Contenido del usuario al final del PIE
		$this->excel_pie_pie($nodo, $es_ultimo);
	}
}
?>