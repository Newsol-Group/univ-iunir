<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Calendario
 * @subpackage Planificacion
 */

class act_clases_nucleo
{
	/**
	 * Se inserta una nueva Clase con los datos dados.
	 *
	 */
	function insertar_clase($banda_horaria, $fecha, $valido, $asistencia, $motivo_invalidacion=null, $tema_planificado=null, $cant_horas, $tramo=null, $modalidad=null)
	{
		$banda_horaria = toba::db()->quote($banda_horaria);
		$fecha = toba::db()->quote($fecha);
		$valido = toba::db()->quote($valido);
		$asistencia = toba::db()->quote($asistencia);

		$motivo_invalidacion = empty($motivo_invalidacion) ? 'null' : toba::db()->quote($motivo_invalidacion);
		$tema_planificado = empty($tema_planificado) ? 'null' : toba::db()->quote($tema_planificado);
		$cant_horas = empty($cant_horas) ? 1 : toba::db()->quote($cant_horas);
		$tramo = empty($tramo) ? 'null' : toba::db()->quote($tramo);
		$modalidad = empty($modalidad) ? 'null' : toba::db()->quote($modalidad);
		
		$sql = "INSERT INTO sga_clases
				(
					banda_horaria,
					fecha,
					valido,
					asistencia,
					motivo_invalidacion,
					tema_planificado,
					cantidad_horas_dictadas, 
					id_tramo,
					modalidad
				)
				VALUES
				(
					$banda_horaria,
					$fecha,
					$valido,
					$asistencia,
					$motivo_invalidacion,
					$tema_planificado,
					$cant_horas,
					$tramo,
					$modalidad
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la clase con éxito. ************');
	}
	
	/**
	 * Se inserta una Asignación nueva, creada para la Clase que se está dando de alta, con los datos dados.
	 * Se retorna el id de la Asignación dada de alta.
	 *
	 */
	function insertar_asignacion_para_clase($fecha_desde, $fecha_hasta, $dia_semana, $hora_inicio, $hora_finalizacion, $asignacion_tipo, $periodicidad, $cantidad_horas)
	{
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		$dia_semana = toba::db()->quote($dia_semana);
		$hora_inicio = toba::db()->quote($hora_inicio);
		$hora_finalizacion = toba::db()->quote($hora_finalizacion);
		$asignacion_tipo = toba::db()->quote($asignacion_tipo);
		$periodicidad = toba::db()->quote($periodicidad);
		
		$cantidad_horas = empty($cantidad_horas) ? 1 : toba::db()->quote($cantidad_horas);

		$sql = "INSERT INTO sga_asignaciones
				(
					dia_semana,
					fecha_desde,
					fecha_hasta,
					periodicidad,
					hora_inicio,
					hora_finalizacion,
					cantidad_horas,
					asignacion_tipo
				)
				VALUES
				(
					$dia_semana,
					$fecha_desde,
					$fecha_hasta,
					$periodicidad,
					$hora_inicio,
					$hora_finalizacion,
					$cantidad_horas,
					$asignacion_tipo
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro de asignación para la clase con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_asignaciones_seq');	
	}
	
	/**
	 * Se inserta una Banda Horaria Nueva con la Comisión dada y la Asignación creada para la Clase que se dará de Alta.
	 * Se retorna el id de la Banda Horaria insertada.
	 *
	 */
	function insertar_bh_comision_para_clase($comision, $asignacion, $tipo_clase)
	{
		$comision = toba::db()->quote($comision);
		$asignacion = toba::db()->quote($asignacion);
		$tipo_clase = toba::db()->quote($tipo_clase);
		
		$sql = "INSERT INTO sga_comisiones_bh
				(
					comision,
					asignacion,
					tipo_clase
				)
				VALUES
				(
					$comision,
					$asignacion,
					$tipo_clase
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la Banda Horaria para la Comisión y Asignación dadas con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_comisiones_bh_seq');	
	}
	
	/**
	 * Se inserta la Banda Horaria creada con la SubComisión dada para la Clase que se dará de Alta.
	 *
	 */
	function insertar_bh_subcomision_para_clase($subcomision, $bh)
	{
		$subcomision = toba::db()->quote($subcomision);
		$bh = toba::db()->quote($bh);

		$sql = "INSERT INTO sga_subcomisiones_bh
				(
					subcomision,
					banda_horaria
				)
				VALUES
				(
					$subcomision,
					$bh
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro de Banda Horaria para la Subcomisión con éxito. ************');
	}
	
	/**
	 * Se modifica un registro de Clase con el id dado. Los únicos datos que pueden editarse de la misma son su
	 * validez y el motivo de invalidación.
	 *
	 */
	function modificar_clase($id_clase, $valido, $asistencia, $motivo_invalidacion=null, $tema_planificado=null, $tramo=null, $modalidad=null)
	{
		$id_clase = toba::db()->quote($id_clase);
		$motivo_invalidacion = empty($motivo_invalidacion) ? 'null' : toba::db()->quote($motivo_invalidacion);
		$tema_planificado = empty($tema_planificado) ? 'null' : toba::db()->quote($tema_planificado);
		$tramo = empty($tramo) ? 'null' : toba::db()->quote($tramo);
		$modalidad = empty($modalidad) ? 'null' : toba::db()->quote($modalidad);

		$sql = "UPDATE	sga_clases
				SET		valido = $valido,
						asistencia = $asistencia,
						motivo_invalidacion = $motivo_invalidacion,
						tema_planificado = $tema_planificado,
						id_tramo = $tramo,
						modalidad = $modalidad
				WHERE	clase = $id_clase
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se modificó el registro de clase con éxito. ************');
	}
	
	/**
	 * Se elimina el registro de la Clase correspondiente al id dado.
	 *
	 */
	function eliminar_clase($id_clase)
	{
		$sql = "DELETE
				FROM	sga_clases
				WHERE	clase = $id_clase
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de clase con éxito. ************');
	}
	
	/**
	 * Se elimina el registro de la Clase correspondiente al id dado.
	 *
	 */
	function eliminar_banda_horaria($id_bh)
	{
		$sql = "DELETE
				FROM	sga_comisiones_bh
				WHERE	banda_horaria = $id_bh
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de banda horaria con éxito. ************');
	}
	
	/**
	 * Se elimina el registro de la Clase correspondiente al id dado.
	 *
	 */
	function eliminar_asignacion($id_asignacion)
	{
		$sql = "DELETE
				FROM	sga_asignaciones
				WHERE	asignacion = $id_asignacion
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de asignación con éxito. ************');
	}
}
?>