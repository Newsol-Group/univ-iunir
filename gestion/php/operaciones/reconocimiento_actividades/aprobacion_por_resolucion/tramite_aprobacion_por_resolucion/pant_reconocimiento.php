<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class pant_reconocimiento extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		if ($this->existe_dependencia('form_ml_activ_reconocidas')) {
			// Caso en que el Tipo de Reconocimiento es Actividades/Módulos.
			$this->dependencia('form_ml_activ_reconocidas')->generar_html();
			echo '<hr />';
			$this->dependencia('filtro_activ_a_reconocer')->generar_html();
			echo '<hr />';
			$this->dependencia('cuadro_activ_a_reconocer')->generar_botones();
			$this->dependencia('cuadro_activ_a_reconocer')->evento('agregar')->set_en_botonera(false);
			$this->dependencia('cuadro_activ_a_reconocer')->generar_html();
			echo '<hr />';
		} elseif ($this->existe_dependencia('form_creditos_globales')) {
			// Caso en que el Tipo de Reconocimiento es Créditos globales.
			$this->dependencia('form_creditos_globales')->generar_html();
		}
	}
}
?>