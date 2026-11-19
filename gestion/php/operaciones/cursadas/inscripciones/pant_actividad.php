<?php
class pant_actividad extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('filtro_actividades')->generar_html();
		$this->dep('cuadro_actividades')->generar_botones();
		$this->dep('cuadro_actividades')->evento('ver_inscripciones')->set_en_botonera(false);
		$this->dep('cuadro_actividades')->generar_html();
	}
}
?>