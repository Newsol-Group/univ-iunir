<?php
class form_ml_config_campos extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		var dependencias = [];
		
		// SECCIÓN: Financiamiento
		dependencias['costeo_estudios_otro'] = ['costeo_estudios_descripcion'];
		dependencias['costeo_estudios_beca'] = ['beca_fuente_universidad', 'beca_fuente_nacional', 'beca_fuente_provincial', 
			'beca_fuente_municipal', 'beca_fuente_internacional', 'beca_fuente_otra', 'beca_tipo_economica', 'beca_tipo_servicios', 
			'beca_tipo_investigacion'];
		dependencias['beca_tipo_economica'] = ['beca_economica_transporte', 'beca_economica_efectivo', 'beca_economica_fotocopias', 
			'beca_economica_habitacional', 'beca_economica_comedor'];

		// SECCIÓN: Datos laborales
		dependencias['trabajo_existe'] = ['trabajo_hace', 'trabajo_ocupacion', 'trabajo_hora_sem', 'trabajo_carrera', 'trabajo_tarea_descripcion', 
			'trabajo_familiar', 'trabajo_con_descuentos_jubilatorios', 'trabajo_recibe_pago'];

		// SECCIÓN: Deportes
		dependencias['deportes'] = ['deportes_universidad', 'deportes_gimnasio', 'deportes_particular', 'deportes_otro_lugar', 
			'deportes_futbol', 'deportes_basquet', 'deportes_voley', 'deportes_natacion', 'deportes_tenis', 'deportes_gimnasia', 
			'deportes_handball', 'deportes_otros'];
									
		// SECCIÓN: Idiomas
		dependencias['idioma_otro'] = ['nivel_idioma_otro'];
									
		// SECCIÓN: Preferencias
		dependencias['insc_otra_institucion'] = ['insc_otra_institucion_desc'];
			
		// SECCIÓN: Discapacidad
		/*dependencias['condicion_discapacidad'] = ['tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
				'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
				'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
				'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
				'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
				'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
				'informacion_importante'];
				
		dependencias['disc_auditiva'] = ['aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos'];
		dependencias['disc_visual'] = ['vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos'];
		dependencias['disc_motora'] = ['mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos'];
		dependencias['disc_cond_psicosocial'] = ['psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos'];
		dependencias['disc_otra'] = ['otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria'];
		
		dependencias['aud_req_otros_apoyos'] = ['aud_otros_apoyos'];
		dependencias['vis_req_otros_apoyos'] = ['vis_otros_apoyos'];
		dependencias['mot_req_apoyo_aula'] = ['mot_apoyo_aula'];
		dependencias['mot_req_otros_apoyos'] = ['mot_otros_apoyos'];
		dependencias['psi_req_otros_apoyos'] = ['psi_otros_apoyos'];
		dependencias['otra_req_apoyo_vida_diaria'] = ['otra_apoyo_vida_diaria'];*/
			
		//----------------------------------------------------------------------
		
		var dependientes = ['costeo_estudios_descripcion', 'beca_fuente_universidad', 'beca_fuente_nacional', 'beca_fuente_provincial', 
			'beca_fuente_municipal', 'beca_fuente_internacional', 'beca_fuente_otra', 'beca_tipo_economica', 'beca_tipo_servicios', 
			'beca_tipo_investigacion', 'beca_economica_transporte', 'beca_economica_efectivo', 'beca_economica_fotocopias', 
			'beca_economica_habitacional', 'beca_economica_comedor', 'trabajo_hace', 'trabajo_ocupacion', 'trabajo_hora_sem', 
			'trabajo_carrera', 'trabajo_tarea_descripcion', 'trabajo_familiar', 'trabajo_con_descuentos_jubilatorios', 'trabajo_recibe_pago', 
			'deportes_universidad', 'deportes_gimnasio', 'deportes_particular', 'deportes_otro_lugar', 'deportes_futbol', 'deportes_basquet', 
			'deportes_voley', 'deportes_natacion', 'deportes_tenis', 'deportes_gimnasia', 'deportes_handball', 'deportes_otros', 'nivel_idioma_otro',
			'insc_otra_institucion_desc'
			];
			
		var dependientes_discapacidad = ['tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
			'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
			'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
			'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
			'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
			'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
			'informacion_importante'];
			
		// Se deshabilitan los checks 'obligatorio' para los campos que son dependientes
		var filas = {$this->objeto_js}.filas();
		for (id_fila in filas) {
			var campo_fila = {$this->objeto_js}.ef('campo').ir_a_fila(filas[id_fila]).get_estado();
			if (jQuery.inArray(campo_fila, dependientes) >= 0) {
				{$this->objeto_js}.ef('obligatorio').ir_a_fila(id_fila).set_solo_lectura(true);
				{$this->objeto_js}.ef('obligatorio').ir_a_fila(id_fila).chequear(false);
			}
		}

		// Campos que no se le pueden cambiar la visibilidad
		var deshabilitar_cambio_visible = ['condicion_discapacidad', 'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
				'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
				'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
				'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
				'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
				'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
				'informacion_importante'];
				
		//---- Funciones auxiliares --------------------------------------------

		function tiene_dependencias(campo)
		{
			var dep = typeof dependencias[campo] !== 'undefined' && dependencias[campo].length > 0;
			return dep;
		}
		
		function procesar_dependencias(campo, activo, es_inicial)
		{
			var filas = {$this->objeto_js}.filas();
			for (id_fila in filas) {
				var campo_fila = {$this->objeto_js}.ef('campo').ir_a_fila(filas[id_fila]).get_estado();
				if (jQuery.inArray(campo_fila, dependencias[campo]) >= 0) {
					if (activo) {
						{$this->objeto_js}.ef('visible').ir_a_fila(id_fila).set_solo_lectura(false);
						if (!es_inicial) {
							{$this->objeto_js}.ef('visible').ir_a_fila(id_fila).chequear(true);
						}
					} else {
						{$this->objeto_js}.ef('visible').ir_a_fila(id_fila).set_solo_lectura(true);
						if (!es_inicial) {
							{$this->objeto_js}.ef('visible').ir_a_fila(id_fila).chequear(false);
						}
					}
				}
			}
		}
		
		//---- Procesamiento de EFs --------------------------------------------

		{$this->objeto_js}.evt__visible__procesar = function(es_inicial, fila)
		{
			var campo = this.ef('campo').ir_a_fila(fila).get_estado();
			
			if (this.ef('visible').ir_a_fila(fila).tiene_estado()) {
				var visible = this.ef('visible').ir_a_fila(fila).get_estado();
				var activo = (visible == 'S');
				if (activo) {
					if (!(jQuery.inArray(campo, dependientes) >= 0)) {
						this.ef('obligatorio').ir_a_fila(fila).set_solo_lectura(false);
					}
				} else {
					this.ef('obligatorio').ir_a_fila(fila).chequear(false);
					this.ef('obligatorio').ir_a_fila(fila).set_solo_lectura(true);
				}
				
				// Si no se le puede cambiar la visibilidad deshabilito dicho checkbox
				if (jQuery.inArray(campo, deshabilitar_cambio_visible) >= 0) {
					this.ef('visible').ir_a_fila(fila).desactivar();
				}
			}
			
			if (tiene_dependencias(campo)) {
				procesar_dependencias(campo, activo, es_inicial);
			}
		}
		";
	}
}