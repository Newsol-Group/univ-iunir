<?php
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('ml_asociados')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_disponibles')->generar_botones();
		$this->dep('cuadro_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_disponibles')->generar_html();
	}

}
?>