<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class form_dominio extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800UNG_confirmar_cambio_dominio');
		$dominio_institucion = unidades_gestion::dominio_institucion;
		
		echo "
		var dominio = {$this->objeto_js}.ef('dominio').get_estado();
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__dominio__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (dominio != $dominio_institucion) {
					resp = confirm('$msj_confirmacion');
				} else {
					resp = true;
				}
				if (resp) {
					this.submit();
				}
			}
		}
		";
	}

}

?>