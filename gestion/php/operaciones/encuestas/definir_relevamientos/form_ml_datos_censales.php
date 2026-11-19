<?php
/**
 * 
 * @package Encuestas
 */
class form_ml_datos_censales extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		var dependientes = ['costeo_estudios_descripcion', 'beca_fuente_universidad', 'beca_fuente_nacional', 'beca_fuente_provincial', 
			'beca_fuente_municipal', 'beca_fuente_internacional', 'beca_fuente_otra', 'beca_tipo_economica', 'beca_tipo_servicios', 
			'beca_tipo_investigacion', 'beca_economica_transporte', 'beca_economica_efectivo', 'beca_economica_fotocopias', 
			'beca_economica_habitacional', 'beca_economica_comedor', 'trabajo_hace', 'trabajo_ocupacion', 'trabajo_hora_sem', 
			'trabajo_carrera', 'trabajo_tarea_descripcion', 'trabajo_familiar', 'trabajo_con_descuentos_jubilatorios', 'trabajo_recibe_pago', 
			'deportes_universidad', 'deportes_gimnasio', 'deportes_particular', 'deportes_otro_lugar', 'deportes_futbol', 'deportes_basquet', 
			'deportes_voley', 'deportes_natacion', 'deportes_tenis', 'deportes_gimnasia', 'deportes_handball', 'deportes_otros', 'nivel_idioma_otro',
			'insc_otra_institucion_desc',
			'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
			'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
			'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
			'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
			'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
			'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
			'informacion_importante'];

		// Se deshabilitan los checks 'obligatorio' para los campos que son dependientes.
		var filas = {$this->objeto_js}.filas();
		for (id_fila in filas) {
			var campo_fila = {$this->objeto_js}.ef('campo').ir_a_fila(filas[id_fila]).get_estado();
			if (jQuery.inArray(campo_fila, dependientes) >= 0) {
				{$this->objeto_js}.ef('obligatorio').ir_a_fila(id_fila).set_solo_lectura(true);
				{$this->objeto_js}.ef('obligatorio').ir_a_fila(id_fila).chequear(false);
			}
		}
		";
	}
}
?>