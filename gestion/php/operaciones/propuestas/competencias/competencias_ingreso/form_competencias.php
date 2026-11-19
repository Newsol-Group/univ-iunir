<?php 
/**
 * 
 * @package Propuestas Formativas
 * @subpackage Competencias
 */
class form_competencias extends guarani_pers_ei_formulario
{
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$campo_especifico = '1';		
		echo " 		
		//---- Procesamiento de EFs --------------------------------
		
		//Si la persona no es extranjera se oculta el campo de ingreso al pais y además varios campos del form de documentos
		
		{$this->objeto_js}.evt__tipo__procesar = function()
		{
			var tipo = this.ef('tipo').get_estado();
			var mostrar_campo_disciplinar = false;
			if (tipo == $campo_especifico) {
				mostrar_campo_disciplinar = true;
			}	
			this.ef('campo_disciplinar').mostrar(mostrar_campo_disciplinar, !mostrar_campo_disciplinar);
		}
			
		";
	}
}

?>