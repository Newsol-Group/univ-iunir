<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class form_tramites_certificacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->set_ancho_etiqueta('220px');
		$this->generar_html_ef('estado');
		$this->generar_html_ef('accion');
		$this->generar_html_ef('estado_nuevo');
		$this->generar_html_ef('observaciones_cambio_estado');
		$this->generar_html_ef('formularios');
		$this->generar_html_ef('colacion');
		$this->generar_html_ef('juramento');
		$this->generar_html_ef('nro_expediente');
		$this->generar_html_ef('nro_expediente_sudocu');
		$this->generar_html_ef('descr_expediente_sudocu');
		$this->generar_html_ef('nro_expediente_externo');
		$this->generar_html_ef('error_expediente_sudocu');
		$this->generar_html_ef('fecha_aprobacion_conacad');
		$this->generar_html_ef('fecha_aprobacion_consup');
		$this->generar_html_ef('fecha_disposicion_decano');
		$this->generar_html_ef('fecha_elevacion_consup');
		$this->generar_html_ef('fecha_firma_autoridad');
		$this->generar_html_ef('fecha_ingreso_conacad');
		$this->generar_html_ef('nro_registro_diploma_fac');
		$this->generar_html_ef('folio_registro_diploma_fac');
		$this->generar_html_ef('libro_registro_diploma_fac');
		$this->generar_html_ef('orden_registro_diploma_fac');
		$this->generar_html_ef('nro_registro_diploma_uni');
		$this->generar_html_ef('nro_disposicion_decano');
		$this->generar_html_ef('resolucion_rectorado');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$campos = toba_js::arreglo(aplanar_matriz($this->controlador()->get_campos_visibles(), 'campo'));
		echo "
		var campos_visibles = $campos;

		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__accion__procesar = function(es_inicial)
		{
			// Oculto campos de expediente de SUDOCU
			this.ef('nro_expediente_sudocu').ocultar(false);
			this.ef('descr_expediente_sudocu').ocultar(false);
		
			for (var campo_visible in campos_visibles) {
				this.ef(campos_visibles[campo_visible]).ocultar(false);
			}
			if (this.ef('accion').tiene_estado()) {
				var accion = this.ef('accion').get_estado();
				this.controlador.ajax('campos_formularios', accion, this, this.respuesta_campos_formularios);
			} else {
				this.ef('formularios').set_estado('');
			}
		}

		{$this->objeto_js}.respuesta_campos_formularios = function(datos)
		{
			var campos = datos['campos'];
			for (var campo in campos) {
				this.ef(campos[campo]['campo']).mostrar(true, false);
				if (campos[campo]['obligatorio'] == '".comunes::si."') {
					this.ef(campos[campo]['campo']).set_obligatorio(true);
				}
			}
			var formularios = datos['formularios'];
			var texto = '';
			for (var formulario in formularios) {
				texto = texto + formularios[formulario]['nombre'];
				texto = texto + '\\n';
			}
			this.ef('formularios').set_estado(texto);
		}
		";
	}
}