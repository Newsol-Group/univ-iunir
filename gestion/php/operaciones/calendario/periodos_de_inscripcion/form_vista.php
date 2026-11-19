<?php
class form_vista extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__vista__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				this.set_evento(new evento_ei('cambiar_vista', true, '' ));
			}
		}
		";
	}

}

?>