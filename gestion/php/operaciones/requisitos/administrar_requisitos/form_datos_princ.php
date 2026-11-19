<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class form_datos_princ extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('nombre');
		$this->generar_html_ef('requisito_tipo');
		$this->generar_html_ef('tipo_documento_digital');
		$this->generar_html_ef('descripcion');
		$this->generar_html_ef('requisito_subordinado_de');
		$this->generar_html_ef('activo', '180px');
		$this->generar_html_ef('requisito_ingreso', '180px');
		$this->generar_html_ef('requisito_egreso', '180px');
		$this->generar_html_ef('aplica_a_becas', '180px');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$tipo_documentales = requisito::tipo_documentales;
		$usa_rdi = $this->controlador()->usa_repositorio_digital() ? 'true' : 'false';
		$si = comunes::si;
                $tipo_generico = requisito_digital::TIPO_RECURSO;

		echo "
		var tipo_original;
		var seteo_tipo_original = false;

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__requisito_tipo__procesar = function(es_inicial)
		{
                        var desc =  this.ef('descripcion');
                        var form = desc._id_form;
			var requisito_tipo = this.ef('requisito_tipo').get_estado();

                        // Si 'Tipo' es 'Documentales'
			if (requisito_tipo == $tipo_documentales) {
				this.controlador.desactivar_tab('pant_reglas');
				this.controlador.desactivar_tab('pant_acciones');
				this.controlador.desactivar_tab('pant_puntos_control');

				this.ef('requisito_subordinado_de').activar();
				this.ef('requisito_ingreso').chequear();
				this.ef('requisito_ingreso').desactivar();
				this.ef('requisito_egreso').activar();

                                // Muestro el checkbox 'Aplica a Becas'
                                this.ef('aplica_a_becas').mostrar();

                                $('#'+form).parent().parent().children('label').children('img').show();
				if ($usa_rdi) {
					this.ef('tipo_documento_digital').mostrar(true);
					this.ef('tipo_documento_digital').set_obligatorio(true);
				}
			} else {
				this.controlador.activar_tab('pant_reglas');
				this.controlador.activar_tab('pant_acciones');
				this.controlador.activar_tab('pant_puntos_control');

				this.ef('requisito_subordinado_de').resetear_estado();
				this.ef('requisito_subordinado_de').desactivar();
				this.ef('requisito_ingreso').activar();
				this.ef('requisito_egreso').chequear(false);
				this.ef('requisito_egreso').desactivar();

                                // Oculto el checkbox 'Aplica a Becas'
                                this.ef('aplica_a_becas').chequear(false);
                                this.ef('aplica_a_becas').ocultar();
                                
                                $('#'+form).parent().parent().children('label').children('img').hide();
				if ($usa_rdi) {
					this.ef('tipo_documento_digital').mostrar(false, true);
					this.ef('tipo_documento_digital').set_obligatorio(false);
				}
			}

			if (! es_inicial) {
				if (! seteo_tipo_original) {
					this.controlador.ajax('validar_cambio_tipo', [requisito_tipo], this, this.respuesta_validacion_cambio_tipo);
				}
				seteo_tipo_original = false;
	      	} else if (this.ef('requisito_tipo').tiene_estado()) {
	      		 tipo_original = requisito_tipo;
	      	}
		}
                
		{$this->objeto_js}.respuesta_validacion_cambio_tipo = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				if (confirm(respuesta['mensaje'])) {
					this.set_evento(new evento_ei('cambio_tipo_requisito'), true); 
	      		} else {
	      			if (tipo_original) {
	      				seteo_tipo_original = true;
	      				this.ef('requisito_tipo').set_estado(tipo_original);
	      			}
	      			return false;
	      		}
			}
		}
		";
	}
}