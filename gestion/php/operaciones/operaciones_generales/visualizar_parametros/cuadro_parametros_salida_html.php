<?php
class cuadro_parametros_salida_html extends guarani_ei_cuadro_salida_html
{
	function generar_layout_fila($columnas, $datos, $id_fila,  $clave_fila, $evt_multiples, $objeto_js, $estilo_fila, $formateo)
	{
		if (!empty($datos[$id_fila]['estilo'])) {
			$estilo_fila .= ' ' . $datos[$id_fila]['estilo'];
		}	
		parent::generar_layout_fila($columnas, $datos, $id_fila,  $clave_fila, $evt_multiples, $objeto_js, $estilo_fila, $formateo);
	}
}
?>