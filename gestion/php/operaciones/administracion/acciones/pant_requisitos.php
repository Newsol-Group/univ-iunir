<?php
class pant_requisitos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_datos_acciones')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_req_asoc')->generar_html();
		echo '<hr />';
		$this->dep('filtro_requisitos')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_req_disp')->generar_botones();		
		$this->dep('cuadro_req_disp')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_req_disp')->generar_html();
	}
}

?>