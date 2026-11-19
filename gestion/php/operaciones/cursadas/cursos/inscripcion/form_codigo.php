<?php
class form_codigo extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$codigo_validacion_cant_digitos = comision::CODIGO_INSCRIPCION_CANT_DIGITOS;

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__codigo_inscripcion__procesar = function(es_inicial)
		{
			if (es_inicial) {
            	$(this.ef('codigo_inscripcion').input()).attr('maxlength', $codigo_validacion_cant_digitos).prop('maxlength', $codigo_validacion_cant_digitos);
            	$(this.ef('codigo_inscripcion').input()).attr('size', $codigo_validacion_cant_digitos).prop('size', $codigo_validacion_cant_digitos);
            }
		}
		";
	}

}

?>