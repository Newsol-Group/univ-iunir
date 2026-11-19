<?php
class pant_dependencias extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_datos_ug')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_dependencias')->generar_html();
		echo '<hr />';
		$this->dep('filtro_dependencias')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_espacios')->generar_botones();		
		$this->dep('cuadro_espacios')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_espacios')->generar_html();
	}
}

?>