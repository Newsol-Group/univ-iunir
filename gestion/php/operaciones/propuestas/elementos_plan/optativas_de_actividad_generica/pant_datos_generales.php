<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class pant_datos_generales extends guarani_pers_ei_pantalla 
{
	function generar_layout()
	{
		$this->dep('form_datos_generales')->generar_html();
		echo '<hr />';
		echo '<fieldset title="Modalidades de Cursada:"><legend>Modalidades de Cursada para la Comisión:</legend>';
			$this->dep('seleccion_modalidades')->generar_html();
		echo '</fieldset>';
	}
}

?>