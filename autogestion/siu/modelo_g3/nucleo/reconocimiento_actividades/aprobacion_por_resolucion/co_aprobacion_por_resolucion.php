<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class co_aprobacion_por_resolucion
{

	/**
	 * Obtiene los tramites de aprobación por resolución según el filtro dado.
	 *
	 * @param str $where filtro
	 */
	function get_listado($where)
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT		sga_reconocimiento.nro_tramite,
							sga_reconocimiento.fecha,
							sga_reconocimiento.total_creditos,
							sga_reconocimiento.tipo_reconocimiento,
							sga_reconocimiento.fecha_generacion,
							sga_documentos.documento_numero,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres 		AS alumno_nombre_completo,
							vw_personas.tipo_nro_documento 	AS alumno_identificacion,
							sga_propuestas.propuesta,
							sga_propuestas.nombre			AS propuesta_nombre,
							sga_propuestas.nombre_abreviado	AS propuesta_tramite,
							sga_planes.nombre			AS plan_tramite,
							sga_reconocimiento.plan_version,
							sga_planes_versiones.version		AS version_tramite,
							sga_reconocimiento.estado,
							sga_reconocimiento_estados.nombre	AS estado_nombre,
							CASE
							  WHEN sga_reconocimiento.tipo_reconocimiento = '".aprobacion_por_resolucion::activ_modulos."' THEN '".aprobacion_por_resolucion::activ_modulos_desc."'
							  WHEN sga_reconocimiento.tipo_reconocimiento = '".aprobacion_por_resolucion::cred_globales."' THEN '".aprobacion_por_resolucion::cred_globales_desc."'
							END AS tipo_reconocimiento_descr,
							sga_reconocimiento.observaciones
							
				FROM	sga_reconocimiento
				JOIN	sga_alumnos ON sga_alumnos.alumno = sga_reconocimiento.alumno
				JOIN	vw_personas ON vw_personas.persona = sga_alumnos.persona 
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_reconocimiento.plan_version
				JOIN	sga_planes ON sga_planes.plan = sga_planes_versiones.plan
				JOIN	sga_propuestas ON sga_propuestas.propuesta = sga_planes.propuesta
				JOIN	sga_reconocimiento_estados ON sga_reconocimiento_estados.estado = sga_reconocimiento.estado
   				LEFT JOIN sga_documentos ON sga_reconocimiento.documento = sga_documentos.documento
				
				$where
				
				ORDER BY	sga_reconocimiento.nro_tramite
  				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos del trámite de aprobación por resolución dado como parámetro
	 *
	 * @param integer $nro_tramite id del trámite por resolución para el cual se desea obtener los datos
	 */
	function get_datos_tramite_aprobacion_por_resolucion($nro_tramite)
	{
		$nro_tramite = toba::db()->quote($nro_tramite);
		$filtro = "sga_reconocimiento.nro_tramite = $nro_tramite";
		$datos = $this->get_listado($filtro);
		
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	/**
	 * Retorna las actividades, módulos y genéricas que pueden reconocerce mediante un trámite de aprobación por resolución 
	 * para un alumno (de su plan version), exceptuando aquellas actividades ya aprobadas por el alumno y aquellos
	 * mòdulos/genericas ya reconocidos en otro trámite de aprobación por resolución para el alumno.
	 *
	 * @param integer	$alumno 		id del alumno al cual está asociado el trámite de aprobación por resolución.
	 * @param integer	$plan_version	id del plan versión correspondiente al trámite de aprobación por resolución.
	 * @param str		$filtro_activ	filtro sobre las actividades a retornar.
	 * @param str		$filtro_mod	filtro sobre los módulos/genericas a retornar.
	 */
	function get_elementos_disponibles_tramite_aprobacion($alumno, $plan_version, $filtro_activ='', $filtro_mod='')
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		if ($filtro_activ) {
			$filtro_activ = ' AND ' . $filtro_activ;
		}
		if ($filtro_mod) {
			$filtro_mod = ' AND ' . $filtro_mod;
		}
		
		$sql = "SELECT	vw_actividades_plan.elemento,
				vw_actividades_plan.codigo,
				vw_actividades_plan.nombre,
				'(' || COALESCE(vw_actividades_plan.codigo, 'Sin código') || ') ' || vw_actividades_plan.nombre as elemento_descr,
				vw_actividades_plan.entidad_subtipo_nombre as tipo_nombre
			FROM	vw_actividades_plan
			JOIN	sga_elementos_plan ON vw_actividades_plan.elemento_plan = sga_elementos_plan.elemento_plan
			-- Se agrega el join con sga_elementos para poder realizar el filtro por nombre unificado para
			-- actividades y modulos.
			JOIN	sga_elementos ON vw_actividades_plan.elemento = sga_elementos.elemento
			-- Se realiza el join con entidades subtipos y entidades tipos porque el filtro genérico
			-- para actividades y módulos filtra sobre el tipo de entidad.
			JOIN	sga_g3entidades_subtipos ON vw_actividades_plan.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
			JOIN	sga_g3entidades_tipos ON sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
			JOIN	sga_alumnos ON sga_alumnos.alumno = $alumno
				
			WHERE	vw_actividades_plan.plan_version = $plan_version
			  AND	sga_elementos_plan.aprobacion_x_resolucion = '" . comunes::si . "'
						
			-- Se obvian las actividades aprobadas por el alumno, ya sea por exámen, promoción, equivalencia total, 
			-- o por otro trámite de aprobación por resolución.
			AND	vw_actividades_plan.elemento
					NOT IN (SELECT	vw_hist_academica_basica.elemento
						FROM	vw_hist_academica_basica
						WHERE	vw_hist_academica_basica.alumno = $alumno
						AND	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."')
				$filtro_activ
			
				UNION ALL -- Modulos
									
				SELECT	vw_modulos_plan.elemento,
					vw_modulos_plan.codigo,
					vw_modulos_plan.nombre,
					COALESCE('(' || vw_modulos_plan.codigo || ') ','') || vw_modulos_plan.nombre as elemento_descr,
					sga_g3entidades_tipos.nombre as tipo_nombre
				FROM	vw_modulos_plan
				-- Se agrega el join con sga_elementos para poder realizar el filtro por nombre unificado para actividades y modulos.
				JOIN	sga_elementos ON vw_modulos_plan.elemento = sga_elementos.elemento
				JOIN	sga_g3entidades_subtipos ON vw_modulos_plan.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				JOIN	sga_g3entidades_tipos ON sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
				JOIN	sga_alumnos ON sga_alumnos.alumno = $alumno
				
				WHERE	vw_modulos_plan.plan_version = $plan_version
				-- Se excluye el módulo raíz.
				AND	sga_g3entidades_subtipos.entidad_subtipo <> ".g3entidad::subtipo_raiz."
				-- Se obvian los módulos que ya han sido reconocido para el alumno en otro trámite de aprobación por resolución.
				AND	vw_modulos_plan.elemento
					NOT IN (SELECT	sga_reconocimiento_act.elemento
						FROM	sga_reconocimiento
						JOIN	sga_reconocimiento_act ON sga_reconocimiento.nro_tramite = sga_reconocimiento_act.nro_tramite
						WHERE	sga_reconocimiento.alumno = $alumno
						  AND	sga_reconocimiento.estado = 'C'  -- Cerrado
						  AND	sga_reconocimiento_act.reconocimiento_total = '".comunes::si."'
						  AND	sga_reconocimiento_act.estado = 'A'
						  AND	sga_reconocimiento_act.rectificado = 'N')
				$filtro_mod
				
				UNION ALL -- Genericas. Se pone alias de la vista vw_modulos_plan por el filtro.
				
				SELECT	vw_modulos_plan.elemento,
					vw_modulos_plan.codigo,
					vw_modulos_plan.nombre,
					COALESCE('(' || vw_modulos_plan.codigo || ') ','') || vw_modulos_plan.nombre as elemento_descr,
					sga_g3entidades_subtipos.nombre as tipo_nombre
					
				FROM	vw_genericas_plan as vw_modulos_plan
				-- Se agrega el join con sga_elementos para poder realizar el filtro por nombre unificado para actividades y modulos.
				JOIN	sga_elementos ON vw_modulos_plan.elemento = sga_elementos.elemento
				JOIN	sga_g3entidades_subtipos ON vw_modulos_plan.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				JOIN	sga_g3entidades_tipos ON sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
				JOIN	sga_alumnos ON sga_alumnos.alumno = $alumno
				
				WHERE	vw_modulos_plan.plan_version = $plan_version
				-- Se obvian las genericas que ya han sido reconocido en forma total para el alumno en otro trámite de aprobación por resolución.
				AND	vw_modulos_plan.elemento
					NOT IN (SELECT	sga_reconocimiento_act.elemento
						FROM	sga_reconocimiento
						JOIN	sga_reconocimiento_act ON sga_reconocimiento.nro_tramite = sga_reconocimiento_act.nro_tramite
						WHERE	sga_reconocimiento.alumno = $alumno
						  AND	sga_reconocimiento.estado = 'C'  -- Cerrado
						  AND	sga_reconocimiento_act.reconocimiento_total = '".comunes::si."'
						  AND	sga_reconocimiento_act.estado = 'A'
						  AND	sga_reconocimiento_act.rectificado = 'N')
				$filtro_mod
				
				";
					
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna las actividades que fueron entregadas por el alumno respaldando la aprobación por
	 * resolución dada como parámetro
	 *
	 * @param integer $nro_tramite id del trámite para el cual se desea obtener sus actividades
	 */
	function get_actividades_tramite_aprobacion($nro_tramite)
	{
		$sql = "SELECT		sga_reconocimiento_doc.reconocimiento_doc,
					sga_reconocimiento_doc.nombre,
					sga_reconocimiento_doc.fecha_desde,
					sga_reconocimiento_doc.fecha_hasta,
					COALESCE(sga_reconocimiento_doc.creditos, 0) as creditos,
					sga_reconocimiento_doc.responsable,
					sga_reconocimiento_doc.descripcion,
					sga_reconocimiento_doc.carga_horaria || ' hs'	AS carga_horaria_descr,
					sga_reconocimiento_tipo_actividad.nombre	AS tipo_actividad_nombre,
					sga_instituciones.nombre_abreviado		AS institucion_nombre_abreviado,
					CASE
					WHEN sga_reconocimiento_doc.tipo_actividad IN ('".aprobacion_por_resolucion::ayudantia."', '".aprobacion_por_resolucion::tutoria."') THEN vw_personas.apellido_nombres
					ELSE sga_reconocimiento_doc.responsable
					END AS responsable_descr
			FROM	sga_reconocimiento_doc
			JOIN	sga_reconocimiento_tipo_actividad ON sga_reconocimiento_tipo_actividad.tipo_actividad	= sga_reconocimiento_doc.tipo_actividad
			LEFT JOIN	sga_docentes ON sga_docentes.docente = sga_reconocimiento_doc.docente_responsable
			LEFT JOIN	vw_personas ON vw_personas.persona = sga_docentes.persona
			LEFT JOIN	sga_instituciones ON sga_instituciones.institucion = sga_reconocimiento_doc.institucion
			WHERE	sga_reconocimiento_doc.nro_tramite	= $nro_tramite
			ORDER BY	sga_reconocimiento_doc.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna las actividades/módulos que reconoce el trámite de aprobación por resolución dado
	 * como parámetro en caso de que el tipo de reconocimiento sea AM y null en caso contrario
	 *
	 * @param integer $nro_tramite id del trámite para el cual se desea obtener las actividades
	 * o módulos que reconoce el trámite
	 */
	function get_actividades_reconocimiento_tramite_aprobacion($nro_tramite=null, $where='', $origen_ficha_alumno = false)
	{
		$filtro = '';
		if (!is_null($nro_tramite)) {
			$datos_tramite = $this->get_datos_tramite_aprobacion_por_resolucion($nro_tramite);
			if ($datos_tramite['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
				$nro_tramite = toba::db()->quote($nro_tramite);
				$filtro .= " AND sga_reconocimiento_act.nro_tramite = $nro_tramite";
			} else {
				return null;
			}
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		
		// Si se consulta desde la Ficha del Alumno "Creditos Otorgados", se recupera el nombre de la generica para la que se cumple la optativa.
		$optativa_se_cumple_para_generica = '';
		if ($origen_ficha_alumno) {
			$optativa_se_cumple_para_generica = ", CASE WHEN sga_g3entidades_tipos.entidad_tipo = '".g3entidad::tipo_actividad."' THEN get_nombre_generica_de_optativa (sga_alumnos.alumno, sga_reconocimiento_act.elemento, sga_alumnos.plan_version) ELSE '' END as optativa_se_cumple_para ";
		}	
		
		
		$sql = "SELECT		sga_reconocimiento_act.reconocimiento_act,
							sga_reconocimiento_act.nro_tramite,
							sga_reconocimiento_act.reconocimiento_total,
							COALESCE(sga_reconocimiento_act.creditos, 0) as creditos,
							sga_reconocimiento_act.nota,
							sga_reconocimiento_act.elemento,
							sga_reconocimiento.alumno,
							sga_reconocimiento.fecha,
							COALESCE('(' || sga_elementos.codigo || ') ', '') || sga_elementos.nombre as elemento_descr,
							sga_elementos.nombre AS elemento_nombre,
							sga_g3entidades_tipos.entidad_tipo,
							CASE
								WHEN sga_g3entidades_tipos.entidad_tipo = '".g3entidad::tipo_actividad."' THEN sga_g3entidades_subtipos.nombre
								WHEN sga_g3entidades_tipos.entidad_tipo = '".g3entidad::tipo_modulo."' THEN
									CASE
									  WHEN sga_g3entidades_subtipos.entidad_subtipo = ".g3entidad::subtipo_materia_generica." THEN sga_g3entidades_subtipos.nombre
									  ELSE sga_g3entidades_tipos.nombre
									 END 
							END AS tipo_elemento_nombre,
							sga_escalas_notas.nombre AS escala_nota_nombre,
							sga_escalas_notas_det.nota || ' (' || sga_escalas_notas_det.descripcion || ')' AS nota_descr,
							CASE
								WHEN sga_reconocimiento_act.reconocimiento_total = '".comunes::si."' THEN '".aprobacion_por_resolucion::rec_total_desc."'
								WHEN sga_reconocimiento_act.reconocimiento_total = '".comunes::no."' THEN '".aprobacion_por_resolucion::rec_parcial_desc."'
							END AS total_parcial_descr,
							sga_planes.nombre as plan_tramite,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_documentos.documento_numero
							$optativa_se_cumple_para_generica
							
				FROM        sga_reconocimiento_act
				JOIN		sga_reconocimiento ON  sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
				JOIN		sga_elementos ON sga_reconocimiento_act.elemento = sga_elementos.elemento
				JOIN		sga_g3entidades_subtipos ON sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				JOIN		sga_g3entidades_tipos ON sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
				JOIN		sga_alumnos ON sga_reconocimiento.alumno = sga_alumnos.alumno
				JOIN		sga_planes_versiones ON sga_reconocimiento.plan_version = sga_planes_versiones.plan_version
				JOIN		sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN		sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				LEFT JOIN	sga_documentos ON sga_reconocimiento.documento = sga_documentos.documento
				LEFT JOIN	sga_escalas_notas ON sga_reconocimiento_act.escala_nota = sga_escalas_notas.escala_nota
				LEFT JOIN	sga_escalas_notas_det ON sga_reconocimiento_act.escala_nota = sga_escalas_notas_det.escala_nota AND sga_reconocimiento_act.nota = sga_escalas_notas_det.nota
				WHERE		TRUE
							$filtro
				ORDER BY	sga_elementos.nombre
  		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los diferentes estados posibles de los trámites de aprobación por resolución.
	 *
	 */
	function get_estados_tramites_aprobacion_por_resolucion()
	{
		return array(array('valor' => aprobacion_por_resolucion::estado_abierto, 'descr' => aprobacion_por_resolucion::estado_abierto_desc),
		             array('valor' => aprobacion_por_resolucion::estado_anulado, 'descr' => aprobacion_por_resolucion::estado_anulado_desc),
		             array('valor' => aprobacion_por_resolucion::estado_cerrado, 'descr' => aprobacion_por_resolucion::estado_cerrado_desc)
		             );
	} 	
		
	/**
	 * Obtiene los diferentes tipos de reconocimiento posibles para los trámites de aprobación por resolución.
	 *
	 */
	function get_tipos_reconocimiento()
	{
		return array(array('valor' => aprobacion_por_resolucion::activ_modulos,	'descr' => aprobacion_por_resolucion::activ_modulos_desc ),
		             array('valor' => aprobacion_por_resolucion::cred_globales,	'descr' => aprobacion_por_resolucion::cred_globales_desc )
		             );
	} 	
	
	/**
	 * Obtiene los diferentes tipos de reconocimiento posibles para los trámites de aprobación por resolución.
	 *
	 */
	function get_tipos_actividades_reconocidas()
	{
		return array(array('valor' => aprobacion_por_resolucion::ayudantia,				'descr' => 'Ayudantía'),
		             array('valor' => aprobacion_por_resolucion::curso,					'descr' => 'Curso'),
		             array('valor' => aprobacion_por_resolucion::investigacion, 		'descr' => 'Investigación'),
		             array('valor' => aprobacion_por_resolucion::pasantia, 				'descr' => 'Pasantía'),
		             array('valor' => aprobacion_por_resolucion::seminario, 			'descr' => 'Seminario'),
		             array('valor' => aprobacion_por_resolucion::trabajo_profesional,	'descr' => 'Trabajo Profesional'),
		             array('valor' => aprobacion_por_resolucion::tutoria, 				'descr' => 'Tutoría')
		             );
	}

	/**
	 * Obtiene las descripciones de los diferentes tipos de actividades asociados a la documentación de reconocimiento.
	 *
	 */
	function get_tipos_actividades_descr($where=null)
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT	sga_reconocimiento_tipo_actividad.tipo_actividad,
						sga_reconocimiento_tipo_actividad.nombre as tipo_actividad_descr
				FROM	sga_reconocimiento_tipo_actividad
				$where
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene las descripciones asociadas a los tipos de actividades dados para la carga externa masiva de 
	 * la documentación
	 * 
	 * @param	$tipo_actividad	array/integer con los ids del o los tipos de actividades para recuerar su descripción.
	 */
	function get_tipo_actividad_descr($tipo_actividad)
	{
		if (is_array($tipo_actividad)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_actividad, 'sga_reconocimiento_tipo_actividad', 'tipo_actividad');
			$datos = $this->get_tipos_actividades_descr($filtro);
			return $datos;
		} 
		$tipo_actividad = toba::db()->quote($tipo_actividad);
		$filtro = "sga_reconocimiento_tipo_actividad.tipo_actividad = $tipo_actividad";
		$datos = $this->get_tipos_actividades_descr($filtro);
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
	 * Obtiene las diferentes posibilidades de Reconocimiento, total o parcial.
	 *
	 */
	function get_opciones_combo_reconocimiento()
	{
		return array(array('valor' => comunes::si,	'descr' => aprobacion_por_resolucion::rec_total_desc),
		             array('valor' => comunes::no,	'descr' => aprobacion_por_resolucion::rec_parcial_desc)
		             );
	}
	
	function get_documento_reconocimiento_digital($documento)
	{
		$documento = toba::db()->quote($documento);
		$sql = "SELECT	sga_reconocimiento_doc_digital.archivo,
				sga_reconocimiento_doc_digital.nombre_archivo
			FROM	sga_reconocimiento_doc_digital
			WHERE	sga_reconocimiento_doc_digital.reconocimiento_doc = $documento";
		return guarani_db::consultar_fila($sql);
	}

	function get_opciones_combo_tipos_elementos_a_reconocer()
	{
		return array(array('valor' => elemento::tipo_actividad,	'descr' => 'Actividad'),
		             array('valor' => elemento::tipo_modulo,	'descr' => 'Módulo')
		             );
	}

	function get_reconocimiento_alumno_modulo($alumno, $modulo)
	{
		$alumno = toba::db()->quote($alumno);
		$modulo = toba::db()->quote($modulo);
		$sql = "SELECT	sga_reconocimiento_act.reconocimiento_total,
				sga_reconocimiento_act.creditos
			FROM	sga_reconocimiento_act
			JOIN	sga_reconocimiento ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
			WHERE	sga_reconocimiento.alumno = $alumno AND
				sga_reconocimiento_act.elemento = $modulo AND
				sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."' AND
				sga_reconocimiento_act.reconocimiento_total = '".comunes::si."'
			UNION
			SELECT	sga_reconocimiento_act.reconocimiento_total,
				SUM(sga_reconocimiento_act.creditos) as creditos
			FROM	sga_reconocimiento_act
			JOIN	sga_reconocimiento ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
			WHERE	sga_reconocimiento.alumno = $alumno AND
				sga_reconocimiento_act.elemento = $modulo AND
				sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."' AND
				sga_reconocimiento_act.reconocimiento_total = '".comunes::no."'
			GROUP BY	sga_reconocimiento_act.elemento,
					sga_reconocimiento_act.reconocimiento_total
		";
		return guarani_db::consultar_fila($sql);		
	}
}
?>
