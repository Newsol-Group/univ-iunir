<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 */
class form_convenio extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$hoy_js = guarani_fecha::get_hoy_js();
		$mensaje_fecha = guarani_mensajes::get('800_control_fechas', array('de inscripción', 'mayor', 'actual.'));

		echo "
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_inscripcion__validar = function()
		{
			var ok = true;
			var hoy = new $hoy_js;
			if(this.ef('fecha_inscripcion').tiene_estado()) {
				if (this.ef('fecha_inscripcion').fecha() > hoy) {
					this.ef('fecha_inscripcion').set_error('$mensaje_fecha');
					ok = false;
				}
			}
			return ok;
		}

		//---- Procesamiento de EFs ------------------------------

		{$this->objeto_js}.evt__tipo_ingreso__procesar = function(es_inicial)
		{
			if (this.ef('tipo_ingreso').tiene_estado()) {
				var params = [];
				params['tipo_ingreso'] = this.ef('tipo_ingreso').get_estado();
				this.controlador.ajax('get_requiere_documento_ingreso', params, this, this.respuesta_set_documento_ingreso);
			} else {
				this.ef('documento_ingreso').resetear_estado();
				this.ef('documento_ingreso').ocultar();
			}
		}

		{$this->objeto_js}.evt__dato_estudio_anterior__procesar = function(es_inicial)
		{
			if (this.ef('dato_estudio_anterior').tiene_estado()) {
				var params = [];
				params['dato_estudio_anterior'] = this.ef('dato_estudio_anterior').get_estado();
				this.controlador.ajax('datos_estudio_anterior', params, this, this.respuesta_datos_estudio_anterior);
			}
			this.ef('institucion_anterior').desactivar();
		}

        {$this->objeto_js}.respuesta_datos_estudio_anterior = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				this.ef('institucion_anterior').set_estado(respuesta['institucion_anterior']);
			} else {
				this.ef('institucion_anterior').set_estado('');
			}
		}

		//---- Respuestas Ajax ------------------------------

		{$this->objeto_js}.respuesta_set_documento_ingreso = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				requiere_res_exp = respuesta['requiere_res_exp'];
				if (requiere_res_exp == '".comunes::si."') {
					this.ef('documento_ingreso').mostrar();
				} else {
					this.ef('documento_ingreso').resetear_estado();
					this.ef('documento_ingreso').ocultar();
				}
			}
		}
		";
	}
}