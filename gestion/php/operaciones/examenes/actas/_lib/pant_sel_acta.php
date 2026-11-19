<?php
class pant_sel_acta extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		$this->dep('cuadro_actas')->generar_html();
	}

}

?>