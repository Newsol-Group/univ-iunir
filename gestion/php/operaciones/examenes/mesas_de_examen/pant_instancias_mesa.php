<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class pant_instancias_mesa extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('datos_instancias')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_instancias')->generar_botones();
		$this->dep('seleccion_instancias')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_instancias')->generar_html();
		echo '<hr />';
	}

}
?>