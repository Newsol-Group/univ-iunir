<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class form_regla extends guarani_pers_ei_formulario
{
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__regla__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.set_evento(new evento_ei('cargar_parametros'));
			}
		}
		";
	}

}
?>