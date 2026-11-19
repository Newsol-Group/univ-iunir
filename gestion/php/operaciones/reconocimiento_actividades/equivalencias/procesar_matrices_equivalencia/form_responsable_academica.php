<?php
/**
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class form_responsable_academica extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__aplicar_a__procesar = function()
		{
			var aplicar_a = this.ef('aplicar_a').get_estado();
			if (aplicar_a == 'Examenes') {
				this.ef('anio_academico').mostrar();
				this.ef('turno_examen').mostrar();
				this.ef('periodo_lectivo').ocultar(true);
			} else if (aplicar_a == 'Cursadas') {
				this.ef('anio_academico').mostrar();
				this.ef('turno_examen').ocultar(true);
				this.ef('periodo_lectivo').mostrar();
			} else {
				this.ef('anio_academico').ocultar(true);
				this.ef('turno_examen').ocultar(true);
				this.ef('periodo_lectivo').ocultar(true);
			}
		}
		";
	}
}
?>