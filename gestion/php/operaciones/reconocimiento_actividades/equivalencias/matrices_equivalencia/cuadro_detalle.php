<?php
class cuadro_detalle extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_detalle_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_detalle_salida_excel');
	}
}

class cuadro_detalle_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__grupo_equivalencia(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "<strong>
					Nombre: {$descripcion['grupo_nombre']}
		";
		if (!empty($descripcion['resolucion_alta_descr'])) {
			$texto .= " - N° Resolución: {$descripcion['resolucion_alta_descr']}";
		}
		$texto .= " - Traslada Nota: {$descripcion['traslada_nota_descr']}<br>Activo: {$descripcion['activo_descr']}";
		if ($descripcion['activo'] == comunes::no) {
			$texto .= " - N° de Resolución Baja: {$descripcion['resolucion_baja_descr']} - Fecha Baja: {$descripcion['fecha_baja_format']}";
		}
		$texto .= "
				  </strong>
		";
		echo $texto;
	}
}

class cuadro_detalle_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__grupo_equivalencia(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "Nombre: {$descripcion['grupo_nombre']}";
		if (!empty($descripcion['resolucion_alta_descr'])) {
			$texto .= " - N° Resolución: {$descripcion['resolucion_alta_descr']}";
		}
		$texto .= " - Traslada Nota: {$descripcion['traslada_nota_descr']} - Activo: {$descripcion['activo_descr']}";
		if ($descripcion['activo'] == comunes::no) {
			$texto .= " - N° de Resolución Baja: {$descripcion['resolucion_baja_descr']} - Fecha Baja: {$descripcion['fecha_baja_format']}";
		}
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>