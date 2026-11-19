<?php
class pant_enviar_mensaje extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_enviar_mensaje()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>