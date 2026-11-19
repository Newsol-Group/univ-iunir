<?php
namespace nsg01\modelo\datos\db;

use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo\datos\db\inscripcion_cursada as inscripcion_cursada_nsg;

class inscripcion_cursada extends inscripcion_cursada_nsg
{
	/**
	 * parametros: alumno, persona, propuesta, plan_version, ubicacion, param_cur_ingresantes_oferta_actividades, param_cur_filtro_actividades_con_comision, param_cur_cursa_en_cualquier_ubicacion, param_cur_recursar_regularizadas
	 * param_null: ubicacion
	 * no_quote: param_cur_ingresantes_oferta_actividades, param_cur_filtro_actividades_con_comision, param_cur_cursa_en_cualquier_ubicacion, param_cur_recursar_regularizadas
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 */
	function lista_materias($parametros)
	{
		$param_cur_ingresantes_oferta_actividades = $parametros['param_cur_ingresantes_oferta_actividades'];
		$param_cur_filtro_actividades_con_comision = $parametros['param_cur_filtro_actividades_con_comision'];
		$param_cur_cursa_en_cualquier_ubicacion = $parametros['param_cur_cursa_en_cualquier_ubicacion'];
		$param_cur_recursar_regularizadas = $parametros['param_cur_recursar_regularizadas'];
		$alumno    = $parametros['alumno'];
		$propuesta = $parametros['propuesta'];
		$plan      = $parametros['plan'];
		$plan_version = $parametros['plan_version'];
		$ubicacion    = $parametros['ubicacion'];
		$where = '';
		$fuera_de_termino = false;
		$interfaz = \comunes::interfaz_autogestion; // Autogestion

       		$entidad_tipo_actividad = kernel::db()->quote(\elemento::tipo_actividad);
        	$entidad_tipo_modulo = kernel::db()->quote(\elemento::tipo_modulo);
        	$entidad_subtipo_materia_generica = kernel::db()->quote(\elemento::subtipo_materia_generica);

		/**
		 * 	Si el parámetro del sistema cur_ingresantes_oferta_actividades = PRIMER_AÑO:
		 *  Sólo traer las actividades correspondan al primer año del plan de estudios.
		 */
		$filtro_solo_primer_año = '';

		if ($param_cur_ingresantes_oferta_actividades == "PRIMER_AÑO") {
			// Verifica si es ingresante en base a los periodos de inscripcion vigente a cursada. De allí toma el año académico
			$es_ingresante = catalogo::consultar('alumno', 'es_ingresante_periodos_insc_cursada', array('alumno' => $alumno));
			if ($es_ingresante) {
				$filtro_solo_primer_año = 'AND sga_elementos_plan.anio_de_cursada = 1';
			}
		}

		if ($param_cur_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		}

		$filtro_solo_con_comision_vigente = '';
		//Si el parámetro cur_filtro_actividades_con_comision = S
		//solo recuperar las actividades con comisión definida y con períodos de inscripción vigentes
		if ($param_cur_filtro_actividades_con_comision == comunes::si) {
			$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';
			$filtro_solo_con_comision_vigente = " AND sga_elementos.elemento IN (SELECT * FROM f_actividades_con_comisiones_vigentes($propuesta, $plan, $plan_version, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno)) ";
		}

		// Si el parámetro cur_recursar_regularizadas = 'S', agregar la columna 'regularizada'.
		$select_regularizada = '';
		$filtro_no_regularizadas = '';
		if ($param_cur_recursar_regularizadas == comunes::si) {
			$select_regularizada = ",	CASE
											WHEN (SELECT	COUNT(1)
												    FROM	vw_regularidades_basica
												   WHERE	vw_regularidades_basica.elemento = sga_elementos.elemento AND
															vw_regularidades_basica.alumno = $alumno AND
															vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
															vw_regularidades_basica.es_vigente = 1
												) > 0  THEN 1
											ELSE 0
										END AS regularizada";
		} else {
			// Si el parámetro cur_recursar_regularizadas = 'N', excluir actividades regularizadas vigentes.
			$filtro_no_regularizadas = " AND sga_elementos.elemento NOT IN (SELECT	vw_regularidades_basica.elemento
																			  FROM	vw_regularidades_basica
																			 WHERE	vw_regularidades_basica.alumno = $alumno AND
																					vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
																					vw_regularidades_basica.es_vigente = 1
																			)";
		}

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT	sga_elementos.elemento as id,
						sga_elementos.codigo,
						sga_elementos_plan.nombre,
						'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre as descr,
						sga_elementos_plan.anio_de_cursada as anio,
                       				sga_elementos_plan.periodo_de_cursada as periodo,
						sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre,
						sga_g3entidades_subtipos.entidad_subtipo as tipo,
						sga_g3entidades_subtipos.entidad_subtipo as entidad_subtipo,
						sga_elementos_plan.creditos,
						(SELECT sga_responsables_academicas.nombre 
						   FROM sga_elementos_ra
						   JOIN sga_responsables_academicas ON sga_elementos_ra.responsable_academica = sga_responsables_academicas.responsable_academica
						WHERE sga_elementos_ra.elemento = sga_elementos.elemento
						LIMIT 1) as responsable_academica,
						sga_elementos_atrib.insc_cursada_por_prioridad as insc_cursada_por_prioridad,
						sga_elementos_atrib.prioridad_cant_maxima as prioridad_cantidad_maxima
						$select_regularizada
						
				FROM sga_elementos_plan
				JOIN sga_elementos_revision		ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				JOIN sga_elementos				ON sga_elementos.elemento = sga_elementos_revision.elemento
				JOIN sga_g3entidades_subtipos 	ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
				LEFT JOIN sga_elementos_atrib		ON sga_elementos_atrib.elemento = sga_elementos.elemento
				
				WHERE	sga_elementos_plan.plan_version = $plan_version
                 		AND (sga_g3entidades_subtipos.entidad_tipo = $entidad_tipo_actividad OR  
                       		(sga_g3entidades_subtipos.entidad_tipo = $entidad_tipo_modulo AND sga_g3entidades_subtipos.entidad_subtipo = $entidad_subtipo_materia_generica)
                      		)

				  AND sga_g3entidades_subtipos.entidad_tipo = 2 
				  AND	sga_elementos.estado = '" . comunes::estado_activo . "'
						$filtro_solo_primer_año
					-- Se obvian las actividades aprobadas
				  AND	sga_elementos.elemento
							NOT IN (SELECT	vw_hist_academica_basica.elemento
									FROM	vw_hist_academica_basica
									WHERE	vw_hist_academica_basica.alumno = $alumno
									AND		vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
						$filtro_solo_con_comision_vigente
						$filtro_no_regularizadas
						$where
						
				 ORDER BY 5,6,2				;";

		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$nuevo[$dato['id']] = $dato;
			$nuevo[$dato['id']][catalogo::id] = catalogo::generar_id($dato['id']);
		}
		return $nuevo;
	}
		
}
