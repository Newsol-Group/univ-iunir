<?php
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dependencia('actividades_equivalencia_tramite')->generar_html();
	}

}
?>