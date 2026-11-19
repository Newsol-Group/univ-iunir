<?php
class form_constancias extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__constancia__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.submit();
			}
		}
		";
	}

}
?>