<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_nivel extends guarani_pers_ei_formulario
{
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__entidad_tipo__validar = function(es_inicial)
		{
			this.submit();
			return true;
		}
		";
	}

}
?>