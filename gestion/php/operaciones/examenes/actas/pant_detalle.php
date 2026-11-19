<?php
class pant_detalle extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('filtro_detalles')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_detalles')->generar_html();
	}

}

?>