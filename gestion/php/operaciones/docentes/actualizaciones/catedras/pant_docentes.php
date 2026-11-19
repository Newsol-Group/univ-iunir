<?php
class pant_docentes extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_ml_asociados')->generar_html();
		echo '<hr />';
		$this->dep('filtro_disp')->generar_html();
		echo '<hr />';
		$this->dep('disponibles')->generar_botones();		
		$this->dep('disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('disponibles')->generar_html();
	}
}

?>