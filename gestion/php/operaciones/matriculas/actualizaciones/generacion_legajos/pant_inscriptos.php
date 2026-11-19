<?php
class pant_inscriptos extends guarani_ei_pantalla
{
	function generar_layout()
	{
		// Ev Info
		if ($this->controlador()->ev_info()->tiene_datos()) {
			$this->controlador()->ev_info()->generar_html();
		}
		// Hay inscriptos a los que se le generará legajo de manera automática
		if ($this->existe_dependencia('cuadro_msg_legajo_autom')) {			
			echo '<hr />';
			$this->dep('cuadro_msg_legajo_autom')->generar_html();
		}
		// Form autocompletar
		if ($this->existe_dependencia('form_autocompletar')) {
			echo '<hr />';
			$this->dep('form_autocompletar')->generar_html();
		}
		// Formulario de inscriptos
		if ($this->existe_dependencia('form_inscriptos')) {
			if (!$this->existe_dependencia('form_autocompletar')) {
				echo '<hr />';
			}
			$this->dep('form_inscriptos')->generar_html();
		}
	}

}

?>