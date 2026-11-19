<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class pant_activ_origen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_actividades_origen')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_activ_origen')->generar_botones();
		$this->dep('seleccion_activ_origen')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_activ_origen')->generar_html();
	}

}

?>