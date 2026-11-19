<?php
class form_datos_docente extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__legajo__procesar = function(es_inicial)
		{
			if (! this.ef('legajo').tiene_estado()) {
				if (! this.ef('estado').tiene_estado()) {
					this.controlador.dep('form_docente_resp').ocultar();
					this.controlador.dep('form_docente_ra').ocultar();
				}
			} else {
				this.controlador.dep('form_docente_resp').mostrar();
				this.controlador.dep('form_docente_ra').mostrar();
			}
		}

		{$this->objeto_js}.evt__estado__procesar = function(es_inicial)
		{
			if (! this.ef('estado').tiene_estado()) {
				if (! this.ef('legajo').tiene_estado()) {
					this.controlador.dep('form_docente_resp').ocultar();
					this.controlador.dep('form_docente_ra').ocultar();
				}
			} else {
				this.controlador.dep('form_docente_resp').mostrar();
				this.controlador.dep('form_docente_ra').mostrar();
			}
		}
		";
	}

}
?>