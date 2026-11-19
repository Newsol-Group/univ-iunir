<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 */
use SIUToba\rest\lib\rest_hidratador;
use SIU\Guarani\php\rest\v1\comisiones\recurso_comisiones;

class co_comisiones
{
	/**
	 * Obtiene una lista de comisiones según el filtro recibido
	 */
	function get_listado($where = '', $select = '', $order = '', $limit = '', $visualizar_cant_evaluaciones = false)
	{
		if ($where) {
			$where = "WHERE $where";
		}

		if ($select) {
			$select = ', ' . $select;
		}

		if (!$order) {
			$order = 'sga_comisiones.nombre';
		}
		
		$cant_evaluaciones = "";
		if ($visualizar_cant_evaluaciones) {
			$cant_evaluaciones = ", (select count(1) 
			                         from sga_evaluaciones as e 
			                         join sga_evaluaciones_tipos as et on et.evaluacion_tipo = e.evaluacion_tipo
			                         where e.entidad = sga_comisiones.entidad
			                         and et.automatica = '".comunes::no ."') as cant_evaluaciones";
		}
		
		// Listado de comisiones
		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre,
							sga_comisiones.periodo_lectivo,
							sga_comisiones.elemento,
							sga_comisiones.turno,
							sga_comisiones.entidad,

							sga_comisiones.catedra,
							sga_comisiones.letra_desde,
							sga_comisiones.letra_hasta,
							sga_comisiones.cupo,
							" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_habilitada', 'inscripcion_habilitada') . ",
							sga_comisiones.ubicacion,
							sga_periodos.nombre	as				nombre_periodo,
							sga_periodos.anio_academico	as		anio_academico_periodo,
							sga_periodos.fecha_inicio as		fecha_inicio_periodo,
							sga_elementos.codigo as				codigo_actividad,
							get_nombre_actividad_comision(sga_comisiones.comision, 1) as nombre_actividad,
							sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
							get_nombre_actividad_comision(sga_comisiones.comision, 2) as nombre_abreviado_actividad,
							sga_turnos_cursadas.nombre as		nombre_turno,

							f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
							f_instancias_comision(sga_comisiones.comision) 		  as nombre_instancias,
							f_instancias_comision_id(sga_comisiones.comision) 	  as id_instancias,
								
							sga_catedras.nombre as				nombre_catedra,
							sga_ubicaciones.nombre as			nombre_ubicacion,
							sga_periodos_lectivos.fecha_inicio_dictado as fecha_inicio_periodo_lectivo,
							sga_periodos_lectivos.fecha_fin_dictado as fecha_fin_periodo_lectivo,
							(SELECT	COUNT	(sga_subcomisiones.subcomision)
									FROM	sga_subcomisiones
									WHERE	sga_subcomisiones.comision = sga_comisiones.comision
							) AS cant_subcomisiones
							$select
							$cant_evaluaciones
				FROM		sga_comisiones
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno									
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				$where
				ORDER BY	$order
				$limit
		";

		return guarani::db()->consultar($sql);
	}

	/**
	 * Obtiene una lista de las Actividades activas y disponibles para comisiones, con los datos de sus comisiones y sus 
	 * subcomisiones asociadas, según el filtro recibido
	 */
	function get_listado_comisiones_subcomisiones($where = '', $select = '', $order = '')
	{
		if ($where) {
			$where = 'AND ' . $where;
		}

		if ($select) {
			$select = ', ' . $select;
		}

		if (!$order) {
			$order = 'actividad_nombre, comision_nombre, subcomision_nombre, orden_dia_semana, horario';
		}

		// Datos de la Actividad, Período Lectivo y Comisión
		$sql = "SELECT		-- Comisiones SIN Subcomisiones.
							vw_actividades.elemento,
							vw_actividades.codigo as actividad_codigo,
							vw_actividades.nombre as actividad_nombre,
							'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_desc,
							vw_periodos_lectivos.anio_academico, 
							vw_periodos_lectivos.periodo_lectivo, 
							vw_periodos_lectivos.nombre as periodo_nombre,
							sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre, 
							sga_comisiones.ubicacion, 
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_comisiones.turno as turno,
							sga_turnos_cursadas.nombre as turno_nombre,
							sga_comisiones_cupo.cupo as comision_cupo,
							sga_comisiones_cupo.cant_inscriptos as comision_inscriptos,
							f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
					
							-- Datos de la Asignación de la Comisión.
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_asignaciones.dia_semana as dia_semana,
							CASE sga_asignaciones.dia_semana
								WHEN 'Lunes' THEN 1
								WHEN 'Martes' THEN 2
								WHEN 'Miercoles' THEN 3
								WHEN 'Jueves' THEN 4
								WHEN 'Viernes' THEN 5
								WHEN 'Sabado' THEN 6
								WHEN 'Domingo' THEN 7
							END as orden_dia_semana,
							" . guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()) . ",
				 			to_char(sga_asignaciones.hora_inicio, '" . comunes::formato_hora_visual . "') || ' a ' || to_char(sga_asignaciones.hora_finalizacion, '" . comunes::formato_hora_visual . "') as horario,
							get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 2) as espacio,
							get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria) as espacio_nombre,

							-- Datos de las Subcomisiones (no existen pero se unifica para poder realizar el Union)
							CAST(null as integer) as subcomision,
							CAST(null as varchar) as subcomision_nombre,
							CAST(null as integer) as subcomision_cupo,
							CAST(null as integer) as subcomision_inscriptos,
							f_docentes_comision(sga_comisiones.comision, true) as docentes_resp,
							
							sga_comisiones.nombre as com_subcom_nombre,
							sga_comisiones.comision || '-0' as com_subcom_codigo
								
				FROM		vw_actividades
							JOIN sga_comisiones ON vw_actividades.elemento = sga_comisiones.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
							LEFT JOIN sga_comisiones_cupo ON sga_comisiones.comision = sga_comisiones_cupo.comision
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_comisiones_bh ON sga_comisiones.comision = sga_comisiones_bh.comision
							LEFT JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase
							LEFT JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
							
				WHERE		vw_actividades.estado = '" . elemento::estado_activo . "' AND
							vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "') AND
							NOT EXISTS (SELECT	1 
										FROM	sga_subcomisiones
										WHERE	sga_subcomisiones.comision = sga_comisiones.comision)
							$where
	
				UNION ALL
				
				SELECT		-- Comisiones CON Subcomisiones.
							vw_actividades.elemento,
							vw_actividades.codigo as actividad_codigo,
							vw_actividades.nombre as actividad_nombre,
							'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_desc,
							vw_periodos_lectivos.anio_academico, 
							vw_periodos_lectivos.periodo_lectivo, 
							vw_periodos_lectivos.nombre as periodo_nombre,
							sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre, 
							sga_comisiones.ubicacion, 
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_comisiones.turno as turno,
							sga_turnos_cursadas.nombre as turno_nombre,
							sga_comisiones_cupo.cupo as comision_cupo,
							sga_comisiones_cupo.cant_inscriptos as comision_inscriptos,
							f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
					
							-- Datos de la Asignación de la Comisión.
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_asignaciones.dia_semana as dia_semana,
							CASE sga_asignaciones.dia_semana
								WHEN 'Lunes' THEN 1
								WHEN 'Martes' THEN 2
								WHEN 'Miercoles' THEN 3
								WHEN 'Jueves' THEN 4
								WHEN 'Viernes' THEN 5
								WHEN 'Sabado' THEN 6
								WHEN 'Domingo' THEN 7
							END as orden_dia_semana,
							" . guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()) . ",
				 			to_char(sga_asignaciones.hora_inicio, '" . comunes::formato_hora_visual . "') || ' a ' || to_char(sga_asignaciones.hora_finalizacion, '" . comunes::formato_hora_visual . "') as horario,
							get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 2) as espacio,
							get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria) as espacio_nombre,

							-- Datos de las Subcomisiones
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as subcomision_nombre,
							sga_subcomisiones_cupo.cupo as subcomision_cupo,
							sga_subcomisiones_cupo.cant_inscriptos as subcomision_inscriptos,
							f_docentes_subcomision(sga_subcomisiones.subcomision, true) as docentes_resp,
								
							sga_comisiones.nombre || ' - ' || sga_subcomisiones.nombre as com_subcom_nombre,
							sga_comisiones.comision || '-' || sga_subcomisiones.subcomision as com_subcom_codigo
							
				FROM		vw_actividades
							JOIN sga_comisiones ON vw_actividades.elemento = sga_comisiones.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
							JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
							LEFT JOIN sga_comisiones_cupo ON sga_comisiones.comision = sga_comisiones_cupo.comision
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_subcomisiones_cupo ON sga_subcomisiones.subcomision = sga_subcomisiones_cupo.subcomision
							LEFT JOIN sga_subcomisiones_bh ON sga_subcomisiones.subcomision = sga_subcomisiones_bh.subcomision
							LEFT JOIN sga_comisiones_bh ON sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
							LEFT JOIN sga_clases_tipos ON (sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase AND sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase)
							LEFT JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion

				WHERE		vw_actividades.estado = '" . elemento::estado_activo . "' AND
							vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "')
							$where

				ORDER BY	$order
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_comisiones_subcomisiones_asist($where = '', $solo_subcom = false)
	{
		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT		-- Comisiones CON Subcomisiones.
					vw_actividades.elemento,
					vw_actividades.codigo as actividad_codigo,
					vw_actividades.nombre as actividad_nombre,
					'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_desc,
					vw_periodos_lectivos.anio_academico, 
					vw_periodos_lectivos.periodo_lectivo, 
					vw_periodos_lectivos.nombre as periodo_nombre,
					sga_comisiones.comision,
					sga_comisiones.nombre as comision_nombre, 
					sga_comisiones.ubicacion, 
					sga_ubicaciones.nombre as ubicacion_nombre,
					sga_comisiones.turno as turno,
					sga_turnos_cursadas.nombre as turno_nombre,
					f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
					sga_clases_tipos.nombre as tipo_clase_nombre,
					-- Datos de las Subcomisiones
					sga_subcomisiones.subcomision,
					sga_subcomisiones.nombre as subcomision_nombre,

					sga_comisiones.nombre || ' - ' || sga_subcomisiones.nombre as com_subcom_nombre,
					sga_comisiones.comision || '-' || sga_subcomisiones.subcomision as com_subcom_codigo

		FROM		vw_actividades
					JOIN sga_comisiones ON vw_actividades.elemento = sga_comisiones.elemento
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
					JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					LEFT JOIN sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase

		WHERE		vw_actividades.estado = '" . elemento::estado_activo . "'
					AND vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "')
					$where
		";

		if (!$solo_subcom) {
			// Datos de la Actividad, Período Lectivo y Comisión
			$sql = "SELECT		-- Comisiones SIN Subcomisiones.
								vw_actividades.elemento,
								vw_actividades.codigo as actividad_codigo,
								vw_actividades.nombre as actividad_nombre,
								'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_desc,
								vw_periodos_lectivos.anio_academico, 
								vw_periodos_lectivos.periodo_lectivo, 
								vw_periodos_lectivos.nombre as periodo_nombre,
								sga_comisiones.comision,
								sga_comisiones.nombre as comision_nombre, 
								sga_comisiones.ubicacion, 
								sga_ubicaciones.nombre as ubicacion_nombre,
								sga_comisiones.turno as turno,
								sga_turnos_cursadas.nombre as turno_nombre,
								f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
								'' as tipo_clase_nombre,
								-- Datos de las Subcomisiones (no existen pero se unifica para poder realizar el Union)
								CAST(null as integer) as subcomision,
								CAST(null as varchar) as subcomision_nombre,		
								sga_comisiones.nombre as com_subcom_nombre,
								sga_comisiones.comision || '-0' as com_subcom_codigo

					FROM		vw_actividades
								JOIN sga_comisiones ON vw_actividades.elemento = sga_comisiones.elemento
								JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
								JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
								LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno

					WHERE		vw_actividades.estado = '" . elemento::estado_activo . "'
								AND vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "')
								AND NOT EXISTS 
									(	SELECT	1
										FROM	sga_subcomisiones
										WHERE	sga_subcomisiones.comision = sga_comisiones.comision)
								$where

					UNION ALL	$sql
									
					ORDER BY	actividad_nombre, comision_nombre, subcomision_nombre
			";
		}

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de comisiones incluyendo el campo 'cant_actas_no_anuladas': 
	 * indica la cantidad de actas no anuladas generadas para la instancia.
	 */
	function get_listado_con_cant_actas_no_anuladas($instancia = null, $where = '')
	{
		$filtro_origen = '';
		if (!empty($instancia)) {
			$origen = ($instancia == comision::inst_cursar) ? acta::origen_regulares : acta::origen_promociones;
			$filtro_origen .= " AND sga_actas.origen = '$origen'";
		}
		$select = "(SELECT	COUNT(sga_actas.id_acta)
					FROM	sga_actas
					WHERE	sga_actas.comision = sga_comisiones.comision AND
							sga_actas.estado <> '" . acta::estado_anulada . "'
							$filtro_origen) AS cant_actas_no_anuladas";
		return $this->get_listado($where, $select);
	}

	/**
	 * Obtiene una lista de comisiones incluyendo el campo 'cant_actas_impresas_no_anuladas': 
	 * indica la cantidad de actas impresas (no digitales) y no anuladas generadas.
	 */
	function get_listado_con_cant_actas_impresas_no_anuladas($where = '')
	{
		$select = "(SELECT	COUNT(sga_actas.id_acta)
					FROM	sga_actas
					WHERE	sga_actas.comision = sga_comisiones.comision AND
							sga_actas.estado <> '" . acta::estado_anulada . "' AND
							sga_actas.estado_documento_digital IS NULL) AS cant_actas_impresas_no_anuladas";
		return $this->get_listado($where, $select);
	}

	/**
	 * Obtiene una lista de comisiones incluyendo el campo 'cant_actas_rect_no_anuladas': indica la cantidad de 
	 * actas rectificativas no anuladas generadas para la instancia.
	 */
	function get_listado_con_cant_actas_rectificativas_no_anuladas($instancia = null, $where = '')
	{
		$filtro_origen = '';
		if (!empty($instancia)) {
			$origen = ($instancia == comision::inst_cursar) ? acta::origen_regulares : acta::origen_promociones;
			$filtro_origen .= " AND sga_actas.origen = '$origen'";
		}
		$select = "(SELECT	COUNT(sga_actas.id_acta)
					FROM	sga_actas
					WHERE	sga_actas.comision = sga_comisiones.comision AND
							sga_actas.estado <> '" . acta::estado_anulada . "' AND
							sga_actas.tipo_acta = '" . acta::tipo_rectificativa . "'
							$filtro_origen) AS cant_actas_no_anuladas";
		return $this->get_listado($where, $select);
	}

	/**
	 * Obtiene una lista de comisiones incluyendo el campo 'cant_actas': indica la cantidad de actas generadas.
	 */
	function get_listado_con_cant_actas($where = '')
	{
		$select = "(SELECT	COUNT(sga_actas.id_acta)
					FROM	sga_actas
					WHERE	sga_actas.comision = sga_comisiones.comision) as cant_actas";
		return $this->get_listado($where, $select);
	}

	/**
	 * Obtiene un listado de las Comisiones existentes para la Actividad y el Año Académico dados, según el 
	 * filtro dado.
	 *
	 * @param integer	$elemento		Actividad para la cual se quieren recuperar las Comisiones existentes.
	 * @param integer	$anio_academico	Año Académico para la cual se quieren recuperar las Comisiones existentes.
	 * @param str 		$where			Filtro sobre las comisiones a recuperar (año, período, etc).
	 */
	function get_comisiones_actividad_anio_academico($anio_academico, $elemento, $where = '')
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$elemento = toba::db()->quote($elemento);
		$filtro = "sga_comisiones.elemento = $elemento AND sga_periodos.anio_academico = $anio_academico";
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		return $this->get_listado($filtro);
	}

	/**
	 * Obtiene un listado de las Comisiones existentes con sus datos, para la Actividad dada, según el 
	 * filtro dado.
	 *
	 * @param integer	$elemento		Actividad para la cual se quieren recuperar las Comisiones existentes.
	 * @param str 		$where			Filtro sobre las comisiones a recuperar (año, período, etc).
	 */
	function get_datos_comisiones_con_cant_subcomisiones_actividad($elemento, $where = '')
	{
		$elemento = toba::db()->quote($elemento);

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre,						
							sga_comisiones.elemento,
							f_horario_comision(sga_comisiones.comision) AS horario,
							f_docentes_comision(sga_comisiones.comision, true) AS docentes,
							sga_comisiones.inscripcion_habilitada,
							sga_comisiones.cupo,
							sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
							(SELECT	COUNT	(sga_subcomisiones.subcomision)
									FROM	sga_subcomisiones
									WHERE	sga_subcomisiones.comision = sga_comisiones.comision
							) AS cant_subcomisiones
				FROM		sga_comisiones,
							sga_elementos
				WHERE		sga_comisiones.elemento = sga_elementos.elemento AND
							sga_comisiones.elemento = $elemento
							$where
				ORDER BY	sga_comisiones.nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de las Comisiones existentes para la Actividad dada, que estén asociadas a la Propuesta
	 * correspondiente al Plan dado, cuyos Períodos Lectivos correspondan al Año Académico del Período de Inscripción
	 * dado, y que verifiquen el filtro especificado.
	 *
	 * @param integer	$elemento		Actividad para la cual se quieren recuperar las Comisiones existentes.
	 * @param integer	$plan			Plan para la cual se quieren recuperar las Comisiones existentes.
	 * @param integer	$periodo_insc	Período de Inscripción cuyo Año Académico filtra el Período Lectivo de las Comisiones a recuperar.
	 * @param str 		$where			Filtro sobre las comisiones a recuperar (año, período, ubicacion, etc).
	 */
	function get_comisiones_actividad_plan_anio_academico($elemento, $plan, $anio_acad, $where = '')
	{
		$elemento = toba::db()->quote($elemento);
		$plan = toba::db()->quote($plan);
		$anio_acad = toba::db()->quote($anio_acad);

		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							sga_comisiones.periodo_lectivo,								
							sga_comisiones.turno,
							sga_comisiones.catedra,
							sga_comisiones.letra_desde,
							sga_comisiones.letra_hasta,
							sga_comisiones.cupo,
							" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_habilitada', 'inscripcion_habilitada') . ",
							sga_comisiones.ubicacion,
							sga_periodos.nombre	as				nombre_periodo,
							sga_periodos.anio_academico	as		anio_academico_periodo,
							sga_turnos_cursadas.nombre as		nombre_turno,
							f_instancias_comision(sga_comisiones.comision) as nombre_instancias,
							sga_catedras.nombre as				nombre_catedra
							
				FROM		sga_comisiones
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_comisiones_propuestas ON sga_comisiones.comision = sga_comisiones_propuestas.comision
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
							LEFT JOIN sga_comisiones_excep_perlect ON sga_comisiones_excep_perlect.comision = sga_comisiones.comision

				WHERE		sga_comisiones.elemento = $elemento AND
							sga_comisiones_propuestas.plan = $plan AND
							sga_periodos.anio_academico = $anio_acad
							$where

				ORDER BY	nombre_periodo,	sga_comisiones.ubicacion, nombre_turno
  		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de las Comisiones existentes para el Período Lectivo dado para la carga de un combo.
	 *
	 * @param integer	$per_lect		Periodo Lectivo para la cual se quieren recuperar las Comisiones existentes.
	 */
	function get_comisiones_periodo_lectivo($per_lect, $where = '')
	{
		$per_lect = toba::db()->quote($per_lect);

		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision as valor,
							sga_comisiones.nombre as descr,
							sga_comisiones_cupo.cupo as comision_cupo,
							sga_comisiones_cupo.cant_inscriptos as comision_inscriptos
				FROM		sga_comisiones
							LEFT JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
				WHERE		sga_comisiones.periodo_lectivo = $per_lect
							$where
				ORDER BY	sga_comisiones.nombre
  			;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de comisiones para el reríodo lectivo, actividad y ubicación dados. 
	 * 
	 * @param int $periodo_lectivo
	 * @param int $elemento
	 * @param int $ubicacion
	 * @return array
	 */
	function get_comisiones_actividad_periodo_lectivo_ubicacion($periodo_lectivo, $elemento, $ubicacion)
	{
		$periodo_lectivo = toba::db()->quote($periodo_lectivo);
		$elemento = toba::db()->quote($elemento);
		$ubicacion = toba::db()->quote($ubicacion);
		$sql = "SELECT		sga_comisiones.comision as valor,
							sga_comisiones.nombre as descr
				FROM		sga_comisiones 
				WHERE		sga_comisiones.periodo_lectivo = $periodo_lectivo AND
							sga_comisiones.elemento = $elemento AND
							sga_comisiones.ubicacion = $ubicacion
				ORDER BY	sga_comisiones.nombre
  		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de comisiones con datos de sus bandas horarias y asignaciones
	 * para el reríodo lectivo, actividad y ubicación dados. 
	 * 
	 * @param int $per_lect
	 * @param int $elemento
	 * @param int $ubicacion
	 * @return array
	 */
	function get_comisiones_bh_actividad_periodo_lectivo_ubicacion($per_lect, $elemento, $ubicacion)
	{
		$per_lect = toba::db()->quote($per_lect);
		$elemento = toba::db()->quote($elemento);
		$ubicacion = toba::db()->quote($ubicacion);

		$sql = "SELECT 
					sga_comisiones.comision,
					sga_comisiones_bh.banda_horaria,
					sga_comisiones_bh.asignacion,
					sga_comisiones.nombre AS comision_nombre,
					sga_clases_tipos.nombre AS clases_tipos_nombre,
					sga_asignaciones.dia_semana,
					to_char(sga_asignaciones.hora_inicio, '" . comunes::formato_hora_visual . "') as hora_inicio,
					to_char(sga_asignaciones.hora_finalizacion, '" . comunes::formato_hora_visual . "') as hora_finalizacion,
					sga_asignaciones.periodicidad,
					sga_asignaciones.fecha_desde,
					sga_asignaciones.fecha_hasta,
					(
						SELECT	count(sga_clases.clase) 
						FROM	sga_clases 
						WHERE	sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
					) AS cant_clases_de_la_bh,
					( 
						SELECT	sum(f_asig_espacio_clase_completa(sga_clases.clase, sga_asignaciones.asignacion)) 
						FROM	sga_clases 
						WHERE	sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria 
					) AS cant_clases_de_la_bh_con_ae_completa,
					(
						SELECT	count(DISTINCT sga_espacios_asignacion.clase)
						FROM	sga_espacios_asignacion 
								JOIN sga_clases ON sga_clases.clase = sga_espacios_asignacion.clase
						WHERE	sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
					) AS cant_ae_clases_de_la_bh
				FROM 
					sga_comisiones 
					JOIN sga_comisiones_bh ON sga_comisiones_bh.comision = sga_comisiones.comision 
					JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion 
					JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase 
				WHERE
					sga_comisiones.periodo_lectivo = $per_lect AND
					sga_comisiones.elemento = $elemento AND
					sga_comisiones.ubicacion = $ubicacion
				ORDER BY comision_nombre
  			;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de las Comisiones existentes para la Actividad dada para la carga de un combo.
	 *
	 * @param integer	$elemento	Actividad para la cual se quieren recuperar las Comisiones existentes.
	 */
	function get_comisiones_actividad($elemento, $where = '')
	{
		$elemento = toba::db()->quote($elemento);

		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision as valor,
							sga_comisiones.nombre as descr
				FROM		sga_comisiones
				WHERE		sga_comisiones.elemento = $elemento
							$where
				ORDER BY	sga_comisiones.nombre
  			;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de las Comisiones existentes para la Actividad dada para la carga de un combo.
	 * En la descripción de la comisión se incluyen tanto el Nombre de la misma como su Ubicación, ya que 
	 * en un mismo período lectivo puede haber más de una Comisión con igual nombre para la misma Actividad, 
	 * en distinta ubicación.
	 *
	 * @param integer	$elemento	Actividad para la cual se quieren recuperar las Comisiones existentes.
	 */
	function get_comisiones_con_ubicacion_actividad($elemento, $where = '')
	{
		$elemento = toba::db()->quote($elemento);

		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision as valor,
							sga_comisiones.nombre || ' - ' || sga_ubicaciones.nombre as descr
				FROM		sga_comisiones,
							sga_ubicaciones
				WHERE		sga_comisiones.ubicacion = sga_ubicaciones.ubicacion AND
							sga_comisiones.elemento = $elemento
							$where
				ORDER BY	sga_comisiones.nombre
  			;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la lista de comisiones para una instancia en particular tales que tienen alumnos inscriptos y no tienen acta generada (no anulada).
	 *
	 * @param str $origen	código de origen.
	 * @param str $where	filtro sobre las comisiones a recuperar.
	 * @return array
	 */
	function get_comisiones_con_inscriptos_sin_acta_generada($origen, $where = '')
	{
		if ($where) {
			$where = "AND $where";
		}
		if ($origen == acta::origen_regulares) {
			$instancia = comision::inst_cursar;
			$campo_instancia = 'instancia_cursada';
		} else {
			$instancia = comision::inst_promocionar;
			$campo_instancia = 'instancia_promocion';
		}
		$origen = toba::db()->quote($origen);

		$sql = "SELECT  	sga_comisiones.comision,
							sga_evaluaciones.evaluacion,
							sga_comisiones.nombre,
							sga_comisiones.entidad,
							sga_periodos.nombre as nombre_periodo,
							sga_periodos.anio_academico,
							sga_elementos.codigo as codigo_actividad,
							get_nombre_actividad_comision(sga_comisiones.comision, 1) as nombre_actividad,
							sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
							sga_turnos_cursadas.nombre as nombre_turno,
							f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
							sga_ubicaciones.nombre as nombre_ubicacion
				FROM		sga_comisiones
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_comisiones_instancias ON sga_comisiones.comision = sga_comisiones_instancias.comision
							JOIN sga_evaluaciones ON sga_comisiones.entidad = sga_evaluaciones.entidad
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				WHERE		sga_comisiones_instancias.instancia = $instancia AND
							sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
							sga_evaluaciones_tipos.aplica_a = '" . acta::evaluacion_tipo_aplica_comision . "' AND

							EXISTS (SELECT	sga_eval_detalle_cursadas.evaluacion
									FROM	sga_eval_detalle_cursadas
											LEFT JOIN sga_insc_cursada ON (sga_insc_cursada.alumno = sga_eval_detalle_cursadas.alumno AND sga_insc_cursada.comision = sga_comisiones.comision)
									WHERE	sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion AND
											$campo_instancia = 1 AND
											(sga_insc_cursada.estado IS NULL OR sga_insc_cursada.estado = '" . inscripcion_cursada::estado_aceptada . "')) AND

							NOT EXISTS (SELECT	sga_actas.id_acta
										FROM	sga_actas
										WHERE	sga_actas.comision = sga_comisiones.comision AND
												sga_actas.origen = $origen AND
												sga_actas.estado <> '" . acta::estado_anulada . "')

							$where
				ORDER BY	sga_comisiones.nombre
  		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de los nombres de las Comisiones existentes, con sus subcomisiones asociadas, si es que las tiene, 
	 * según el filtro dado.
	 *
	 * @param str $where filtro sobre las comisiones o subcomisiones de las mismas a recuperar.
	 */
	function get_comisiones_con_subcomisiones($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT  	DISTINCT sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as subcomision_nombre
				FROM		sga_comisiones
							JOIN sga_comisiones_propuestas ON sga_comisiones.comision = sga_comisiones_propuestas.comision
							LEFT JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
				$where
				ORDER BY	sga_comisiones.nombre,
							sga_subcomisiones.nombre
  		";
		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_comisiones_propuestas'));
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Obtiene una lista de comisiones diferentes asociadas a las subcomisiones enviadas
	 *
	 * @param array arreglo de subcomisiones
	 */
	function get_comisiones_de_subcomisiones($subcos = array())
	{
		$filtro = guarani_sql::armar_condicion_in($subcos, 'sga_subcomisiones', 'subcomision');
		$sql = "SELECT  	DISTINCT sga_subcomisiones.comision
				FROM		sga_subcomisiones
				WHERE		$filtro
  				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la cantidad de Comisiones que existen definidas para una Actividad y un Período Lectivo 
	 * correspondientes a la comisión dada. Es decir cuenta la cantidad total de Comisiones, en suma a la dada,
	 * que existen para esa actividad y período lectivo.
	 *
	 * @param integer $comision id del elemento para el cual se consulta la cantidad de comisiones.
	 */
	function get_cant_comisiones_actividad_per_lectivo_de_comision($comision)
	{
		$sql = "SELECT  COUNT(sga_comisiones.comision) as cant_comisiones
				FROM	sga_comisiones AS comision_original,
						sga_comisiones
				WHERE	comision_original.comision = $comision AND
						comision_original.elemento = sga_comisiones.elemento AND
						comision_original.periodo_lectivo = sga_comisiones.periodo_lectivo
  				;";

		$rs = guarani_db::consultar_fila($sql);
		if (!empty($rs)) {
			return $rs['cant_comisiones'];
		}
	}

	/**
	 * Obtiene Subcomisiones y sus datos según el filtro dado.
	 *
	 * @param str $where filtro sobre las comisiones o subcomisiones de las mismas a recuperar.
	 */
	function get_subcomisiones($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as subcomision_nombre,
							sga_subcomisiones.tipo_clase,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_subcomisiones.inscripcion_habilitada,
							sga_subcomisiones.letra_desde,
							sga_subcomisiones.letra_hasta,
							sga_subcomisiones.cupo,
							f_horario_subcomision(sga_subcomisiones.subcomision) as horarios_subcomision
				FROM		sga_comisiones
							JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
							JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
				$where
				ORDER BY	sga_comisiones.nombre,
							sga_comisiones.comision,
							sga_clases_tipos.nombre,
							sga_subcomisiones.nombre
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene Subcomisiones y sus datos + cupo según el filtro dado.
	 *
	 * @param str $where filtro sobre las comisiones o subcomisiones de las mismas a recuperar.
	 */
	function get_subcomisiones_cupo($where = '', $select_inscriptos = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if (empty($select_inscriptos)) {
			$select_inscriptos = ", sga_subcomisiones_cupo.cant_inscriptos AS cant_inscriptos";
		}

		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as subcomision_nombre,
							sga_subcomisiones.tipo_clase,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_turnos_cursadas.nombre AS turno_nombre,
							sga_comisiones.ubicacion,
							sga_comisiones.turno,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_subcomisiones.inscripcion_habilitada,
							sga_subcomisiones.letra_desde,
							sga_subcomisiones.letra_hasta,
							sga_subcomisiones_cupo.cupo,
							f_docentes_subcomision(sga_subcomisiones.subcomision,true) as subcomision_docentes
							$select_inscriptos
				FROM		sga_comisiones
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
							JOIN sga_subcomisiones_cupo ON sga_subcomisiones.subcomision = sga_subcomisiones_cupo.subcomision
							JOIN sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase
						    LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				ORDER BY	sga_comisiones.nombre,
							sga_comisiones.comision,
							sga_clases_tipos.nombre,
							sga_subcomisiones.nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el nombre de la subcomision según el id. dado.
	 *
	 * @param subcomision Id de la subcomision a obtener el nombre
	 */
	function get_nombre_subcomision($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "SELECT  	sga_subcomisiones.nombre
				FROM		sga_subcomisiones 
				WHERE		sga_subcomisiones.subcomision = $subcomision;";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['nombre'];
	}

	/**
	 * Obtiene Subcomisiones y sus datos según el filtro dado.
	 *
	 * @param str $where filtro sobre las comisiones o subcomisiones de las mismas a recuperar.
	 */
	function get_datos_subcomisiones($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT  	sga_subcomisiones.comision,
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as nombre,
							sga_subcomisiones.tipo_clase,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_subcomisiones.inscripcion_habilitada,
							sga_subcomisiones.letra_desde,
							sga_subcomisiones.letra_hasta,
							sga_subcomisiones.cupo,
							f_horario_subcomision(sga_subcomisiones.subcomision) as horario,
							f_docentes_subcomision(sga_subcomisiones.subcomision, true) as docentes
				FROM		sga_subcomisiones,
							sga_clases_tipos
				WHERE		sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
							$where
				ORDER BY	sga_clases_tipos.nombre, 
							sga_subcomisiones.nombre
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene Subcomisiones de la Comisión dada con sus datos y la asignación de banda horaria asociada, 
	 * según el filtro dado.
	 *
	 * @param str $where filtro sobre las subcomisiones a recuperar.
	 */
	function get_subcomisiones_comision_con_asignacion_horaria($comision, $where = '')
	{
		$comision = toba::db()->quote($comision);

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT  	sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as subcomision_nombre,
							sga_subcomisiones.tipo_clase,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_subcomisiones.inscripcion_habilitada,
							sga_subcomisiones.letra_desde,
							sga_subcomisiones.letra_hasta,
							sga_subcomisiones.cupo,
							f_horario_subcomision(sga_subcomisiones.subcomision) as horario
				FROM		sga_subcomisiones
							JOIN sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase
				WHERE		sga_subcomisiones.comision = $comision
							$where
				ORDER BY	sga_clases_tipos.nombre,
							sga_subcomisiones.nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de las Subcomisiones-Comisiones existentes para el Período Lectivo.
	 *
	 * @param integer	$per_lect		Periodo Lectivo para la cual se quieren recuperar las Comisiones existentes.
	 * @param str		$where			Filtro extra
	 */
	function get_subcom_com_periodo_lectivo($per_lect, $where = '')
	{
		$per_lect = toba::db()->quote($per_lect);

		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre as subcomision_nombre,
							sga_subcomisiones.tipo_clase,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_subcomisiones.inscripcion_habilitada,
							sga_subcomisiones.letra_desde,
							sga_subcomisiones.letra_hasta,
							sga_subcomisiones_cupo.cupo,
							sga_subcomisiones_cupo.cant_inscriptos
				FROM		sga_comisiones
							JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
							JOIN sga_subcomisiones_cupo ON sga_subcomisiones.subcomision = sga_subcomisiones_cupo.subcomision
							JOIN sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase
				WHERE		sga_comisiones.periodo_lectivo = $per_lect
							$where
				ORDER BY	sga_comisiones.nombre,
							sga_comisiones.comision,
							sga_clases_tipos.nombre,
							sga_subcomisiones.nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene las Subcomisiones de la Comisión dada y sus datos, según el filtro dado.
	 *
	 * @param integer	$id_comision	id de la comisión para la que se quieren recuperar las subcomisiones asociadas.
	 * @param str		$where 			filtro sobre las comisiones o subcomisiones de las mismas a recuperar.
	 */
	function get_subcomisiones_comision($id_comision, $where = '')
	{
		$id_comision = toba::db()->quote($id_comision);
		$filtro = "sga_comisiones.comision = $id_comision";
		if ($where) {
			$filtro .= $where;
		}
		return $this->get_subcomisiones($filtro);
	}

	/**
	 * Obtiene los tiposd e clase de las Subcomisiones de la Comisión dada.
	 *
	 * @param integer	$id_comision	id de la comisión para la que se quieren recuperar las subcomisiones asociadas.
	 */
	function get_tipos_subcos_de_comision($id_comision)
	{
		$id_comision = toba::db()->quote($id_comision);
		$sql = "SELECT DISTINCT sga_subcomisiones.tipo_clase
				FROM			sga_subcomisiones
				WHERE			sga_subcomisiones.comision = $id_comision
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la lista valor, descripción de turnos de cursadas
	 */
	function get_listado_turnos_cursadas()
	{
		$sql = "SELECT 	sga_turnos_cursadas.turno	AS valor,
						sga_turnos_cursadas.nombre	AS descr
				FROM  	sga_turnos_cursadas
			   ";

		return guarani_db::consultar($sql);
	}

	//Obtiene el nombre correspondiente al turno de cursada dado como parámetro.
	function get_nombre_turno_cursada($turno)
	{
		if (!is_null($turno)) {
			$turno = toba::db()->quote($turno);

			$sql = "SELECT	sga_turnos_cursadas.nombre	
					FROM	sga_turnos_cursadas
					WHERE	sga_turnos_cursadas.turno = $turno
					;";

			$datos = guarani_db::consultar_fila($sql);

			if (!empty($datos)) {
				return current($datos);
			}
		}
	}

	/**
	 * Obtiene los datos de todos los docentes asociados a la comisión dada como parámetro.
	 *
	 * @param integer	$comision	id de la comisión para la cual se desean recuperar los docentes.
	 * @param str		$where		filtro sobre los docentes a recuperar.
	 */
	function get_docentes_comision($comision = null, $where = '')
	{
		$filtro = '';
		if (!is_null($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_docentes_comision.comision = $comision ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		$sql = "SELECT		sga_docentes_comision.docente,
							sga_docentes_comision.responsabilidad,
							sga_docentes_comision.fecha_desde,
							sga_docentes_comision.fecha_hasta,
							sga_docentes.legajo,
							sga_docentes.persona,
							sga_docentes.estado,
							    COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as nombre_persona,
							sga_docentes_responsabilidades.nombre as responsabilidad_nombre,
							sga_docentes_comision.comision
				FROM		sga_docentes_comision,
							sga_docentes,
							mdp_personas,
				    		sga_docentes_responsabilidades
				WHERE		sga_docentes_comision.docente = sga_docentes.docente AND
							sga_docentes.persona = mdp_personas.persona AND
							sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
							$filtro
				ORDER BY	sga_docentes_responsabilidades.orden_listado,  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de todas las comisiones a las cuales se encuentra asociado el docente dado 
	 * como parámetro.
	 *
	 * @param integer	$docente	id del docente para la cual se desean recuperar las comisiones 
	 * 								a las que está asociado.
	 * @param str		$where		filtro sobre las comisiones a recuperar.
	 */
	function get_comisiones_docente($docente, $where = '')
	{
		$docente = toba::db()->quote($docente);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT		sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							vw_periodos_lectivos.anio_academico,
							vw_periodos_lectivos.nombre as periodo_nombre, 
							vw_actividades.codigo as actividad_codigo,
							vw_actividades.nombre as actividad_nombre, 
							sga_turnos_cursadas.nombre as turno_nombre,
							f_modalidad_cursada_comision(sga_comisiones.comision) as modalidades_nombres,
							sga_ubicaciones.nombre as ubicacion_nombre
				FROM		sga_docentes_comision
							JOIN sga_comisiones ON sga_docentes_comision.comision = sga_comisiones.comision
							JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
							JOIN vw_actividades ON sga_comisiones.elemento = vw_actividades.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				WHERE		sga_docentes_comision.docente = $docente
							$where
				ORDER BY	vw_periodos_lectivos.anio_academico, 
							vw_periodos_lectivos.nombre, 
							vw_actividades.nombre, 
							sga_comisiones.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de todos los docentes asociados a la subcomisión dada como parámetro.
	 *
	 * @param integer	$subcomision	id de la subcomisión para la cual se desean recuperar los docentes.
	 * @param str		$where			filtro sobre los docentes a recuperar.
	 */
	function get_docentes_subcomision($subcomision = null, $where = '')
	{
		$filtro = '';
		if (!is_null($subcomision)) {
			$subcomision = toba::db()->quote($subcomision);
			$filtro .= " AND sga_docentes_subcomision.subcomision = $subcomision ";
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		$sql = "SELECT		sga_docentes_subcomision.docente,
							sga_docentes.legajo,
							sga_docentes.persona,
							sga_docentes.estado,
							COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as nombre_persona,
							sga_docentes_responsabilidades.nombre as responsabilidad_nombre
				FROM		sga_docentes_subcomision
							JOIN sga_docentes ON sga_docentes_subcomision.docente = sga_docentes.docente
							JOIN mdp_personas ON sga_docentes.persona = mdp_personas.persona
							JOIN sga_subcomisiones ON sga_docentes_subcomision.subcomision = sga_subcomisiones.subcomision
							JOIN sga_docentes_comision ON (sga_docentes_subcomision.docente = sga_docentes_comision.docente AND sga_subcomisiones.comision = sga_docentes_comision.comision)
							JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
				WHERE		true
							$filtro
				ORDER BY	sga_docentes_responsabilidades.orden_listado,  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos del docente en la subcomisión dada como parámetro.
	 *
	 * @param integer	$subcomision	id de la subcomisión
	 * @param integer	$docente		id del docente
	 */
	function get_datos_docente_subcomision($subcomision = null, $docente = null)
	{
		if (is_null($subcomision) || is_null($docente)) {
			return false;
		}

		$docente = toba::db()->quote($docente);
		$where = "sga_docentes.docente = $docente";
		$datos = $this->get_docentes_subcomision($subcomision, $where);
		if (isset($datos[0])) {
			return $datos[0];
		}
		return array();
	}

	/**
	 * Obtiene los datos del Período Lectivo para el cual está definida la comisión dada como parámetro.
	 *
	 * @param integer	$comision	id de la comisión para la cual se desean recuperar los datos del período lectivo.
	 * @param str		$where		filtro sobre los datos a recuperar.
	 */
	function get_datos_periodo_comision($comision, $where = '')
	{
		$comision = toba::db()->quote($comision);
		$filtro = " AND sga_comisiones.comision = $comision ";
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT		sga_comisiones.periodo_lectivo,
                            sga_comisiones.ubicacion,
							sga_periodos_lectivos.fecha_inicio_dictado,
							to_char(sga_periodos_lectivos.fecha_inicio_dictado,'" . comunes::formato_fecha_visual . "') as fecha_inicio_dictado_visual,
							sga_periodos_lectivos.fecha_fin_dictado,
							to_char(sga_periodos_lectivos.fecha_fin_dictado, '" . comunes::formato_fecha_visual . "') as fecha_fin_dictado_visual,
							sga_periodos_lectivos.fecha_tope_movimientos,
							sga_periodos_lectivos.fecha_inactivacion,
							sga_periodos_lectivos.entidad,
							sga_periodos_lectivos.periodo,
							sga_periodos.anio_academico,
							sga_periodos.nombre as periodo_nombre,
							sga_periodos.fecha_inicio as periodo_fecha_inicio,
							sga_periodos.fecha_fin as periodo_fecha_fin,
							sga_periodos.descripcion as periodo_descr,
							sga_periodos.periodo_generico as periodo_generico,
							sga_periodos_genericos.nombre as periodo_generico_nombre,
							sga_periodos_genericos.periodo_generico_tipo as periodo_generico_tipo,
							sga_periodos_genericos.periodo_lectivo_tipo as periodo_lectivo_tipo
							
				FROM		sga_comisiones,
							sga_periodos_lectivos,
							sga_periodos,
							sga_periodos_genericos
							
				WHERE		sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
							sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							sga_periodos_genericos.periodo_generico = sga_periodos.periodo_generico
							$filtro
				;";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Valida si un nombre de Comisión se puede usar o no para una Actividad y un Período Lectivo dados.
	 * 	@param string  nombre		Nombre de Comisión que se intenta validar.
	 * @param integer act			Id de la Actividad a la cual está asociada la Comisión.
	 * @param integer per			Id del Período Lectivo al cual está asociado la Comisión.
	 * @param integer ubicacion	Id de la Ubicación a la cual está asociada la Comisión.
	 * @param integer com			Id de una Comisión a excluir en la comparación si es distinto de nulo.
	 */
	function validar_nombre_comision($nombre, $act, $per, $ubicacion, $com = null)
	{
		if (is_null($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$act = toba::db()->quote($act);
		$per = toba::db()->quote($per);
		$ubicacion = toba::db()->quote($ubicacion);
		$sql = "SELECT 	count(sga_comisiones.comision) as cant 	
                FROM 	sga_comisiones
				WHERE 	sga_comisiones.elemento = $act AND
						sga_comisiones.periodo_lectivo = $per AND
						sga_comisiones.ubicacion = $ubicacion AND
						" . guarani_sql::armar_condicion_compara_cadenas('sga_comisiones.nombre', $nombre) . "
				";
		if (!is_null($com)) {
			$com = toba::db()->quote($com);
			$sql .= " AND sga_comisiones.comision <> $com ;";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Valida si un nombre y tipo de Subcomisión se puede usar para una Comisión dada.
	 * 	@param string  nombre	Nombre de Subcomisión que se intenta validar.
	 * @param integer tipo		Id del tipo de Subcomisión que se intenta validar.
	 * @param integer com		Id de la Comisión a la que pertenece la Subcomisión.
	 * @param integer subcom	Id de una Subcomisión a excluir en la comparación si es distinto de nulo.
	 */
	function validar_nombre_tipo_subcomision($comision, $nombre, $tipo, $subcom = null)
	{
		if (is_null($nombre) || is_null($tipo)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$tipo = toba::db()->quote($tipo);
		$comision = toba::db()->quote($comision);
		$sql = "SELECT 	count(sga_subcomisiones.comision) as cant 	
                FROM 	sga_subcomisiones
				WHERE 	sga_subcomisiones.comision = $comision AND
						sga_subcomisiones.tipo_clase = $tipo AND
						" . guarani_sql::armar_condicion_compara_cadenas('sga_subcomisiones.nombre', $nombre) . "
				";
		if (!is_null($subcom)) {
			$subcom = toba::db()->quote($subcom);
			$sql .= " AND sga_subcomisiones.subcomision <> $subcom ;";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna el listado de comisiones por período lectivo según el filtro especificado
	 */
	function get_listado_comisiones($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$estados = toba::consulta_php('co_comunes')->get_estado_activo_baja_pendiente();
		$sql = "SELECT 		vw_actividades.codigo			AS codigo,
							vw_actividades.nombre			AS actividad,
            				sga_comisiones.nombre			AS comisiones,
            				sga_turnos_cursadas.nombre		AS turnos,
            				sga_catedras.nombre				AS catedras,            
            				f_modalidad_cursada_comision(sga_comisiones.comision) AS modalidades,
							sga_comisiones.cupo				AS cupos,
							CASE
	        					WHEN ((sga_comisiones.letra_desde IS NOT NULL) AND
                      				  (sga_comisiones.letra_hasta IS NOT NULL))
									THEN letra_desde || ' - ' || letra_hasta
	    	        				ELSE ''
	    					END 							AS desde_hasta,  
							sga_ubicaciones.ubicacion		AS ubicacion_id,
            				sga_ubicaciones.nombre			AS ubicaciones,
            				sga_comisiones.inscripcion_habilitada,
							".guarani_sql::get_case('sga_comisiones', 'estado', 'estado', $estados)."
				FROM 		sga_comisiones
							JOIN vw_actividades ON sga_comisiones.elemento = vw_actividades.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
            				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				ORDER BY	sga_ubicaciones.nombre,
							vw_actividades.nombre,
							sga_comisiones.nombre
	   ";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna las modalidades de una comision
	 */
	function get_comision_modalidades($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT f_modalidad_cursada_comision($comision) AS modalidades";
		$datos = guarani_db::consultar_fila($sql);
		$modalidades = $datos['modalidades'];
		return $modalidades;
	}

	/**
	 * Retorna un string con la descripción de todas las instancias de una comision
	 */
	function get_comision_instancias($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT f_instancias_comision($comision) AS instancias";
		$datos = guarani_db::consultar_fila($sql);
		$instancias = $datos['instancias'];
		return $instancias;
	}

	/**
	 * Retorna el listado de comisiones activas/pendientes disponibles para inscribirse a cursar según el filtro especificado
	 */
	function get_comisiones_disponibles_para_inscripcion($param_usa_subcomisiones, $param_cursa_en_cualquier_ubicacion, $param_filtra_rango_apellido_comision, $alumno, $elemento, $propuesta, $plan, $plan_version, $ubicacion, $inicial_apellido, $fuera_de_termino = false, $where = '')
	{
		if ($param_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		} else {
			$ubicacion = toba::db()->quote($ubicacion);
		}

		$filtro_division = '';
		if (guarani::parametros()->get_valor('cur_division_estricta') == comunes::si ) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($alumno);
			$division = $datos_alumno[0]['division'];

			if(isset($division)) {
				$filtro_division = " AND (sga_comisiones.division = $division OR sga_comisiones.division IS NULL ) ";
			} 
		}

		$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';
		$interfaz = comunes::interfaz_gestion; // Gestion
		$alumno = toba::db()->quote($alumno);
		$elemento = toba::db()->quote($elemento);
		$propuesta = toba::db()->quote($propuesta);
		$plan_q = toba::db()->quote($plan);
		$plan_version = toba::db()->quote($plan_version);
		
		if ($where) {
			$where = ' AND ' . $where;
		}

		$usa_subcomisiones = ($param_usa_subcomisiones == comunes::si);
		$select_subcomisiones = ', 0 as subcomisiones_cant';
		if ($usa_subcomisiones) {
			$select_subcomisiones = ", (SELECT	count(*) as subcomisiones_cant
										FROM	sga_subcomisiones
										WHERE	sga_subcomisiones.comision = sga_comisiones.comision
										AND		sga_subcomisiones.inscripcion_habilitada = '" . comunes::si . "') as subcomisiones_cant ";
		}

		$filtra_rango_apellido = ($param_filtra_rango_apellido_comision == comunes::si);
		$where_apellido = '';
		if ($filtra_rango_apellido) {
			$where_apellido = " AND (	sga_comisiones.letra_desde IS NULL AND sga_comisiones.letra_hasta IS NULL OR
								sga_comisiones.letra_desde IS NULL AND sga_comisiones.letra_hasta IS NOT NULL AND '$inicial_apellido' <= sga_comisiones.letra_hasta OR
								sga_comisiones.letra_desde IS NOT NULL AND sga_comisiones.letra_hasta IS NULL AND '$inicial_apellido' >= sga_comisiones.letra_desde OR
								sga_comisiones.letra_desde IS NOT NULL AND sga_comisiones.letra_hasta IS NOT NULL AND '$inicial_apellido' BETWEEN sga_comisiones.letra_desde AND sga_comisiones.letra_hasta)";
		}
		
		$sql = "SELECT 	
					sga_comisiones.comision			AS comision,
            		sga_comisiones.nombre			AS comision_nombre,
					sga_comisiones.inscripcion_cerrada,
					" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_cerrada', 'inscripcion_cerrada_desc') . ",
					sga_comisiones.inscripcion_cerrada_codigo,
					sga_periodos_lectivos.periodo_lectivo AS periodo_lectivo,
					sga_periodos.anio_academico || ' - ' || sga_periodos.nombre				AS periodo_lectivo_nombre,
					sga_periodos.anio_academico,
					sga_turnos_cursadas.turno		AS turno,
					COALESCE(sga_turnos_cursadas.nombre, '') AS turno_nombre,
					sga_catedras.catedra			AS catedra,
					COALESCE (sga_catedras.nombre, '')		AS catedra_nombre,
                    sga_ubicaciones.ubicacion		AS ubicacion,
					sga_ubicaciones.nombre			AS ubicacion_nombre,
					sga_periodos.anio_academico
					$select_subcomisiones

				FROM sga_comisiones
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
            	LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno

				WHERE sga_comisiones.elemento = $elemento
				  AND sga_comisiones.comision NOT IN (SELECT comision FROM sga_insc_cursada WHERE sga_insc_cursada.alumno = $alumno)
				  AND sga_comisiones.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad($propuesta, $plan, $plan_version, $elemento, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno))
				  $where_apellido
            	  $where
				  $filtro_division
							
				ORDER BY	sga_periodos.anio_academico,
							sga_periodos.nombre,
							sga_ubicaciones.nombre,
							sga_turnos_cursadas.nombre 
			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si una Ubicacón y un Período Lectivo dados tienen definidas Comisiones.
	 * @param  integer $ubicacion	id de la ubicación de la que se desea conocer si tiene o no comisiones
	 * @param  integer $p_lectivo	id del período lectivo del que se desea conocer si tiene o no comisiones
	 * @return boolean	true si el período lectivo en la ubicación dados tiene comisiones definidas y false en caso contrario
	 */
	function tiene_comisiones($ubicacion, $p_lectivo)
	{
		$ubicacion = toba::db()->quote($ubicacion);
		$p_lectivo = toba::db()->quote($p_lectivo);

		$filtro = 'sga_comisiones.ubicacion = ' . $ubicacion . ' AND ';
		$filtro .= 'sga_comisiones.periodo_lectivo = ' . $p_lectivo;
		$comisiones = $this->get_listado($filtro);

		if (!empty($comisiones)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Determina para la Comisión dada si su Período Lectivo ha finalizado.
	 * @param  integer $comision	id de la comisión.
	 * @return boolean	true si el período lectivo de la comisión ha finalizado y false en caso contrario.
	 */
	function comision_con_periodo_lectivo_finalizado($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT 
					(CURRENT_DATE > sga_periodos_lectivos.fecha_fin_dictado) as periodo_finalizado
				FROM 
				    sga_comisiones,
				    sga_periodos_lectivos
				WHERE
					sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
					sga_comisiones.comision = $comision
				";

		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['periodo_finalizado'];
	}

	/**
	  Obtiene una lista de la tabla de comisiones según el filtro recibido
	 */
	function get_listado_simple($where = '')
	{
		if ($where) {
			$where = 'WHERE ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre,
							sga_comisiones.periodo_lectivo,								
							sga_comisiones.elemento,
							sga_comisiones.turno,
							sga_comisiones.entidad,
							sga_comisiones.catedra,
							sga_comisiones.letra_desde,
							sga_comisiones.letra_hasta,
							sga_comisiones.cupo,
							" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_habilitada', 'inscripcion_habilitada') . ",
							sga_comisiones.ubicacion
				FROM		sga_comisiones
				$where
				ORDER BY	sga_comisiones.nombre
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el código de todas las propuestas asociadas a la comisión dada como parámetro.
	 * 
	 * @param integer $comision id de la comisión para la cual se desean recuperar las propuestas.
	 * @param str $where filtro sobre las propuestas asociadas a la comisión dada que se recuperan.
	 */
	function get_propuestas_comision($comision = null, $where = '')
	{
		$filtro = 'true';
		if (!empty($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_comisiones_propuestas.comision = $comision";
		}
		if ($where) {
			$filtro .= " AND $where";
		}

		$sql = "SELECT	DISTINCT sga_comisiones_propuestas.propuesta
				FROM	sga_comisiones_propuestas
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el código de todos los planes asociados a la comisión dada como parámetro.
	 * 
	 * @param integer $comision id de la comisión para la cual se desean recuperar los planes.
	 * @param str $where filtro sobre los planes asociados a la comisión dada que se recuperan.
	 */
	function get_planes_comision($comision = null, $where = '')
	{
		$filtro = 'true';
		if (!empty($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_comisiones_propuestas.comision = $comision";
		}
		if ($where) {
			$filtro .= " AND $where";
		}

		$sql = "SELECT	DISTINCT sga_comisiones_propuestas.plan
				FROM	sga_comisiones_propuestas
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de las propuestas asociadas a la comisión con su cupo definido.
	 * 
	 * @param int $comision código de la comisión para la cual se desean recuperar las propuestas-cupo.
	 */
	function get_propuestas_cupo_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT	sga_comisiones_propuestas_cupo.propuesta,
						sga_propuestas.codigo as propuesta_codigo,
						sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
						sga_comisiones_propuestas_cupo.cupo
				FROM	sga_comisiones_propuestas_cupo
						JOIN sga_propuestas ON sga_comisiones_propuestas_cupo.propuesta = sga_propuestas.propuesta
				WHERE	sga_comisiones_propuestas_cupo.comision = $comision
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de todas las instancias asociadas a la comisión dada como parámetro.
	 *
	 * @param integer	$comision	id de la comisión para la cual se desean recuperar las instancias.
	 * @param str		$where		filtro sobre los datos a recuperar.
	 */
	function get_instancias_comision($comision = null, $where = '')
	{
		$filtro = '';
		if (!empty($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_comisiones_instancias.comision = $comision";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT		sga_comisiones_instancias.instancia,
							sga_instancias.nombre,
							sga_comisiones_instancias.escala_nota,
							sga_escalas_notas.nombre as escala_nota_nombre,
							sga_comisiones_instancias.porc_asistencia,
							sga_instancias.orden
				FROM		sga_comisiones_instancias,
							sga_comisiones,
							sga_instancias,
							sga_escalas_notas
				WHERE		sga_comisiones_instancias.comision = sga_comisiones.comision AND
							sga_comisiones_instancias.instancia = sga_instancias.instancia AND
							sga_comisiones_instancias.escala_nota = sga_escalas_notas.escala_nota
							$filtro
				ORDER BY	sga_instancias.orden
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de todas las instancias asociadas a la comisión dada como parámetro.
	 *
	 * @param integer	$comision	id de la comisión para la cual se desean recuperar las instancias.
	 * @param str		$where		filtro sobre los datos a recuperar.
	 */
	function existe_instancia_para_comision($instancia, $comision, $where = '')
	{
		$instancia = toba::db()->quote($instancia);
		$comision = toba::db()->quote($comision);
		if ($where) {
			$where .= ' AND ' . $where;
		}

		$sql = "SELECT		1
				FROM		sga_comisiones_instancias
				WHERE		sga_comisiones_instancias.comision = $comision	AND
							sga_comisiones_instancias.instancia = $instancia
							$where
				;";
		$datos = guarani_db::consultar($sql);
		return empty($datos);
	}

	function get_comisiones_alternativas($actividad, $cant_comisiones_disponibles = null)
	{
		$res = array();
		$datos = toba::consulta_php('co_elementos')->get_datos_basicos_elemento($actividad);
		$max = ($datos['insc_cursada_por_prioridad'] == comunes::si) ? $datos['prioridad_cant_maxima'] : 1;
		for ($i = 1; $i <= $max; $i++) {
			if ($cant_comisiones_disponibles && $i > $cant_comisiones_disponibles) {
				break;
			}
			$res[] = array('valor' => $i, 'descr' => $i);
		}
		return $res;
	}

	/**
	 * Retorna los datos correspondientes a una instancia en formato RecordSet, en caso de que el parámetro sea un 
	 * array retorna los datos de las instancias correspondientes según la condición del array (para la carga masiva).
	 * 	@param integer/array instancia id o arreglo de ids de la o las instancias para los cuales se consultan los datos.
	 */
	function get_datos_instancia($instancia)
	{
		if (is_array($instancia)) {
			$filtro = guarani_sql::armar_condicion_in($instancia, 'sga_instancias', 'instancia');
			$datos = $this->get_listado_instancias_comisiones($filtro);
			return $datos;
		}
		$instancia = toba::db()->quote($instancia);
		$datos = $this->get_listado_instancias_comisiones("sga_instancias.instancia = $instancia");
		if (!empty($datos)) {
			return current($datos);
		}
	}

	/**
	 * Obtiene el listado de instancias que aplican a comisiones
	 *
	 */
	function get_listado_instancias_comisiones($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 		sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre
				FROM 		sga_instancias
				WHERE		sga_instancias.aplica_a = 'C'
							$where
				ORDER BY 	sga_instancias.orden";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de alumnos inscriptos en una comisión
	 *
	 */
	function get_alumnos_inscriptos_a_comision($comision, $solo_inscripciones_aceptadas=true, $where='', $filtrar_perfil_de_datos=true)
	{
		$comision = toba::db()->quote($comision);
		$filtro = '';
		if ($solo_inscripciones_aceptadas) {
			$filtro = " AND sga_insc_cursada.estado = '".inscripcion_cursada::estado_aceptada."'";
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		$sql = "SELECT		sga_insc_cursada.alumno,
							sga_alumnos.legajo,
							sga_alumnos.persona,
							vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado							
				FROM		sga_insc_cursada							
							JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
							JOIN vw_personas ON	vw_personas.persona = sga_alumnos.persona
							JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				WHERE		sga_insc_cursada.comision = $comision
							$filtro
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**
	 * Retorna la lista de alumnos inscriptos en una comisión con el usuario de Moodle
	 *
	 */
	function get_alumnos_inscriptos_a_comision_moodle($comision, $where = '', $plataforma = null)
	{
		$from = "";
		// Si no se pasa la plataforma se hace el JOIN desde "int_pv_cursos"
		if (is_null($plataforma)) {
			$from = "	LEFT JOIN	int_pv_cursos_comisiones	ON (sga_comisiones.comision = int_pv_cursos_comisiones.comision)
						LEFT JOIN	int_pv_cursos				ON (int_pv_cursos_comisiones.curso = int_pv_cursos.curso)
						LEFT JOIN	int_pv_plataformas			ON (int_pv_cursos.plataforma = int_pv_plataformas.plataforma)";
		} else {
			// Si se pasa la plataforma se hace el JOIN directamente con "int_pv_plataformas"
			$plataforma = toba::db()->quote($plataforma);
			$from = "JOIN int_pv_plataformas ON (int_pv_plataformas.plataforma = $plataforma)";
		}
		
		if ($where) {
			$where = " AND $where";
		}

		$comision = toba::db()->quote($comision);
		$estado_aceptada = toba::db()->quote(inscripcion_cursada::estado_aceptada);
		$estado_pendiente = toba::db()->quote(inscripcion_cursada::estado_pendiente);
		$calidad_activo = toba::db()->quote(alumno::calidad_activo);

		$sql = "SELECT		DISTINCT sga_insc_cursada.alumno,
							vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) AS usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas.propuesta_tipo,
							int_pv_usuarios.id_usuario_externo AS usuario_moodle						
				FROM		sga_insc_cursada							
				JOIN		sga_alumnos					ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
				JOIN		vw_personas					ON (vw_personas.persona = sga_alumnos.persona)
				JOIN		sga_propuestas				ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				JOIN		sga_comisiones				ON (sga_comisiones.comision = sga_insc_cursada.comision)
				$from
				LEFT JOIN	int_pv_usuarios				ON (int_pv_usuarios.plataforma = int_pv_plataformas.plataforma AND int_pv_usuarios.persona = vw_personas.persona)
				
				WHERE		sga_insc_cursada.comision	= $comision
				AND			sga_insc_cursada.estado		IN ($estado_aceptada, $estado_pendiente)
				AND 		sga_alumnos.calidad			= $calidad_activo
				$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de alumnos eliminados en una comisión con el usuario de Moodle
	 *
	 */
	function get_alumnos_eliminados_a_comision_moodle($comision, $where = '', $plataforma = null)
	{
		$from = "";
		// Si no se pasa la plataforma se hace el JOIN desde "int_pv_cursos"
		if (is_null($plataforma)) {
			$from = "	LEFT JOIN	int_pv_cursos_comisiones	ON (sga_comisiones.comision = int_pv_cursos_comisiones.comision)
						LEFT JOIN	int_pv_cursos				ON (int_pv_cursos_comisiones.curso = int_pv_cursos.curso)
						LEFT JOIN	int_pv_plataformas			ON (int_pv_cursos.plataforma = int_pv_plataformas.plataforma)";
		} else {
			// Si se pasa la plataforma se hace el JOIN directamente con "int_pv_plataformas"
			$plataforma = toba::db()->quote($plataforma);
			$from = "JOIN int_pv_plataformas ON (int_pv_plataformas.plataforma = $plataforma)";
		}
		
		if ($where) {
			$where = " WHERE $where";
		}

		$comision = toba::db()->quote($comision);
		$operacion_baja = toba::db()->quote(inscripcion_cursada::operacion_baja);
		$operacion_rechazo = toba::db()->quote(inscripcion_cursada::operacion_rechazo);
		$operacion_cambio_comision = toba::db()->quote(inscripcion_cursada::operacion_cambio_comision);
		$calidad_pasivo = toba::db()->quote(alumno::calidad_pasivo);

		$sql = "
			SELECT * FROM (
			
				SELECT		DISTINCT sga_insc_cursada_log.alumno,
							vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) AS usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas.propuesta_tipo,
							int_pv_usuarios.id_usuario_externo AS usuario_moodle						
				FROM		sga_insc_cursada_log							
				JOIN		sga_alumnos					ON (sga_insc_cursada_log.alumno = sga_alumnos.alumno)
				JOIN		vw_personas					ON (vw_personas.persona = sga_alumnos.persona)
				JOIN		sga_propuestas				ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				JOIN		sga_comisiones				ON (sga_comisiones.comision = sga_insc_cursada_log.comision)
				$from
				LEFT JOIN	int_pv_usuarios				ON (int_pv_usuarios.plataforma = int_pv_plataformas.plataforma AND int_pv_usuarios.persona = vw_personas.persona)
				WHERE		sga_insc_cursada_log.comision = $comision
				AND		sga_insc_cursada_log.operacion IN ($operacion_baja, $operacion_rechazo, $operacion_cambio_comision)
					
				UNION
				
				SELECT		DISTINCT sga_insc_cursada.alumno,
							vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) AS usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas.propuesta_tipo,
							int_pv_usuarios.id_usuario_externo AS usuario_moodle						
				FROM		sga_insc_cursada							
				JOIN		sga_alumnos					ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
				JOIN		vw_personas					ON (vw_personas.persona = sga_alumnos.persona)
				JOIN		sga_propuestas				ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				JOIN		sga_comisiones				ON (sga_comisiones.comision = sga_insc_cursada.comision)
				$from
				LEFT JOIN	int_pv_usuarios				ON (int_pv_usuarios.plataforma = int_pv_plataformas.plataforma AND int_pv_usuarios.persona = vw_personas.persona)
				WHERE		sga_insc_cursada.comision	= $comision
				AND 		sga_alumnos.calidad			= $calidad_pasivo
					
			) AS alumnos_eliminados
			$where
			ORDER BY	apellido,
						nombres
		";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna la lista de alumnos inscriptos en un curso de Moodle
	 *
	 */
	function get_alumnos_inscriptos_a_curso_moodle($curso, $where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$curso = toba::db()->quote($curso);
		$estado_aceptada = toba::db()->quote(inscripcion_cursada::estado_aceptada);
		$estado_pendiente = toba::db()->quote(inscripcion_cursada::estado_pendiente);
		$calidad_activo = toba::db()->quote(alumno::calidad_activo);

		$sql = "SELECT		DISTINCT vw_personas.persona,
							sga_insc_cursada.alumno,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) AS usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas.propuesta_tipo,
							int_pv_usuarios.id_usuario_externo AS usuario_moodle						
				FROM		sga_insc_cursada							
				JOIN		sga_alumnos					ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
				JOIN		vw_personas					ON (vw_personas.persona = sga_alumnos.persona)
				JOIN		sga_propuestas				ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				LEFT JOIN	int_pv_cursos_comisiones	ON (int_pv_cursos_comisiones.comision = sga_insc_cursada.comision)
				LEFT JOIN	int_pv_cursos				ON (int_pv_cursos.curso = int_pv_cursos_comisiones.curso)
				LEFT JOIN	int_pv_usuarios				ON ((int_pv_usuarios.plataforma = int_pv_cursos.plataforma) AND (int_pv_usuarios.persona = vw_personas.persona))
				WHERE		int_pv_cursos.curso = $curso
				AND			sga_insc_cursada.estado		IN ($estado_aceptada, $estado_pendiente)
				AND 		sga_alumnos.calidad			= $calidad_activo
				$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de docentes de una comisión con el usuario y rol de Moodle
	 *
	 */
	function get_docentes_comision_moodle($comision, $plataforma=null, $where = '')
	{
		$comision = toba::db()->quote($comision);
		if (is_null($plataforma)) {
			$from = '	LEFT JOIN int_pv_cursos_comisiones ON sga_docentes_comision.comision = int_pv_cursos_comisiones.comision
						LEFT JOIN int_pv_cursos	ON int_pv_cursos_comisiones.curso = int_pv_cursos.curso
						LEFT JOIN int_pv_plataformas ON int_pv_cursos.plataforma = int_pv_plataformas.plataforma';
		} else {
			$plataforma = toba::db()->quote($plataforma);
			$from = "JOIN int_pv_plataformas ON int_pv_plataformas.plataforma = $plataforma";
		}
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) AS usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_docentes_comision.responsabilidad AS responsabilidad_docente,
							int_pv_roles_docentes.rol_externo AS rol_moodle,
							int_pv_usuarios.id_usuario_externo AS usuario_moodle
				FROM		sga_docentes_comision
							JOIN sga_docentes ON sga_docentes_comision.docente = sga_docentes.docente
							JOIN vw_personas ON sga_docentes.persona = vw_personas.persona
							$from
							LEFT JOIN int_pv_usuarios ON (int_pv_plataformas.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
							LEFT JOIN int_pv_roles_docentes ON (int_pv_plataformas.plataforma = int_pv_roles_docentes.plataforma AND sga_docentes_comision.responsabilidad = int_pv_roles_docentes.responsabilidad)
				WHERE		sga_docentes_comision.comision = $comision
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de docentes de un curso de Moodle
	 *
	 */
	function get_docentes_curso_moodle($curso, $where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$curso = toba::db()->quote($curso);

		$sql = "SELECT		DISTINCT vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) AS usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_docentes_comision.responsabilidad AS responsabilidad_docente,
							int_pv_roles_docentes.rol_externo AS rol_moodle,
							int_pv_usuarios.id_usuario_externo AS usuario_moodle
							
				FROM int_pv_cursos
				JOIN int_pv_cursos_comisiones		ON (int_pv_cursos.curso = int_pv_cursos_comisiones.curso)
				JOIN sga_docentes_comision			ON (int_pv_cursos_comisiones.comision = sga_docentes_comision.comision)
				JOIN sga_docentes					ON (sga_docentes_comision.docente = sga_docentes.docente)
				JOIN vw_personas					ON (sga_docentes.persona = vw_personas.persona)
				LEFT JOIN int_pv_usuarios			ON ((int_pv_usuarios.plataforma = int_pv_cursos.plataforma) AND (int_pv_usuarios.persona = vw_personas.persona))
				LEFT JOIN int_pv_roles_docentes		ON ((int_pv_roles_docentes.plataforma = int_pv_cursos.plataforma) AND (int_pv_roles_docentes.responsabilidad = sga_docentes_comision.responsabilidad))
				
				WHERE		int_pv_cursos.curso = $curso
				$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de alumnos inscriptos en una comisión
	 *
	 */
	function get_datos_alumnos_inscriptos_a_comision($comision, $where = '')
	{
		if ($where) {
			$where = " AND $where";
		}
		$comision = toba::db()->quote($comision);

		$sql = "SELECT		sga_insc_cursada.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres_elegido AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS documento_principal
				FROM		sga_insc_cursada,
							sga_alumnos,
				    		vw_personas
				WHERE		sga_insc_cursada.comision = $comision AND
							sga_insc_cursada.alumno = sga_alumnos.alumno AND
							vw_personas.persona = sga_alumnos.persona
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
			;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de alumnos inscriptos en una subcomisión
	 *
	 */
	function get_datos_alumnos_inscriptos_a_subcomision($subcomision, $where = '')
	{
		if ($where) {
			$where = " AND $where";
		}
		$subcomision = toba::db()->quote($subcomision);

		$sql = "SELECT		sga_insc_cursada.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres_elegido AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS documento_principal
				FROM		sga_insc_subcomision
							JOIN sga_insc_cursada ON sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion
							JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
							JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				WHERE		sga_insc_subcomision.subcomision = $subcomision
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de comisiones asociables a una mesa de examen (a partir del llamado_mesa y filtros especificados)
	 *
	 */
	function get_comisiones_asociables_a_mesa($llamado_mesa, $where = '')
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT		vw_comisiones.comision,
							vw_comisiones.comision_nombre,
							sga_catedras.nombre AS nombre_catedra,
							sga_turnos_cursadas.nombre AS nombre_turno,   
							vw_comisiones.anio_academico AS anio_academico_periodo,
							vw_comisiones.periodo_nombre,
							f_modalidad_cursada_comision(vw_comisiones.comision) AS nombre_modalidad,
							f_propuestas_comision(vw_comisiones.comision) AS propuestas,
							sga_ubicaciones.nombre AS nombre_ubicacion
				FROM		sga_llamados_mesa
							JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
							JOIN vw_comisiones ON sga_mesas_examen.elemento = vw_comisiones.elemento
							JOIN sga_ubicaciones ON vw_comisiones.ubicacion = sga_ubicaciones.ubicacion
							LEFT JOIN sga_turnos_cursadas ON vw_comisiones.turno_cursada = sga_turnos_cursadas.turno
							LEFT JOIN sga_catedras ON vw_comisiones.catedra = sga_catedras.catedra                 
				WHERE		-- Llamado seleccionado
							sga_llamados_mesa.llamado_mesa = $llamado_mesa

							-- Comisiones de períodos lectivos anteriores a la fecha de la mesa de examen.
							-- (Fecha del examen >= fecha fin de dictado del p. lectivo de la comisión)
							AND sga_llamados_mesa.fecha >= vw_comisiones.fecha_fin_dictado

							-- Devolver las comisiones que cumplan con (alguna de) la/s modalidad/es
							-- que está/n definida/s en la Mesa seleccionada.
							AND EXISTS (SELECT	1 
										FROM	sga_comisiones_modalidad as cm
										WHERE	cm.comision = vw_comisiones.comision AND
												cm.modalidad IN (	SELECT	modalidad 
																	FROM	sga_mesas_examen_modalidad as mm
																	WHERE	mm.mesa_examen = sga_mesas_examen.mesa_examen))
							$where
				ORDER BY	vw_comisiones.anio_academico,
							vw_comisiones.periodo_lectivo_nombre,
							vw_comisiones.comision_nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de comisiones asociadas a mesas de examen
	 *
	 */
	function get_comisiones_asociadas_a_mesas($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT		-- Grupo 1
							sga_ubicaciones.ubicacion,
							sga_ubicaciones.nombre AS nombre_ubicacion,
							-- Campos del reporte
							'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,    
							sga_comisiones.nombre AS comision_nombre,
							sga_catedras.nombre AS nombre_catedra,
							f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) AS nombre_modalidad,
							f_propuestas_comision(sga_comisiones.comision) AS propuestas,
							sga_turnos_cursadas.nombre AS nombre_turno,   
							periodo_llamado.nombre AS nombre_llamado,
							sga_mesas_examen.nombre AS mesa_nombre,
							sga_llamados_mesa.fecha,
							f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) as instancias_nombre
				FROM		sga_insc_auto_examen
							JOIN sga_llamados_mesa ON sga_insc_auto_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
							JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
							JOIN vw_actividades ON sga_mesas_examen.elemento = vw_actividades.elemento
							JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							JOIN sga_periodos AS periodo_llamado ON sga_llamados_turno.periodo = periodo_llamado.periodo
							JOIN sga_comisiones ON sga_insc_auto_examen.comision = sga_comisiones.comision
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				$where					
				ORDER BY	sga_ubicaciones.nombre,
							sga_comisiones.nombre
  		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si existe alguna comision para una dada cátedra y (opcionalmente) actividad.
	 *
	 */
	function existe_comision_asociada_catedra($catedra, $elemento = '')
	{
		$catedra = toba::db()->quote($catedra);
		$where = '';

		if ($elemento) {
			$elemento = toba::db()->quote($elemento);
			$where = " AND sga_comisiones.elemento = $elemento";
		}


		$sql = "SELECT 
					sga_comisiones.comision
				FROM 
					sga_comisiones
				WHERE
					sga_comisiones.catedra = $catedra
					$where
				";

		$res = guarani_db::consultar($sql);

		return guarani_arreglos::count($res) > 0;
	}

	function get_cant_comisiones_de_actividad_en_per_lectivo($id_activ, $id_periodo)
	{
		$id_activ = toba::db()->quote($id_activ);
		$id_periodo = toba::db()->quote($id_periodo);

		$sql = "SELECT  COUNT(sga_comisiones.comision) AS cant_comisiones
				FROM	sga_comisiones
				WHERE	sga_comisiones.elemento = $id_activ
				AND		sga_comisiones.periodo_lectivo = $id_periodo
  				";

		$rs = guarani_db::consultar_fila($sql);

		if (!empty($rs)) {
			return $rs['cant_comisiones'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- opciones de autocompletado - ingreso de notas --------------------------------
	//-----------------------------------------------------------------------------------

	function get_opciones_combo_para()
	{
		return array(array('valor' => comision::autocompletar_para_todos_pagina, 'descr' => 'Todos los alumnos de la página'),
			array('valor' => comision::autocompletar_para_sin_datos_pagina, 'descr' => 'Alumnos de la página sin datos'));
	}
	
	function get_todas_opciones_combo_para($es_acta = false)
	{
                $msg_acta = $es_acta ? ' del acta' : '';
		return [
			['valor' => comision::autocompletar_para_todos_pagina, 'descr' => 'Todos los alumnos de la página'],
			['valor' => comision::autocompletar_para_sin_datos_pagina, 'descr' => 'Alumnos de la página sin datos'],
			['valor' => comision::autocompletar_para_todos, 'descr' => "Todos los alumnos{$msg_acta}"],
			['valor' => comision::autocompletar_para_sin_datos, 'descr' => "Alumnos{$msg_acta} sin datos"],
		];
	}

	/**
	 * Opciones de completado para carga de notas a Evaluaciones.
	 *
	 */
	function get_opciones_combo_completar_evaluaciones()
	{
		return array(array('valor' => comision::autocompletar_campo_nota, 'descr' => 'Nota'),
			array('valor' => comision::autocompletar_campo_resultado, 'descr' => 'Resultado'),
			array('valor' => comision::autocompletar_campo_corregido_por, 'descr' => 'Corregido por'),
			array('valor' => comision::autocompletar_campo_observaciones, 'descr' => 'Observaciones'));
	}

	//-----------------------------------------------------------------------------------
	//---- opciones de distribución de comisiones ---------------------------------------
	//-----------------------------------------------------------------------------------

	function get_opciones_combo_distribucion_comisiones()
	{
		return array(array('valor' => comision::dist_entre_com, 'descr' => comision::dist_entre_com_desc),
			array('valor' => comision::dist_com_subcom, 'descr' => comision::dist_com_subcom_desc),
			array('valor' => comision::dist_com_subcom_otra_com, 'descr' => comision::dist_com_subcom_otra_com_desc)
		);
	}

	function get_opciones_combo_alumnos_subcomision()
	{
		return array(array('valor' => comision::alumno_sin_subcomision, 'descr' => comision::alumno_sin_subcomision_desc),
			array('valor' => comision::alumno_con_subcomision, 'descr' => comision::alumno_con_subcomision_desc)
		);
	}

	// Obtiene los datos básicos de sga_comisiones asociados a la comisión dada.
	function get_datos_basicos_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT	sga_comisiones.comision,
						sga_comisiones.nombre,
						sga_comisiones.periodo_lectivo,
						sga_comisiones.elemento,
						sga_comisiones.turno,
						sga_comisiones.entidad,
						sga_comisiones.catedra,
						sga_comisiones.letra_desde,
						sga_comisiones.letra_hasta,
						sga_comisiones.cupo,
						sga_comisiones.cupo_minimo,
						sga_comisiones.asistencia_por_horas,
						sga_comisiones.asistencia_cantidad_inasist,
						sga_comisiones.inscripcion_habilitada,
						sga_comisiones.ubicacion,
						sga_comisiones.division,
						sga_comisiones.observaciones,
						sga_comisiones.acepta_planes_personalizados,
						sga_comisiones.cobrable,
						sga_comisiones.estado,
						sga_comisiones.inscripcion_cerrada,
						sga_comisiones.inscripcion_cerrada_codigo
				FROM	sga_comisiones
				WHERE	sga_comisiones.comision = $comision
				;";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Datos de comision(es) para carga masiva.
	 *
	 */
	function get_datos_comision($comision, $clave = 'comision', $select_inscriptos = '')
	{
		if (is_array($comision)) {
			$where = ' WHERE ' . guarani_sql::armar_condicion_in($comision, 'sga_comisiones', 'comision', $clave);
		} else {
			$comision = toba::db()->quote($comision);
			$where = " WHERE sga_comisiones.comision = $comision";
		}
		if (empty($select_inscriptos)) {
			$select_inscriptos = ", sga_comisiones_cupo.cant_inscriptos AS cant_inscriptos";
		}

		$sql = "SELECT	sga_comisiones.comision,
						sga_comisiones.nombre AS comision_nombre,
						sga_comisiones.asistencia_por_horas,
						sga_comisiones.asistencia_cantidad_inasist,
						sga_periodos.anio_academico,
						sga_comisiones.periodo_lectivo,
						sga_periodos_genericos.nombre AS periodo_nombre,
						sga_periodos.nombre AS periodo_lectivo_nombre,
						sga_catedras.nombre AS catedra_nombre,
						sga_turnos_cursadas.nombre AS turno_nombre,
						sga_comisiones.ubicacion,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						sga_comisiones.letra_desde,
						sga_comisiones.letra_hasta,
						sga_elementos.elemento,
						get_nombre_actividad_comision(sga_comisiones.comision, 1) || ' (' || COALESCE(sga_elementos.codigo, 'Sin código') || ')' AS actividad_desc,
						f_modalidad_cursada_comision(sga_comisiones.comision) 								AS modalidad_nombre,
						f_instancias_comision(sga_comisiones.comision)		AS instancias_nombre,
						sga_periodos_lectivos.fecha_inicio_dictado,
						sga_periodos_lectivos.fecha_fin_dictado,
						sga_comisiones_cupo.cupo,
						" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_habilitada', 'inscripcion_habilitada') . ",
						sga_comisiones.division,
						sga_divisiones.nombre AS division_nombre,
						f_docentes_comision(sga_comisiones.comision, true)	AS comision_docentes,
						f_horario_comision_aula(sga_comisiones.comision, true) AS horario_comision_clase,
						(SELECT	COUNT(sga_subcomisiones.subcomision)
						 FROM	sga_subcomisiones
						 WHERE	sga_subcomisiones.comision = sga_comisiones.comision
						) AS cant_subcomisiones
						$select_inscriptos
				FROM	sga_comisiones
						JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
						JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
						JOIN sga_periodos_genericos ON sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico
						JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion 
						JOIN sga_comisiones_cupo ON sga_comisiones.comision = sga_comisiones_cupo.comision
						JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
						LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
						LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
						LEFT JOIN sga_divisiones ON sga_comisiones.division = sga_divisiones.division
			    $where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Recupera los datos de la subcomision dada.
	 *
	 */
	function get_datos_subcomision($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);

		$sql = "SELECT 
					sga_subcomisiones.nombre as subcomision_nombre,
					sga_subcomisiones.tipo_clase,
					sga_clases_tipos.nombre as tipo_clase_nombre,
					sga_subcomisiones.inscripcion_habilitada,
					sga_subcomisiones.letra_desde,
					sga_subcomisiones.letra_hasta,
					sga_subcomisiones.cupo
				  
				FROM 
				    sga_subcomisiones,
				    sga_clases_tipos
				    
				WHERE
					sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase AND
					sga_subcomisiones.subcomision = $subcomision
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la descripción del Período Lectivo asociado a una Comisión para la carga de un ef fijo asociado a la misma.
	 *
	 * @param integer $comision id de la comisión para la cual se desea recuperar el nombre del Período Lectivo asociado.
	 */
	function get_descr_per_lectivo_comision($comision)
	{
		$datos_comision = current($this->get_datos_comision($comision));
		if (!empty($datos_comision)) {
			return $datos_comision['periodo_lectivo_nombre'];
		}
	}

	/**
	 * Obtiene el Año Académico asociado a una Comisión para la carga de un ef fijo asociado a la misma.
	 *
	 * @param integer $comision id de la comisión para la cual se desea recuperar el Año Académico asociado.
	 */
	function get_anio_academico_comision($comision)
	{
		$datos_comision = current($this->get_datos_comision($comision));
		if (!empty($datos_comision)) {
			return $datos_comision['anio_academico'];
		}
	}

	/**
	 * Obtiene la descripción del Elemento asociado a una Comisión para la carga de un ef fijo asociado a la misma.
	 *
	 * @param integer $comision id de la comisión para la cual se desea recuperar el nombre del Elemento asociado.
	 */
	function get_descr_actividad_comision($comision)
	{
		$datos_comision = current($this->get_datos_comision($comision));
		if (!empty($datos_comision)) {
			return $datos_comision['actividad_desc'];
		}
	}

	/**
	 * Obtiene el nombre asociado a la comisión dada (método descripción para un ef popup).
	 *
	 * @param integer $comision id de la comisión para la cual se desea recuperar el nombre.
	 */
	function get_nombre_comision($comision)
	{
		$datos_comision = current($this->get_datos_comision($comision));
		if (!empty($datos_comision)) {
			return $datos_comision['comision_nombre'];
		}
	}

	/**
	 * Obtiene la ubicación asociada a la comisión dada
	 *
	 * @param integer $comision id de la comisión para la cual se desea recuperar la ubicación
	 */
	function get_ubicacion_comision($comision)
	{
		$datos_comision = current($this->get_datos_comision($comision));
		if (!empty($datos_comision)) {
			return $datos_comision['ubicacion'];
		}
	}

	/**
	 * Obtiene la cátedra asociada a la comisión dada
	 *
	 * @param integer $comision id de la comisión para la cual se desea recuperar la cátedra
	 */
	function get_catedra_comision($comision)
	{
		$datos_comision = $this->get_datos_basicos_comision($comision);
		if (!empty($datos_comision) && isset($datos_comision['catedra'])) {
			return $datos_comision['catedra'];
		}
	}

	/**
	 * Verifica si una comision dada tiene cupo. Si cupo = 0 la comision no tiene definido cupo, por lo tanto hay.
	 *
	 * @param integer $comision id de la comisión para la cual se desea verificar el cupo.
	 */
	function comision_tiene_cupo($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT 
					CASE
    					WHEN (sga_comisiones_cupo.cupo > 0)
							THEN  (sga_comisiones_cupo.cant_inscriptos < sga_comisiones_cupo.cupo)
	        				ELSE TRUE
					END AS hay_cupo
				FROM 
				   sga_comisiones_cupo 
				WHERE
					sga_comisiones_cupo.comision = $comision;
				";
		$res = guarani_db::consultar($sql);

		return $res[0]['hay_cupo'];
	}

	/**
	 * Verifica si una comision dada tiene subcomisiones asociadas.
	 *
	 * @param integer $comision id de la comisión para la cual se desea verificar si existen subcomisiones.
	 */
	function comision_tiene_subcomisiones($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT	COUNT(sga_subcomisiones.subcomision) as cant_subcom
				FROM 	sga_subcomisiones 
				WHERE	sga_subcomisiones.comision = $comision
			;";
		$res = guarani_db::consultar($sql);

		if ($res[0]['cant_subcom'] > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Chequea si existe la comision enviada. 
	 * Ademas en el where, puede incluirse cualquier otra condicion para chequear su existencia
	 *
	 * @param integer $comision id del elemento para el cual se consulta la cantidad de comisiones.
	 * @param str $where filtro.
	 */
	function existe_comision($comision, $where = '')
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT 
						count(sga_comisiones.comision) as cant
				FROM 
				   		sga_comisiones
				WHERE
					sga_comisiones.comision = $comision 
					$where
				;";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}

	/**
	 * Retorna la cantidad de Clases y la cantidad de Horas de Clases que posee una comisión dada.
	 *
	 * @param integer $comision id de la comisión
	 */
	function get_cantidades_horas_y_clases_comision($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT	COUNT(sga_clases.clase) AS cant_clases,
						SUM(sga_clases.cantidad_horas_dictadas) AS total_horas_clases
				FROM 	sga_clases,
						sga_comisiones_bh
				WHERE	sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
				AND		sga_comisiones_bh.comision = $comision
				AND		sga_clases.valido = 1
				";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna las comisiones relacionadas a divisiones, según el filtro recibido.
	 */
	function get_reporte_comisiones_relacionadas_a_divisiones($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT 		sga_comisiones.nombre,
							sga_periodos.anio_academico,
							sga_periodos.anio_academico AS anio_academico_desc,
							sga_elementos.codigo	AS codigo_actividad,
							get_nombre_actividad_comision(sga_comisiones.comision, 1) as nombre_actividad,
							sga_periodos.nombre		AS nombre_periodo,
							tc.turno,
							tc.nombre 				AS nombre_turno,
							sga_ubicaciones.nombre	AS nombre_ubicacion,
							sga_comisiones.division,
							CASE 
							WHEN sga_comisiones.division IS NULL
								THEN 'Sin división asociada'
								ELSE 'División: ' || sga_divisiones.nombre || ' - ' || 'Turno: ' || sga_turnos_cursadas.nombre
							END														AS division_turno_nombres,
							CASE 
							WHEN sga_comisiones.division IS NULL
								THEN 'Sin división asociada'
								ELSE 'División: ' || sga_divisiones.nombre || ' - ' || 'Turno: ' || sga_turnos_cursadas.nombre
							END														AS division_turno_nombres_desc,
							f_modalidad_cursada_comision(sga_comisiones.comision)	AS nombre_modalidad

				FROM 		sga_comisiones
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							LEFT JOIN sga_divisiones ON sga_comisiones.division = sga_divisiones.division
							LEFT JOIN sga_turnos_cursadas ON sga_divisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_turnos_cursadas AS tc ON sga_comisiones.turno = tc.turno

				$where
				
				ORDER BY	sga_periodos.anio_academico,
							division_turno_nombres,
							sga_elementos.codigo,
							sga_elementos.nombre,
							sga_comisiones.nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de Comisiones que tienen una División asociada.
	 * @param str $where filtro sobre las comisiones a recuperar.
	 */
	function get_comisiones_con_divisiones($where)
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 		sga_comisiones.comision,
							sga_comisiones.nombre,
							sga_comisiones.periodo_lectivo,
							sga_comisiones.elemento, 
							sga_comisiones.ubicacion,
							sga_comisiones.division
							
				FROM 		sga_comisiones

				WHERE		sga_comisiones.division IS NOT NULL
							$where
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de comisiones, para la API rest. Se puede usar internamente, pero no se puede modificar ya que
	 * podría impactar en la API. Solo modificar para modificaciones en la API.
	 */
	function get_listado_rest($where = '', $con_horarios = 0, $con_docentes = 0, $solo_vigentes = false, $order = '', $limit = '', $filtro = array())
	{
		$select = '';
		$from = '';

		if ($where) {
			$where = 'AND ' . $where;
		}
		if ($solo_vigentes) {
			$where .= 'AND CURRENT_DATE BETWEEN sga_periodos.fecha_inicio AND sga_periodos.fecha_fin';
		}
		if (isset($filtro['docente'])) {
			$docente = (int) $filtro['docente'];
			$from .= ', sga_docentes_comision
					  , sga_docentes_responsabilidades
					  , sga_docentes';

			$select .= ', sga_docentes_comision
						, sga_docentes_responsabilidades.nombre as responsabilidad ';
			$where .= "
				AND sga_docentes_comision.docente = $docente
				AND	sga_docentes_comision.comision = sga_comisiones.comision
				AND sga_docentes_comision.docente = sga_docentes.docente
				AND sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad

			";
		}

		$sql = "SELECT  	sga_comisiones.comision,
							sga_comisiones.nombre,
							sga_comisiones.periodo_lectivo,
							sga_comisiones.elemento,
							sga_comisiones.turno,
							sga_comisiones.entidad,
							" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_habilitada', 'inscripcion_habilitada') . ",
							sga_comisiones.catedra,
							sga_comisiones.ubicacion,
							sga_periodos.nombre	as				nombre_periodo,
							sga_periodos.anio_academico	as		anio_academico_periodo,
							sga_periodos.fecha_inicio as		fecha_inicio_periodo,
							sga_elementos.codigo as				codigo_actividad,
							get_nombre_actividad_comision(sga_comisiones.comision, 1) as nombre_actividad,
							sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
							sga_turnos_cursadas.nombre as		nombre_turno,

							f_modalidad_cursada_comision(sga_comisiones.comision) as nombre_modalidad,
							f_instancias_comision(sga_comisiones.comision) 		  as nombre_instancias,

							sga_catedras.nombre as				nombre_catedra,
							sga_ubicaciones.nombre as			nombre_ubicacion
							$select
				FROM		sga_comisiones
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra,
							sga_periodos_lectivos,
							sga_periodos,
							sga_elementos,
							sga_ubicaciones
							$from

				WHERE		sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
							sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							sga_comisiones.elemento = sga_elementos.elemento AND
							sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							$where 	$order	$limit
		";
		$comisiones = guarani::db()->consultar($sql);

		$modelos = recurso_comisiones::_get_modelos();

		$comisiones_ids = array_column($comisiones, 'comision');

		if ($con_horarios == 1 && !empty($comisiones_ids)) {

			$horarios_comisiones = $this->get_horarios_comisiones($comisiones_ids);

			$horarios_comisiones_final = array();

			foreach ($horarios_comisiones as $horario_comision) {

				if (is_null($horario_comision['horario_dia']) && is_null($horario_comision['horario_inicio']) && is_null($horario_comision['horario_fin']))
					continue;

				$horarios_comisiones_final[$horario_comision['comision']][] = array(
					'horario_dia' => $horario_comision['horario_dia'],
					'horario_inicio' => $horario_comision['horario_inicio'],
					'horario_fin' => $horario_comision['horario_fin'],
				);
			}

			unset($horarios_comisiones);
		}

		if ($con_docentes == 1 && !empty($comisiones_ids)) {

			$docentes_comisiones = $this->get_docentes_comisiones($comisiones_ids);

			$docentes_comisiones_final = array();

			foreach ($docentes_comisiones as $docente_comision) {

				$docentes_comisiones_final[$docente_comision['comision']][] = array(
					'responsabilidad' => $docente_comision['responsabilidad'],
					'docente' => $docente_comision['docente'],
					'legajo' => $docente_comision['legajo'],
					'documento' => $docente_comision['documento'],
					'apellido' => $docente_comision['apellido'],
					'nombres' => $docente_comision['nombres'],
				);
			}

			unset($docentes_comisiones);
		}

		unset($comisiones_ids);

		foreach ($comisiones as $c => $comision) {
			$comisiones[$c]['actividad_arr']['codigo'] = $comision['codigo_actividad'];
			$comisiones[$c]['actividad_arr']['nombre'] = $comision['nombre_actividad'];
			$comisiones[$c]['turno_arr']['turno'] = $comision['turno'];
			$comisiones[$c]['turno_arr']['nombre'] = $comision['nombre_turno'];
			$comisiones[$c]['ubicacion_arr']['ubicacion'] = $comision['ubicacion'];
			$comisiones[$c]['ubicacion_arr']['nombre'] = $comision['nombre_ubicacion'];
			$comisiones[$c]['periodo_lectivo_arr']['periodo_lectivo'] = $comision['periodo_lectivo'];
			$comisiones[$c]['periodo_lectivo_arr']['nombre'] = $comision['nombre_periodo'];

			if (($con_horarios == 1) && (isset($horarios_comisiones_final[$comision['comision']]))) {
				$horarios_comision_hidratados = rest_hidratador::hidratar($modelos['HorarioComision'], $horarios_comisiones_final[$comision['comision']]);
				$comisiones[$c]['horarios'] = $horarios_comision_hidratados;
			} else {
				$comisiones[$c]['horarios'] = array();
			}

			if (($con_docentes == 1) && (isset($docentes_comisiones_final[$comision['comision']]))) {
				$docentes_comision_hidratados = rest_hidratador::hidratar($modelos['DocenteComision'], $docentes_comisiones_final[$comision['comision']]);
				$comisiones[$c]['docentes'] = $docentes_comision_hidratados;
			} else {
				$comisiones[$c]['docentes'] = array();
			}

			unset($comisiones[$c]['codigo_actividad']);
			unset($comisiones[$c]['nombre_actividad']);
			unset($comisiones[$c]['turno']);
			unset($comisiones[$c]['nombre_turno']);
			unset($comisiones[$c]['ubicacion']);
			unset($comisiones[$c]['nombre_ubicacion']);
			unset($comisiones[$c]['periodo_lectivo']);
			unset($comisiones[$c]['nombre_periodo']);
		}
		return $comisiones;
	}

	public function get_horarios_comisiones(array $ids)
	{

		$IN = implode(',', $ids);
		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);

		$sql = "SELECT
					sga_comisiones.comision,
					sga_asignaciones.dia_semana as horario_dia,
					to_char(sga_asignaciones.hora_inicio, $formato_hora_visual) as horario_inicio,
					to_char(sga_asignaciones.hora_finalizacion, $formato_hora_visual) as horario_fin,
					(CASE sga_asignaciones.dia_semana
				        WHEN 'Lunes' THEN 1
				        WHEN 'Martes' THEN 2
				        WHEN 'Miercoles' THEN 3
				        WHEN 'Jueves' THEN 4
				        WHEN 'Viernes' THEN 5
				        WHEN 'Sabado' THEN 6
				        WHEN 'Domingo' THEN 7
						ELSE 8
					END) as dia_semana_orden,
					sga_asignaciones.hora_inicio
				FROM
					sga_comisiones
					LEFT JOIN sga_comisiones_bh ON sga_comisiones_bh.comision = sga_comisiones.comision
					LEFT JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				WHERE sga_comisiones.comision IN ($IN)
				ORDER BY dia_semana_orden, sga_asignaciones.hora_inicio
				";
		return guarani::db()->consultar($sql);
	}

	public function get_docentes_comisiones(array $ids)
	{

		$IN = implode(',', $ids);

		$estado_activo = toba::db()->quote(comunes::estado_activo);

		$sql = "SELECT 		sga_docentes.docente,
				  			sga_docentes.legajo,
				  			sga_docentes.persona,"
				. guarani_sql::get_case('sga_docentes', 'estado', 'estado_docente') . ",
				  			vw_personas.apellido 			AS apellido,
				  			vw_personas.nombres 			AS nombres,
				  			vw_personas.apellido_nombres 	AS nombre_persona,
							vw_personas.tipo_nro_documento 	AS documento,
							mdp_personas_sexo.descripcion 	AS sexo,
				   			vw_personas.usuario 			AS usuario,
				   			mdp_nacionalidades.descripcion 	AS nacionalidad,
				   			sga_docentes_responsabilidades.nombre as responsabilidad,
				   			sga_comisiones.comision
				FROM 		vw_personas,
				     		sga_docentes,
							mdp_personas_sexo,
							mdp_nacionalidades,
							sga_docentes_comision,
							sga_docentes_responsabilidades,
							sga_comisiones,
							sga_periodos_lectivos
				WHERE 		sga_docentes.persona = vw_personas.persona AND
							mdp_personas_sexo.sexo 	= vw_personas.sexo AND
							mdp_nacionalidades.nacionalidad = vw_personas.nacionalidad AND
							sga_docentes_comision.comision = sga_comisiones.comision AND
							sga_docentes_comision.docente = sga_docentes.docente AND
							sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad AND
							sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
							sga_docentes.estado = $estado_activo AND
							sga_docentes_comision.comision IN ($IN)
				";

		return guarani::db()->consultar($sql);
	}

	/*
	 * Obtener las propuestas que se relacionan con planes personalizados activos o vigentes
	 * que tienen asociada la actividad a la que se vincula la propuesta
	 * y además que la actividad del plan personalizado no esté aprobada por el alumno que lo cursa.
	 */
	function get_propuestas_a_vincular($actividad)
	{
		$sql = "SELECT DISTINCT
						vw_elementos_plan.propuesta,
						vw_elementos_plan.plan
				FROM 	vw_elementos_plan
						JOIN sga_planes_versiones ON vw_elementos_plan.plan_version	= sga_planes_versiones.plan_version
						JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
						JOIN sga_elementos_plan ON vw_elementos_plan.elemento_revision = sga_elementos_plan.elemento_revision
				WHERE 	vw_elementos_plan.elemento = $actividad
						AND sga_planes.tipo_plan IN ('" . planes::tipo_personalizado . "','" . planes::tipo_convenio . "')
						AND	sga_planes.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "')	
						AND sga_elementos_plan.requiere_cursada = '" . comunes::si . "'
						AND NOT EXISTS (SELECT 	1
										FROM 	vw_hist_academica
										WHERE 	vw_hist_academica.elemento 			= vw_elementos_plan.elemento
												AND vw_hist_academica.plan_version 	= vw_elementos_plan.plan_version
												AND vw_hist_academica.resultado 	= '" . escalas_notas::resultado_aprobado . "'
										)
		";
		return guarani::db()->consultar($sql);
	}

	/**
	 * Obtiene (si existen) las nuevas fechas de inicio y fin de dictado de una comisión
	 * 
	 * @param	int $comision
	 * @return	array
	 */
	function get_fechas_excep_perlect($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT 
					sga_comisiones_excep_perlect.fecha_inicio_dictado,
					sga_comisiones_excep_perlect.fecha_fin_dictado
				FROM 
					sga_comisiones_excep_perlect
				WHERE
					sga_comisiones_excep_perlect.comision = $comision";

		return guarani::db()->consultar_fila($sql);
	}

	//--------------------------------------------------------------------------
	//---- Comisiones para cursos
	//--------------------------------------------------------------------------
	/**
	 * Obtiene un listado de comisiones definidas para administrar cursos
	 * 
	 * @param int $propuesta Propuesta englobadora
	 * @param int $plan Plan único de la propuesta
	 * @param int $plan_version Plan_version único de la propuesta
	 * @param string $where
	 * @param boolean $para_inscripcion Cursos con inscripción habilitada y vigente
	 * @param boolean $con_costo Se debe buscar el costo de los cursos cobrables
	 * @param string $param_cur_controlar_cupo_por
	 * @param boolean $con_cursado_no_finalizado
	 * @param array $params_sistema
	 * @param int $interfaz
	 * 
	 * @return array
	 */
	function get_cursos($propuesta, $plan, $plan_version, $where = '', $para_inscripcion = true, $con_costo = true, $param_cur_controlar_cupo_por = 'comision', $con_cursado_no_finalizado = true, $params_sistema = [], $interfaz = comunes::interfaz_gestion, $para_bajas = false)
	{
		$usa_sq = $params_sistema['sq_usa_sanaviron'];
		$alumno = 'NULL';
		$propuesta_no_quote = $propuesta;
		$propuesta = toba::db()->quote($propuesta);
		$plan = toba::db()->quote($plan);
		$plan_version = toba::db()->quote($plan_version);

		if ($where) {
			$where = 'AND ' . $where;
		}
		
		
		if ($para_inscripcion) {
			// Comisiones con periodos de inscripcion vigente.
			$where .= " 
					-- Con inscripción habilitada
					AND	sga_comisiones.inscripcion_habilitada = '" . comunes::si . "'
					-- Con período de inscripción vigente
					AND sga_comisiones.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad($propuesta, $plan, $plan_version, sga_elementos.elemento, true, null, $interfaz, $alumno))";
		}					
		if ($para_bajas) {
			// Comisiones con fecha de tope de bajas vigente.
			$where .= " AND	f_comision_con_tope_bajas_vigente($plan_version, sga_comisiones.comision, $interfaz, $alumno)";
		}	
			

		if ($param_cur_controlar_cupo_por == 'comision') {
			$cant_insc = ',cupo.cant_inscriptos as cant_inscriptos';
		} else {
			// Busca los inscriptos a todas las comisiones que comparten las bandas horarias
			// de la comision que seleccionó el alumno para inscribirse.
			$cant_insc = ',(SELECT COALESCE(SUM(sga_comisiones_cupo.cant_inscriptos), 0)
											FROM sga_comisiones_cupo
											WHERE sga_comisiones_cupo.comision IN (
												SELECT DISTINCT bh2.comision
													FROM sga_comisiones_bh,
															sga_comisiones_bh as bh2
													WHERE sga_comisiones_bh.comision = sga_comisiones.comision
														AND bh2.asignacion = sga_comisiones_bh.asignacion
													)
											) as cant_inscriptos';
		}

		if ($con_cursado_no_finalizado) {
			$where .= " AND CURRENT_DATE <= COALESCE(sga_comisiones_excep_perlect.fecha_fin_dictado, sga_periodos_lectivos.fecha_fin_dictado)";
		}

		$sql = "SELECT  	
					sga_comisiones.comision,
					sga_comisiones.nombre,
					sga_comisiones.elemento,
					sga_periodos.anio_academico,
					sga_comisiones.periodo_lectivo,
					sga_periodos.nombre as periodo_lectivo_nombre,
					sga_elementos.elemento AS actividad,
					get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_nombre,
					sga_elementos.codigo AS	actividad_codigo,
					'(' || sga_elementos.codigo || ') ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_codigo_y_nombre,
					get_nombre_actividad_comision(sga_comisiones.comision, 1) || ' - ' || sga_comisiones.nombre AS actividad_comision,
					f_horario_comision(sga_comisiones.comision) AS horario_descr,
					f_horario_comision_aula(sga_comisiones.comision, false) AS horario_aula_descr,
					sga_comisiones.ubicacion,
					sga_comisiones.cobrable,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					f_docentes_comision(sga_comisiones.comision, false) AS docentes,
					f_docentes_comision(sga_comisiones.comision, true) AS docentes_responsabilidad,
					COALESCE(sga_comisiones_excep_perlect.fecha_inicio_dictado, sga_periodos_lectivos.fecha_inicio_dictado) AS fecha_inicio_dictado,
					to_char(COALESCE(sga_comisiones_excep_perlect.fecha_inicio_dictado, sga_periodos_lectivos.fecha_inicio_dictado), '" . comunes::formato_fecha_visual . "') as fecha_inicio_dictado_visual,
					COALESCE(sga_comisiones_excep_perlect.fecha_fin_dictado, sga_periodos_lectivos.fecha_fin_dictado) AS fecha_fin_dictado,
					to_char(COALESCE(sga_comisiones_excep_perlect.fecha_fin_dictado, sga_periodos_lectivos.fecha_fin_dictado), '" . comunes::formato_fecha_visual . "') as fecha_fin_dictado_visual,
					f_comision_con_tope_bajas_vigente($plan_version, sga_comisiones.comision, $interfaz, $alumno) as baja_habilitada,
					CASE
                        WHEN (cupo.cupo IS NULL OR cupo.cupo = 0 OR cupo.cupo > cupo.cant_inscriptos) THEN 'S'
                        ELSE 'N'
                    END as tiene_cupo,
                    cupo.cupo,
                    sga_comisiones.inscripcion_cerrada,
                    sga_comisiones.inscripcion_cerrada_codigo
                    $cant_insc,
					f_modalidad_cursada_comision(sga_comisiones.comision) as modalidad 					,
					(SELECT array_to_string(array_agg(DISTINCT dia_semana), ',')
					FROM sga_comisiones_bh
					JOIN sga_asignaciones ON (sga_comisiones_bh.asignacion = sga_asignaciones.asignacion)
					WHERE sga_comisiones_bh.comision = sga_comisiones.comision) AS dias_semana
				FROM		
					sga_comisiones					
					JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
					JOIN sga_elementos_revision ON sga_elementos.elemento = sga_elementos_revision.elemento
					JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
					LEFT JOIN sga_comisiones_cupo AS cupo ON cupo.comision = sga_comisiones.comision
					LEFT JOIN sga_comisiones_excep_perlect ON sga_comisiones_excep_perlect.comision = sga_comisiones.comision
					
				WHERE		
					sga_elementos_plan.plan_version = $plan_version
					-- Comisiones activas y pendientes
					AND sga_comisiones.estado IN ('" . comunes::estado_activo . "', '" . comunes::estado_pendiente . "')
					$where
				";

		$datos = guarani::db()->consultar($sql);

		if (($con_costo) && ($usa_sq == comunes::si)) {
			foreach ($datos as $key => $dato) {

				if ($dato['cobrable'] == comunes::si) {

					$id_externo_SQ = "{$propuesta_no_quote}|{$dato['elemento']}|{$dato['comision']}";
					$fallo_comunicacion_SQ = false;

					try {
						$info_valorizaciones_SQ = cobro::get_servicios_instancias_valorizaciones($id_externo_SQ);
					} catch (Exception $e) {
						$fallo_comunicacion_SQ = true;
					}


					if ($fallo_comunicacion_SQ) {
						$costo = "No se pudo obtener";
					} elseif (empty($info_valorizaciones_SQ)) {
						$costo = "Sin definir";
					} else {
						$info_valorizacion_SQ = current($info_valorizaciones_SQ);
						if ($info_valorizacion_SQ['id_moneda'] == cobro::MONEDA_PESOS) {
							$moneda_simbolo = cobro::MONEDA_PESOS_SIMBOLO;
						} elseif ($info_valorizacion_SQ['id_moneda'] == cobro::MONEDA_DOLAR) {
							$moneda_simbolo = cobro::MONEDA_DOLAR_SIMBOLO;
						} elseif ($info_valorizacion_SQ['id_moneda'] == cobro::MONEDA_EURO) {
							$moneda_simbolo = cobro::MONEDA_EURO_SIMBOLO;
						}

						$costo = $moneda_simbolo . round($info_valorizacion_SQ['importe'], 2);
					}

					$datos[$key]['costo'] = $costo;
				}
			}
		}

		return $datos;
	}

	/**
	 * Obtiene una modalidad de la una comisión para usar en la inscripción a 
	 * la propuesta que abarca el curso.
	 * 
	 * @param int $comision
	 */
	function get_modalidad_inscripcion($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT	sga_comisiones_modalidad.modalidad
				FROM	sga_comisiones_modalidad
				WHERE	sga_comisiones_modalidad.comision = $comision
				LIMIT	1";

		$datos = guarani::db()->consultar_fila($sql);
		return $datos['modalidad'];
	}

	/**
	 * Informa si una comisión es cobrable
	 * 
	 * @param	int $comision
	 * @return	boolean
	 */
	function es_cobrable($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "SELECT	sga_comisiones.cobrable
				FROM	sga_comisiones
				WHERE	sga_comisiones.comision = $comision
				";

		$datos = guarani::db()->consultar_fila($sql);
		return $datos['cobrable'] == comunes::si;
	}

	/**
	 * Retorna el listado de oferta de cursos vigentes.
	 * @param string $inscripcion_vigente filtro sobre las incripciones vigentes o no.
	 */
	function get_info_oferta_cursos($inscripcion_vigente = '1')
	{
		$from_insc_vigente = "";
		$where_insc_vigente = "";
		if ($inscripcion_vigente == '1') {
			// Solo comisiones de inscripciones vigentes
			$from_insc_vigente = " JOIN sga_planes_versiones ON sga_planes_versiones.plan = sga_planes.plan ";
			$where_insc_vigente = " AND sga_comisiones.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad(sga_propuestas.propuesta, sga_planes.plan, sga_planes_versiones.plan_version, sga_comisiones.elemento, true, sga_comisiones.ubicacion, ".comunes::interfaz_gestion.", null))";
		}

		$si = toba::db()->quote(comunes::si);
		$tipo_curso = toba::db()->quote(propuesta::tipo_curso);
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);

		$sql = "
            SELECT
                sga_propuestas.nombre as propuesta_nombre,
                vw_actividades.nombre as actividad_nombre,
                vw_comisiones.comision_nombre  as comision_nombre,
                sga_ubicaciones.nombre as ubicacion_nombre,
                vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
                (CASE WHEN vw_comisiones.inscripcion_cerrada = 'S' THEN 'Si' ELSE 'No' END) as es_inscripcion_cerrada,
                sga_comisiones.cupo_minimo as cupo_minimo,
                vw_comisiones.cupo as cupo_maximo,
                f_modalidad_cursada_comision(vw_comisiones.comision) as modalidades_cursada,
                f_horario_comision_aula(vw_comisiones.comision,true) as horarios,    
                (CASE WHEN sga_comisiones.cobrable = 'S' THEN 'Si' ELSE 'No' END) as es_cobrable,
                '' as costos,
                vw_comisiones.estado as estado,
                f_docentes_comision(vw_comisiones.comision, true) as docentes,
                '' as periodo_inscripcion,
                '' as periodo_dictado,
                to_char(vw_comisiones.fecha_inicio_dictado, $formato_fecha_visual) as fecha_inicio_dictado,
                to_char(vw_comisiones.fecha_fin_dictado, $formato_fecha_visual) as fecha_fin_dictado,
                sga_propuestas.propuesta as propuesta,
                vw_actividades.elemento as actividad,
                vw_comisiones.comision  as comision

            FROM
                vw_actividades
                JOIN vw_comisiones ON vw_comisiones.elemento = vw_actividades.elemento
                JOIN sga_comisiones ON sga_comisiones.comision = vw_comisiones.comision
                JOIN sga_comisiones_propuestas ON sga_comisiones_propuestas.comision = vw_comisiones.comision 
                JOIN sga_propuestas ON sga_propuestas.propuesta = sga_comisiones_propuestas.propuesta
                JOIN sga_planes ON sga_planes.plan = sga_comisiones_propuestas.plan
                $from_insc_vigente
                JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = vw_comisiones.periodo_lectivo
                JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
                JOIN vw_periodos_lectivos ON vw_periodos_lectivos.periodo_lectivo = vw_comisiones.periodo_lectivo
                LEFT JOIN sga_comisiones_cupo as cupo ON cupo.comision =  vw_comisiones.comision
                LEFT JOIN sga_catedras ON vw_comisiones.catedra = sga_catedras.catedra
                LEFT JOIN sga_turnos_cursadas ON vw_comisiones.turno_cursada = sga_turnos_cursadas.turno

            WHERE 
                vw_comisiones.inscripcion_habilitada = $si
                AND sga_periodos_lectivos.fecha_publicacion_comisiones <= CURRENT_DATE  
                AND vw_comisiones.fecha_inicio_dictado >= CURRENT_DATE  -- No comenzó el dictado
                AND sga_propuestas.propuesta_tipo = $tipo_curso  -- Propuestas de Tipo Curso
                AND sga_planes.administra_cursos  = $si  -- Planes que administran cursos
                $where_insc_vigente

            ORDER BY
                vw_comisiones.anio_academico,
                vw_actividades.nombre,
                vw_comisiones.fecha_inicio,
                vw_comisiones.comision_nombre
            ";
		$datos = guarani::db()->consultar($sql);
		return $datos;
	}

	/*
	  Reporte de comisiones que tienen alumnos en instancias de cursada que no estan en actas de cursadas
	  e instancias de promocion que no estan en acta de promocion.
	  Se listan las comisiones por cada instancia, solo por la instancia en donde hay alumnos que no estan en algun
	  acta abierta o cerrada, es decir solo asignados a la comision.

	 */
	function get_comisiones_con_alumnos_no_estan_en_acta($anio_academico, $where = '')
	{
		$anio_academico = toba::db()->quote($anio_academico);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT	vw_comisiones.anio_academico as anio_academico,
						cast(vw_comisiones.anio_academico as varchar) || ' - ' || vw_comisiones.periodo_lectivo_nombre as periodo_anio_nombre,	
						vw_comisiones.periodo_lectivo,
						vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,	
						vw_comisiones.elemento as actividad, 
						vw_actividades.nombre || '(' || vw_actividades.codigo || ')' as codigo_y_nombre_actividad, 
						vw_comisiones.comision as comision,
						vw_comisiones.comision_nombre as comision_nombre,
						sga_ubicaciones.ubicacion as ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre,
						sga_evaluaciones.evaluacion as evaluacion,
						sga_instancias.instancia as instancia,
						sga_instancias.nombre as instancia_nombre,
						CASE
							WHEN sga_instancias.instancia = ".comision::inst_cursar." THEN (SELECT count(1) FROM sga_actas WHERE comision = vw_comisiones.comision AND origen = '".acta::origen_regulares."' AND estado IN ('".acta::estado_abierta."','".acta::estado_cerrada."'))
							WHEN sga_instancias.instancia = ".comision::inst_promocionar." THEN (SELECT count(1) FROM sga_actas WHERE comision = vw_comisiones.comision AND origen = '".acta::origen_promociones."' AND estado IN ('".acta::estado_abierta."','".acta::estado_cerrada."'))
						END as cant_actas,
						CASE
							-- Acta de Regularidad
							WHEN sga_instancias.instancia = 1 THEN (SELECT	count(1)
																	FROM	sga_eval_detalle_cursadas
																	WHERE	evaluacion = sga_evaluaciones.evaluacion AND
																			id_acta_cursada IS NULL AND
																			instancia_cursada = 1 AND
																			alumno NOT IN (	SELECT	da.alumno
																							FROM	sga_actas as a
																									JOIN sga_actas_detalle as da ON da.id_acta = a.id_acta
																							WHERE	a.comision = vw_comisiones.comision AND
																									a.estado = '".acta::estado_cerrada."' AND
																									a.origen = '".acta::origen_regulares."' AND
																									da.estado = '".acta::detalle_estado_activo."'))
							 -- Acta de Promoción
							WHEN sga_instancias.instancia = 1 THEN (SELECT	count(1) 
																	FROM	sga_eval_detalle_cursadas
																	WHERE	evaluacion = sga_evaluaciones.evaluacion AND
																			id_acta_promocion IS NULL AND
																			instancia_promocion = ".comision::inst_promocionar." AND
																			alumno NOT IN (	SELECT	alumno
																							FROM	sga_actas as a
																									JOIN sga_actas_detalle as da ON da.id_acta = a.id_acta  
																							WHERE	a.comision = vw_comisiones.comision AND
																									a.estado = '".acta::estado_cerrada."' AND
																									a.origen = '".acta::origen_promociones."' AND
																									da.estado = '".acta::detalle_estado_activo."'))
						END as cant_alumnos_sin_acta
				FROM		vw_comisiones
							JOIN sga_comisiones_instancias ON vw_comisiones.comision = sga_comisiones_instancias.comision
							JOIN sga_instancias ON sga_comisiones_instancias.instancia = sga_instancias.instancia
							JOIN sga_evaluaciones ON vw_comisiones.entidad = sga_evaluaciones.entidad
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
							JOIN vw_actividades ON vw_comisiones.elemento = vw_actividades.elemento
							JOIN sga_ubicaciones ON vw_comisiones.ubicacion = sga_ubicaciones.ubicacion
				WHERE		vw_comisiones.anio_academico = $anio_academico AND
							sga_evaluaciones_tipos.automatica = '".comunes::si."' AND
							((EXISTS (	SELECT	1
										FROM	sga_eval_detalle_cursadas
										WHERE	evaluacion = sga_evaluaciones.evaluacion AND
												id_acta_cursada IS NULL AND
												instancia_cursada = 1 AND
												-- Alumno no esta en un acta de cursada cerrada de la comision
												alumno NOT IN (	SELECT	da.alumno 
																FROM	sga_actas as a
																		JOIN sga_actas_detalle as da ON da.id_acta = a.id_acta
																WHERE	a.comision = vw_comisiones.comision AND
																		a.estado = '".acta::estado_cerrada."' AND
																		a.origen = '".acta::origen_regulares."' AND
																		da.estado = '".acta::detalle_estado_activo."')) AND
										sga_comisiones_instancias.instancia = ".comision::inst_cursar.") OR
							(EXISTS (	SELECT	1 
										FROM	sga_eval_detalle_cursadas
										WHERE	evaluacion = sga_evaluaciones.evaluacion AND
												instancia_promocion = 1 AND
												id_acta_promocion IS NULL AND
												alumno NOT IN (	SELECT	da.alumno 
																FROM	sga_actas as a
																		JOIN sga_actas_detalle as da ON da.id_acta = a.id_acta
																WHERE	a.comision = vw_comisiones.comision AND
																		a.estado = '".acta::estado_cerrada."' AND
																		a.origen = '".acta::origen_promociones."' AND
																		da.estado = '".acta::detalle_estado_activo."'
																UNION 					
																-- Alumnos promocionales que no promocionaron y no fueron incluidos en el acta de promocion cerrada ni en la de no promocionados.
																SELECT	1 
																FROM	sga_eval_detalle_cursadas as d
																WHERE	d.evaluacion = sga_evaluaciones.evaluacion AND
																		d.instancia_promocion = 1 AND -- El alumno es promocional
																		d.id_acta_promocion IS NOT NULL	AND -- El alumno estaba asignado al acta de promocion
																		d.acta_promocion_cerrada = 1 AND -- El acta de promocion esta cerrada 	
																		COALESCE(d.resultado_promocion,'') <> '".escalas_notas::resultado_aprobado."' AND -- No promocionó
																		NOT EXISTS (SELECT	1 
																					FROM	sga_actas as a 
																							JOIN sga_actas_detalle as d2 ON d2.id_acta = a.id_acta AND d2.alumno = d.alumno
																					WHERE	a.id_acta = d.id_acta_promocion AND
																							a.estado = '".acta::estado_cerrada."' AND
																							d2.estado = '".acta::detalle_estado_activo."' AND
																							d2.rectificado = '".comunes::no."'
																					UNION   													 
																					-- No existe en la tabla de NO promocionados del acta de promocion.
																					SELECT	1  
																					FROM	sga_actas_alumnos_no_prom as anp
																					WHERE	anp.acta = d.id_acta_promocion AND
																							anp.alumno = d.alumno))) AND
										sga_comisiones_instancias.instancia = ".comision::inst_cursar."))
							$where
				ORDER BY	vw_comisiones.fecha_inicio,
							vw_comisiones.periodo_lectivo_nombre,
							vw_actividades.nombre,
							vw_comisiones.comision_nombre,
							sga_instancias.instancia
		";
		return guarani::db()->consultar($sql);
	}

	/*
	  Recupera el listado de alumnos de una comision e instancia que no estan en algun acta
	  (abierta/cerrada) de esa instancia (Regularidad /Promocion).
	 */
	function get_alumnos_comision_no_estan_en_acta($comision, $instancia)
	{
		$sql = "SELECT
					vw_personas.apellido_nombres,	
					sga_alumnos.legajo,
					sga_alumnos_calidad.nombre as calidad,
					CASE sga_eval_detalle_cursadas.inscripto
					  WHEN 'S' THEN 'Si'
					  WHEN 'N' THEN 'No'
					END as alumno_inscripto
				FROM
					sga_comisiones, 
					sga_evaluaciones,
					sga_evaluaciones_tipos,
					sga_eval_detalle_cursadas,
					sga_alumnos,
					vw_personas,
					sga_alumnos_calidad
				WHERE sga_comisiones.comision = $comision
				  AND sga_evaluaciones.entidad = sga_comisiones.entidad
				  AND sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				  AND sga_evaluaciones_tipos.automatica = 'S'
				  AND sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion
				  AND sga_alumnos.alumno = sga_eval_detalle_cursadas.alumno
				  AND sga_alumnos.calidad = sga_alumnos_calidad.calidad
				  AND sga_alumnos.persona = vw_personas.persona
				  AND (($instancia = 1 AND sga_eval_detalle_cursadas.instancia_cursada = 1 AND sga_eval_detalle_cursadas.id_acta_cursada IS NULL AND
				  		sga_eval_detalle_cursadas.alumno NOT IN (SELECT d.alumno FROM sga_actas as a, sga_actas_detalle as d
																	WHERE a.comision = sga_comisiones.comision
																	  AND a.origen = 'R'
																	  AND a.estado = 'C'
																	  AND d.id_acta = a.id_acta
																	  AND d.estado = 'A')
						) OR											  
						($instancia = 2 AND sga_eval_detalle_cursadas.instancia_promocion = 1 AND sga_eval_detalle_cursadas.id_acta_promocion IS NULL AND 
				  		 sga_eval_detalle_cursadas.alumno NOT IN (SELECT d.alumno FROM sga_actas as a, sga_actas_detalle as d
																	WHERE a.comision = sga_comisiones.comision
																	  AND a.origen = 'P'
																	  AND a.estado = 'C'
																	  AND d.id_acta = a.id_acta
																	  AND d.estado = 'A')
						)
						)		
				ORDER BY vw_personas.apellido_nombres
				";
		return guarani::db()->consultar($sql);
	}

	function get_reporte_cursada($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		//Creo la tabla temporal que luego voy a consultar.
		$sql = "CREATE TEMP TABLE temp_estadistica (comision integer, comision_nombre varchar,		
													elemento integer, elemento_nombre varchar,
													cond_regularidad integer, cond_regularidad_desc varchar, 
													cantidad integer
													);";

		guarani::db()->ejecutar($sql);
		$sql = "INSERT INTO temp_estadistica
				(SELECT 
					vw_comisiones.comision as comision,
					vw_comisiones.comision_nombre as comision_nombre,
					vw_comisiones.elemento as elemento,
					vw_comisiones.elemento_nombre as elemento_nombre,
					sga_eval_detalle_cursadas.cond_regularidad as cond_regularidad,
					COALESCE(sga_cond_regularidad.nombre,'Sin Condición') as cond_regularidad_desc,
					COUNT(*) as cantidad
				  FROM 
					vw_comisiones
					JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_comisiones.entidad
					JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
					JOIN sga_eval_detalle_cursadas ON sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion
					LEFT JOIN sga_cond_regularidad ON sga_cond_regularidad.cond_regularidad = sga_eval_detalle_cursadas.cond_regularidad
				WHERE 
					sga_evaluaciones_tipos.automatica = 'S'  -- Solo evaluaciones relacionadas con actas de cursadas
					AND sga_eval_detalle_cursadas.instancia_cursada = 1      
					AND sga_eval_detalle_cursadas.acta_cursada_cerrada = 0
					$where
				GROUP BY 
					1, 2, 3, 4, 5, 6
				)
				UNION (
				SELECT 
					sga_actas.comision,
					vw_comisiones.comision_nombre as comision_nombre,
					vw_comisiones.elemento as elemento,
					vw_comisiones.elemento_nombre as elemento_nombre,
					sga_actas_detalle.cond_regularidad,
					COALESCE(sga_cond_regularidad.nombre,'Sin Condición') as cond_regularidad_desc,
					COUNT(*) as cantidad
				FROM 
					vw_comisiones   	   
					JOIN sga_actas ON sga_actas.comision = vw_comisiones.comision
					JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
					LEFT JOIN sga_cond_regularidad ON sga_cond_regularidad.cond_regularidad = sga_actas_detalle.cond_regularidad
				WHERE 
					sga_actas.origen = 'R'
					AND sga_actas.tipo_acta = 'N'
					AND sga_actas.estado = 'C'
					AND sga_actas_detalle.estado = 'A'
					AND sga_actas_detalle.rectificado = 'N'
					$where
				GROUP BY 
					1, 2, 3, 4, 5, 6);
				";
		guarani::db()->ejecutar($sql);

		$sql = "
				SELECT 
					comision, 
					comision_nombre, 
					cond_regularidad,
					cond_regularidad_desc, 
					elemento,
					elemento_nombre,
					SUM(cantidad) as cantidad,
					cast(SUM(cantidad) * 100 as decimal(10,2))  /  cast((SELECT SUM(cantidad) FROM temp_estadistica tee WHERE te.comision = tee.comision) as decimal(10,2)) as porcentaje

				FROM
					temp_estadistica as te
				GROUP BY 
					1, 2, 3, 4, 5, 6
				ORDER BY 
					2, 4
				";
		$datos = guarani::db()->consultar($sql);
		$sql = "DROP TABLE temp_estadistica";
		guarani::db()->ejecutar($sql);
		return $datos;
	}

	/**
	 * Retorna el listado de comisiones activas disponibles para inscribirse a cursar según el filtro especificado
	 */
	function get_comisiones_disponibles_para_inscripcion_masiva($param_usa_subcomisiones, $param_cursa_en_cualquier_ubicacion, $elemento, $propuesta, $plan, $plan_version, $ubicacion, $where = '')
	{
		if ($param_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		} else {
			$ubicacion = toba::db()->quote($ubicacion);
		}

		$en_periodo_inscripcion = 'true';
		$interfaz  = comunes::interfaz_gestion; // Gestion
		$alumno    = 'null'; 
		$elemento  = toba::db()->quote($elemento);
		$propuesta = toba::db()->quote($propuesta);
		$plan_q    = toba::db()->quote($plan);
		$plan_version = toba::db()->quote($plan_version);

		if ($where) {
			$where = ' AND ' . $where;
		}

		$usa_subcomisiones = ($param_usa_subcomisiones == comunes::si);
		$select_subcomisiones = ', 0 as subcomisiones_cant';
		if ($usa_subcomisiones) {
			$select_subcomisiones = ", (SELECT	count(1) as subcomisiones_cant
										FROM	sga_subcomisiones
										WHERE	sga_subcomisiones.comision = sga_comisiones.comision
										AND		sga_subcomisiones.inscripcion_habilitada = '" . comunes::si . "') as subcomisiones_cant ";
		}

		$sql = "SELECT 		sga_comisiones.comision			AS comision,
            				sga_comisiones.nombre			AS comision_nombre,
							" . guarani_sql::get_case_si_no('sga_comisiones', 'inscripcion_cerrada', 'inscripcion_cerrada_desc') . ",
							sga_periodos_lectivos.periodo_lectivo AS periodo_lectivo,
							sga_periodos.anio_academico || ' - ' || sga_periodos.nombre				AS periodo_lectivo_nombre,
							sga_periodos.anio_academico,
							sga_turnos_cursadas.turno		AS turno,
							COALESCE(sga_turnos_cursadas.nombre, '') AS turno_nombre,
							sga_catedras.catedra			AS catedra,
							COALESCE (sga_catedras.nombre, '')		AS catedra_nombre,
                    		sga_ubicaciones.ubicacion		AS ubicacion,
							sga_ubicaciones.nombre			AS ubicacion_nombre,
							sga_periodos.anio_academico
							$select_subcomisiones
		
				FROM 		sga_comisiones
							JOIN sga_ubicaciones ON	sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
            				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							
				WHERE		sga_comisiones.elemento = $elemento
							AND sga_comisiones.estado = '" . comunes::estado_activo . "'
							AND	sga_comisiones.inscripcion_habilitada = '" . comunes::si . "'
							AND	sga_periodos_lectivos.fecha_tope_movimientos >= " . guarani_sql::fecha_actual . " 
							AND sga_comisiones.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad($propuesta, $plan, $plan_version, $elemento, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno))
            				$where
							
				ORDER BY	sga_periodos.anio_academico,
							sga_periodos.nombre,
							sga_ubicaciones.nombre,
							sga_turnos_cursadas.nombre 
			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el curso virtual asociado a una comisión
	 */
	function get_curso_virtual_by_comision($comision)
	{

		$comision = toba::db()->quote($comision);

		$sql = "SELECT 	int_pv_cursos.curso,
						int_pv_cursos.id_curso_externo,
						int_pv_cursos.nombre,
						int_pv_plataformas.plataforma,
						int_pv_plataformas.nombre					AS plataforma_nombre,
						int_pv_plataformas.url						AS plataforma_url,
						int_pv_plataformas.host						AS plataforma_host,
						int_pv_plataformas.token					AS plataforma_token,
						int_pv_plataformas.rol_alumno				AS plataforma_rol_alumno,
						int_pv_plataformas.rol_alumno_curso			AS plataforma_rol_alumno_curso,
						int_pv_cursos_comisiones.grupo                          AS grupo,
						int_pv_plataformas.categoria_curso_default AS plataforma_categoria_curso_default
				FROM int_pv_cursos
				JOIN int_pv_cursos_comisiones	ON (int_pv_cursos.curso = int_pv_cursos_comisiones.curso)
				JOIN int_pv_plataformas			ON (int_pv_cursos.plataforma = int_pv_plataformas.plataforma)
				WHERE int_pv_cursos_comisiones.comision = $comision
				LIMIT 1
			   ";

		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Retorna las plataformas de educación virtual relacionadas a una comisión
	 * por las responsables académicas de la actividad a la que corresponde.
	 * 
	 * @param int $comision
	 * @return array
	 */
	function get_plataformas_by_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT		DISTINCT int_pv_plataformas.plataforma,
							int_pv_plataformas.nombre,
							int_pv_plataformas.url,
							int_pv_plataformas.host,
							int_pv_plataformas.token,
							int_pv_plataformas.rol_alumno,
							int_pv_plataformas.rol_alumno_curso,
							int_pv_plataformas.categoria_curso_default
				FROM		sga_comisiones
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_elementos_ra ON sga_elementos.elemento = sga_elementos_ra.elemento
							JOIN int_pv_plataformas_ra ON sga_elementos_ra.responsable_academica = int_pv_plataformas_ra.responsable_academica
							JOIN int_pv_plataformas ON int_pv_plataformas_ra.plataforma = int_pv_plataformas.plataforma
				WHERE		sga_comisiones.comision = $comision	AND
							int_pv_plataformas.activa = true
				ORDER BY	int_pv_plataformas.plataforma
	   ";
		return guarani_db::consultar($sql);
	}

	function get_listado_comisiones_curso_virtual($where)
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	vw_comisiones.comision,
						vw_comisiones.elemento,
						vw_comisiones.elemento_codigo,
						vw_comisiones.elemento_nombre,
						vw_comisiones.comision_nombre,
						vw_comisiones.periodo_nombre,
						vw_comisiones.anio_academico,
						vw_comisiones.fecha_fin_dictado,
						vw_comisiones.fecha_inicio_dictado,
						f_modalidad_cursada_comision(vw_comisiones.comision) as modalidad,
						int_pv_cursos_comisiones.curso,
						int_pv_cursos.id_curso_externo,
						CASE
							WHEN (int_pv_cursos_comisiones.curso is NULL) THEN	0 
							ELSE 1 
						END as curso_creado, 
						'' as alumnos_sincronizados, 
						'' as docentes_sincronizados
				FROM	vw_comisiones
                        LEFT JOIN int_pv_cursos_comisiones ON vw_comisiones.comision = int_pv_cursos_comisiones.comision
						LEFT JOIN int_pv_cursos ON int_pv_cursos_comisiones.curso = int_pv_cursos.curso
				WHERE	vw_comisiones.fecha_fin_dictado >= CURRENT_DATE AND
						NOT EXISTS (SELECT	sga_actas.comision 
									FROM	sga_actas
									WHERE	sga_actas.comision = vw_comisiones.comision AND
											sga_actas.estado = '".comunes::estado_cerrado."')
                        $where 
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Verifica si la comision tiene actas de cursadas o promociones cerradas
	 *
	 * @param integer $comision id de la comision
	 */
	function tiene_acta_cursada_promocion_cerrada($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT count(*) as cantidad
				FROM sga_actas
				WHERE sga_actas.comision = $comision 
				AND sga_actas.origen IN ('R','P')
				AND sga_actas.estado = 'C'
			   ;";

		$res = guarani_db::consultar_fila($sql);
		// Retorna TRUE si existe al menos un acta de cursada o promocion cerrada.
	
		if ($res['cantidad'] > 0) {
			return true;
		} else {
			return false;
		}
	}

	function get_opciones_combo_cupo_tipo()
	{
		return array(	array('valor' => comision::cupo_tipo_comision, 'descr' => comision::cupo_tipo_comision_desc),
						array('valor' => comision::cupo_tipo_propuesta, 'descr' => comision::cupo_tipo_propuesta_desc));
	}

	function get_opciones_radio_comisiones_a_copiar()
	{
		return array(	array('valor' => comunes::todas, 'descr' => 'Todas las comisiones'),
						array('valor' => comunes::algunas, 'descr' => 'Algunas comisiones'));
	}
	function get_cursos_grupos_curso_virtual($curso_id)
	{
		
		$sql = "select count(1) from int_pv_cursos_comisiones 
				WHERE curso = $curso_id AND grupo IS NOT NULL
		";
		
		return (guarani_db::consultar($sql)>0)? 'true' : 'false';
	}
}
