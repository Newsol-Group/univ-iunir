<?php
class pant_resumen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_resumen()->generar_html();
		parent::generar_layout();
	}
}
?>