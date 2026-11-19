<?php
class pant_resumen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		echo $this->controlador()->get_datos_resumen();
	}

}
?>