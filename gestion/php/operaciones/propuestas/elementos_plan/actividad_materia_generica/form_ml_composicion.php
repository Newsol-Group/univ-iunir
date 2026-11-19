<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class form_ml_composicion extends guarani_pers_ei_formulario_ml
{

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.post_eliminar_fila = function(fila)
		{
			this.controlador.submit();
		};
		
		";
	}

}

?>