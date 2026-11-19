<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class form_aplicacion_req extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		
		//---- Validacion general ----------------------------------
		
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__nivel_aplicacion__procesar = function(es_inicial)
		{
			//Cuando el usuario modifica el ef que determina el nivel de aplicación se dispara el evento que almacenará
			//los valores correspondientes para reflejar el nuevo nivel.
			if (!es_inicial) {
				this.set_evento(new evento_ei('modificacion', true, true));
			}
		}
		
		";
	}
	
}
?>