<?php
class form_docentes extends guarani_pers_ei_formulario
{
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