<?php
/**
 *
 * @package Administración
 * @subpackage Araucano
 */
// Se extendió para corregir la actualización que se hizo en Araucano para la versión 3.22.3 de Guaraní, que aún no salió
class co_araucano_nsg extends co_araucano
{
	/*
	  Métodos para generacion de informacion para Araucano Nominal.
	*/
	function get_nominal_alumnos($anio_academico, $responsable_academica=null)
	{
		$formato_fecha_orden  = toba::db()->quote(comunes::formato_fecha_orden);
		// Genera archivo de alumnos.
		// archivo_0
		$anio_academico = toba::db()->quote($anio_academico);
		$condicion_ra = is_null($responsable_academica) ? 'IS NULL' : '= '.toba::db()->quote($responsable_academica);
		$sql = "SELECT		mdp_personas.apellido,
							mdp_personas.nombres,
							int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento,
							int_arau_nominal_alumnos.cuil_cuit,
							mdp_personas_sexo.codigo_araucano as genero,
							to_char(mdp_personas.fecha_nacimiento, $formato_fecha_orden) as fecha_nacimiento,
							int_arau_nominal_alumnos.cue_escuela_origen,
							int_arau_nominal_alumnos.horas_trabajo,
							int_arau_nominal_alumnos.nivel_instruccion_padre,
							int_arau_nominal_alumnos.nivel_instruccion_madre,
							int_arau_nominal_alumnos.pais_nacimiento,
							COALESCE(get_pais_localidad(his_datos_personales.procedencia_localidad), 99999) as pais_procedencia,
							CASE
								WHEN (mdp_personas.nacionalidad = ".persona::nac_extranjero." OR get_pais_localidad(his_datos_personales.procedencia_localidad) <> 54) THEN 
									 COALESCE(to_char(mdp_personas.fecha_ingreso_pais, $formato_fecha_orden), '99999999')
								WHEN get_pais_localidad(his_datos_personales.procedencia_localidad) = 54 THEN COALESCE(to_char(mdp_personas.fecha_ingreso_pais, $formato_fecha_orden), '99999999')
								ELSE ''
							END as fecha_ingreso_pais,
							int_arau_nominal_alumnos.pais_titulo_anterior,
							his_datos_personales.procedencia_localidad,
							COALESCE(mdp_personas.identidad_genero,0) as identidad_genero,
							CASE WHEN mdp_personas.identidad_genero = 13 THEN mdp_personas.identidad_genero_otro ELSE '' END as identidad_genero_otro,
							-- Pueblo Originario
							mdp_personas.pueblo_originario,
							mdp_personas.pueblo_originario_otro,
							-- Discapacidad
							COALESCE(his_datos_discapacidad.condicion_discapacidad, '".comunes::no."') as condicion_discapacidad,
							COALESCE(his_datos_discapacidad.tiene_cud, '".comunes::no."') as tiene_cud,
							COALESCE(his_datos_discapacidad.disc_auditiva, '".comunes::no."') as disc_auditiva,
							COALESCE(his_datos_discapacidad.disc_visual, '".comunes::no."') as disc_visual,
							COALESCE(his_datos_discapacidad.disc_motora, '".comunes::no."') as disc_motora,
							COALESCE(his_datos_discapacidad.disc_cond_psicosocial, '".comunes::no."') as disc_cond_psicosocial,
							COALESCE(his_datos_discapacidad.disc_otra, '".comunes::no."') as disc_otra,
							COALESCE(his_datos_discapacidad.otra_descripcion, '') as disc_otra_descripcion
				FROM		int_arau_nominal_alumnos
							JOIN mdp_personas ON int_arau_nominal_alumnos.persona = mdp_personas.persona
							JOIN mdp_personas_sexo ON mdp_personas.sexo = mdp_personas_sexo.sexo
							LEFT JOIN his_datos_personales ON int_arau_nominal_alumnos.dato_censal_personal = his_datos_personales.dato_censal_per 
							LEFT JOIN his_datos_discapacidad ON int_arau_nominal_alumnos.dato_censal_discapacidad = his_datos_discapacidad.dato_censal_disc
				WHERE		int_arau_nominal_alumnos.anio_academico = $anio_academico AND
							int_arau_nominal_alumnos.responsable_academica $condicion_ra
				ORDER BY	int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento
		";
		return guarani::db()->consultar($sql);
	}

	function get_nominal_alumnos_movilidad($anio_academico, $responsable_academica=null)
	{
		$formato_fecha_orden  = toba::db()->quote(comunes::formato_fecha_orden);

		// Genera archivo de alumnos internacionales de movilidad.
		// archivo_13
		$anio_academico = toba::db()->quote($anio_academico);
		$condicion_ra = is_null($responsable_academica) ? 'IS NULL' : '= '.toba::db()->quote($responsable_academica);
		$sql = "SELECT		DISTINCT mdp_personas.apellido,
							mdp_personas.nombres,
							int_arau_nominal_movilidad.tipo_documento,
							int_arau_nominal_movilidad.nro_documento,
							mdp_personas_sexo.codigo_araucano as genero,
							to_char(mdp_personas.fecha_nacimiento, $formato_fecha_orden) as fecha_nacimiento,
							int_arau_nominal_movilidad.pais_nacimiento,
							COALESCE(get_pais_localidad(his_datos_personales.procedencia_localidad), 99999) as pais_procedencia,
							CASE
								WHEN mdp_personas.nacionalidad = ".persona::nac_extranjero." THEN COALESCE(to_char(mdp_personas.fecha_ingreso_pais, $formato_fecha_orden), '99999999')
								ELSE ''
							END as fecha_ingreso_pais,
							CASE split_part(int_arau_nominal_movilidad.titulo_institucion_habilitante, '|', 7)
								WHEN '' THEN '99999'
								ELSE split_part(int_arau_nominal_movilidad.titulo_institucion_habilitante, '|', 7)
							END as pais_titulo_anterior,
							split_part(int_arau_nominal_movilidad.titulo_institucion_habilitante, '|', 2) as institucion_origen,
							split_part(int_arau_nominal_movilidad.titulo_institucion_habilitante, '|', 1) as propuesta_origen,
							int_arau_nominal_movilidad.unidad_academica_araucano,
							int_arau_nominal_movilidad.titulo_araucano,
							int_arau_nominal_movilidad.convenio,
							int_arau_nominal_movilidad.convenio_tipo,
							1 as propuesta_movilidad,
							to_char(int_arau_nominal_movilidad.fecha_inscripcion, $formato_fecha_orden) as fecha_inscripcion,
							COALESCE(to_char(int_arau_nominal_movilidad.fecha_egreso, $formato_fecha_orden), '99999999') as fecha_egreso,
							int_arau_nominal_movilidad.area_araucano_propuesta
				FROM		int_arau_nominal_movilidad
							JOIN mdp_personas ON int_arau_nominal_movilidad.persona = mdp_personas.persona
							JOIN mdp_personas_sexo ON mdp_personas.sexo = mdp_personas_sexo.sexo
							LEFT JOIN his_datos_personales ON int_arau_nominal_movilidad.dato_censal_personal = his_datos_personales.dato_censal_per
				WHERE		int_arau_nominal_movilidad.anio_academico = $anio_academico AND
							int_arau_nominal_movilidad.responsable_academica $condicion_ra
				ORDER BY	int_arau_nominal_movilidad.unidad_academica_araucano,
							int_arau_nominal_movilidad.titulo_araucano,
							int_arau_nominal_movilidad.tipo_documento,
							int_arau_nominal_movilidad.nro_documento
		";
		return guarani::db()->consultar($sql);
	}

	function get_nominal_alumnos_oferta($anio_academico, $responsable_academica=null)
	{
		$formato_fecha_orden  = toba::db()->quote(comunes::formato_fecha_orden);
		
		// Genera archivo de oferta academica.
		// archivo_1
		$anio_academico = toba::db()->quote($anio_academico);
		$condicion_ra = is_null($responsable_academica) ? 'IS NULL' : '= '.toba::db()->quote($responsable_academica);
		$sql = "SELECT		DISTINCT int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento,
							int_arau_nominal_alumnos.cuil_cuit,
							int_arau_nominal_oferta.unidad_academica_araucano,
							int_arau_nominal_oferta.titulo_araucano,
							int_arau_nominal_oferta.anio_academico_ingreso,
							to_char(int_arau_nominal_oferta.fecha_inscripcion, $formato_fecha_orden) as fecha_ingreso,
							int_arau_nominal_oferta.forma_ingreso,
							to_char(int_arau_nominal_oferta.fecha_egreso, $formato_fecha_orden),
							int_arau_nominal_oferta.dependencia_ingreso,
							int_arau_nominal_oferta.dependencia_egreso,
							CASE
								WHEN (	SELECT	COUNT(1)
										FROM	int_arau_nominal_act_x_certificado
												JOIN sga_elementos_plan ON int_arau_nominal_act_x_certificado.plan_version = sga_elementos_plan.plan_version
												JOIN sga_elementos_revision ON int_arau_nominal_act_x_certificado.elemento = sga_elementos_revision.elemento AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
												JOIN sga_elementos ON sga_elementos_revision.elemento = sga_elementos.elemento
										WHERE	int_arau_nominal_act_x_certificado.plan_version = sga_alumnos.plan_version AND
												int_arau_nominal_act_x_certificado.certificado = int_arau_nominal_oferta.certificado AND
												sga_elementos.entidad_subtipo = ".elemento::subtipo_tesis.") > 0 THEN '".comunes::si."'
								ELSE '".comunes::no."'
							END as requiere_tesis
				FROM		int_arau_nominal_oferta
							JOIN int_arau_nominal_alumnos ON int_arau_nominal_alumnos.anio_academico = $anio_academico AND int_arau_nominal_alumnos.responsable_academica $condicion_ra AND int_arau_nominal_oferta.persona = int_arau_nominal_alumnos.persona
							JOIN sga_alumnos ON int_arau_nominal_oferta.alumno = sga_alumnos.alumno
				WHERE		int_arau_nominal_oferta.anio_academico = $anio_academico AND
							int_arau_nominal_oferta.responsable_academica $condicion_ra AND
							int_arau_nominal_oferta.unidad_academica_araucano IS NOT NULL
				ORDER BY	int_arau_nominal_oferta.unidad_academica_araucano,
							int_arau_nominal_oferta.titulo_araucano,
							int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento
		";
		return guarani::db()->consultar($sql);
	}

	function get_nominal_actividades($anio_academico, $fecha_desde=null, $fecha_hasta=null, $responsable_academica=null)
	{
		// Genera el archivo de actividades (A = Aprob. x resolucion / B = equivalencias totales / E = Examenes / P = Promociones)
		// archivo_2
		$where = '';
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$formato_fecha_orden  = toba::db()->quote(comunes::formato_fecha_orden);
		if (!is_null($fecha_desde)) {
			$where = " AND int_arau_nominal_actividades.fecha >= to_date('$fecha_desde', $formato_fecha_visual)";
		}
		if (!is_null($fecha_hasta)) {
			$where .= " AND int_arau_nominal_actividades.fecha <= to_date('$fecha_hasta', $formato_fecha_visual)";
		}
		$anio_academico = toba::db()->quote($anio_academico);
		$condicion_ra = is_null($responsable_academica) ? 'IS NULL' : '= '.toba::db()->quote($responsable_academica);
		$sql = "SELECT		DISTINCT int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento,
							int_arau_nominal_alumnos.cuil_cuit,
							int_arau_nominal_oferta.unidad_academica_araucano,
							int_arau_nominal_oferta.titulo_araucano,
							to_char(int_arau_nominal_actividades.fecha, $formato_fecha_orden) as fecha,
							int_arau_nominal_actividades.resultado,
							int_arau_nominal_actividades.actividad_codigo,
							int_arau_nominal_actividades.actividad_nombre,
							int_arau_nominal_actividades.carga_horaria,
							int_arau_nominal_actividades.obligatoriedad,
							int_arau_nominal_actividades.forma_aprobacion,
							int_arau_nominal_actividades.institucion_araucano_ubicacion as dependencia_aprobacion,
							int_arau_nominal_actividades.anio_cursada,
							int_arau_nominal_actividades.nro_libro,
							int_arau_nominal_actividades.nro_acta,
							int_arau_nominal_actividades.folio_fisico,
							int_arau_nominal_actividades.expediente
				FROM		int_arau_nominal_actividades
							JOIN int_arau_nominal_alumnos ON int_arau_nominal_alumnos.anio_academico = $anio_academico AND int_arau_nominal_alumnos.responsable_academica $condicion_ra AND int_arau_nominal_actividades.persona = int_arau_nominal_alumnos.persona
							JOIN int_arau_nominal_oferta ON int_arau_nominal_oferta.anio_academico = $anio_academico AND int_arau_nominal_oferta.responsable_academica $condicion_ra AND int_arau_nominal_actividades.alumno = int_arau_nominal_oferta.alumno AND int_arau_nominal_actividades.certificado = int_arau_nominal_oferta.certificado
				WHERE		int_arau_nominal_actividades.anio_academico = $anio_academico AND
							int_arau_nominal_actividades.responsable_academica $condicion_ra AND
							int_arau_nominal_actividades.origen_codigo IN ('A','B','E','P') AND
							int_arau_nominal_oferta.unidad_academica_araucano IS NOT NULL
							$where
				ORDER BY	int_arau_nominal_oferta.unidad_academica_araucano,
							int_arau_nominal_oferta.titulo_araucano,
							int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento,
							fecha
		";
		return guarani::db()->consultar($sql);
	}

	function get_nominal_actividades_regularizadas($anio_academico, $excluir_historico=false, $responsable_academica=null)
	{
		// Genera el archivo de actividades (P=promociones (Solo cuando la comision no tiene instancia de cursada / R=cursada / C=Equivalencia de Regularidad )
		// archivo_3
		$where = '';
		if ($excluir_historico) {
			// La tabla 'int_arau_nominal_actividades' tiene datos históricos para el archivo 4, sólo se tienen en cuenta los del año académico en cuestión.
			$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
			$where = " AND int_arau_nominal_actividades.fecha >= to_date('01/04/$anio_academico', $formato_fecha_visual)";
		}
		$anio_academico = toba::db()->quote($anio_academico);
		$condicion_ra = is_null($responsable_academica) ? 'IS NULL' : '= '.toba::db()->quote($responsable_academica);
		$formato_fecha_orden = toba::db()->quote(comunes::formato_fecha_orden);
		$sql = "SELECT	DISTINCT
							int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento,
							int_arau_nominal_alumnos.cuil_cuit,
							int_arau_nominal_oferta.unidad_academica_araucano,
							int_arau_nominal_oferta.titulo_araucano,
							to_char(int_arau_nominal_actividades.fecha, $formato_fecha_orden) as fecha,
							int_arau_nominal_actividades.resultado,
							int_arau_nominal_actividades.actividad_codigo,
							int_arau_nominal_actividades.actividad_nombre,
							int_arau_nominal_actividades.carga_horaria,
							int_arau_nominal_actividades.obligatoriedad,
							int_arau_nominal_actividades.forma_aprobacion_regularizadas,
							int_arau_nominal_actividades.institucion_araucano_ubicacion as dependencia_cursada,
							int_arau_nominal_actividades.nro_libro,
							int_arau_nominal_actividades.nro_acta,
							int_arau_nominal_actividades.folio_fisico,
							int_arau_nominal_actividades.expediente
				FROM		int_arau_nominal_actividades
							JOIN int_arau_nominal_alumnos ON int_arau_nominal_alumnos.anio_academico = $anio_academico AND int_arau_nominal_alumnos.responsable_academica $condicion_ra AND int_arau_nominal_actividades.persona = int_arau_nominal_alumnos.persona
							JOIN int_arau_nominal_oferta ON int_arau_nominal_oferta.anio_academico = $anio_academico AND int_arau_nominal_oferta.responsable_academica $condicion_ra AND int_arau_nominal_actividades.alumno = int_arau_nominal_oferta.alumno AND int_arau_nominal_actividades.certificado = int_arau_nominal_oferta.certificado
				WHERE		int_arau_nominal_actividades.anio_academico = $anio_academico AND
							int_arau_nominal_actividades.responsable_academica $condicion_ra AND
							int_arau_nominal_oferta.unidad_academica_araucano IS NOT NULL AND
							int_arau_nominal_actividades.origen_codigo IN ('R', 'P', 'C') AND
							(int_arau_nominal_actividades.origen_codigo <> 'P' OR NOT EXISTS (	-- Comisión sólo promocional.
																								SELECT	1
																								FROM	int_arau_nominal_actividades as a
																								WHERE	a.anio_academico = $anio_academico AND
																										a.responsable_academica $condicion_ra AND
																										a.alumno = int_arau_nominal_actividades.alumno AND
																										a.comision = int_arau_nominal_actividades.comision AND
																										a.origen_codigo  = 'R'))
							$where
				ORDER BY	int_arau_nominal_oferta.unidad_academica_araucano,
							int_arau_nominal_oferta.titulo_araucano,
							int_arau_nominal_alumnos.tipo_documento,
							int_arau_nominal_alumnos.nro_documento,
							fecha
		";
		return guarani::db()->consultar($sql);
	}

	function obtener_alumnos_en_condiciones_de_egresar($anio_academico, $responsable_academica=null)
	{
		// Se evaluan los alumnos que no solicitaron el título, pero que podrian estar en codiciones de egresar durante el año académico a evaluar.
		$anio_academico = toba::db()->quote($anio_academico);
		if (is_null($responsable_academica)) {
			$responsable_academica = 'null';
			$condicion_ra = 'IS NULL';
		} else {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$condicion_ra = "= $responsable_academica";
		}
		$sql = "SELECT		int_arau_nominal_oferta.alumno,
							int_arau_nominal_oferta.certificado,
							sga_alumnos.plan_version
				FROM		int_arau_nominal_oferta
							JOIN sga_alumnos ON int_arau_nominal_oferta.alumno = sga_alumnos.alumno
				WHERE		int_arau_nominal_oferta.anio_academico = $anio_academico AND
							int_arau_nominal_oferta.responsable_academica $condicion_ra AND
							int_arau_nominal_oferta.fecha_egreso IS NULL AND
							NOT EXISTS	(	SELECT	1
											FROM	sga_certificados_otorg
											WHERE	sga_certificados_otorg.alumno = int_arau_nominal_oferta.alumno AND
													sga_certificados_otorg.certificado = int_arau_nominal_oferta.certificado AND
													sga_certificados_otorg.anulado = 0)
				ORDER BY	sga_alumnos.plan_version,
							int_arau_nominal_oferta.certificado
		";
		$datos = guarani::db()->consultar($sql, null, false);
		$salida = array();
		foreach ($datos as $dato) {
			if (empty($salida[$dato['plan_version']])) {
				$salida[$dato['plan_version']] = array();
			}
			if (empty($salida[$dato['plan_version']][$dato['certificado']])) {
				$salida[$dato['plan_version']][$dato['certificado']] = array();
			}
			$salida[$dato['plan_version']][$dato['certificado']][] = $dato;
		}
		return $salida;
	}

	function get_ultima_fecha_aprob_actividad($alumno, $plan_version, $certificado)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$certificado = toba::db()->quote($certificado);
		$sql = "SELECT	MAX(int_arau_nominal_ha.fecha) as fecha
				FROM	int_arau_nominal_ha
						JOIN int_arau_nominal_act_x_certificado ON (int_arau_nominal_ha.elemento = int_arau_nominal_act_x_certificado.elemento AND int_arau_nominal_act_x_certificado.plan_version = $plan_version AND int_arau_nominal_act_x_certificado.certificado = $certificado)
				WHERE	int_arau_nominal_ha.alumno = $alumno AND
						int_arau_nominal_ha.resultado = '".escalas_notas::resultado_aprobado."' AND
						int_arau_nominal_ha.origen_codigo IN ('".acta::origen_examen."', '".acta::origen_promociones."', '".acta::origen_equivalencias."', '".acta::origen_aprobacion_resolucion."')
		";	
		$rs = guarani::db()->consultar_fila($sql, null, false);
		return $rs['fecha'];
	}

	function get_nominal_estudiantes_ingreso($anio_academico, $responsable_academica=null)
	{
		/* Información nominal de estudiantes de ingreso.
		   Se informa cada alumno por cada título de nivel final que esta marcado que informa a Araucano, es de tipo "título" y que tiene definido codigo de titulo Araucano.
		*/
		$formato_fecha_orden = toba::db()->quote(comunes::formato_fecha_orden);
		$fecha_dato_censal = "01/04/$anio_academico";
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$where_ra = '';
		if (!is_null($responsable_academica)) {
			$where_ra = ' AND sga_alumnos.propuesta IN (SELECT sga_propuestas_ra.propuesta FROM sga_propuestas_ra WHERE sga_propuestas_ra.responsable_academica = '.toba::db()->quote($responsable_academica).')';
		}	
		$sql = "SELECT		DISTINCT	
							mdp_personas_documentos.tipo_documento,
							mdp_personas_documentos.nro_documento, 
							cuit.nro_documento as cuit,
							mdp_personas_sexo.codigo_araucano as genero,
							to_char(mdp_personas.fecha_nacimiento, $formato_fecha_orden) as fecha_nacimiento,
							to_char(sga_propuestas_aspira.fecha_inscripcion, $formato_fecha_orden) as fecha_alta_sistema,
							get_ra_inst_araucano_propuesta(sga_alumnos.propuesta, 'IA') as institucion_araucano,
							sga_certificados.titulo_araucano as titulo_araucano, 
							CASE   
								WHEN sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."' THEN 1 -- SI
								ELSE 2 -- NO
							END as requisitos_administrativos,
							5 as requisitos_academicos, -- SIN REQUISITO ACADÉMICO
							CASE
								WHEN sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."' THEN 1 -- SI
								WHEN sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_rechazada."' THEN 2 -- NO
								ELSE 3 -- CONDICIONAL
							END as en_condicion_de_ingreso,
							'' as fecha_finalizacion_curso_ingreso,
							get_dependencia_araucano_propuesta_ubicacion(sga_propuestas_aspira.propuesta, sga_propuestas_aspira.ubicacion) as dependencia_ingreso,
							CASE
								wHEN mdp_personas.nacionalidad = ".persona::nac_extranjero." THEN 888888888
								WHEN sga_propuestas_aspira.tipo_ingreso = ".inscripcion::tipo_ingreso_mayor_25." THEN 777777777
								ELSE COALESCE((	SELECT		sga_colegios_secundarios.cue
												FROM		mdp_datos_estudios
															JOIN sga_colegios_secundarios ON sga_colegios_secundarios.colegio = mdp_datos_estudios.colegio
												WHERE		mdp_datos_estudios.persona = mdp_personas.persona AND
															mdp_datos_estudios.nivel_estudio = ".persona::nivel_secundario."
												ORDER BY	mdp_datos_estudios.fecha_egreso DESC
												LIMIT		1), 999999999)
							END as cue_escuela_origen,
							get_horas_trabajo_araucano(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual)) as horas_trabajo,
							get_nivel_instruccion_araucano(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual), '".persona::allegado_padre."') as nivel_instruccion_padre,
							get_nivel_instruccion_araucano(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual), '".persona::allegado_madre."') as nivel_instruccion_madre,
							COALESCE(get_pais_localidad(mdp_personas.localidad_nacimiento), 99999) as pais_nacimiento,
							COALESCE(get_pais_localidad(his_datos_personales.procedencia_localidad), 99999) as pais_procedencia,
							CASE
								WHEN (mdp_personas.nacionalidad = ".persona::nac_extranjero." OR get_pais_localidad(his_datos_personales.procedencia_localidad) <> 54) THEN COALESCE(to_char(mdp_personas.fecha_ingreso_pais, $formato_fecha_orden), '99999999')
								WHEN get_pais_localidad(his_datos_personales.procedencia_localidad) = 54 THEN COALESCE(to_char(mdp_personas.fecha_ingreso_pais, $formato_fecha_orden), '99999999')
								ELSE ''
							END as fecha_ingreso_pais,
							CAST(COALESCE(get_titulo_institucion_habilitante(sga_alumnos.alumno, 7), '99999') as integer) as pais_titulo_anterior,
							his_datos_personales.procedencia_localidad,
							-- Identidad de Genero
							COALESCE(mdp_personas.identidad_genero, 0) as identidad_genero,
							CASE WHEN mdp_personas.identidad_genero = 13 THEN mdp_personas.identidad_genero_otro ELSE '' END as identidad_genero_otro,
							-- Pueblo Originario
							mdp_personas.pueblo_originario,
							mdp_personas.pueblo_originario_otro,
							-- Discapacidad
							COALESCE(his_datos_discapacidad.condicion_discapacidad, '".comunes::no."') as condicion_discapacidad,
							COALESCE(his_datos_discapacidad.tiene_cud, '".comunes::no."') as tiene_cud,
							COALESCE(his_datos_discapacidad.disc_auditiva, '".comunes::no."') as disc_auditiva,
							COALESCE(his_datos_discapacidad.disc_visual, '".comunes::no."') as disc_visual,
							COALESCE(his_datos_discapacidad.disc_motora, '".comunes::no."') as disc_motora,
							COALESCE(his_datos_discapacidad.disc_cond_psicosocial, '".comunes::no."') as disc_cond_psicosocial,
							COALESCE(his_datos_discapacidad.disc_otra, '".comunes::no."') as disc_otra,
							COALESCE(his_datos_discapacidad.otra_descripcion, '') as disc_otra_descripcion,
							sga_alumnos.alumno,
							mdp_personas.persona,
							sga_propuestas.propuesta,
							sga_alumnos.plan_version,
							sga_certificados.certificado		
				FROM		sga_propuestas_aspira
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
							JOIN sga_alumnos ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
							JOIN mdp_personas ON sga_propuestas_aspira.persona = mdp_personas.persona
							JOIN mdp_personas_sexo ON mdp_personas.sexo = mdp_personas_sexo.sexo
							JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
							JOIN sga_planes_certificados ON sga_alumnos.plan_version = sga_planes_certificados.plan_version
							JOIN sga_certificados ON sga_planes_certificados.certificado = sga_certificados.certificado
							JOIN sga_certificados_tipos ON sga_certificados.certificado_tipo = sga_certificados_tipos.certificado_tipo
							LEFT JOIN his_datos_personales ON his_datos_personales.dato_censal_per = get_dato_censal_personal(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual))
							LEFT JOIN his_datos_discapacidad ON his_datos_discapacidad.dato_censal_disc = get_dato_censal_discapacidad(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual))
							LEFT JOIN mdp_personas_documentos as cuit ON cuit.persona = mdp_personas.persona AND cuit.tipo_documento = ".persona::doc_CUIL."
				WHERE		sga_propuestas_aspira.anio_academico = ".toba::db()->quote($anio_academico)." AND
							sga_propuestas.propuesta_tipo <> ".propuesta::tipo_posgrado." AND
							sga_certificados.titulo_nivel = '".tramite_certificacion::nivel_titulo_final."' AND
							sga_certificados.titulo_araucano IS NOT NULL AND
							sga_certificados.informar_araucano = '".comunes::si."' AND 
							sga_certificados_tipos.es_titulo = '".comunes::si."'
							$where_ra
				ORDER BY	mdp_personas_documentos.tipo_documento,
							mdp_personas_documentos.nro_documento
		";
		$datos = guarani::db()->consultar($sql, null, false);
		foreach ($datos as $clave => $valor) {
			$relacion = toba::consulta_php('co_propuestas_relaciones')->get_relacion_por_anio_academico_propuesta_plan_version($anio_academico, $valor['propuesta'], $valor['plan_version']);
			if (! empty($relacion)) { // Existe propuesta vinculada, hay curso de ingreso.
				list($cumple_propuesta, $certificado, $otro_alumno) = propuesta::validar_relacion($valor['persona'], $relacion, propuesta::tipo_curso_ingreso);
				if ($cumple_propuesta && !is_null($certificado) && !is_null($otro_alumno)) {
					$datos[$clave]['requisitos_academicos'] = 1; // SI
					$datos[$clave]['fecha_finalizacion_curso_ingreso'] = toba::consulta_php('co_alumnos')->get_fecha_egreso($otro_alumno, null, $certificado, comunes::formato_fecha_orden);
				} else {
					$datos[$clave]['requisitos_academicos'] = 2; // NO
				}
			}
			unset($datos[$clave]['alumno']);
			unset($datos[$clave]['persona']);
			unset($datos[$clave]['propuesta']);
			unset($datos[$clave]['plan_version']);
			unset($datos[$clave]['certificado']);
		}
		return $datos;
	}
}
