<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Actualizaciones
 *
 */
class co_clases
{
	//------------------------------------------------------------------------
	//-- Clases
	//------------------------------------------------------------------------

	/**
	 * Retorna el listado de clases para una comisión dada.
	 *
	 * @param integer $comision
	 * @return array
	 */
	function get_clases_comision($comision, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$comision = toba::db()->quote($comision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases.banda_horaria
				FROM		sga_comisiones_bh,
							sga_clases,
							sga_asignaciones
				WHERE		sga_comisiones_bh.comision = $comision AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_asignaciones.asignacion = sga_comisiones_bh.asignacion AND
							sga_clases.valido = 1
							$where
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}

	
	/**
	 * Retorna el listado de las fechas de las clases para la Comisión dada, a partir de la fecha dada. 
	 * Se limita el listado a lo sumo a $cant_clases clases para obtener la planilla de asistencia para los alumnos 
	 * de la comisión.
	 * 
	 *
	 * @param integer $comision
	 * @return array
	 */
	function get_fechas_clases_comision_para_asistencia($comision, $fecha, $cant_clases)
	{
		$comision = toba::db()->quote($comision);
		$fecha = toba::db()->quote($fecha);
		$cant_clases = toba::db()->quote($cant_clases);
		
		$sql = "SELECT		sga_clases.clase,
							sga_clases.fecha as fecha_clase,
							to_char(sga_clases.fecha, '" . comunes::formato_fecha_visual_sin_anio . "' ) as fecha_clase_visual
				FROM		sga_comisiones_bh,
							sga_clases
				WHERE		sga_comisiones_bh.comision = $comision AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_clases.valido = 1 AND
							sga_clases.fecha > $fecha
				ORDER BY	sga_clases.fecha
				LIMIT		$cant_clases
			;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de las fechas de las clases para la Subcomisión dada, a partir de la fecha dada. 
	 * Se limita el listado a lo sumo a $cant_clases clases para obtener la planilla de asistencia para los alumnos 
	 * de la Subcomisión.
	 * 
	 *
	 * @param integer $subcomision
	 * @return array
	 */
	function get_fechas_clases_subcomision_para_asistencia($subcomision, $fecha, $cant_clases)
	{
		$subcomision = toba::db()->quote($subcomision);
		$fecha = toba::db()->quote($fecha);
		$cant_clases = toba::db()->quote($cant_clases);
		
		$sql = "SELECT		sga_clases.clase,
							sga_clases.fecha as fecha_clase,
							to_char(sga_clases.fecha, '" . comunes::formato_fecha_visual_sin_anio . "') as fecha_clase_visual
				FROM		sga_subcomisiones_bh,
							sga_clases
				WHERE		sga_subcomisiones_bh.subcomision = $subcomision AND
							sga_clases.banda_horaria = sga_subcomisiones_bh.banda_horaria AND
							sga_clases.valido = 1 AND
							sga_clases.fecha > $fecha
				ORDER BY	sga_clases.fecha
				LIMIT		$cant_clases
			;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de clases para una comisión dada, y las aulas asignadas para las mismas.
	 *
	 * @param integer $comision
	 */
	function get_clases_comision_con_aulas($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases.valido,
							sga_clases.asistencia,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							get_espacios_clase(sga_clases.clase) as espacio_nombre
				FROM		sga_comisiones_bh,
							sga_clases,
							sga_clases_tipos,
							sga_asignaciones
				WHERE		sga_comisiones_bh.comision = $comision AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase AND
							sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de clases para una comisión dada, junto con la cantidad de ausentes y presentes para cada
	 * clase, si es que ya existen registros de asistencia creados para las mismas.
	 *
	 * @param integer $comision
	 */
	function get_clases_comision_con_presentes_ausentes($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							get_espacios_clase(sga_clases.clase) as espacio_nombre,
							(	SELECT	CAST(SUM(CASE WHEN cant_inasistencias = 0 THEN 1 ELSE 0 END) AS VARCHAR) || ' - ' ||
										CAST(SUM(CASE WHEN cant_inasistencias > 0 THEN 1 ELSE 0 END) AS VARCHAR) 
								FROM	sga_clases_asistencia
								WHERE	clase = sga_clases.clase) as presentes_ausentes
				FROM		sga_comisiones_bh 
							JOIN sga_clases ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
							JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase 
							JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE		sga_comisiones_bh.comision = $comision AND
							sga_clases.valido = 1
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el conjunto de clases para una subcomisión dada.
	 *
	 * @param integer $subcomision
	 * @return array
	 */
	function get_clases_subcomision($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases.banda_horaria			
				FROM		sga_subcomisiones_bh,
							sga_comisiones_bh,
							sga_clases,
							sga_asignaciones
				WHERE		sga_subcomisiones_bh.subcomision = $subcomision AND
							sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_asignaciones.asignacion = sga_comisiones_bh.asignacion AND
							sga_clases.valido = 1
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
		/**
	 * Retorna el conjunto de clases para una subcomisión dada, y las aulas asignadas para las mismas.
	 *
	 * @param integer $subcomision
	 */
	function get_clases_subcomision_con_aulas($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases.valido,
							sga_clases.asistencia,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							get_espacios_clase(sga_clases.clase) as espacio_nombre
				FROM		sga_subcomisiones_bh,
							sga_comisiones_bh,
							sga_clases,
							sga_clases_tipos,
							sga_asignaciones
				WHERE		sga_subcomisiones_bh.subcomision = $subcomision AND
							sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase AND
							sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de clases para una subcomisión dada, junto con la cantidad de ausentes y presentes para cada
	 * clase, si es que ya existen registros de asistencia creados para las mismas.
	 *
	 * @param integer $comision
	 */
	function get_clases_subcomision_con_presentes_ausentes($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							get_espacios_clase(sga_clases.clase) as espacio_nombre,
							(sga_clases.fecha < current_date ) as dictada,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_clases.cantidad_horas_dictadas,
							(	SELECT	CAST(SUM(CASE WHEN sga_clases_asistencia.cant_inasistencias = 0 THEN 1 ELSE 0 END) AS VARCHAR) || ' - ' ||
										CAST(SUM(CASE WHEN sga_clases_asistencia.cant_inasistencias > 0 THEN 1 ELSE 0 END) AS VARCHAR)
								FROM	sga_clases_asistencia
										JOIN sga_insc_cursada ON sga_clases_asistencia.alumno = sga_insc_cursada.alumno
										JOIN sga_insc_subcomision ON sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion
								WHERE	sga_clases_asistencia.clase = sga_clases.clase AND
										sga_insc_subcomision.subcomision = $subcomision
							) as presentes_ausentes
				FROM		sga_subcomisiones_bh
							JOIN sga_comisiones_bh ON sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
							JOIN sga_clases ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
							JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase
							JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE		sga_subcomisiones_bh.subcomision = $subcomision AND
							sga_clases.valido = 1
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos básicos de una clase determinada
	 *
	 * @param 	int 	$clase
	 * @return 	array
	 */
	function get_datos_clase($clase)
	{
		$clase = toba::db()->quote($clase);
		
		$sql = "SELECT 
					sga_clases.clase,
					sga_clases.fecha,
					to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha_formateada,
					sga_clases.valido,
					sga_clases.tema_planificado,
					sga_clases.tema_dictado,
					sga_clases.cantidad_horas_dictadas
				FROM 
					sga_clases
				WHERE
					sga_clases.clase = $clase
				";
		
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Obtiene los datos básicos de una clase y su asignación determinada, según la banda horaria a la 
	 * que corresponde.
	 *
	 * @param 	int 	$clase
	 */
	function get_datos_clase_asignacion($clase)
	{
		$clase = toba::db()->quote($clase);
		
		$sql = "SELECT 
						sga_clases.clase,
						sga_clases.fecha,
						to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha_formateada,
						sga_clases.valido,
						sga_clases.motivo_invalidacion,
						sga_clases.tema_planificado,
						sga_clases.tema_dictado,
						sga_clases.cantidad_horas_dictadas,
						sga_clases.banda_horaria,
						sga_clases.id_tramo,
						sga_clases.asistencia,
						sga_clases.modalidad,
						sga_comisiones_bh.asignacion,
						sga_comisiones_bh.tipo_clase,
						sga_asignaciones.dia_semana,
						".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
						sga_asignaciones.hora_inicio,
						sga_asignaciones.hora_finalizacion,
						to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') 		AS hora_inicio_formateada,
						to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') AS hora_finalizacion_formateada
				FROM 
						sga_clases,
						sga_comisiones_bh,
						sga_asignaciones
				WHERE
						sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria 
					AND	sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
					AND sga_clases.clase = $clase
				";
		
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el listado de clases que tienen asistencia registrada (de alumnos o docentes) para una banda horaria dada.
	 *
	 * @param integer $banda_horaria
	 * @return array
	 */
	function get_clases_con_asistencia_bh($banda_horaria, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_clases.clase
				FROM	sga_clases
				WHERE	sga_clases.banda_horaria = $banda_horaria AND
						(	sga_clases.clase IN (	SELECT	sga_clases_asistencia.clase
													FROM	sga_clases_asistencia)
							OR
							sga_clases.clase IN (	SELECT	sga_clases_ausencias_docentes.clase
													FROM	sga_clases_ausencias_docentes))
						$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de clases que tienen temas planificados para una banda horaria dada.
	 *
	 * @param integer $banda_horaria
	 * @return array
	 */
	function get_clases_con_temas_planificados_bh($banda_horaria, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_clases.clase
				FROM	sga_clases
				WHERE	sga_clases.banda_horaria = $banda_horaria AND
						sga_clases.tema_planificado IS NOT NULL
						$where
		";
		return guarani_db::consultar($sql);
	}

        
        //tengo un tema planificado y un tema dictado
        function get_clases_temas($where='')
        {
            if (! empty($where['clase'])) {
			$where_clase = " WHERE sga_clases.clase =". $where['clase'];
		}
            $sql=" select clase,tema_planificado, tema_dictado from sga_clases "
                    . "$where_clase ";
            
            return guarani_db::consultar($sql);
             
        }
        //------------------------------------------------------------------------
	//-- Tipos de Clase
	//------------------------------------------------------------------------

	/**
	 * Retorna el listado de tipos de clase.
	 *
	 * @param string $where filtro sobre los tipos de clase a retornar.
	 * @return array
	 */
	function get_tipos_clase($where='')
	{
		if (! empty($where)) {
			$where = " WHERE $where";
		}
		$sql = "SELECT		sga_clases_tipos.tipo_clase,
							sga_clases_tipos.nombre,
							sga_clases_tipos.descripcion
				FROM		sga_clases_tipos
				$where
				ORDER BY	sga_clases_tipos.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna el nombre correspondiente a un tipo de clase en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
 	  * 
      *	@param integer/array código de tipo de clase o arreglo de códigos de tipos de clase.
      * @return array
    */
	function get_nombre_tipo_clase($tipo_clase)
	{
		if (is_array($tipo_clase)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_clase, 'sga_clases_tipos', 'tipo_clase');
		} else {
			$tipo_clase = toba::db()->quote($tipo_clase);
			$filtro = "sga_clases_tipos.tipo_clase = $tipo_clase";
		}
		
		$sql = "SELECT	sga_clases_tipos.tipo_clase,
						sga_clases_tipos.nombre as tipo_clase_nombre
				FROM	sga_clases_tipos
				WHERE	$filtro
		";
		
		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de Tipo de Clase se puede usar o no.
     */
	function validar_nombre_tipo_clase($nombre, $tipo_clase)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(1) as cant
                FROM 	sga_clases_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_clases_tipos.nombre', $nombre).'
				';
		
		if (!is_null($tipo_clase)) {
			$tipo_clase = toba::db()->quote($tipo_clase);
			$sql .= " AND sga_clases_tipos.tipo_clase <> $tipo_clase ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}

	// Verifica si la clase tiene algún registro de asistencia/inasistencia de Alumnos cargado.
	function clase_con_asistencia_alumnos_cargada($clase)
	{
		$clase = toba::db()->quote($clase);
		
		$sql = "SELECT	count(1) AS cant
				FROM	sga_clases_asistencia
				WHERE	sga_clases_asistencia.clase = $clase";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] <> 0);		
	}
	
	// Verifica si la clase tiene algún registro de inasistencia de Docentes cargado.
	function clase_con_inasistencia_docentes_cargada($clase)
	{
		$clase = toba::db()->quote($clase);
		
		$sql = "SELECT	count(1) AS cant
				FROM	sga_clases_ausencias_docentes
				WHERE	sga_clases_ausencias_docentes.clase = $clase";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] <> 0);	
	}
	
	//------------------------------------------------------------------------
	//-- Docentes de Clase
	//------------------------------------------------------------------------
	
	function get_docentes_clase($clase)
	{
		$clase = toba::db()->quote($clase);
		
		$sql = "SELECT 
					sga_docentes.legajo,
					COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as nombre_persona,
				    sga_docentes_responsabilidades.nombre as responsabilidad_nombre,
					sga_clases_docentes.responsable
				
				FROM 
					sga_clases_docentes,
					sga_clases,
				    sga_comisiones_bh,
				    sga_docentes_comision,
				    sga_docentes_responsabilidades,
				    sga_docentes,
				    mdp_personas
				    
				WHERE
					sga_clases.clase = $clase
					AND sga_clases.clase = sga_clases_docentes.clase
				    AND sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
					AND sga_docentes_comision.comision = sga_comisiones_bh.comision
				    AND sga_docentes_comision.docente = sga_clases_docentes.docente
				    AND sga_docentes.docente = sga_clases_docentes.docente
				    AND sga_docentes.persona = mdp_personas.persona
				    AND sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad
				    
				ORDER BY	
					sga_docentes.legajo, 
					COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
					COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
    			";
		
		return guarani_db::consultar($sql);
	}
	
	function get_cant_clases_horas_comision_alumno($alumno, $comision)
	{
		
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);
		
		$sql = "SELECT
				    sga_comisiones_bh.comision,
				    sga_clases_asistencia.alumno,
				    sga_comisiones.asistencia_por_horas,
				    sga_comisiones.asistencia_cantidad_inasist,
				    COALESCE(sum(sga_clases.cantidad_horas_dictadas),0) as cant_horas_clases,
				    count(sga_clases.clase) as cant_clases
				FROM
				    sga_comisiones_bh,
				    sga_comisiones,
				    sga_clases,
				    sga_clases_asistencia
				WHERE	
					sga_clases_asistencia.alumno = $alumno
					AND sga_comisiones_bh.comision = $comision
					AND sga_comisiones.comision = sga_comisiones_bh.comision
				    AND sga_clases_asistencia.clase = sga_clases.clase
				    AND sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
				GROUP BY
				      sga_comisiones_bh.comision,
					  sga_comisiones.asistencia_por_horas,
					  sga_comisiones.asistencia_cantidad_inasist,
				      sga_clases_asistencia.alumno
			   ";
		
		return guarani_db::consultar_fila($sql);
	}     
	
	function get_cant_clases_banda_horaria($bh)
	{
		$bh = toba::db()->quote($bh);
		
		$sql = "SELECT	COUNT(1) as cant_clases
				FROM	sga_clases
				WHERE	sga_clases.banda_horaria = $bh
				;";
		
		$datos = guarani_db::consultar_fila($sql);
		if (!empty($datos)) {
			return $datos['cant_clases'];
		}
	}


	function get_clases_anuladas_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases.banda_horaria,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_clases_motivos_inval.nombre as motivo_nombre
				FROM		sga_comisiones_bh,
							sga_clases,
							sga_clases_tipos,
							sga_asignaciones,
							sga_clases_motivos_inval
				WHERE		sga_comisiones_bh.comision = $comision AND
							sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_asignaciones.asignacion = sga_comisiones_bh.asignacion AND
							sga_clases.valido = 0 AND
							sga_clases.motivo_invalidacion = sga_clases_motivos_inval.motivo_invalidacion
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}

	function get_clases_anuladas_subcomision($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "SELECT		sga_clases.clase,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							to_char(sga_clases.fecha,'".comunes::formato_fecha_visual."') as fecha,
							sga_clases.fecha as fecha_clase,
							to_char(sga_asignaciones.hora_inicio,'".comunes::formato_hora_visual."') as hora_inicio,
							to_char(sga_asignaciones.hora_finalizacion,'".comunes::formato_hora_visual."') as hora_finalizacion,
							sga_clases.tema_planificado,
							sga_clases.tema_dictado,
							sga_clases.cantidad_horas_dictadas,
							sga_clases.banda_horaria,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_clases_motivos_inval.nombre as motivo_nombre
				FROM		sga_subcomisiones_bh,
							sga_comisiones_bh,
							sga_clases,
							sga_clases_tipos,
							sga_asignaciones,
							sga_clases_motivos_inval
				WHERE		sga_subcomisiones_bh.subcomision = $subcomision AND
							sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase AND
							sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
							sga_asignaciones.asignacion = sga_comisiones_bh.asignacion AND
							sga_clases.valido = 0 AND
							sga_clases.motivo_invalidacion = sga_clases_motivos_inval.motivo_invalidacion
				ORDER BY	sga_clases.fecha,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
    
    function get_clases_tipos_comision($comision)
    {
		$comision = toba::db()->quote($comision);
        $sql = "SELECT		sga_clases_tipos.tipo_clase,
							sga_clases_tipos.nombre
				FROM		sga_subcomisiones
							JOIN sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase
				WHERE		sga_subcomisiones.comision = $comision
				ORDER BY	sga_clases_tipos.nombre
		";
        return guarani_db::consultar($sql);
    }
	
	/*
	 * Devuelve las clases a las que asistió el alumno, en las que estaba inscripto a cursada.
	 */
	function get_clases_alumno_asistio($alumno, $where = '')
	{	
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);
		
		$sql = "
				SELECT        
					sga_clases.clase as clase,
					sga_asignaciones.dia_semana as dia_semana,
					".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
					to_char(sga_clases.fecha, $formato_fecha_visual) as fecha,
					to_char(sga_asignaciones.hora_inicio, $formato_hora_visual) || ' a ' || to_char(sga_asignaciones.hora_finalizacion, $formato_hora_visual) as horario,
					vw_comisiones.anio_academico as anio_academico,
					vw_comisiones.periodo_lectivo as periodo_lectivo,
					vw_comisiones.comision as comision,
					vw_comisiones.comision_nombre as comision_nombre,
					vw_comisiones.elemento_nombre as elemento_nombre,
					vw_comisiones.elemento_codigo as elemento_codigo,
					vw_comisiones.elemento_nombre || ' - ' || vw_comisiones.comision_nombre as elemento_comision
				FROM
					sga_comisiones_bh,
					vw_comisiones,
					sga_clases,
					sga_asignaciones,
					sga_clases_asistencia
				WHERE   	
					sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria AND
					sga_asignaciones.asignacion = sga_comisiones_bh.asignacion AND
					sga_comisiones_bh.comision = vw_comisiones.comision AND	
					sga_clases_asistencia.clase = sga_clases.clase AND
					sga_clases_asistencia.alumno = $alumno AND
					sga_clases_asistencia.cant_inasistencias = 0
					$where
				ORDER BY
					vw_comisiones.elemento_nombre,
					vw_comisiones.comision_nombre,
					sga_clases.fecha,
					sga_asignaciones.hora_inicio
				";
		return guarani_db::consultar($sql);
	}
}