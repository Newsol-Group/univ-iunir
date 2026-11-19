<?php
/**
 * 
 * @package Encuestas
 */
class form_habilitacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table><tr><td>';
		$this->generar_html_ef('titulo');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('kolla_unidad_gestion');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('tipo_relevamiento');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('tipo');
		echo '</td><td>';
		$this->generar_html_ef('encuestado', '90px');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('encuesta_por_propuesta', '200px');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('tehuelche_unidad_gestion');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('becas_convocatoria');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('becas');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('descripcion');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('fecha_desde');
		echo '</td><td>';
		$this->generar_html_ef('fecha_hasta', '80px');
		echo '</td><td></table><table><tr><td>';
		$this->generar_html_ef('fecha_evaluacion_ha_desde', '210px');
		echo '</td><td>';
		$this->generar_html_ef('fecha_evaluacion_ha_hasta', '80px');
		echo '</td></tr></table>';
		echo '<fieldset><legend>Características</legend><table width=100%><tr><td>';
		$this->generar_html_ef('obligatoria', '140px');
		echo '</td><td>';
		$this->generar_html_ef('anonima', '130px');
		echo '</td><td>';
		$this->generar_html_ef('activo', '40px');
		echo '</td></tr></table>';
		echo "</fieldset>";
		echo '<table><tr><td>';
		$this->generar_html_ef('kolla_estilo_visual');
		echo '</td></tr><table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$tipo_relevamiento_becas = encuesta::tipo_relevamiento_becas;
		$tipo_relevamiento_encuestas = encuesta::tipo_relevamiento_encuestas;
		$tipo_docente = encuesta::tipo_docente;
		$tipo_docente_academica = encuesta::tipo_docente_academica;
		$tipo_generica = encuesta::tipo_generica;
		$encuestado_alumno = encuesta::encuestado_alumno;

		$mje_fechas = guarani_mensajes::get('800_control_fechas', array('desde (de validez)', 'mayor o igual', 'hasta.'));

		if (beca::tehuelche_utiliza_ug()) {
			echo "var tehuelche_utiliza_ug = true;";
		} else {
			echo "var tehuelche_utiliza_ug = false;";
		}

		if ($this->controlador()->usa_repositorio_digital()) {
			echo "var usa_repositorio_digital = true;";
		} else {
			echo "var usa_repositorio_digital = false;";
		}

		echo "
		var tipo_relevamiento_original;
		var seteo_tipo_relevamiento_original = false;
		var tipo_original;
		var seteo_tipo_original = false;
		var encuestado_original;
		var seteo_encuestado_original = false;

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__tipo_relevamiento__procesar = function(es_inicial)
		{
			var tipo_relevamiento = this.ef('tipo_relevamiento').get_estado();

			if (! es_inicial) {
				if (! seteo_tipo_relevamiento_original) {
					this.controlador.ajax('validar_cambio_tipo_relevamiento', [tipo_relevamiento], this, this.respuesta_validacion_cambio_tipo_relevamiento);
				}
				seteo_tipo_relevamiento_original = false;
			} else if (this.ef('tipo_relevamiento').tiene_estado()) {
				tipo_relevamiento_original = tipo_relevamiento;
			}

			if (tipo_relevamiento == '$tipo_relevamiento_becas') {
				if (!this.ef('tipo').tiene_estado() || this.ef('tipo').get_estado() != $tipo_generica) {
					this.ef('tipo').cambiar_valor($tipo_generica);
				}
				this.ef('tipo').desactivar();
				this.ef('encuesta_por_propuesta').ocultar(true);
				if (tehuelche_utiliza_ug) {
					this.ef('tehuelche_unidad_gestion').mostrar();
				}
				this.ef('becas_convocatoria').mostrar();
				this.ef('becas').mostrar();
				this.ef('becas').desactivar();
				this.ef('fecha_desde').desactivar();
				this.ef('fecha_hasta').desactivar();
				this.ef('fecha_evaluacion_ha_desde').mostrar();
				this.ef('fecha_evaluacion_ha_desde').desactivar();
				this.ef('fecha_evaluacion_ha_hasta').mostrar();
				this.ef('fecha_evaluacion_ha_hasta').desactivar();
				this.ef('obligatoria').resetear_estado();
				this.ef('obligatoria').desactivar();
				this.ef('anonima').resetear_estado();
				this.ef('anonima').desactivar();
				this.controlador.activar_tab('pant_datos_censales');
				if (usa_repositorio_digital) {
					this.controlador.activar_tab('pant_requisitos');
				}
			} else {
				this.ef('tipo').activar();
				if (this.ef('tipo').tiene_estado() && this.ef('tipo').get_estado() == $tipo_generica &&
					this.ef('encuestado').tiene_estado() && this.ef('encuestado').get_estado() == '$encuestado_alumno') {
					this.ef('encuesta_por_propuesta').mostrar();
				}
				this.ef('encuestado').activar();
				if (tehuelche_utiliza_ug) {
					this.ef('tehuelche_unidad_gestion').ocultar(true);
				}
				this.ef('becas_convocatoria').ocultar(true);
				this.ef('becas').ocultar(true);
				this.ef('fecha_desde').activar();
				this.ef('fecha_hasta').activar();
				this.ef('fecha_evaluacion_ha_desde').ocultar(true);
				this.ef('fecha_evaluacion_ha_hasta').ocultar(true);
				this.ef('obligatoria').activar();
				this.ef('anonima').activar();
				this.controlador.desactivar_tab('pant_datos_censales');
				this.controlador.desactivar_tab('pant_requisitos');
			}
		}

		{$this->objeto_js}.respuesta_validacion_cambio_tipo_relevamiento = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				if (confirm(respuesta['mensaje'])) {
					this.set_evento(new evento_ei('cambio_tipo_relevamiento')); 
				} else {
					if (tipo_relevamiento_original) {
						seteo_tipo_relevamiento_original = true;
						this.ef('tipo_relevamiento').set_estado(tipo_relevamiento_original);
					}
					return false;
				}
			}
		}

		{$this->objeto_js}.evt__tipo__procesar = function(es_inicial)
		{
			var tipo = this.ef('tipo').get_estado();

			if (! es_inicial) {
				if (! seteo_tipo_original) {
					this.controlador.ajax('validar_cambio_tipo', [tipo], this, this.respuesta_validacion_cambio_tipo);
				}
				seteo_tipo_original = false;
			} else if (this.ef('tipo').tiene_estado()) {
				tipo_original = tipo;
			}

			if (tipo == $tipo_generica &&
				this.ef('tipo_relevamiento').tiene_estado() && this.ef('tipo_relevamiento').get_estado() == '$tipo_relevamiento_encuestas' &&
				this.ef('encuestado').tiene_estado() && this.ef('encuestado').get_estado() == '$encuestado_alumno') {
				this.ef('encuesta_por_propuesta').mostrar();
			} else {
				this.ef('encuesta_por_propuesta').ocultar(true);
			}

			if (tipo == $tipo_docente || tipo == $tipo_docente_academica) {
				this.controlador.activar_tab('pant_docentes');
			} else {
				this.controlador.desactivar_tab('pant_docentes');
			}
		}

		{$this->objeto_js}.respuesta_validacion_cambio_tipo = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				if (confirm(respuesta['mensaje'])) {
					this.set_evento(new evento_ei('cambio_tipo')); 
				} else {
					if (tipo_original) {
						seteo_tipo_original = true;
						this.ef('tipo').set_estado(tipo_original);
					}
					return false;
				}
			}
		}

		{$this->objeto_js}.evt__encuestado__procesar = function(es_inicial)
		{
			var encuestado = this.ef('encuestado').get_estado();

			if (! es_inicial) {
				if (! seteo_encuestado_original) {
					this.controlador.ajax('validar_cambio_encuestado', [encuestado], this, this.respuesta_validacion_cambio_encuestado);
				}
				seteo_encuestado_original = false;
			} else if (this.ef('encuestado').tiene_estado()) {
				encuestado_original = encuestado;
			}

			if (this.ef('tipo_relevamiento').get_estado() == '$tipo_relevamiento_becas') {
				this.ef('encuestado').desactivar();
			}

			if (encuestado == '$encuestado_alumno' &&
				this.ef('tipo_relevamiento').tiene_estado() && this.ef('tipo_relevamiento').get_estado() == '$tipo_relevamiento_encuestas' &&
				this.ef('tipo').tiene_estado() && this.ef('tipo').get_estado() == $tipo_generica) {
				this.ef('encuesta_por_propuesta').mostrar();
			} else {
				this.ef('encuesta_por_propuesta').ocultar(true);
			}
		}

		{$this->objeto_js}.respuesta_validacion_cambio_encuestado = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				if (confirm(respuesta['mensaje'])) {
					this.set_evento(new evento_ei('cambio_encuestado')); 
				} else {
					if (encuestado_original) {
						seteo_encuestado_original = true;
						this.ef('encuestado').set_estado(encuestado_original);
					}
					return false;
				}
			}
		}

		{$this->objeto_js}.evt__becas_convocatoria__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('becas_convocatoria').tiene_estado()) {
					var becas_convocatoria = this.ef('becas_convocatoria').get_estado();
					var params = [becas_convocatoria];
					this.controlador.ajax('datos_becas_convocatoria', params, this, this.respuesta_datos_becas_convocatoria);
					this.ef('becas').mostrar();
					this.ef('becas').desactivar();
					this.ef('fecha_desde').desactivar();
					this.ef('fecha_hasta').desactivar();
					this.ef('fecha_evaluacion_ha_desde').desactivar();
					this.ef('fecha_evaluacion_ha_hasta').desactivar();
				} else {
					this.ef('becas').ocultar(true);
					this.ef('fecha_desde').activar();
					this.ef('fecha_hasta').activar();
					this.ef('fecha_evaluacion_ha_desde').activar();
					this.ef('fecha_evaluacion_ha_hasta').activar();
				}
			}
		}

		{$this->objeto_js}.respuesta_datos_becas_convocatoria = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				this.ef('becas').set_estado(respuesta['becas']);
				this.ef('descripcion').set_estado(respuesta['aviso_aspirantes']);
				this.ef('fecha_desde').set_estado(respuesta['fecha_desde_visual']);
				this.ef('fecha_hasta').set_estado(respuesta['fecha_hasta_visual']);
				this.ef('fecha_evaluacion_ha_desde').set_estado(respuesta['fecha_evaluacion_ha_desde_visual']);
				this.ef('fecha_evaluacion_ha_hasta').set_estado(respuesta['fecha_evaluacion_ha_hasta_visual']);
			}
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('fecha_desde').fecha() >= this.ef('fecha_hasta').fecha()) {
				notificacion.agregar('$mje_fechas');
				return false;
			}
			return true;
		}
		";
	}
}
?>