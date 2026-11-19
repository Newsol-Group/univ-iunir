<?php
require_once dirname(__FILE__) . '/pant_sel_multiple.php';
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class pant_comisiones_mesas extends pant_sel_multiple
{
	function generar_layout()
	{
		$this->dep('form_inscripciones')->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>