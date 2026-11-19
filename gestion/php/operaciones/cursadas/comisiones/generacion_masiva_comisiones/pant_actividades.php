<?php
class pant_actividades extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_destino()->generar_html();
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}

}
?>