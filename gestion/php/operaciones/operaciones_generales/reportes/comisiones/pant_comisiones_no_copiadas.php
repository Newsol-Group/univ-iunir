<?php
class pant_comisiones_no_copiadas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		parent::generar_layout();
	}

}

?>