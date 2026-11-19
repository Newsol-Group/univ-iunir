<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;

class comisiones
{
	
	/**
	 * parametros: anio_academico, docente, periodo_lectivo, actividad, ubicacion
	 * param_null: anio_academico, docente, periodo_lectivo, actividad, ubicacion
	 * no_quote: anio_academico, docente, periodo_lectivo, actividad, ubicacion
	 * cache: no
	 * cache_expiracion: 600
	 * filas: n
	 */
	function lista_comisiones($parametros)
	{
		// listado de comisiones activas

		$where_periodo_lectivo = '';
		$where_actividad = '';
		$from_docente = '';
		$where_docente = '';
		$where_ubicacion = '';
		$where_anio_academico = '';
		$responsabilidad_docente = ' CAST(NULL as varchar(50)) as resp_docente';

		// ********************** FILTROS *********************************
		// Filtro por Docente
		if ($parametros['docente'] != NULL) {

			// Solo comisiones donde el docente esta asignado
			$from_docente  = " JOIN sga_docentes_comision ON sga_docentes_comision.comision = vw_comisiones.comision
			                   JOIN sga_docentes_responsabilidades ON  sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad ";
			
			$where_docente = " AND sga_docentes_comision.docente = {$parametros['docente']}";

			$responsabilidad_docente = ' sga_docentes_responsabilidades.nombre as resp_docente';
		}
		
		// Filtro por Periodo Lectivo
		if ($parametros['periodo_lectivo'] != NULL) {
			$where_periodo_lectivo = " AND vw_comisiones.periodo_lectivo = {$parametros['periodo_lectivo']}";
		}
		
		// Filtro por Actividad    
		if ($parametros['actividad'] != NULL) {
			// Recupera comisiones de la actividad seleccionada
			$where_actividad = " AND vw_comisiones.elemento = {$parametros['actividad']}";
		}
		// Filtro por Ubicacion    
		if ($parametros['ubicacion'] != NULL) {
			// Recupera comisiones de la ubicacion seleccionada
			$where_ubicacion = " AND vw_comisiones.ubicacion = {$parametros['ubicacion']}";
		}
		//Filtro por Año Academico
		if (!empty($parametros['anio_academico'])){
			$where_anio_academico = " AND vw_comisiones.anio_academico = {$parametros['anio_academico']}";
		}
		
		// Listado de comisiones
		$sql = "SELECT
			   vw_comisiones.anio_academico as anio_academico,
			   vw_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
			   vw_comisiones.comision as comision,
			   vw_comisiones.comision_nombre as comision_nombre,
			   vw_comisiones.fecha_inicio as fecha_inicio,
			   vw_comisiones.fecha_fin as fecha_fin,
			   vw_comisiones.fecha_inicio_dictado as fecha_inicio_dictado,
			   vw_comisiones.fecha_fin_dictado as fecha_fin_dictado,
			   vw_comisiones.fecha_tope_movimientos as fecha_tope_movimientos,
			   vw_comisiones.fecha_inactivacion as fecha_inactivacion,
			   vw_comisiones.elemento        as actividad,
			   vw_comisiones.elemento_codigo as actividad_codigo,
			   vw_comisiones.elemento_nombre as actividad_nombre,
			   vw_comisiones.cupo as cupo,
			   vw_comisiones.turno_cursada as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (vw_comisiones.comision) as instancias,
			   vw_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   vw_comisiones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   $responsabilidad_docente
			FROM vw_comisiones
			JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
			$from_docente
			LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
			LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = vw_comisiones.turno_cursada
			   
			WHERE vw_comisiones.estado = 'A'
			  $where_docente
			  $where_anio_academico
			  $where_periodo_lectivo
			  $where_actividad
			  $where_ubicacion

			ORDER BY vw_comisiones.anio_academico,
			         vw_comisiones.fecha_inicio,
					 vw_comisiones.elemento_nombre,
			         vw_comisiones.comision_nombre
			";

		$datos = kernel::db()->consultar($sql);		
		$nuevo = array();
		foreach ($datos as $id => $dato) {
			$nuevo[$id] = $dato;
			$nuevo[$id]['comision_id'] = catalogo::generar_id($dato['comision']);
		}
		return $nuevo;		
	}

	/**
	 * Recupera el cronograma de evaluaciones de las comisiones vigentes en las que el alumno esta inscripto en todas sus propuestas.
	 * No recupera el resultado de las notas en las evaluaciones.
	 *
	 * parametros: persona, visible_al_alumno, solo_insc_cursos
	 * param_null: visible_al_alumno, solo_insc_cursos
	 * no_quote: visible_al_alumno, solo_insc_cursos
	 * cache: memoria
	 * filas: n
	 */
	function get_cronograma_evaluaciones_persona($parametros)
	{
		// Recupera los datos de las evaluaciones parciales de las comisiones en las que el alumno esta inscripto
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$si = kernel::db()->quote(\comunes::si);
		$no = kernel::db()->quote(\comunes::no);
		$tipo_inscripcion = kernel::db()->quote(\inscripcion_cursada::tipo_inscripcion);
		$tipo_preinscripcion = kernel::db()->quote(\inscripcion_cursada::tipo_preinscripcion);
		$estado_aceptada = kernel::db()->quote(\inscripcion_cursada::estado_aceptada);
        
		// Por defecto muestro las evaluaciones visibles al alumno.
	    $filtro_visible_al_alumno = " AND sga_evaluaciones.visible_al_alumno = {$si}";
		// Si el parámetro 'visible_al_alumno' es vacío o 'N' muestro todas las evaluaciones.
	    if (empty($parametros['visible_al_alumno']) || ($parametros['visible_al_alumno'] == \comunes::no)) {
			$filtro_visible_al_alumno = "";
		}
		
		// Filtra solo inscripciones a materias de propuestas de tipo curso
	    $filtro_solo_cursos = "";
	    if (!empty($parametros['solo_insc_cursos'])) {
			$propuesta_tipo_curso = \propuesta::tipo_curso;
			$filtro_solo_cursos = " AND sga_propuestas.propuesta_tipo = $propuesta_tipo_curso";
		}
		

	    // Recupera el cronograma de evaluaciones de las comisiones vigentes que esta inscripto el alumno
		$sql = "SELECT	
						sga_propuestas.propuesta as propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						vw_comisiones.elemento as actividad,
						vw_comisiones.elemento_codigo as actividad_codigo,
						sga_elementos_plan.nombre as actividad_nombre,
						vw_comisiones.comision_nombre as comision_nombre,
						vw_comisiones.comision      as comision,
						sga_evaluaciones.evaluacion  as evaluacion,
						sga_evaluaciones.nombre      as evaluacion_nombre,
						sga_evaluaciones.descripcion as evaluacion_desc,
						sga_evaluaciones_tipos.nombre as tipo_evaluacion_desc,
						sga_evaluaciones.fecha        as fecha_evaluacion,
						sga_evaluaciones.hora_inicio  as hora_inicio,
						to_char(sga_evaluaciones.hora_inicio, $formato_hora_visual) as hora_inicio_visual,
						sga_evaluaciones.hora_fin  as hora_fin,
						to_char(sga_evaluaciones.hora_fin, $formato_hora_visual) as hora_fin_visual,
						to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as fecha_evaluacion_visual
				FROM sga_alumnos 
				JOIN sga_insc_cursada ON sga_insc_cursada.alumno = sga_alumnos.alumno 
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN vw_comisiones ON vw_comisiones.comision = sga_insc_cursada.comision
				JOIN sga_elementos_revision ON sga_elementos_revision.elemento = vw_comisiones.elemento
				JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND sga_elementos_plan.plan_version = sga_insc_cursada.plan_version)
				JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_comisiones.entidad
				JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				
				WHERE sga_alumnos.persona = {$parametros['persona']}
				  AND vw_comisiones.fecha_fin >= CURRENT_DATE  -- Solo vigentes
				  AND (sga_insc_cursada.tipo = {$tipo_inscripcion} OR (sga_insc_cursada.tipo = {$tipo_preinscripcion} AND sga_insc_cursada.estado_preinscripcion = {$estado_aceptada}))
                  AND sga_evaluaciones_tipos.automatica = {$no}  -- Solo evaluaciones de la cursada
				  $filtro_visible_al_alumno
				  $filtro_solo_cursos
				ORDER BY propuesta_nombre, actividad_nombre, comision_nombre, fecha_evaluacion, hora_inicio
				";
				  
		return kernel::db()->consultar($sql);
	}	
	
	/**
	 * parametros: comision, visible_al_alumno
	 * param_null: visible_al_alumno
	 * cache: no
	 * filas: n
	 */
	function get_evaluaciones($parametros)
	{
		// Recupera los datos de las evaluaciones parciales de la comision. No los resultados de las notas de los alumnos.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$formato_fecha_orden = kernel::db()->quote(\comunes::formato_fecha_orden);
        
	    // visible_al_alumno = S-Las evaluaciones visibles al alumno / N - Las evaluaciones que no son visibles al alumno
	    $filtro_visible_al_alumno = "";
	    if($parametros['visible_al_alumno'] != "''"){
	       $filtro_visible_al_alumno = " AND sga_evaluaciones.visible_al_alumno = 'S' ";
		}

	    // Recupera evaluaciones parciales de una cursada, ordenadas por fecha, hora inicio y evaluacion.
		$sql = "SELECT	sga_comisiones.comision      as comision,
						sga_evaluaciones.evaluacion  as evaluacion,
						sga_evaluaciones.nombre      as evaluacion_nombre,
						sga_evaluaciones.descripcion as evaluacion_desc,
						sga_evaluaciones_tipos.nombre as tipo_evaluacion_desc,
						sga_evaluaciones.fecha        as fecha_evaluacion,
						sga_evaluaciones.hora_inicio  as hora_evaluacion,
						to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as sfecha_evaluacion,
                        to_char(sga_evaluaciones.fecha, $formato_fecha_orden) || '-' || coalesce(to_char(sga_evaluaciones.hora_inicio, $formato_hora_visual), '0000') || '-' || cast(sga_evaluaciones.evaluacion as text) as fecha_evaluacion
				FROM 	sga_comisiones
						JOIN sga_evaluaciones ON sga_evaluaciones.entidad  = sga_comisiones.entidad
						JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				WHERE	sga_comisiones.comision    = {$parametros['comision']}
                AND		sga_evaluaciones_tipos.automatica = 'N'  -- Solo evaluaciones de la cursada...
				$filtro_visible_al_alumno
				ORDER BY 9
				";
			RETURN kernel::db()->consultar($sql);
	}

	/**
	 * parametros: comision, visible_al_alumno
	 * param_null: visible_al_alumno
	 * cache: no
	 * filas: n
	 */
	function get_datos_evaluaciones_alumno($parametros)
	{
	    // visible_al_alumno = S-Las evaluaciones visibles al alumno / N - Las evaluaciones que no son visibles al alumno
	    $filtro_visible_al_alumno = "";
	    if($parametros['visible_al_alumno'] != "''"){
	       $filtro_visible_al_alumno = " AND sga_evaluaciones.visible_al_alumno = 'S' ";
		}
		
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$formato_fecha_orden = kernel::db()->quote(\comunes::formato_fecha_orden);
        
	    // Recupera evaluaciones parciales y las notas de los alumnos...
		$sql = "SELECT	sga_comisiones.comision as comision,
						sga_evaluaciones.evaluacion as evaluacion,
                        to_char(sga_evaluaciones.fecha, $formato_fecha_orden) || '-' || coalesce(to_char(sga_evaluaciones.hora_inicio, $formato_hora_visual), '0000') || '-' || cast(sga_evaluaciones.evaluacion as text) as fecha_evaluacion,
						vw_personas.apellido as apellido,
						vw_personas.nombres as  nombres,
						vw_personas.apellido_nombres_elegido  as apellido_nombres_elegido,
						sga_alumnos.legajo as legajo,
						sga_alumnos.alumno as alumno,
						sga_eval_detalle.nota as nota,
						sga_escalas_notas_det.descripcion as nota_descripcion,
						sga_escalas_notas_concepto.descripcion as nota_concepto,
						sga_eval_detalle.resultado as resultado,
						sga_escalas_notas_resultado.descripcion as resultado_desc,
						sga_eval_detalle.observaciones as observaciones,
						sga_eval_detalle.corregido_por as corregido_por
				FROM 	sga_comisiones
						JOIN sga_evaluaciones ON sga_evaluaciones.entidad  = sga_comisiones.entidad
						JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
						JOIN sga_eval_detalle ON sga_eval_detalle.evaluacion = sga_evaluaciones.evaluacion
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle.alumno 
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota AND
						                                    sga_escalas_notas_det.nota = sga_eval_detalle.nota)
						LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle.resultado
						LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_concepto.concepto = sga_escalas_notas_det.concepto
						
				WHERE	sga_comisiones.comision	  = {$parametros['comision']}
                AND		sga_evaluaciones_tipos.automatica = 'N'  -- Solo evaluaciones de la cursada...
				AND		sga_eval_detalle.rectificado = 'N'
				$filtro_visible_al_alumno
				ORDER BY 3,4,5
				";

			return kernel::db()->consultar($sql); 
	}

	 /**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function get_alumnos_de_parciales_insc_acta($parametros)
	{
	    // Recupera los alumnos de evaluaciones parciales, inscripciones, nombre de propuesta y acta de cursada/promocion cerrada.
		// Es para armar la tabla del reporte ubicando los alumnos ordenado por el nombre, y por el nombre abreviado de propuesta.
	    // Usa el UNION simple (sin el ALL) para que no traiga registros duplicados.
		$sql = "
				-- Alumnos de las evaluaciones parciales de la comision
				SELECT	vw_personas.apellido as apellido,
						vw_personas.nombres as nombres,
						vw_personas.apellido_nombres  as apellido_nombres,
						vw_personas.apellido_nombres_elegido  as apellido_nombres_elegido,
						sga_alumnos.alumno as alumno,
						sga_alumnos.legajo as legajo,
						vw_personas.id_imagen as id_imagen,
						vw_personas.tipo_nro_documento as documento_principal,
						sga_propuestas.nombre_abreviado as nombre_abreviado
				FROM 	sga_comisiones
						JOIN sga_evaluaciones ON sga_evaluaciones.entidad  = sga_comisiones.entidad
						JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
						JOIN sga_eval_detalle ON sga_eval_detalle.evaluacion = sga_evaluaciones.evaluacion
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle.alumno 
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas on sga_propuestas.propuesta=sga_alumnos.propuesta
				WHERE	sga_comisiones.comision = {$parametros['comision']}
				  AND	sga_evaluaciones_tipos.automatica = 'N'

				UNION

				-- Alumnos de las evaluaciones parciales de la comision
				SELECT	vw_personas.apellido as apellido,
						vw_personas.nombres as nombres,
						vw_personas.apellido_nombres  as apellido_nombres,
						vw_personas.apellido_nombres_elegido  as apellido_nombres_elegido,
						sga_alumnos.alumno as alumno,
						sga_alumnos.legajo as legajo,
						vw_personas.id_imagen as id_imagen,
						vw_personas.tipo_nro_documento as documento_principal,
						sga_propuestas.nombre_abreviado as nombre_abreviado
				FROM 	sga_comisiones
						JOIN sga_evaluaciones ON sga_evaluaciones.entidad  = sga_comisiones.entidad
						JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
						JOIN sga_eval_detalle_cursadas ON sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle_cursadas.alumno 
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas on sga_propuestas.propuesta=sga_alumnos.propuesta
				WHERE	sga_comisiones.comision = {$parametros['comision']}
				  AND	sga_evaluaciones_tipos.automatica = 'S'

				UNION
				
				-- Inscripciones a cursadas
				SELECT	vw_personas.apellido as apellido,
						vw_personas.nombres as nombres,
						vw_personas.apellido_nombres  as apellido_nombres,
						vw_personas.apellido_nombres_elegido  as apellido_nombres_elegido,
						sga_alumnos.alumno as alumno,
						sga_alumnos.legajo as legajo,
						vw_personas.id_imagen as id_imagen,
						vw_personas.tipo_nro_documento as documento_principal,
						sga_propuestas.nombre_abreviado as nombre_abreviado
				FROM 	sga_insc_cursada
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno 
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas on sga_propuestas.propuesta=sga_alumnos.propuesta
				WHERE	sga_insc_cursada.comision = {$parametros['comision']}

				UNION

				-- Alumnos de actas de cursadas/promocion cerrada
				SELECT	vw_personas.apellido as alumno_apellido,
						vw_personas.nombres as alumno_nombres,
						vw_personas.apellido_nombres  as apellido_nombres,
						vw_personas.apellido_nombres_elegido  as apellido_nombres_elegido,
						sga_alumnos.alumno as alumno,
						sga_alumnos.legajo as legajo,
						vw_personas.id_imagen as id_imagen,
						vw_personas.tipo_nro_documento as documento_principal,
						sga_propuestas.nombre_abreviado as nombre_abreviado
				FROM 	sga_actas
						JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_actas_detalle.alumno 
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas on sga_propuestas.propuesta=sga_alumnos.propuesta
				WHERE	sga_actas.comision	= {$parametros['comision']}
				AND		sga_actas.estado = 'C'
				AND     sga_actas_detalle.estado = 'A'
				GROUP BY 1, 2, 3, 4, 5, 6, 7, 8, 9
				ORDER BY 1, 2, 3, 5, 4
				";

			return kernel::db()->consultar($sql); 
	}
    
    
    /**
	 * parametros: comision	, estado_acta 
	 * cache: no
	 * filas: n
	 */
	function get_resultados_acta_cursada($parametros)
	{
		// Recupera datos de los alumnos de actas de cursadas abiertas y cerradas de una comision.
		// estado_acta: C-Cerrada / A-Abierta / T-Actas cerradas y abiertas.
		$sql_acta = '';
		// Recupero datos de las actas de cursadas cerradas 
		$sql1 = "SELECT 
				   a.id_acta as id_acta, 
				   a.nro_acta as acta, 
				   'C' as estado_acta,
				   a.alumno as alumno, 
				   p.persona as persona, 
				   p.apellido as apellido, 
				   p.nombres as nombres, 
				   alu.legajo as legajo, 
				   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
				   a.nota as nota, 
				   a.nota_descripcion as nota_descripcion, 
				   a.cond_regularidad as cond_regularidad, 
				   cr.nombre as cond_regularidad_desc, 
				   a.resultado as resultado, 
				   a.resultado_descripcion as resultado_descripcion
			  FROM vw_actas as a 
				   JOIN sga_alumnos as alu ON alu.alumno = a.alumno
				   JOIN mdp_personas as p ON p.persona = alu.persona
				   JOIN sga_propuestas ON sga_propuestas.propuesta = alu.propuesta
				   JOIN sga_cond_regularidad as cr ON cr.cond_regularidad = a.cond_regularidad
			 WHERE a.comision = {$parametros['comision']}
			   AND a.origen = 'R'  -- Acta de Regulares
			   AND a.estado = 'A'  -- estado del alumno en el acta
			"; 
			 
		// Recupero datos de las actas de cursadas abiertas 
		$sql2 = "SELECT 
				   a.id_acta as id_acta, 
				   a.nro_acta as acta, 
				   a.estado as estado_acta,
				   alu.alumno as alumno, 
				   p.persona as persona,
				   p.apellido as apellido, 
				   p.nombres as nombres, 
				   alu.legajo as legajo, 
				   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
				   dc.nota_cursada as nota_cursada, 
				   escala.descripcion as nota_descripcion, 
				   dc.cond_regularidad as cond_regulardiad, 
				   cr.nombre as cond_regularidad_desc, 
				   dc.resultado_cursada as resultado, 
				   ir.descripcion as resultado_descripcion
			  FROM sga_actas as a 
				   JOIN sga_eval_detalle_cursadas as dc ON dc.id_acta_cursada = a.id_acta
				   JOIN sga_alumnos as alu ON alu.alumno = dc.alumno
				   JOIN mdp_personas as p ON p.persona = alu.persona
				   JOIN sga_propuestas ON sga_propuestas.propuesta = alu.propuesta
				   LEFT JOIN sga_cond_regularidad as cr ON cr.cond_regularidad = dc.cond_regularidad
				   LEFT JOIN sga_escalas_notas_det as escala ON (escala.escala_nota = dc.escala_nota_cursada AND escala.nota = dc.nota_cursada)
				   LEFT JOIN sga_instancias_resultado as ir ON (ir.instancia = dc.instancia_cursada AND ir.resultado = dc.resultado_cursada)
			 WHERE a.comision = {$parametros['comision']}
			   AND a.origen = 'R'  -- Acta de Regulares
			   AND a.estado = 'A'  -- Acta Abierta
			   AND a.tipo_acta = 'N'  -- Acta de cursada Normal (no rectificativa)
			";   
				
	    if(strcmp($parametros['estado_acta'] , 'C' )){ 
			$sql_acta = $sql1 . ' ORDER BY 5, 6, 4';			
		}		
		
	    if(strcmp($parametros['estado_acta'] , 'A' )){
			$sql_acta = $sql2 . ' ORDER BY 5, 6, 4';		
		}		
	    if(strcmp($parametros['estado_acta'] , 'T' )){
			$sql_acta = $sql1 . ' UNION ' . $sql2 . ' ORDER BY 5, 6, 4';	
		}					   
		$alumnos_acta = kernel::db()->consultar($sql_acta);
		return $alumnos_acta;    
	}
    
    /**
	 * parametros: comision, estado_acta
	 * cache: no
	 * filas: n
	 */
	function get_resultados_acta_promocion($parametros)
	{
		// Recupera datos de los alumnos de actas de promociones abiertas y cerradas de una comision.
		// estado_acta: C-Cerrada / A-Abierta / T-Actas cerradas y abiertas.
		// Recupero datos de las actas de promocion cerradas 
			$sql1 = "SELECT 
		               a.id_acta as id_acta, 
					   a.nro_acta as acta, 
					   'C' as estado_acta,
		               a.alumno as alumno, 
					   p.persona as persona, 
					   p.apellido as apellido, 
					   p.nombres as nombres, 
					   alu.legajo as legajo, 
					   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					   a.nota as nota, 
					   a.nota_descripcion as nota_descripcion, 
		               a.cond_regularidad as cond_regularidad, 
					   cr.nombre as cond_regularidad_desc, 
					   a.resultado as resultado, 
					   a.resultado_descripcion as resultado_descripcion
		          FROM vw_actas as a 
				       JOIN sga_alumnos as alu ON alu.alumno = a.alumno
				       JOIN mdp_personas as p ON p.persona = alu.persona
				       JOIN sga_propuestas ON sga_propuestas.propuesta = alu.propuesta
					   JOIN sga_cond_regularidad as cr ON cr.cond_regularidad = a.cond_regularidad
				 WHERE a.comision = {$parametros['comision']}
				   AND a.origen = 'P'  -- Acta de promocion.
				   AND a.estado = 'A'  -- estado del alumno en el acta
				";  
				 
			// Recupero datos de las actas de promocion abiertas 
			$sql2 = "SELECT 
		               a.id_acta as id_acta, 
					   a.nro_acta as acta, 
					   a.estado as estado_acta,
		               alu.alumno as alumno, 
					   p.persona as persona, 
					   p.apellido as apellido, 
					   p.nombres as nombres, 
					   alu.legajo as legajo, 
					   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					   dc.nota_promocion as nota, 
					   escala.descripcion as nota_descripcion, 
		               dc.cond_regularidad as cond_regularidad, 
					   cr.nombre as cond_regularidad_desc, 
					   dc.resultado_promocion as resultado, 
					   ir.descripcion as resultado_descripcion
		          FROM sga_actas as a 
				       JOIN sga_eval_detalle_cursadas as dc ON dc.id_acta_promocion = a.id_acta
				       JOIN sga_alumnos as alu ON alu.alumno = dc.alumno
				       JOIN mdp_personas as p ON p.persona = alu.persona
				       JOIN sga_propuestas ON sga_propuestas.propuesta = alu.propuesta
					   LEFT JOIN sga_cond_regularidad as cr ON cr.cond_regularidad = dc.cond_regularidad
					   LEFT JOIN sga_escalas_notas_det as escala ON (escala.escala_nota = dc.escala_nota_promocion AND escala.nota = dc.nota_promocion)
					   LEFT JOIN sga_instancias_resultado as ir ON (ir.instancia = dc.instancia_promocion AND ir.resultado = dc.resultado_promocion)
				 WHERE a.comision = {$parametros['comision']}
				   AND a.origen = 'P'  -- Acta de Promocion
				   AND a.estado = 'A'  -- Acta Abierta
				   AND a.tipo_acta = 'N'  -- Acta de promocion Normal (no rectificativa)
				";   
				 
		$sql_acta = '';
		
	    if(strcmp($parametros['estado_acta'] , 'C' )){ 
			$sql_acta = $sql1 . ' ORDER BY 5, 6, 4';			
		}		
		
	    if(strcmp($parametros['estado_acta'] , 'A' )){
			$sql_acta = $sql2 . ' ORDER BY 5, 6, 4';		
		}		
	    if(strcmp($parametros['estado_acta'] , 'T' )){
			$sql_acta = $sql1 . ' UNION ' . $sql2 . ' ORDER BY 5, 6, 4';	
		}	
						   
		$alumnos_acta = kernel::db()->consultar($sql_acta);
		return $alumnos_acta;
		
	}
	
	/**
	 * parametros: comision, docente
	 * param_null: docente
	 * no_quote: comision, docente
	 * cache: memoria
	 * filas: n
	 */
	function get_subcomisiones_by_comision($parametros)
	{
		$where = "";
		if (!is_null($parametros['docente'])) {
			$docente = kernel::db()->quote($parametros['docente']);
			$where = " AND sga_subcomisiones.subcomision IN (
							SELECT subcomision
							FROM sga_docentes_subcomision
							WHERE docente = $docente
							AND CURRENT_DATE BETWEEN fecha_desde AND fecha_hasta
							)";
		}
		
		return \toba::consulta_php('co_comisiones')->get_subcomisiones_comision($parametros['comision'], $where);
	}
        
}