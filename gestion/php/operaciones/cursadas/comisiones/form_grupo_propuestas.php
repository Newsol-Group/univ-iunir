<?php
/**
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class form_grupo_propuestas extends guarani_pers_ei_formulario 
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__grupo_propuesta__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.submit();
			}
		}
		";
	}
}
?>