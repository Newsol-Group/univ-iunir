<?php
class pant_datos_princ extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_datos_princ()->generar_html();
		echo '<hr />';
		$this->dep('form_datos_princ')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_activ_asociadas')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_activ_disp')->generar_botones();
		$this->dep('cuadro_activ_disp')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_activ_disp')->generar_html();
	}

}
?>