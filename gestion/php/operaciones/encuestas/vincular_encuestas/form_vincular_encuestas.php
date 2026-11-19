<?php
/**
 * 
 * @package Encuestas
 */
class form_vincular_encuestas extends guarani_pers_ei_formulario
{

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__kolla_id_encuesta__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('kolla_id_encuesta').tiene_estado() && !this.ef('nombre').tiene_estado()) {
			 		var kolla_id_encuesta = this.ef('kolla_id_encuesta').get_estado();
					this.controlador.ajax('nombre_default', [kolla_id_encuesta], this, this.respuesta_nombre_default);
				}
			}
		}

		{$this->objeto_js}.respuesta_nombre_default = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				nombre = respuesta['nombre'];
				this.ef('nombre').set_estado(nombre);
			}
		}
		";
	}

}
?>