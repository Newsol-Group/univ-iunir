<?php
class pant_inicial extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('actividades_aprobacion')->generar_html();
		if ($this->existe_dependencia('actividades_modulos_reconocimiento')) {
			$this->dep('actividades_modulos_reconocimiento')->generar_html();
		}
		echo '<hr />';
		$this->dep('form_observaciones')->generar_html();
	}

}
?>