<?php
class pant_solicitar_informacion extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';      
		$this->dep('form_solicitar_informacion')->generar_html();

	
	}

}
?>