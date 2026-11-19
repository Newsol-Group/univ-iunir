<?php
/**
 * Ejecución de funciones o scripts que producen cambios en la base de negocio
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class act_comisiones_nucleo
{
	/**
	 * Genera una nueva Comisión a partir de los parámetros de entrada
	 */
	function generar_comision($nombre, $per_lectivo_destino, $elemento, $turno, $catedra, $inscripcion_habilitada, $ubicacion, $instancia, $escala, $porc_asistencia, $plan, $modalidades, $utilizar_docentes, $asist_por_hr, $asist_cant_inasist)
	{
		$nombre = quote($nombre);
		$inscripcion_habilitada = quote($inscripcion_habilitada);
		$asist_por_hr = quote($asist_por_hr);
		$asist_cant_inasist = quote($asist_cant_inasist);

		$instancia = 'array[' . implode(',', $instancia) . ']';
		$plan = 'array[' . implode(',', $plan) . ']';
		$escala = 'array[' . implode(',', $escala) . ']';
		$porc_asistencia = 'array[' . implode(',', $porc_asistencia) . ']';
		$modalidades = 'array[' . implode(',', toba::db()->quote($modalidades)) . ']';

		$ejecutar = "	SELECT	*
						FROM 	f_generar_comision($nombre, $per_lectivo_destino, $elemento, $turno, $catedra, $inscripcion_habilitada, $ubicacion, $instancia, $escala, $porc_asistencia, $plan, $modalidades, $utilizar_docentes, $asist_por_hr, $asist_cant_inasist)";
		return guarani_db::ejecutar($ejecutar, true, true);
	}

	/**
	 * Crea una nueva Comisión a partir de una de origen, con todas sus Subcomisiones, a un Período Lectivo destino
	 * Por defecto la nueva Comisión se genera en estado 'Activo'.
	 */
	function copiar_comision($comision_origen, $per_lec_destino, $asist_por_hr, $asist_cant_inasist, $copiar_docentes, $copiar_bandas, $copiar_evaluaciones , $copiar_espacios, $estado='A')
	{
		$asist_por_hr = toba::db()->quote($asist_por_hr);
		$asist_cant_inasist = quote($asist_cant_inasist);
		
		$ejecutar = "	SELECT	*
						FROM	f_copiar_comision($comision_origen, NULL, $per_lec_destino, NULL, 'true', '$copiar_docentes', '$copiar_bandas', 'true', $asist_por_hr, $asist_cant_inasist, '$estado', $copiar_evaluaciones ,$copiar_espacios  )
						";		
		return guarani_db::ejecutar($ejecutar, true, true);
	}

	/**
	 * Copia la estructura de una Comisión de una Actividad Origen en una Actividad Destino para un Período Lectivo.
	 * Por defecto la nueva Comisión se genera en estado 'Activo'.
	 */
	function copiar_estructura_comision($comision, $actividad, $copiar_subc, $copiar_doc_com, $copiar_bh_com, $copiar_division, $asist_por_hr, $asist_cant_inasist, $estado='A')
	{
		$subc = ($copiar_subc) ? 'true' : 'false';
		$doc = ($copiar_doc_com) ? 'true' : 'false';
		$bh = ($copiar_bh_com) ? 'true' : 'false';
		$division = ($copiar_division) ? 'true' : 'false';
		$asist_por_hr = toba::db()->quote($asist_por_hr);
		$asist_cant_inasist = quote($asist_cant_inasist);
		
		$ejecutar = "	SELECT	*
						FROM	f_copiar_comision($comision, $actividad, NULL, NULL, $subc, $doc, $bh, $division, $asist_por_hr, $asist_cant_inasist, '$estado');
						";
		return guarani_db::ejecutar($ejecutar, true, true);
	}

	/**
	 * Crea una nueva Comisión a partir de una de origen para la misma Actividad y el mismo Período Lectivo, 
	 * con todos los datos replicados, y docentes e instancias asociadas (salvo por las subcomisiones), retornando
	 * el id de la nueva comisión generada.
	 */
	function duplicar_comision($comision_origen, $nombre_nueva_comision, $asist_por_hr, $asist_cant_inasist, $estado=null)
	{
		/**
		 * Se invoca a la función de copia de comisión pero con el nuevo nombre a asignar, ya que será distinto
		 * al de la comisión original, el período lectivo destino en NULL ya que se copiará en el mismo período
		 * origen, y el parámetro copiar subcomisiones en falso.
		 */
		$asist_por_hr = toba::db()->quote($asist_por_hr);
		$asist_cant_inasist = toba::db()->quote($asist_cant_inasist);
		if (is_null($estado)) {
			$estado = 'NULL';
		} else {
			$estado = "'$estado'";
		}
			
		$ejecutar = "	SELECT	*
						FROM	f_copiar_comision($comision_origen, NULL, NULL, '$nombre_nueva_comision', 'false', 'true', 'false', 'true', $asist_por_hr, $asist_cant_inasist, $estado)
						";
		return guarani_db::ejecutar($ejecutar, true, true);
	}

	/**
	 * Se actualiza la habilitación de inscripción para la comisión dada, según el valor dado.
	 */
	function modificar_habilitacion_comision($comision, $inscr_habilitada)
	{
		$comision = toba::db()->quote($comision);
		$sql = "UPDATE	sga_comisiones
				SET		inscripcion_habilitada = '$inscr_habilitada'
				WHERE	sga_comisiones.comision = $comision	
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de las habilitación de la subcomisión con éxito. ************');
	}

	/**
	 * Se actualiza la habilitación de inscripción para la subcomisión dada, según el valor dado.
	 */
	function modificar_habilitacion_subcomision($subcomision, $inscr_habilitada)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "UPDATE	sga_subcomisiones
				SET		inscripcion_habilitada = '$inscr_habilitada'
				WHERE	sga_subcomisiones.subcomision = $subcomision	
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de las habilitación de la subcomisión con éxito. ************');
	}

	/**
	 * Agrega excepciones a todos los períodos de inscripción de un conjunto de comisiones dado.
	 */
	function insertar_excepciones_periodos_inscripcion($comisiones, $fecha_inicio, $fecha_fin, $fecha_tope_bajas)
	{
		$sql = "INSERT INTO		sga_comisiones_excep_perinsc (
		            			periodo_insc_com,
								comision,
            					periodo_insc,
				            	fecha_inicio,
            					fecha_fin,
            					fecha_tope_bajas)

				SELECT DISTINCT ON (sga_comisiones.comision, sga_periodos_inscripcion_fechas.periodo_insc)
								nextval(('sga_comisiones_excep_perinsc_seq'::text)::regclass),
								sga_comisiones.comision,
								sga_periodos_inscripcion_fechas.periodo_insc,
								$fecha_inicio,
								$fecha_fin,
								$fecha_tope_bajas
				FROM 			sga_comisiones,
								sga_periodos_lectivos,
								sga_comisiones_propuestas,
        						sga_planes_versiones,
        						sga_periodos_inscripcion_aplanado,
        						sga_periodos_inscripcion_fechas,
        						sga_periodos_inscripcion,
        						sga_periodos,
        						sga_comisiones_modalidad
				WHERE			sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
								sga_comisiones.comision = sga_comisiones_propuestas.comision AND
								sga_comisiones_propuestas.plan = sga_planes_versiones.plan AND
        						sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version AND
        						sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc AND
        						sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
   								sga_periodos_inscripcion.periodo = sga_periodos.periodo AND
        						sga_periodos_lectivos.periodo = sga_periodos.periodo AND
        						sga_comisiones.comision = sga_comisiones_modalidad.comision AND
        						EXISTS (SELECT	1
        								FROM	sga_per_insc_ubicacion
										WHERE	sga_per_insc_ubicacion.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
												sga_per_insc_ubicacion.ubicacion = sga_comisiones.ubicacion) AND
								EXISTS (SELECT	1
										FROM	sga_per_insc_modalidad
										WHERE	sga_per_insc_modalidad.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
												sga_per_insc_modalidad.modalidad = sga_comisiones_modalidad.modalidad) AND " .
				guarani_sql::armar_condicion_in($comisiones, 'sga_comisiones', 'comision', 'comision');

		guarani_db::ejecutar($sql);
	}

	/**
	 * Elimina excepciones a todos los períodos de inscripción de un conjunto de comisiones dado.
	 */
	function eliminar_excepciones_periodos_inscripcion($comisiones)
	{
		$sql = "DELETE
				FROM	sga_comisiones_excep_perinsc
				WHERE " . guarani_sql::armar_condicion_in($comisiones, 'sga_comisiones_excep_perinsc', 'comision');

		guarani_db::ejecutar($sql);
	}
	
	/**
	 * Se desvincula el Curso Virtual de la Comisión.
	 */
	function desvincular_curso_virtual_by_comision($comision)
	{
		
		$comision = toba::db()->quote($comision);
		
		$sql = "DELETE
				FROM	int_pv_cursos_comisiones
				WHERE comision = $comision";

		return guarani_db::ejecutar($sql);
	}

	function actualizar_propuesta_cupo($comision, $propuesta, $cupo)
	{
		$comision = toba::db()->quote($comision);
		$propuesta = toba::db()->quote($propuesta);
		$cupo = toba::db()->quote($cupo);
		$sql = "UPDATE	sga_comisiones_propuestas_cupo
				SET		cupo = $cupo
				WHERE	sga_comisiones_propuestas_cupo.comision = $comision AND
						sga_comisiones_propuestas_cupo.propuesta = $propuesta
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el cupo por propuesta para la comisión con éxito. ************');
	}

	function actualizar_cupo_por_propuesta_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$ejecutar = "SELECT	* FROM f_actualizar_cupo_por_propuesta_comision($comision)";
		return guarani_db::ejecutar($ejecutar, true, true);
	}
}