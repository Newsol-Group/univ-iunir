<?php
class pant_reporte extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		parent::generar_layout();
	}

}

?>