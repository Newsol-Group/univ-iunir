<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class form_opciones extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{		

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__ver__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.submit();
			}
		}
		";
	}

}

?>