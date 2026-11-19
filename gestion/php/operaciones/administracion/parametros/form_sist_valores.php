<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Parámetros
 * 
 */
class form_sist_valores extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__nivel__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var estado = this.ef('nivel').get_estado();
				if (estado != 'nopar') {
					// Si cambia el valor del nivel para el cual se define el valor de parámetro:
					//   1. Reseteo la selección actual (vía ajax)
					//   2. Cargo el filtro y cuadro correspondiente a la nueva selección.
					var parametros = [];
					this.controlador.ajax('unset_seleccion', parametros, this, this.atender_respuesta);
					return false;
				}
			}
		}
		
		{$this->objeto_js}.atender_respuesta = function(datos)
		{
			// Submit para refrescar la pantalla.
			this.submit();
		}
		
		";
	}

}
?>