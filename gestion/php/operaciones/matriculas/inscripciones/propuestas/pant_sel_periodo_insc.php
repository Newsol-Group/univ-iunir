<?php
class pant_sel_periodo_insc extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('per_insc')->generar_html();
	}

}
?>