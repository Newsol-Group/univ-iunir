<?php

class guarani_ei_cuadro_salida_fop_xml extends toba_ei_cuadro_salida_xml
{
	
	function generar_layout_fila($columnas, $datos_cuadro, $id_fila, $formateo)
	{
		//---> Creo las CELDAS de una FILA <----
			foreach (array_keys($columnas) as $a) {
				$valor = "";
				if(isset($columnas[$a]["clave"])){
						if(isset($datos_cuadro[$id_fila][$columnas[$a]["clave"]])){
						$valor_real = $datos_cuadro[$id_fila][$columnas[$a]["clave"]];
					}else{
						$valor_real = '';
					}
					//Hay que formatear?
					if(isset($columnas[$a]["formateo"])){
						$funcion = "formato_" . $columnas[$a]["formateo"];
						//Formateo el valor
						$valor = $formateo->$funcion($valor_real);
					} else {
						$valor = $valor_real;
					}
				}
				$valor = guarani_texto::reemplazar_caracteres_especiales_xml_fop($valor);
				$this->_objeto_toba_salida .= '<'.$this->xml_ns.'dato clave="'.$columnas[$a]["clave"].'" valor="'.$valor.'"/>';
			}
	}
	
	protected function xml_get_titulos()
	{
		$columnas = $this->_cuadro->get_columnas();
					
		foreach(array_keys($columnas) as $id) {
			if (isset($columnas[$id]['grupo']) && $columnas[$id]['grupo'] != '') {
				$titulo = '(' . $columnas[$id]['grupo'] . ") " . $columnas[$id]['titulo'];
			} else {
				$titulo = $columnas[$id]['titulo'];
			}
			
			$this->_objeto_toba_salida .= '<'.$this->xml_ns.'col titulo="'.$titulo.'" ';
			
			$estilo = $this->xml_get_estilo($columnas[$id]['estilo']);
			if (isset($estilo)) {
				$this->_objeto_toba_salida .= ' text-align="'.$estilo.'"';
			}
			if(isset($this->xml_columna[$id]) && isset($this->xml_columna[$id]['ancho'])) {
				$this->_objeto_toba_salida .= ' width="'.$this->xml_columna[$id]['ancho'].'"';
			}
			$this->_objeto_toba_salida .= '/>';
		}
	}

}
?>