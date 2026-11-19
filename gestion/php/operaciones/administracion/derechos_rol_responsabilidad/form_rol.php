<?php
/**
 * 
 * @package Administración
 */
class form_rol extends guarani_pers_ei_formulario
{
	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__rol__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.submit();
			}
		}
		";
	}
}
?>