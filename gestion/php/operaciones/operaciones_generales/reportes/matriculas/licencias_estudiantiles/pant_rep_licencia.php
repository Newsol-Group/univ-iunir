<?php
class pant_rep_licencia extends toba_ei_pantalla
{
	
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
	}
}

?>