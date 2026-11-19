<?php
class pant_incial extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_actividad')->generar_html();
		if ($this->existe_dependencia('formulario')) {
			$this->dep('formulario')->generar_html();
		}
		if ($this->existe_dependencia('seleccion_planes')) {
			echo '<fieldset><legend>Planes de Estudio</legend>';
			$this->dep('seleccion_planes')->generar_html();
			echo '</fieldset>';
		}
	}

}

?>