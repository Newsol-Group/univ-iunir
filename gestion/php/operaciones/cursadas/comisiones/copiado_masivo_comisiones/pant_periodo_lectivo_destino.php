<?php
class pant_periodo_lectivo_destino extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_origen()->generar_html();
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}

}
?>