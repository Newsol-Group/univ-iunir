<?php
class pant_inicial extends guarani_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('form_acta')->generar_html();
		$this->dep('alumnos')->generar_html();
	}

}
?>