<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 */
use SIUToba\rest\lib\rest_hidratador;
use SIU\Guarani\php\rest\v1\comisiones\recurso_comisiones;

class co_comisiones_nsg extends co_comisiones
{
	// Newsol - 06/2025 Se agregó el porcentaje de asistencia de la comisión
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
							) AS cant_subcomisiones,
							sga_comisiones_instancias.porc_asistencia
							$select
							$cant_evaluaciones
				FROM		sga_comisiones
							JOIN sga_comisiones_instancias ON sga_comisiones.comision = sga_comisiones_instancias.comision
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

	// Newsol - 04/2026 Se filtran los alumnos libres, morosos y pasivos
	/**
	 * Retorna la lista de alumnos inscriptos en una comisión
	 *
	 */
	function get_alumnos_inscriptos_a_comision_eval($comision, $solo_inscripciones_aceptadas=true, $where='', $filtrar_perfil_de_datos=true, $evaluacion=null)
	{
		$comision = toba::db()->quote($comision);
		$habilitados = toba::consulta_php('co_cobros')->get_condicion_habilitados_evaluacion($evaluacion);
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
						JOIN sga_comisiones_instancias ON sga_comisiones_instancias.comision = sga_insc_cursada.comision
						LEFT JOIN sga_clases_asistencia_acum ON (sga_insc_cursada.comision = sga_clases_asistencia_acum.comision AND sga_insc_cursada.alumno = sga_clases_asistencia_acum.alumno)							
						JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
						JOIN vw_personas ON	vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
							
				WHERE		sga_insc_cursada.comision = $comision
				AND		sga_alumnos.calidad = 'A'
				AND		$habilitados
				AND 		((sga_clases_asistencia_acum.porc_asistencia >= sga_comisiones_instancias.porc_asistencia and sga_clases_asistencia_acum.porc_asistencia is not null) OR
						sga_clases_asistencia_acum.porc_asistencia IS NULL)
						$filtro
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}


}