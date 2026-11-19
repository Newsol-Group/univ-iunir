<?php
require_once dirname(__FILE__) . '/pant_sel_multiple.php';
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class pant_alumnos_ingresantes extends pant_sel_multiple
{
	function generar_layout()
	{
		$this->dep('form_anio_academico')->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>