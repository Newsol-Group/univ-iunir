<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class form_ml_params_req extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		
		var mensaje_duplicado = '';
		
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__verificar_requisito_params = function()
		{
			var params = [];		
			//Se obtienen los valores definidos para todos los parámetros formales y se transforman en una string separados por '|'
			var filas = this.filas();
			var cadena_params = '';
 			for (id_fila in filas) {
      			if (this.ef('parametro_valor').ir_a_fila(filas[id_fila]).tiene_estado()) {
      				var valor = this.ef('parametro_valor').ir_a_fila(filas[id_fila]).get_estado();
      				if (cadena_params != '') {
      					cadena_params = cadena_params + '|' + valor;
      				} else {
      					cadena_params = valor;
      				}
      			}
 			}
 			params['req_params'] = cadena_params;
			this.controlador.ajax('validar_requisito_params', params, this, this.respuesta_validacion_requisito_params);
			return false;
		}
		
		{$this->objeto_js}.respuesta_validacion_requisito_params = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				mensaje_duplicado = respuesta['mensaje'];
				this.confirmacion_edicion();
			} else {
				this.set_evento(new evento_ei('confirmar', true));
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