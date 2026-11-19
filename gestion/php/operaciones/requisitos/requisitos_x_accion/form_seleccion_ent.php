<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class form_seleccion_ent extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		
		//---- Validacion general ----------------------------------
		
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__seleccion_entidad__procesar = function(es_inicial)
		{
			//Cuando el usuario modifica el ef que determina la entidad seleccionada para visualizar todos los 
			//requisitos de la misma, se dispara el evento que almacenará el valor correspondiente para la selección.
			if (!es_inicial) {
				this.set_evento(new evento_ei('modificacion', true, true));
			}
		}
		
		";
	}
	
}
?>