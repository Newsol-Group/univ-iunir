<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class form_suplemento extends guarani_pers_ei_formulario
{
	function extender_objeto_js()
	{
		echo "
			//---- Procesamiento de EFs --------------------------------
			{$this->objeto_js}.evt__tipo_suplemento__procesar = function(es_inicial)
			{
				if (!es_inicial) {
					this.submit();
				}
			}
		";
	}
}
?>