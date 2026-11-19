<?php
class pant_docentes extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_docentes')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_docentes')->generar_botones();
		$this->dep('cuadro_docentes')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_docentes')->generar_html();
	}
}
?>