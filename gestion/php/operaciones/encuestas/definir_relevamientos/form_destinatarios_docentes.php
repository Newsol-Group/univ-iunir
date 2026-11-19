<?php
class form_destinatarios_docentes extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table width='100%'><tr><td>";
		$this->generar_html_ef('docentes');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('responsabilidades');
		echo '</td></tr></table>';
		echo "<table width='100%'><tr><td colspan=2>";
		$this->generar_html_ef('label_docentes');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('anio_academico', '110px');
		echo '</td><td>';
		$this->generar_html_ef('periodo_lectivo');
		echo '</td></tr></table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$docentes_todos = encuesta::docentes_todos;

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__docentes__procesar = function(es_inicial)
		{
			if (this.ef('docentes').get_estado() == '$docentes_todos') {
				this.ef('responsabilidades').ocultar(true);
			} else {
				this.ef('responsabilidades').mostrar();
			}
		}
		";
	}
}
?>