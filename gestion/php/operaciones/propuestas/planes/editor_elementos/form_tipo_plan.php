<?php
class form_tipo_plan extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800PRO_cambiar_listado_pierde_datos');
		if ($this->ef('tipo_plan')->tiene_estado()) {
			$valor_tipo = $this->ef('tipo_plan')->get_estado();
		} else {
			$valor_tipo = apex_ef_no_seteado;
		}


		echo "
			valor_anterior_tipo = '$valor_tipo';
			
			function confirmar_cambio_tipo()
			{
				var tipo = $this->objeto_js.ef('tipo_plan').get_estado();
				var igual = valor_anterior_tipo != apex_ef_no_seteado;
				//Se emite mensaje de confirmación si se está cambiando de un Tipo de Plan a otro.
				if (valor_anterior_tipo != apex_ef_no_seteado) {
					resp = confirm('$msj_confirmacion');
					if (resp) {
						valor_anterior_tipo = tipo;
					} 
					return resp;
				} else {
					valor_anterior_tipo = tipo;
					return true;
				}
			}
			
			//---- Validación de EFs -----------------------------------
	
			/**
			 * Se redefine el evt de modificación para que al modificar el valor del combo Tipo de Plan, se pida
			 * confirmación al usuario ya que se pueden perder los datos editados hasta el momento. Se exceptúa el
			 * caso de pasar de 'Seleccione' a un valor dado, para lo cual se almacena el último valor del combo en
			 * la variable local, y se vuelve a setear el mismo en caso de que el usuario cancele la opción.
			 */
		
			{$this->objeto_js}.evt__tipo_plan__procesar = function(es_inicial)
			{
				if (!es_inicial) {
					var resp = confirmar_cambio_tipo();
					if (resp) {
						/**
						 * Se deshabilitan las validaciones de las dependencias del Ci Controlador, ante el cambio del valor 
						 * del Combo Tipo Plan, para poder hacer el submit y eliminar y agregar las dependencias necesarias, 
						 * sin controlar los campos obligatorios.
						 **/
						$this->objeto_js.controlador.set_validar_todo(false);	
						/** Cuando se valida el campo del Tipo de Plan seleccionado se dispara un evento para agregar 
						*  las dependencias correspondientes al mismo.
						*/
						tipo = this.ef('tipo_plan').get_estado();
						params = [tipo];
						this.set_evento(new evento_ei('habilitar_dependencias_cambio_tipo_plan', false, false, params), true); 
					} else {
						/** 
						 * En caso de que el usuario cancele el cambio de Tipo de Plan, se vuelve a setear
						 * el valor antiguo, previo a procesar el cambio del combo. Esto se realiza 
						 * seleccionando manualmente la opción del mismo, para evitar que se dispare nuevamente
						 * el procesar del ef como al hacer un set_estado.
						 **/
						var input = this.ef('tipo_plan').input();
						if (input.options){
							for (var i =0 ; i < input.options.length; i++) {
								if (input.options[i].value == valor_anterior_tipo) {
									input.options[i].selected = true;
									break;
								}
							}
						}
					}
				}
			} 
		";
	}

}
?>