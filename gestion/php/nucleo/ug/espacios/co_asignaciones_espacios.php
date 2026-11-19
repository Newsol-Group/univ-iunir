<?php
/**
 *
 *
 * @package		Unidades de Gestion
 * @subpackage	Espacios
 */
class co_asignaciones_espacios
{
	/**
	 * Retorna un arreglo asociativo con los campos de una asignacion de espacio y
	 * los valores del que se corresponde con el índice recibido por parámetro.
	 *
	 * @param integer $asignacion (índice)
	 * @return array
	 */
	function get_datos_asignacion_espacio($asignacion)
	{
		$asignacion = toba::db()->quote($asignacion);

		$sql = "SELECT	*
				FROM	sga_espacios_asignacion
				WHERE	sga_espacios_asignacion.asignacion_espacio = $asignacion ";
		
		$resultado = guarani_db::consultar($sql);
		return $resultado[0];
	}
	
	/**
	 * Retorna la lista de edificaciones en orden alfabético.
	 *
	 * @return array
	 */
	function get_edificaciones()
	{
		$sql = "SELECT		sga_edificaciones.edificacion	AS valor,
	   						sga_edificaciones.nombre		AS descr
				FROM 		sga_edificaciones
	   			ORDER BY	sga_edificaciones.nombre ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de espacios activos de una edificación dada.
	 * Cuando se invoca especificando un id de espacio, entonces la lista
	 * resultante tiene primero ese espacio y luego el resto de los del edificio.
	 *
	 * @param integer $edificacion
	 * @param integer $espacio (opcional)
	 * @return array
	 */
	function get_espacios_activos($edificacion, $espacio = null)
	{
		$edificacion = toba::db()->quote($edificacion);
		$filtro = " sga_espacios.estado = '" . espacio::estado_activo . "' 
					AND sga_espacios.edificacion = $edificacion ";
		$sql = "";
		
		if (isset($espacio)) {
			$espacio = toba::db()->quote($espacio);
			
			$sql = "
				SELECT	valor, descr
				FROM (
						(SELECT	1 						AS orden,
								sga_espacios.espacio	AS valor,
								sga_espacios.nombre		AS descr
						FROM 	sga_espacios
						WHERE	sga_espacios.espacio = $espacio )
						UNION
						(SELECT	2 						AS orden,
								sga_espacios.espacio	AS valor,
								sga_espacios.nombre		AS descr
						FROM 	sga_espacios
						WHERE	$filtro
								AND sga_espacios.espacio <> $espacio ) 
					 ) AS resultados
				ORDER BY orden ASC, descr ";
		} else {
			$sql = "
				SELECT		sga_espacios.espacio	AS valor,
	   						sga_espacios.nombre		AS descr
				FROM 		sga_espacios
				WHERE		$filtro 
	   			ORDER BY	sga_espacios.nombre ";
		}
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los tipos de asignación de espacios.
	 * 1-Comision / 3-Examen
	 */
	function get_asignaciones_tipo()
	{
		return array(
			array(
				'valor' => asignacion_espacio::tipo_comision, 
				'descr' => asignacion_espacio::tipo_comision_descripcion
			),
			array(
				'valor' => asignacion_espacio::tipo_examen, 
				'descr' => asignacion_espacio::tipo_examen_descripcion
			)
		);
	}

	/**
	 * Retorna las estados de las asignaciones de espacios.
	 * C-Confirmada / P-Provisoria 
	 */
	function get_estado_confirmada_provisoria()
	{
		return array(
			array(
				'valor' => asignacion_espacio::estado_confirmada,
				'descr' => asignacion_espacio::estado_confirmada_descripcion
			),
			array(
				'valor' => asignacion_espacio::estado_provisoria, 
				'descr' => asignacion_espacio::estado_provisoria_descripcion
			)
		);
	}

	/**
	 * Retorna una lista de actividades pendientes, es decir:
	 *	- Actividades que no tienen asignación de espacio, ó
	 *	- Actividades que sí tienen asignación de espacio pero no completan la duración total de la actividad.
	 * que existen en la fecha y edificación especificadas, para examenes, comisiones (clases), o ambas.
	 * 
	 * @param string $fecha
	 * @param integer $edificacion
	 * @param boolean $examen
	 * @param boolean $comision
	 * @return array
	 */
	function get_actividades_pendientes($fecha, $edificacion, $examen = false, $comision = false)
	{
		$resultado = array();
		$fecha = toba::db()->quote($fecha);
		$edificacion = toba::db()->quote($edificacion);

		if ($comision) {
			$sql_comision = "
				SELECT	
					sga_clases.clase,
					get_nombre_actividad_comision(sga_comisiones.comision, 1) as elemento_nombre,
					sga_comisiones.comision,
					sga_comisiones.nombre			AS comision_nombre,
					sga_subcomisiones.nombre		AS subcomision_nombre,
					sga_asignaciones.asignacion,
					sga_asignaciones.hora_inicio,
					sga_asignaciones.hora_finalizacion
				FROM 	
					sga_clases
					JOIN sga_comisiones_bh	ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
					JOIN sga_comisiones		ON sga_comisiones.comision = sga_comisiones_bh.comision
					JOIN sga_asignaciones	ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
					JOIN sga_edificaciones	ON sga_comisiones.ubicacion = sga_edificaciones.ubicacion
					JOIN f_asig_espacio_clase_completa(sga_clases.clase, sga_asignaciones.asignacion) ON TRUE
					LEFT JOIN sga_subcomisiones ON sga_subcomisiones.comision = sga_comisiones.comision
				WHERE sga_clases.fecha = $fecha
				  AND sga_edificaciones.edificacion = $edificacion
				  AND (f_asig_espacio_clase_completa IS NULL OR f_asig_espacio_clase_completa = '0')
				ORDER BY elemento_nombre";

			$resultado[asignacion_espacio::tipo_comision] = guarani_db::consultar($sql_comision);
		}

		if ($examen) {
			$sql_examen = "
				SELECT 	
					sga_mesas_examen.mesa_examen,
					get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as elemento_nombre,
					sga_llamados_mesa.llamado_mesa,
					sga_mesas_examen.nombre,
					sga_llamados_mesa.hora_inicio,
					sga_llamados_mesa.hora_finalizacion,
					sga_llamados_mesa.fecha  
				FROM	
					sga_mesas_examen 
					JOIN sga_llamados_mesa	ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
					JOIN sga_edificaciones	ON sga_mesas_examen.ubicacion = sga_edificaciones.ubicacion
					JOIN f_asig_espacio_examen_completa(sga_llamados_mesa.llamado_mesa) ON TRUE
				WHERE sga_llamados_mesa.fecha = $fecha
				  AND sga_edificaciones.edificacion = $edificacion
				  AND (f_asig_espacio_examen_completa IS NULL OR f_asig_espacio_examen_completa = '0')
				ORDER BY elemento_nombre";

			$resultado[asignacion_espacio::tipo_examen] = guarani_db::consultar($sql_examen);
		}

		return $resultado;
	}

	/**
	 * Retorna una lista de actividades asignadas en la fecha y edificación 
	 * especificadas, para examenes, comisiones (clases), o ambas.
	 * 
	 * @param string $fecha
	 * @param integer $edificacion
	 * @param boolean $examen
	 * @param boolean $comision
	 * @return array
	 */
	function get_actividades_asignadas($fecha, $edificacion, $examen = false, $comision = false)
	{
		$resultado = array();
		
		if($examen && $comision) {
			$clases = $this->get_clases_asignadas($fecha, $edificacion);
			$examenes = $this->get_examenes_asignados($fecha, $edificacion);
			
			$resultado = array_merge($clases, $examenes);
		} else if($examen) {
			$resultado = $this->get_examenes_asignados($fecha, $edificacion);
		} else {
			$resultado = $this->get_clases_asignadas($fecha, $edificacion);
		}
		
		return $resultado;
	}
	
	/**
	 * Retorna una lista de clases asignadas en la fecha y edificación especificadas.
	 * 
	 * @param string $fecha
	 * @param integer $edificacion
	 * @return array
	 */
	function get_clases_asignadas($fecha, $edificacion)
	{
		$edificacion = toba::db()->quote($edificacion);
		$fecha = toba::db()->quote($fecha);
				
		$sql = "
			SELECT 
				sga_espacios_asignacion.asignacion_espacio,
				sga_espacios_asignacion.asignacion_tipo,
				sga_comisiones.comision,
				sga_comisiones.nombre				AS comision_nombre,
				get_nombre_actividad_comision(sga_comisiones.comision, 1) as elemento_nombre,
				get_nombre_actividad_comision(sga_comisiones.comision, 2) as elemento_nombre_abrev,
				sga_espacios_asignacion.clase, 
				sga_espacios_asignacion.llamado_mesa,
				sga_espacios_asignacion.hora_inicio,
				sga_espacios_asignacion.hora_finalizacion,
				sga_espacios_asignacion.espacio,
				sga_espacios_asignacion.estado,
				sga_espacios_asignacion.fecha,
				sga_espacios.nombre					AS espacio_nombre,
				sga_espacios.capacidad,
				sga_comisiones_bh.asignacion
			FROM sga_espacios_asignacion
			JOIN sga_espacios		ON sga_espacios.espacio = sga_espacios_asignacion.espacio
			JOIN sga_clases			ON sga_clases.clase = sga_espacios_asignacion.clase
			JOIN sga_comisiones_bh	ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
			JOIN sga_comisiones		ON sga_comisiones.comision = sga_comisiones_bh.comision
			WHERE sga_espacios.edificacion = $edificacion
			  AND sga_espacios_asignacion.fecha = $fecha
			  -- AND sga_espacios_asignacion.llamado_mesa IS NULL 
			  AND sga_espacios_asignacion.asignacion_tipo = 1 -- Comision
			ORDER BY elemento_nombre, comision_nombre, hora_inicio ";
			
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna una lista de examenes asignados en la fecha y edificación especificadas.
	 * 
	 * @param string $fecha
	 * @param integer $edificacion
	 * @return array
	 */
	function get_examenes_asignados($fecha, $edificacion)
	{
		$edificacion = toba::db()->quote($edificacion);
		$fecha = toba::db()->quote($fecha);
				
		$sql = "
			SELECT 
				sga_espacios_asignacion.asignacion_espacio,
				sga_espacios_asignacion.asignacion_tipo,
				sga_mesas_examen.mesa_examen,
				sga_mesas_examen.nombre				AS examen_nombre,
				get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as elemento_nombre,
				get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 2) as elemento_nombre_abrev,
				sga_espacios_asignacion.clase, 
				sga_espacios_asignacion.llamado_mesa,
				sga_espacios_asignacion.hora_inicio,
				sga_espacios_asignacion.hora_finalizacion,
				sga_espacios_asignacion.espacio,
				sga_espacios_asignacion.estado,
				sga_espacios_asignacion.fecha,
				sga_espacios.nombre					AS espacio_nombre,
				sga_espacios.capacidad
			FROM sga_espacios_asignacion
			JOIN sga_espacios		ON sga_espacios.espacio = sga_espacios_asignacion.espacio
			JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = sga_espacios_asignacion.llamado_mesa
			JOIN sga_mesas_examen	ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
			WHERE sga_espacios.edificacion = $edificacion
			  AND sga_espacios_asignacion.fecha = $fecha
			  -- AND sga_espacios_asignacion.clase IS NULL
			  AND sga_espacios_asignacion.asignacion_tipo = 3 -- Mesas de Examen.
			ORDER BY elemento_nombre ";
			
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna un arreglo con los detalles de una comisión:
	 * - Nombres de la materia (elemento) y la comisión.
	 * - Nombres de los docentes
	 * - La cantidad de alumnos inscriptos
	 * - Las comisiones con las que comparte banda horaria (puede ser un arreglo vacío).
	 * 
	 * @param integer $comision
	 * @param integer $asignacion
	 * 
	 * @return array de la forma ['nombres'=>[...],'docentes'=>[...],'cantidad'=>Integer,'comparte_bh'=>[]]
	 */
	function get_datos_detalles_comision($comision, $asignacion)
	{
		$resultado = array();
		$comision = toba::db()->quote($comision);
		$asignacion = toba::db()->quote($asignacion);

		$sql_nombres = "
			SELECT get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad,
				sga_comisiones.nombre	AS comision
			FROM sga_comisiones
			WHERE sga_comisiones.comision = $comision ";

		$sql_docentes = "
			SELECT	 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS apellido_nombres    
			FROM sga_docentes_comision
			JOIN sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
			JOIN mdp_personas ON mdp_personas.persona = sga_docentes.persona
			WHERE sga_docentes_comision.comision = $comision ";

		$sql_cantidad = "
			SELECT	count(1) as cantidad
			FROM	sga_insc_cursada 
			WHERE	sga_insc_cursada.comision = $comision";

		$sql_comparte_bh = "
			SELECT	sga_comisiones.nombre		AS comision_nombre,
					sga_subcomisiones.nombre	AS subcomision_nombre
			FROM sga_comisiones_bh
			JOIN sga_comisiones	ON sga_comisiones_bh.comision = sga_comisiones.comision
			LEFT JOIN sga_subcomisiones ON sga_subcomisiones.comision = sga_comisiones.comision
			WHERE sga_comisiones_bh.asignacion = $asignacion
			  AND sga_comisiones.comision <> $comision";

		$nombres = guarani_db::consultar($sql_nombres);
		$resultado['nombres'] = $nombres[0];
		$resultado['docentes'] = guarani_db::consultar($sql_docentes);
		$cantidad = guarani_db::consultar($sql_cantidad);
		$resultado['cantidad'] = $cantidad[0]['cantidad'];
		$resultado['comparte_bh'] = guarani_db::consultar($sql_comparte_bh);

		return $resultado;
	}

	function get_detalle_comision($comision, $asignacion)
	{
		$resultado = toba::consulta_php('co_asignaciones_espacios')->get_datos_detalles_comision($comision, $asignacion);
		$detalle = "";
		
		if(guarani_arreglos::count($resultado) > 0) {
			$detalle = "Actividad: " . $resultado['nombres']['actividad'];
			$detalle .= " - Comisión: " . $resultado['nombres']['comision'];
			$detalle .= "\r\n";

			$detalle .= "Total de alumnos: " . $resultado['cantidad'];

			if (guarani_arreglos::count($resultado['docentes']) > 0) {
				$detalle .= " - Profesores: ";
				foreach ($resultado['docentes'] as $docente) {
					$detalle .= $docente['apellido_nombres'] . ', ';
				}
				$detalle = substr($detalle, 0, strlen($detalle) - 2);
			} else {
				$detalle .= ' - No tiene profesores asignados.';
			}

			$detalle .= "\r\n";

			if (guarani_arreglos::count($resultado['comparte_bh']) > 0) {
				$detalle .= 'Comparte banda horaria con: ';
				foreach ($resultado['comparte_bh'] as $com) {
					$subcomision = '';
					if(isset($com['subcomision_nombre'])) { 
						$subcomision = " - " . $com['subcomision_nombre'];
					}
					$detalle .= $com['comision_nombre'] . $subcomision . ', ';
				}
				$detalle = substr($detalle, 0, strlen($detalle) - 2);
			} else {
				$detalle .= 'No comparte banda horaria con otra comisión.';
			}
		}
		
		return $detalle;
	}
	
	/**
	 * Retorna un arreglo con los detalles de un examen:
	 * - Nombres de la materia (elemento) y del examen.
	 * - Nombres de los docentes
	 * - La cantidad de alumnos inscriptos
	 * 
	 * @param integer $llamado_mesa
	 * 
	 * @return array
	 */
	function get_datos_detalles_examen($llamado_mesa)
	{
		$resultado = array();
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		$sql_nombres = "
			SELECT	get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen,1) as actividad,
					sga_mesas_examen.nombre as examen
			FROM	sga_llamados_mesa
					JOIN sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
			WHERE	sga_llamados_mesa.llamado_mesa = $llamado_mesa";

		$sql_docentes = "
			SELECT	 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS apellido_nombres    
			FROM	mdp_personas 
					JOIN sga_docentes ON mdp_personas.persona = sga_docentes.persona
					JOIN sga_docentes_mesa_llamado ON sga_docentes.docente = sga_docentes_mesa_llamado.docente
			WHERE	sga_docentes_mesa_llamado.llamado_mesa = $llamado_mesa ";

		$nombres = guarani_db::consultar($sql_nombres);
		$resultado['nombres'] = $nombres[0];
		$resultado['docentes'] = guarani_db::consultar($sql_docentes);
		$resultado['cantidad'] = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_un_llamado_mesa(trim($llamado_mesa, "'"));

		return $resultado;
	}
 
	function get_detalle_examen($llamado_mesa)
	{
		$resultado = toba::consulta_php('co_asignaciones_espacios')->get_datos_detalles_examen($llamado_mesa);
		$detalle = "";
		
		if(guarani_arreglos::count($resultado) > 0) {
			$detalle = "Actividad: " . $resultado['nombres']['actividad'];
			$detalle .= " - Examen: " . $resultado['nombres']['examen'];
			$detalle .= "\r\n";

			$detalle .= "Total de alumnos: " . $resultado['cantidad'];
			$detalle .= "\r\n";

			if (guarani_arreglos::count($resultado['docentes']) > 0) {
				$detalle .= "Profesores: ";
				foreach ($resultado['docentes'] as $docente) {
					$detalle .= $docente['apellido_nombres'] . ', ';
				}
				$detalle = substr($detalle, 0, strlen($detalle) - 2);
			} else {
				$detalle .= 'No tiene profesores asignados.';
			}
		}
		
		return $detalle;
	}
	
	/**
	 * Retorna un arreglo (de una entrada) con la fecha_hasta de una comisión.
	 * 
	 * @param integer $comision
	 * 
	 * @return array
	 */
	function get_fecha_hasta_comision($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "
			SELECT DISTINCT sga_asignaciones.fecha_hasta
			FROM sga_comisiones_bh 
			JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
			WHERE sga_comisiones_bh.comision = $comision ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna un arreglo (de una entrada) con la hora_inicio de una actividad.
	 * 
	 * @param array $actividad
	 * Posibles formas del parámetro: 
	 *		['tipo'=>'1', 'asignacion'=>'id_asignacion']
	 *	ó	['tipo'=>'3', 'llamado_mesa'=>'id_llamado_mesa']
	 * 
	 * @return array
	 */
	function get_hora_inicio($actividad)
	{
		$resultado = null;

		if ($actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$asignacion = toba::db()->quote($actividad['asignacion']);

			$sql = "
				SELECT	sga_asignaciones.hora_inicio
				FROM	sga_asignaciones
				WHERE	sga_asignaciones.asignacion = $asignacion ";
			
			$resultado = guarani_db::consultar($sql);
		} else { // es de tipo examen
			$llamado_mesa = toba::db()->quote($actividad['llamado_mesa']);

			$sql = "
				SELECT	sga_llamados_mesa.hora_inicio
				FROM	sga_llamados_mesa
				WHERE	sga_llamados_mesa.llamado_mesa = $llamado_mesa ";

			$resultado = guarani_db::consultar($sql);
		}

		return $resultado;
	}
	
	/**
	 * Retorna un arreglo (de una entrada) con la hora_finalizacion de una actividad.
	 * 
	 * @param array $actividad
	 * Posibles formas del parámetro: 
	 *		['tipo'=>'1', 'asignacion'=>'id_asignacion']
	 *	ó	['tipo'=>'3', 'llamado_mesa'=>'id_llamado_mesa']
	 * 
	 * @return array
	 */
	function get_hora_fin($actividad)
	{
		$resultado = null;

		if ($actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$asignacion = toba::db()->quote($actividad['asignacion']);

			$sql = "
				SELECT	sga_asignaciones.hora_finalizacion
				FROM	sga_asignaciones
				WHERE	sga_asignaciones.asignacion = $asignacion ";
			
			$resultado = guarani_db::consultar($sql);
		} else { // es de tipo examen
			$llamado_mesa = toba::db()->quote($actividad['llamado_mesa']);

			$sql = "
				SELECT	sga_llamados_mesa.hora_finalizacion
				FROM	sga_llamados_mesa
				WHERE	sga_llamados_mesa.llamado_mesa = $llamado_mesa ";

			$resultado = guarani_db::consultar($sql);
		}

		return $resultado;
	}
	
	/**
	 * Retorna un arreglo con las clases de una asignacion entre las fechas especificadas.
	 * Si se especifica una comisión, sus clases serán excluidas y se retornarán las clases 
	 * de otras comisiones distintas a la que tengan la misma asignacion que ésta,
	 * es decir, que compartan la banda horaria.
	 * 
	 * @param integer $asignacion
	 * @param string $fecha_desde
	 * @param string $fecha_hasta
	 * @param integer $comision
	 * 
	 * @return array
	 */
	function get_clases_de_otras_comisiones_de_la_bh($asignacion, $fecha_desde, $fecha_hasta, $comision = null ) 
	{
		$where = "";
		$asignacion = toba::db()->quote($asignacion);
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		
		if(isset($comision)) {
			$comision = toba::db()->quote($comision);
			$where .= "AND sga_comisiones_bh.comision <> $comision";
		}
		
		$sql = "
			SELECT	sga_clases.clase,
					sga_clases.fecha
			FROM sga_comisiones_bh
			JOIN sga_clases ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
			WHERE sga_comisiones_bh.asignacion = $asignacion
			  AND sga_clases.fecha >= $fecha_desde
			  AND sga_clases.fecha <= $fecha_hasta
			$where";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna un arreglo con las asignaciones de espacios para las 
	 * clases de una asignacion entre las fechas especificadas.
	 * Útil para los casos de bandas horarias compartidas.
	 * 
	 * @param integer $asignacion
	 * @param string $fecha_desde
	 * @param string $fecha_hasta
	 * @param string $hora_inicio (opcional)
	 * @param string $hora_fin (opcional)
	 * 
	 * @return array
	 */
	function get_asig_espacios_de_comisiones_que_comparten_bh($asignacion, $fecha_desde, $fecha_hasta, $hora_inicio = null, $hora_fin = null) 
	{
		$asignacion = toba::db()->quote($asignacion);
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		
		$filtro_hora_inicio = '';
		$filtro_hora_fin = '';
		
		if(isset($hora_inicio)) {
			$hora_inicio = toba::db()->quote($hora_inicio);
			$filtro_hora_inicio = "AND sga_espacios_asignacion.hora_inicio = $hora_inicio";
		}
		if(isset($hora_fin)) {
			$hora_fin = toba::db()->quote($hora_fin);
			$filtro_hora_fin = "AND sga_espacios_asignacion.hora_finalizacion = $hora_fin";
		}
		
		$sql = "
			SELECT	asignacion_espacio, clase, fecha
			FROM	sga_espacios_asignacion
			WHERE	clase IN (SELECT sga_clases.clase
					            FROM sga_comisiones_bh
					            JOIN sga_clases ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
					            WHERE sga_comisiones_bh.asignacion = $asignacion
							      AND sga_clases.fecha >= $fecha_desde
							      AND sga_clases.fecha <= $fecha_hasta)
			$filtro_hora_inicio
			$filtro_hora_fin";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna un arreglo con las asignaciones de espacios de otras comisiones 
	 * distintas a la recibida como parámetro, que compartan la banda horaria.
	 * 
	 * @param integer $asignacion
	 * @param integer $comision
	 * @param string $hora_inicio
	 * @param string $hora_fin
	 * @param string $fecha (opcional)
	 * 
	 * @return array
	 */
	function get_asignaciones_de_otras_comisiones_de_la_bh($asignacion, $comision, $hora_inicio, $hora_fin, $fecha = null) 
	{
		$where = "";
		$asignacion = toba::db()->quote($asignacion);
		$comision = toba::db()->quote($comision);
		$hora_inicio = toba::db()->quote($hora_inicio);
		$hora_fin = toba::db()->quote($hora_fin);
		
		if(isset($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND sga_espacios_asignacion.fecha = $fecha ";
		} 
		
		$sql = "
			SELECT	sga_espacios_asignacion.asignacion_espacio,	
					sga_espacios_asignacion.clase, 
					sga_espacios_asignacion.fecha
			FROM sga_comisiones_bh
			JOIN sga_clases ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria 
			LEFT JOIN sga_espacios_asignacion ON sga_espacios_asignacion.clase = sga_clases.clase
			WHERE sga_comisiones_bh.asignacion = $asignacion
			  AND sga_comisiones_bh.comision <> $comision
			  AND sga_espacios_asignacion.hora_inicio = $hora_inicio
			  AND sga_espacios_asignacion.hora_finalizacion = $hora_fin
			 $where ";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna la cantidad de inscriptos a una actividad.
	 * 
	 * @param integer $tipo
	 * @param integer $id (es el id de asignación, si el tipo es comision, o el id de llamado mesa, si el tipo es examen)
	 * 
	 * @return integer
	 */
	function get_cant_inscriptos($tipo, $id)
	{
		$sql = "";
		
		if($tipo == asignacion_espacio::tipo_comision) {
			$sql = "
				SELECT	COUNT(1) as cantidad
				FROM	sga_insc_cursada
				WHERE	sga_insc_cursada.comision IN (
							SELECT	comision
							FROM	sga_comisiones_bh 
							WHERE	asignacion = $id
						)";
		} else {
			$sql = "
				SELECT	COUNT(1) as cantidad
				FROM	sga_insc_examen
				WHERE	sga_insc_examen.llamado_mesa = $id";
		}
		
		$cantidad = guarani_db::consultar($sql);
		
		return $cantidad[0]['cantidad'];
	}
	
	/**
	 * Retorna los datos que necesita el visualizador para que el ci_edi lo procese y lo estructure acordemente.
	 * 
	 * @return array de la forma ['asignaciones'=>[...],'espacios'=>[...]]
	 */
	function get_datos_visualizador($fecha, $edificacion, $examen = false, $comision = false)
	{
		$resultado = array();
		$resultado['asignaciones'] = $this->get_actividades_asignadas($fecha, $edificacion, $examen, $comision);
		
		$edificacion = toba::db()->quote($edificacion);
		$sql = "SELECT DISTINCT
						nombre,
						COALESCE(capacidad::text, '-') AS capacidad,
						accesible
				FROM	sga_espacios
				WHERE	edificacion = $edificacion
				ORDER BY nombre ";
		$sql_espacios = guarani_db::consultar($sql);
		
		$espacios = array();
		foreach($sql_espacios as $esp) {
			$entry = ['nombre'=> utf8_encode($esp['nombre']), 'capacidad'=>$esp['capacidad'], 'accesible'=>$esp['accesible']];
			array_push($espacios, $entry);
		}
		$resultado['espacios'] = $espacios;
		
		return $resultado;
	}

	/**
	 * Retorna el detalle de ocupación de aulas por día de la semana.
	 *
	 * @param string $where filtro sobre el detalle a retornar.
	 * @return array
	 */
	function get_distribucion_comisiones_en_edificacion($edificacion, $periodo_lectivo)
	{
		// Se obtienen todas las aulas de la edificación seleccionada.
		$aulas = toba::consulta_php('co_espacios')->get_aulas_edificacion($edificacion);
		$datos = rs_convertir_asociativo_matriz($aulas, array('espacio'), array('nombre'));
		
		$sql = "SELECT DISTINCT
					sga_asignaciones.dia_semana,
					sga_espacios_asignacion.hora_inicio,
					sga_espacios_asignacion.hora_finalizacion,
					sga_espacios.espacio,
					'(' || get_nombre_actividad_comision(sga_comisiones.comision, 2) || ') ' || sga_comisiones.nombre AS comision_nombre
				FROM sga_comisiones
				JOIN sga_comisiones_bh ON sga_comisiones.comision = sga_comisiones_bh.comision
				JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				JOIN sga_clases ON sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
				JOIN sga_espacios_asignacion ON sga_espacios_asignacion.clase = sga_clases.clase
				JOIN sga_espacios ON sga_espacios.espacio = sga_espacios_asignacion.espacio
				WHERE sga_espacios.edificacion = $edificacion
				  AND sga_comisiones.periodo_lectivo = $periodo_lectivo
		";
		
		$rs = guarani_db::consultar($sql);
		
		foreach ($rs as $r) {
			$descripcion = substr($r['hora_inicio'], 0, 5) . " a " . substr($r['hora_finalizacion'], 0, 5) . ": {$r['comision_nombre']}";
			
			if(isset($datos[$r['espacio']][$r['dia_semana']])) {
				array_push($datos[$r['espacio']][$r['dia_semana']], $descripcion);	
			} else {
				$datos[$r['espacio']][$r['dia_semana']] = [$descripcion];
			}
		}
		
		foreach ($datos as $espacio => $dias) {
			foreach($dias as $dia => $asignaciones) {
				if($dia != 'nombre') {
					$descripcion = '';
					asort($asignaciones);
					foreach ($asignaciones as $asignacion) {
						$descripcion .= $asignacion . '<br>';
					}
					$datos[$espacio][$dia] = $descripcion;
				}
			}
		} 
		
		return $datos;
	}
	
	/**
	 * Retorna el detalle de ocupación de aulas por día de la semana.
	 *
	 * @param string $where filtro sobre el detalle a retornar.
	 * @return array
	 */
	function get_asignaciones_espacios_por_comision($comision, $banda_horaria)
	{
		$comision = toba::db()->quote($comision);
		$banda_horaria = toba::db()->quote($banda_horaria);
		
		$sql = "
			SELECT
				sga_espacios_asignacion.fecha,
				sga_espacios_asignacion.hora_inicio,
				sga_espacios_asignacion.hora_finalizacion,
				sga_espacios.nombre,
				f_asig_espacio_clase_completa(sga_clases.clase, sga_comisiones_bh.asignacion) AS completa
			FROM sga_comisiones_bh
			JOIN sga_clases ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
			JOIN sga_espacios_asignacion ON sga_espacios_asignacion.clase = sga_clases.clase
			JOIN sga_espacios ON sga_espacios.espacio = sga_espacios_asignacion.espacio
			WHERE sga_comisiones_bh.comision = $comision 
			  AND sga_comisiones_bh.banda_horaria = $banda_horaria
			ORDER BY fecha, hora_inicio";
		
		$datos = guarani_db::consultar($sql);
		
		$fechas = array_column($datos, 'fecha');
		$fechas = array_unique($fechas);
		
		$asignaciones = [];
		foreach ($fechas as $fecha) {
			$asignaciones[$fecha] = [];
		}
		
		foreach ($datos as $dato) {
			array_push($asignaciones[$dato['fecha']], $dato);
		}
		
		$result = "";
		if (guarani_arreglos::count($asignaciones) > 0) {
			$result .= "Asignaciones de espacios de las clases pertenecientes a la banda horaria de la comisión seleccionada:\r\n";
			foreach ($asignaciones as $asignaciones_fecha) {
				$result .= "\t" . guarani_fecha::convertir_fecha_formato_frontend($asignaciones_fecha[0]['fecha']);
				$result .= ($asignaciones_fecha[0]['completa']) ? ' (Completa): ' : ' (Incompleta): '; 
				foreach($asignaciones_fecha as $fecha) {
					$result .= $fecha['nombre'] . ' de ' . substr($fecha['hora_inicio'], 0, 5) . ' a ' . substr($fecha['hora_finalizacion'], 0, 5) . ', ';
				}
				$result[strlen($result)-2] = '.';
				$result .= "\r\n";
			}
		} else {
			$result = "Ninguna clase de la comisión tiene asignado un espacio físico.";
		}
		
		return $result;
	}
	
	
	function validar_duracion_clase($hora_inicio, $hora_fin, $clase, $asignacion, $asignacion_espacio = null) 
	{
		// si se especificó una asignación de espacio, entonces se trata de una modificación y no debe ser tenida en cuenta
		if(isset($asignacion_espacio)) {
			$asignacion_espacio = toba::db()->quote($asignacion_espacio);
			
			$sql = "SELECT f_duracion_asig_espacio_clase_valida($hora_inicio, $hora_fin, $clase, $asignacion, $asignacion_espacio) as duracion_valida";
		} else {
			$sql = "SELECT f_duracion_asig_espacio_clase_valida($hora_inicio, $hora_fin, $clase, $asignacion) as duracion_valida";
		}
		
		$duracion_valida = guarani_db::consultar($sql);
		
		return $duracion_valida[0]['duracion_valida'] == '1';
	}
	
	function validar_duracion_examen($hora_inicio, $hora_fin, $llamado_mesa, $asignacion_espacio = null) 
	{
		// si se especificó una asignación de espacio, entonces se trata de una modificación y no debe ser tenida en cuenta
		if(isset($asignacion_espacio)) {
			$asignacion_espacio = toba::db()->quote($asignacion_espacio);
			
			$sql = "SELECT f_duracion_asig_espacio_examen_valida($hora_inicio, $hora_fin, $llamado_mesa, $asignacion_espacio) as duracion_valida";
		} else {
			$sql = "SELECT f_duracion_asig_espacio_examen_valida($hora_inicio, $hora_fin, $llamado_mesa) as duracion_valida";
		}
		
		$duracion_valida = guarani_db::consultar($sql);
		
		return $duracion_valida[0]['duracion_valida'] == '1';
	}
	
	function validar_duracion_clases($hora_inicio, $hora_fin, $clases, $asignacion, $asignaciones_espacio = null) 
	{
		$hora_inicio = toba::db()->quote($hora_inicio);
		$hora_fin = toba::db()->quote($hora_fin);
		$clases = guarani_sql::armar_condicion_in($clases, 'sga_clases', 'clase');
		
		// si se especificó una asignación de espacio, entonces se trata de una modificación y no debe ser tenida en cuenta
		if(isset($asignaciones_espacio)) {
			$asignaciones_espacio = guarani_sql::armar_condicion_in($asignaciones_espacio, 'sga_espacios_asignacion', 'asignacion_espacio');
			
			$sql = "
				SELECT	f_duracion_asig_espacio_clase_valida($hora_inicio, $hora_fin, sga_espacios_asignacion.clase, $asignacion, sga_espacios_asignacion.asignacion_espacio) as duracion_valida
				FROM	sga_espacios_asignacion
				WHERE	$asignaciones_espacio
			";
		} else {
			$sql = "
				SELECT	f_duracion_asig_espacio_clase_valida($hora_inicio, $hora_fin, clase, $asignacion) as duracion_valida
				FROM	sga_clases
				WHERE	$clases
			";
		}
		
		$datos = guarani_db::consultar($sql);
		
		$duracion_valida = array_unique(array_values(array_column($datos, 'duracion_valida')));
		
		if(guarani_arreglos::count($duracion_valida) > 1) {
			$duracion_valida = false;
		} else {
			$duracion_valida = $duracion_valida[0] == '1';
		}
		
		return $duracion_valida;
	}
	
	/**
	 * Es una consulta que busca ocurrencias con el fin de detectar solapamientos.
	 * Solapamientos sobre qué, depende de la condición recibida por parámetro
	 * (condiciones sobre espacios, fechas, clases, llamados de mesa, asignaciones de espacios)
	 * de modo que se encuentren resultados al validar horarios de clases, examenes, disponibilidad
	 * de espacios, tanto en ALTAS como en MODIFICACIONES.
	 */
	function obtener_disponibilidad($hora_ini, $hora_fin, $condicion) 
	{
		$hora_ini = toba::db()->quote($hora_ini);
		$hora_fin = toba::db()->quote($hora_fin);
		
		$sql = "( --  ----[--(--)--]----
					SELECT	sga_espacios_asignacion.asignacion_tipo
					FROM	sga_espacios_asignacion
					WHERE	$condicion
							AND sga_espacios_asignacion.hora_inicio < $hora_fin
							AND sga_espacios_asignacion.hora_finalizacion > $hora_ini
				)
				UNION
				( --  --(-[-)------]----
					SELECT	sga_espacios_asignacion.asignacion_tipo
					FROM	sga_espacios_asignacion
					WHERE	$condicion
							AND sga_espacios_asignacion.hora_inicio > $hora_ini
							AND sga_espacios_asignacion.hora_inicio < $hora_fin
				)
				UNION
				( --  ----[------(-]-)--
					SELECT	sga_espacios_asignacion.asignacion_tipo
					FROM	sga_espacios_asignacion
					WHERE	$condicion
							AND sga_espacios_asignacion.hora_finalizacion > $hora_ini
							AND sga_espacios_asignacion.hora_finalizacion < $hora_fin
				)";
		
		return guarani_db::consultar($sql);
	}
}
?>