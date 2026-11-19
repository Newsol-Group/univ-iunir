<?php
class pant_ubicaciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('filtro_ubicaciones')->generar_html();
		$this->dep('cuadro_ubicaciones')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_modalidades')->generar_html();
	}

}
?>