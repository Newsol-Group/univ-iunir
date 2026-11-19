<?php
/**
	*
	*
	* @package Cursadas
	* @subpackage Comisiones
	*/
class pant_datos_princ extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_datos_princ')->generar_html();
		echo '<hr />';
		echo '<fieldset title="Modalidades para el Período:"><legend>Modalidades para el Período:</legend>';
			$this->dep('seleccion_modalidades')->generar_html();
		echo '</fieldset>';
	}

}
?>