<?php
class pant_alumnos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_alumnos')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_alumnos')->generar_botones();
		$this->dep('cuadro_alumnos')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_alumnos')->generar_html();
	}
}
?>