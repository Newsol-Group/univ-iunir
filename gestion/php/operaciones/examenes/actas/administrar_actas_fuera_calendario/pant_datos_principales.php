<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class pant_datos_principales extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_datos_principales')->generar_html();
		echo '<hr/>';
		echo '<fieldset><legend>Instancias del Acta de Exámen:</legend>';
		$this->dep('form_ml_instancias')->generar_html();
		echo '</fieldset>';
	}

}
?>