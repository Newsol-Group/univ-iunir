<?php
class pant_seleccion_multiple extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('informacion')->generar_html();
		echo '<hr />';
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