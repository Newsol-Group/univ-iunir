<?php
class pant_sel_plan_mod_version extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('ubicacion')->generar_html();
		echo '<hr />';
		$this->dep('plan_mod_version')->generar_html();
	}

}
?>