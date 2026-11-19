<?php
class pant_actividades extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('composicion')->generar_html();
		echo '<hr />';
		$this->dep('filtro')->generar_html();
		echo '<hr />';
		$this->dep('materias')->generar_botones();
		if ($this->dep('materias')->existe_evento('agregar')) {
			$this->dep('materias')->evento('agregar')->set_en_botonera(false);			
		}
		$this->dep('materias')->generar_html();
	}
}

?>