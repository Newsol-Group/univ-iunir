<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class pant_activ_destino extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_actividades_destino')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_activ_destino')->generar_botones();
		$this->dep('seleccion_activ_destino')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_activ_destino')->generar_html();
	}

}

?>