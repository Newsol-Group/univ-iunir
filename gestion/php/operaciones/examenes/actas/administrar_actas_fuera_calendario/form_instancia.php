<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class form_instancia extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------

		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		";
	}

}
?>