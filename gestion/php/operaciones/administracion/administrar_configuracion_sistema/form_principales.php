<?php
class form_principales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		
		echo '<fieldset title="Datos Principales"><legend>Datos Principales</legend>';		
			$this->generar_html_ef('nombre');
			$this->generar_html_ef('host');
			$this->generar_html_ef('activa');
		echo '</fieldset>';
		
		echo '<fieldset title="API REST"><legend>API REST</legend>';		
			$this->generar_html_ef('url');
			$this->generar_html_ef('usuario');
			$this->generar_html_ef('token');
		echo '</fieldset>';
		
		echo '<fieldset style="text-align: center;">';		
			$this->generar_boton('probar_conf');
		echo '</fieldset>';
		
		echo '<fieldset>';		
			$this->generar_html_ef('resp_acad', '200px');
		echo '</fieldset>';
		
	}

}
?>