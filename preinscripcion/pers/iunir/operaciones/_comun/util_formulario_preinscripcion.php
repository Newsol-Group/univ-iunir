<?php

namespace iunir\operaciones\_comun;

use SIU\Chulupi\kernel;
use siu\modelo\util;

/**
 * Utilidades propias de la lógica particular del formulario de preinscripción
 * y sus posibilidades de configuración de visibilidad, obligatoriedad e impresión.
 * 
 * Maneja además las relaciones de campos descriptivos con ID's internos de la BD.
 * 
 */
class util_formulario_preinscripcion extends \siu\operaciones\_comun\util_formulario_preinscripcion
{

	function get_campos_no_ocultables_obligatorios()
	{
		$campos = ['pais_documento', 'pais_nacionalidad', 'tipo_documento',
			'apellido', 'nombres', 'fecha_nacimiento', 'nacionalidad',
			'nro_documento', 'sede', 'sexo', 'unidad_academica', 'fecha_respre_venc', 'e_mail', 'es_mayor_25_sin_secundario',
			'condicion_discapacidad', 'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
			'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
			'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
			'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
			'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
			'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
			'informacion_importante',
			'identidad_genero', 'identidad_genero_otro'];
		return $campos;
	}

}