<?php
class pant_asignaciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('formulario')->generar_html();
		echo '<hr />';
		$this->dep('formulario_ml_asig')->generar_html();
		echo '<hr />';
		$this->dep('filtro')->generar_html();
		echo '<hr />';
		$this->dep('disponibles')->generar_botones();		
		$this->dep('disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('disponibles')->generar_html();
	}

}

?>