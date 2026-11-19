<?php
class pant_observaciones extends toba_ei_pantalla
{
	function generar_layout()
	{
		// ev_info
		$this->controlador()->ev_info()->generar_html();
	}

}
?>