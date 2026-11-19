<?php
class pantalla_seleccion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('asignados')->generar_html();
		echo '<hr />';
		$this->dep('filtro')->generar_html();
		echo '<hr />';
		$this->dep('disponibles')->generar_botones();
		$this->dep('disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('disponibles')->generar_html();
	}

}
?>