<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Requisitos de Ingreso
 */
class co_requisitos_ingreso_nsg extends co_requisitos_ingreso
{
	// Newsol - Se agregó el filtro por calidad, para que muestre sólo alumnos activos
	/**
	 * Retorna los datos de los alumnos con algún requisito de ingreso a propuesta PENDIENTES, de acuerdo al filtro especificado.
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_deudores($where='')
	{
		$where_requisitos = '';
		$where_otros = '';
		
		if ($where['otros']) {
			$where_otros = " AND ".$where['otros'];
		}
		if (!empty($where['requisitos'])) {
			$where_requisitos =  " AND ".$where['requisitos'];
		}

		$sql = "SELECT        vw_personas.persona,
                            sga_propuestas_aspira.propuesta_aspira,
                            sga_propuestas_aspira.plan_version,
                            vw_personas.apellido_nombres     as persona_nombre,
                            vw_personas.apellido_elegido     as apellido_elegido,
                            vw_personas.nombres_elegido     as nombres_elegido,
                            vw_personas.tipo_nro_documento     as persona_documento,
 
                            sga_propuestas.propuesta,
                            sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
                            '(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre,
 
                            vw_periodos_insc_propuesta.anio_academico as propuesta_anio,
 
                            sga_requisitos.nombre as requisito_nombre,
			    (select a.fecha_presentacion from sga_requisitos_presentados a where a.persona = vw_personas.persona and a.requisito = sga_requisitos_aspirante.requisito) as fecha_presentacion,
			    (select a.fecha_vencimiento from sga_requisitos_presentados a where a.persona = vw_personas.persona and a.requisito = sga_requisitos_aspirante.requisito) as fecha_vencimiento,
			    sga_requisitos_aspirante.fecha_excepcion,
			    sga_requisitos_aspirante.fecha_prorroga,
                            COALESCE(sga_requisitos_aspirante.fecha_prorroga, sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos) as fecha_tope_presentacion,
                            
                            sga_requisitos.requisito_tipo,
                            sga_requisitos_tipos.nombre as requisito_tipo_descr,
                            sga_requisitos.regla,
                            vw_periodos_insc_propuesta.anio_academico,
                            sga_requisitos_aspirante.parametros	   
 
				FROM		sga_propuestas_aspira
				join		sga_propuestas		on sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
				join		vw_personas		on sga_propuestas_aspira.persona = vw_personas.persona
				join		sga_alumnos		on sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
				join		vw_perfil_de_datos_propuestas on sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta  
				join		sga_situacion_aspirante on sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp 
				join		vw_periodos_insc_propuesta on  sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
				join		sga_propuestas_ra on sga_propuestas_aspira.propuesta = sga_propuestas_ra.propuesta 
				JOIN 		sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
                            	JOIN 		sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
                            	JOIN 		sga_requisitos_tipos ON sga_requisitos_tipos.requisito_tipo = sga_requisitos.requisito_tipo
                            	LEFT JOIN 	sga_requisitos_ingreso_excep ON sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos.requisito

				WHERE		sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."' AND
						sga_alumnos.calidad = 'A' AND
							-- Existe un requisito que no esta exceptuado ni presentado
							-- (presentado sin vencimiento o con vencimiento posterior a la fecha de inicio de la inscripción a propuesta).
						NOT f_requisito_presentado(sga_propuestas_aspira.persona, sga_requisitos_aspirante.requisito, CAST(vw_periodos_insc_propuesta.fecha_inicio as DATE), sga_propuestas_aspira.propuesta_aspira, COALESCE(sga_requisitos_aspirante.fecha_prorroga, sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos))
						$where_requisitos
						$where_otros
				ORDER BY		vw_perfil_de_datos_propuestas.nombre,
							apellido,
							nombres

		";
		
		return guarani_db::consultar($sql);	
	}
}
?>