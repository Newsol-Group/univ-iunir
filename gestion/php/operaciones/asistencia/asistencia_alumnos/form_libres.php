<?php
class form_libres extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__mostrar_libres__procesar = function(es_inicial)
		{			
			if (! es_inicial) {
				this.set_evento(new evento_ei('mostrar_libres', true, '' ));
			}
		}
		";
	}

}
?>