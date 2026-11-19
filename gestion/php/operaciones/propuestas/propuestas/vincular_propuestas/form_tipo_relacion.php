<?php
/**
 * 
 * @package Propuestas Formativas
 */
class form_tipo_relacion extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__anio_academico__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('anio_academico').tiene_estado()) {
				this.submit();
			}
		}
		";
	}
}
?>