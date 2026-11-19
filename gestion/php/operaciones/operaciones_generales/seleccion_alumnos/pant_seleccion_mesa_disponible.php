<?php
class pant_seleccion_mesa_disponible extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_mesa')->generar_html();
		echo '<hr />';
		$this->dep('mesa_disponibles')->generar_botones();
		$this->dep('mesa_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('mesa_disponibles')->generar_html();
	}

}

?>