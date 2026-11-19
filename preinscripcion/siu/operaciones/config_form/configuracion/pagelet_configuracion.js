var dependencias;
		
function inicializar_dependencias()
{
	//---- Dependencias entre campos -------------------------------------------
	dependencias = []; 
	// SECCIÓN: Propuestas
	dependencias['insc_otra_inst'] = ['insc_otra_inst_descr'];
	// SECCIÓN: Datos principales
	dependencias['tipo_visa'] = ['vencimiento_visa'];
	dependencias['residencia'] = ['fecha_respre_otorg', 'fecha_respre_venc'];
	// SECCIÓN: Financiamiento de estudios
	dependencias['alu_cos_est_otra'] = ['alu_cos_est_espec'];
	dependencias['alu_cos_est_beca'] = ['tiene_beca_univ', 'tiene_beca_inter', 'tiene_beca_nacio', 'alu_beca_prov', 'alu_beca_muni', 'alu_beca_otra', 
										'alu_beca_tipo_ser', 'alu_beca_tipo_inv', 'alu_beca_tipo_eco'];
	dependencias['alu_beca_tipo_eco'] = ['alu_beca_eco_tran', 'alu_beca_eco_efec', 'alu_beca_eco_foto', 'alu_beca_eco_habi', 'alu_beca_eco_come'];
	// SECCIÓN: Situación laboral
	dependencias['existe_trab_alum'] = ['alu_trab_hace', 'alu_trab_ocup', 'hora_sem_trab_alum', 'rel_trab_propuesta', 'alu_trab_tarea',
										'alu_trab_desjub', 'alu_trab_fami', 'remuneracion'];
	// SECCIÓN: Situación familiar
	dependencias['padre_vive'] = ['sit_laboral_padre'];
	dependencias['sit_laboral_padre'] = ['padre_trab_hace', 'padre_trab_ocup', 'padre_trab_tarea', 'padre_trab_otro'];
	dependencias['madre_vive'] = ['sit_laboral_madre'];
	dependencias['sit_laboral_madre'] = ['madre_trab_hace', 'madre_trab_ocup', 'madre_trab_tarea', 'madre_trab_otro'];
	// SECCIÓN: Estudios
//	dependencias['orient_voc_rec'] = ['orient_voc_col_sec', 'orient_voc_esta_inst', 'orient_voc_otra_inst', 'orient_voc_consult', 'orient_voc_otros'];
	dependencias['alu_otestsup_esta'] = ['alu_otestsup_razon_abandono', 'alu_otestsup_razon_abandono_otros'];
	// SECCIÓN: Deportes
	dependencias['practica_deportes'] = ['hace_dep_gim_priv', 'hace_dep_univ', 'hace_dep_partic', 'hace_dep_otros', 
										 'prac_dep_futbol', 'prac_dep_basquet', 'prac_dep_voley', 'prac_dep_gimnasia', 
										 'prac_dep_tenis', 'prac_dep_natacion', 'prac_dep_handball', 'prac_dep_otros'];
        
        // SECCIÓN: Discapacidad
        dependencias['condicion_discapacidad'] = ['tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
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
        dependencias['otra_req_apoyo_vida_diaria'] = ['otra_apoyo_vida_diaria'];
    
	//---- Campos que dependen de una selección previa -------------------------
	dependientes = ['insc_otra_inst_descr', 'vencimiento_visa', 'fecha_respre_otorg', 'fecha_respre_venc', 'alu_cos_est_espec', 'tiene_beca_univ', 'tiene_beca_inter', 
		'tiene_beca_nacio', 'alu_beca_prov', 'alu_beca_muni', 'alu_beca_otra', 'alu_beca_tipo_ser', 'alu_beca_tipo_inv', 'alu_beca_tipo_eco', 
		'alu_beca_eco_tran', 'alu_beca_eco_efec', 'alu_beca_eco_foto', 'alu_beca_eco_habi', 'alu_beca_eco_come', 'alu_trab_hace', 'alu_trab_ocup', 
		'hora_sem_trab_alum', 'rel_trab_rel_trab_propuesta', 'alu_trab_tarea', 'alu_trab_desjub', 'alu_trab_fami', 'remuneracion', 'sit_laboral_padre', 'padre_trab_hace', 'padre_trab_ocup', 
		'padre_trab_tarea', 'padre_trab_otro', 'sit_laboral_madre', 'madre_trab_hace', 'madre_trab_ocup', 'madre_trab_tarea', 'madre_trab_otro', 
		'alu_otestsup_razon_abandono', 'alu_otestsup_razon_abandono_otros',
		'hace_dep_gim_priv', 'hace_dep_univ', 'hace_dep_partic', 'hace_dep_otros', 'prac_dep_futbol', 'prac_dep_basquet', 'prac_dep_voley', 
		'prac_dep_gimnasia', 'prac_dep_tenis', 'prac_dep_natacion', 'prac_dep_handball', 'prac_dep_otros',
                'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
                'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
                'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
                'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
                'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
                'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
                'informacion_importante'];
}

function tiene_dependencias(campo)
{
	var dep = typeof dependencias[campo] !== "undefined" && dependencias[campo].length > 0;
	return dep;
}

function es_dependiente(campo)
{
	return jQuery.inArray(campo, dependientes) >= 0; 
}

function deshabilitar_dependientes()
{
	var elem;
	for (index in dependientes) {
		elem = '#form_configuracion-' + dependientes[index] + '_obligatorio';
		$(elem).addClass('js-no_modificable');
		$(elem).attr("disabled", true);
	}
}

function procesar_dependencias(campo, activo)
{
	var elem = '#form_configuracion-' + campo + '_visible';
	
	if (activo) {
		if (!$(elem).hasClass('js-no_modificable')) {
			$(elem).removeAttr("disabled");
		}
	} else {
		if (!$(elem).hasClass('js-no_modificable')) {
			$(elem).attr("disabled", true);
		}
		$(elem).attr('checked', false);
	}
	
	dependencia_visible(elem);
	
	if (tiene_dependencias(campo)) {
		var dependientes = dependencias[campo];
		var checked = $(elem).attr("checked") === "checked";
		$.each(dependientes, function(clave, valor) {
			procesar_dependencias(valor, checked);
		});
	}
}

function dependencia_visible(elem)
{
	var campo = $(elem).data('campo');
	var tiene_dep = tiene_dependencias(campo);
	
	var obligatorio = 'form_configuracion-'+($(elem).data('obligatorio'));
	var se_imprime = 'form_configuracion-'+($(elem).data('se_imprime'));
	var genera_reporte = 'form_configuracion-'+($(elem).data('genera_reporte'));

	if ($(elem).attr("checked") === "checked") {
		if (!$('#'+obligatorio).hasClass('js-no_modificable')) {
			$('#'+obligatorio).removeAttr("disabled");
		}
		$('#'+se_imprime).removeAttr("disabled");
		$('#'+genera_reporte).removeAttr("disabled");
		
		if (tiene_dep) {
			var dependientes = dependencias[campo];
			$.each(dependientes, function(clave, valor) {
				procesar_dependencias(valor, true);
			});
		}
		
	} else {
		if (!$('#'+obligatorio).hasClass('js-no_modificable')) {
			$('#'+obligatorio).attr("disabled", true);
			$('#'+obligatorio).attr('checked', false);
		}
		$('#'+se_imprime).attr("disabled", true);
		$('#'+se_imprime).attr('checked', false);
		$('#'+genera_reporte).attr("disabled", true);
		$('#'+genera_reporte).attr('checked', false);
		
		if (tiene_dep) {
			var dependientes = dependencias[campo];
			$.each(dependientes, function(clave, valor) {
				procesar_dependencias(valor, false);
			});
		}
	}
}

kernel.renderer.registrar_pagelet('configuracion', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			inicializar_dependencias();
			deshabilitar_dependientes();
			
			$('.js-check-visible').on('click', function() {
				dependencia_visible(this);
			}); 
			
			$('.js-check-visible').each(function() {
				dependencia_visible(this);
			});
			
			$('#form_tipos_propuesta-tipo_de_propuesta').on('change', function() { 
				$('#form_tipos_propuesta').submit();
			});
		}
	};
});
