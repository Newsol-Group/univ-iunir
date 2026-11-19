<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Reportes
 * 
 */
class cuadro_tramites_certificacion extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_tramites_certificado_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_tramites_certificado_salida_excel');
	}
}

class cuadro_tramites_certificado_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__estado(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = '<strong>';
		$texto .= empty($descripcion['estado_nombre']) ? htmlspecialchars('<Sin estado>') : $descripcion['estado_nombre'];
		$texto .= '</strong>';
		echo $texto;
	}
}

class cuadro_tramites_certificado_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__estado(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = empty($descripcion['estado_nombre']) ? '<Sin estado>' : $descripcion['estado_nombre'];
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>