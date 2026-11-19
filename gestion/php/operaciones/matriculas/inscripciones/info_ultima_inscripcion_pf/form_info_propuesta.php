<?php
class form_info_propuesta extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		echo "<H3>".$this->get_html_alineado(array('propuesta_nombre'),2)."</H3>";
		echo "<fieldset title='Período de Inscripción'><legend>Período de Inscripción</legend>";
		echo $this->get_html_alineado(array('periodo_nombre'),3);
		echo $this->get_html_alineado(array('anio_academico','fecha_inicio','fecha_fin'),3);
		echo '</fieldset>';
		echo "<br>";
		echo $this->generar_html_ef('nombre_resultado_asp');
	}


	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()
		{
			window.close();
		}";

	}

}