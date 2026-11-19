<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pant_datos_generales extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('datos_generales')->generar_html();
		echo '<hr />';
		echo '<fieldset title="Modalidades de Cursada para la Comisión:"><legend>Modalidades de Cursada para la Comisión:</legend>';
		$this->dep('seleccion_modalidades')->generar_html();
		echo '</fieldset>';
		echo '<hr />';
		echo '<fieldset title="Comisión habilitada para:"><legend>Comisión habilitada para:</legend>';
		$this->dep('form_grupo_propuestas')->generar_html();
		$this->dep('seleccion_propuestas')->generar_html();
		echo '</fieldset>';
	}

}
?>