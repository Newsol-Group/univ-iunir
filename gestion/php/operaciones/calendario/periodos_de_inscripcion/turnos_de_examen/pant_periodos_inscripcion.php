<?php
class pant_periodos_inscripcion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}

}
?>