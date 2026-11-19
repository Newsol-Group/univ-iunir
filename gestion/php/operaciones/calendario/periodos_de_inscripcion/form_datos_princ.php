<?php
class form_datos_princ extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__periodo_insc_tipo__procesar = function(es_inicial)
		{
			if (this.ef('periodo_insc_tipo').get_estado() == " . periodo_de_inscripcion::tipo_inscripcion . " 
			|| this.ef('periodo_insc_tipo').get_estado() == " . periodo_de_inscripcion::tipo_todos . ") {
				this.ef('fecha_cierre_requisitos').mostrar();
				this.ef('fecha_ingreso_alumnos').mostrar();
			} else {
				this.ef('fecha_cierre_requisitos').resetear_estado();
				this.ef('fecha_cierre_requisitos').ocultar();
				
				this.ef('fecha_ingreso_alumnos').resetear_estado();
				this.ef('fecha_ingreso_alumnos').ocultar();
			}
		}
		";
	}

}
?>