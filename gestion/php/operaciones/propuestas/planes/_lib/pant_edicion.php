<?php
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		toba::zona()->generar_info_html();
		parent::generar_layout();
	}


}
?>