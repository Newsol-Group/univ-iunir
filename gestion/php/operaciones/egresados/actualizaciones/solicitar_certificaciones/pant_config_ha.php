<?php
class pant_config_ha extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		echo $this->controlador()->get_config_ha();
	}

}
?>