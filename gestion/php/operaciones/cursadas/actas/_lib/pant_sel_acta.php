<?php
class pant_sel_acta extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_comision()->generar_html();
		echo '<hr />';
		if ($this->existe_dependencia('cuadro_actas')) {
			$this->dep('cuadro_actas')->generar_html();
		}
	}

}

?>