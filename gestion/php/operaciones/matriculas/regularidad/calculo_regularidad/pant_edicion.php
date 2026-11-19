<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Preinscripciones
 * 
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('propuestas_ubicacion')->generar_html();
		echo '<hr />';
		$this->dep('filtro_propuestas')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_propuestas')->generar_botones();
		$this->dep('cuadro_propuestas')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_propuestas')->generar_html();
		echo '<hr />';
	}
}
?>