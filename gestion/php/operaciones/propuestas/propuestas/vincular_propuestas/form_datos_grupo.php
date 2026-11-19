<?php
/**
	* 
	* @package Propuestas Formativas
	*/
class form_datos_grupo extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset><legend>Características</legend><table width=100%><tr><td>';
		$this->generar_html_ef('incluir_en_analitico');
		echo '</td><td>';
		$this->generar_html_ef('inscripcion_automatica');
		echo '</td><td>';
		$this->generar_html_ef('considerar_fecha_ingreso');
		echo '</td></tr></table>';
		echo "</fieldset>";
	}
}
?>