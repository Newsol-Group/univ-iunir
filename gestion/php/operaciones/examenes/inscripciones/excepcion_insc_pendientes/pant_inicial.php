<?php
class pant_inicial extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('cuadro_aceptadas')->generar_html();
		if ($this->existe_dependencia('form_autocompletar')) {
			echo '<hr />';
			$this->dep('form_autocompletar')->generar_html();
		}
		if ($this->existe_dependencia('form_ml_pendientes')) {
			$this->dep('form_ml_pendientes')->generar_html();
		}
	}
}
?>