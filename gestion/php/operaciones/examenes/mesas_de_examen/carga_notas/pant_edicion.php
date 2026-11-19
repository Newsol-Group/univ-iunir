<?php
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('form_autocompletar')->generar_html();
		echo '<hr />';
		$this->dep('form_ml_notas')->generar_html();
	}

}
?>