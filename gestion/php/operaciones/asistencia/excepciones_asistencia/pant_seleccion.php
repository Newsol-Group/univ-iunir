<?php
class pant_seleccion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_alumno()->generar_html();
		echo '<hr />';
		echo '&nbsp Solo se listarán comisiones en curso.';
		parent::generar_layout();
	}
}
?>