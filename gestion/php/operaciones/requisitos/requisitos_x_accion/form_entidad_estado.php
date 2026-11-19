<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class form_entidad_estado extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$estado_cumplido = g3entidad::estado_cumplido;
		
		echo "
		
		var mensaje_duplicado = '';
		
		//---- Validacion general ----------------------------------
		
		//---- Procesamiento de EFs --------------------------------		
		
		/**
		 * El campo de selección de reglas solo debe mostrarse al usuario para su carga, si la entidad seleccionada
		 * es Elemento Módulo y el estado es Cumplido (solo en este caso debe ingresarse una regla). Esto se refleja en
		 * el procesar de ambos campos (es decir cuando se cambia el valor de uno, otro o ambos).
		 */
		 		
		{$this->objeto_js}.evt__estado__procesar = function(es_inicial)
		{
			this.ef('regla').mostrar(false);
			var estado = this.ef('estado').get_estado();
			if (this.ef('entidad').tiene_estado()) {
				var entidad = this.ef('entidad').get_estado();
				var params = [];
				if (estado == '$estado_cumplido') {
					params['entidad'] = entidad;
					this.controlador.ajax('es_entidad_tipo_modulo', params, this, this.respuesta_entidad_tipo_modulo);
				}
			}
		}
				
		{$this->objeto_js}.respuesta_entidad_tipo_modulo = function(respuesta) 
		{
			var mostrar_regla = false;
			if (respuesta) {
				mostrar_regla = true;
			} else {
				mostrar_regla = false;
			}
			this.ef('regla').mostrar(mostrar_regla, !mostrar_regla);
		}

		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__verificar_entidad_estado = function()
		{
			var params = [];
			if (this.ef('entidad').tiene_estado() && this.ef('estado').tiene_estado()) {
				params['entidad'] = this.ef('entidad').get_estado();
				params['estado'] = this.ef('estado').get_estado();
				this.controlador.ajax('validar_entidad_estado', params, this, this.respuesta_validacion_entidad_estado);				
			}
			return false;
		}
		
		{$this->objeto_js}.respuesta_validacion_entidad_estado = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				mensaje_duplicado = respuesta['mensaje'];
				this.confirmacion_edicion();
			} else {
				this.set_evento(new evento_ei('confirmar'), true);
			}
		}
		
		{$this->objeto_js}.confirmacion_edicion = function()
		{
			if (confirmar_edicion()) {
       			return false;
      		} else {
      			//La persona confirma que desea volver a la visualización para seleccionar la entidad-estado ya creada y editarla.
          		this.set_evento(new evento_ei('retornar_a_edicion')); 
      		}
		}
		
		function confirmar_edicion(proyecto, operacion, url, es_popup) 
		{
			if (! es_popup || typeof(es_popup) == 'undefined') {
				resp = confirm(mensaje_duplicado);
				if (resp) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}
		
		toba.set_callback_menu(confirmar_edicion);
		
		";
	}

}
?>