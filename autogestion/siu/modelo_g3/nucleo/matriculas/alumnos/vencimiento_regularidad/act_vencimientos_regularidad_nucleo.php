<?php
/**
 * 
 * 
 * @package 	Matrículas
 * @subpackage 	Alumnos
 * 
 */
class act_vencimientos_regularidad_nucleo
{
	function insertar_modificatoria($id_acta, $alumno, $equivalencia, $documento, $fecha_vigencia_anterior, $fecha_vigencia_nuevo, $observaciones, $id_acta_revalida = null)
	{
		$id_acta = (is_null($id_acta)) ? 'null' : toba::db()->quote($id_acta);
		$alumno = toba::db()->quote($alumno);
		$equivalencia = (is_null($equivalencia)) ? 'null' : toba::db()->quote($equivalencia);
		$documento = (is_null($documento)) ? 'null' : toba::db()->quote($documento);
		$fecha_vigencia_anterior = (is_null($fecha_vigencia_anterior)) ? 'null' : toba::db()->quote($fecha_vigencia_anterior);
		$fecha_vigencia_nuevo = (is_null($fecha_vigencia_nuevo)) ? 'null' : toba::db()->quote($fecha_vigencia_nuevo);
		$observaciones = (is_null($observaciones)) ? 'null' : toba::db()->quote($observaciones);
		$id_acta_revalida = (is_null($id_acta_revalida)) ? 'null' : toba::db()->quote($id_acta_revalida);

		$sql = "INSERT INTO sga_regularidades_venc
				(
					id_acta,
					alumno,
					equivalencia,
					documento,
					fecha_vigencia_anterior,
					fecha_vigencia_nuevo,
					observaciones,
					id_acta_revalida
				)
				VALUES (
					$id_acta,
					$alumno,
					$equivalencia,
					$documento,
					$fecha_vigencia_anterior,
					$fecha_vigencia_nuevo,
					$observaciones,
					$id_acta_revalida
				);
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la modificatoria de vencimiento de regularidad con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Pérdidas de Regularidad de Alumnos ----------------------------------
	//------------------------------------------------------------------------

	function insertar_perdida_regularidad($alumno, $anio_academico, $fecha, $documento, $nro_perdida_regularidad, $fecha_control_desde, $fecha_control_hasta)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		$fecha = toba::db()->quote($fecha);
		$documento = is_null($documento) ? 'null' : toba::db()->quote($documento);
		$nro_perdida_regularidad = toba::db()->quote($nro_perdida_regularidad);
		$fecha_control_desde = toba::db()->quote($fecha_control_desde);
		$fecha_control_hasta = toba::db()->quote($fecha_control_hasta);

		$sql = "INSERT INTO sga_perdida_regularidad
				(
					alumno,
					anio_academico,
					fecha,
					documento,
					nro_perdida_regularidad,
					fecha_control_desde,
					fecha_control_hasta
				)
				VALUES 
				(
					$alumno,
					$anio_academico,
					$fecha,
					$documento,
					$nro_perdida_regularidad,
					$fecha_control_desde,
					$fecha_control_hasta
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la pérdida de regularidad del alumno con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_perdida_regularidad_seq');
	}

	function insertar_perdida_regularidad_det($perdida_regularidad, $causa_perdida_reg, $motivo_solucion)
	{
		$perdida_regularidad = toba::db()->quote($perdida_regularidad);
		$causa_perdida_reg = toba::db()->quote($causa_perdida_reg);
		$motivo_solucion = is_null($motivo_solucion) ? 'null' : toba::db()->quote($motivo_solucion);

		$sql = "INSERT INTO sga_perdida_regularidad_det
				(
					perdida_regularidad,
					causa_perdida_reg,
					motivo_solucion
				)
				VALUES 
				(
					$perdida_regularidad,
					$causa_perdida_reg,
					$motivo_solucion
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el detalle de la pérdida de regularidad del alumno con éxito. ************');
	}

	function actualizar_estado_readmision($readmision, $estado)
	{
		$readmision = toba::db()->quote($readmision);
		$estado = toba::db()->quote($estado);

		$sql = "UPDATE	sga_readmisiones
				SET		estado = $estado
				WHERE	readmision = $readmision
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de la readmisión con éxito. ************');
	}

	function actualizar_estado_solicitudes_readmision($solicitudes_readmision, $estado, $fecha_procesamiento, $observaciones = '')
	{
		$filtro = is_array($solicitudes_readmision) ? guarani_sql::armar_condicion_in($solicitudes_readmision, 'sga_readmisiones_solicitud', 'solicitud_readmision', 'solicitud_readmision') : 'sga_readmisiones_solicitud.solicitud_readmision = ' . toba::db()->quote($solicitudes_readmision);
		$estado = toba::db()->quote($estado);
		$fecha_procesamiento = toba::db()->quote($fecha_procesamiento);
		$observaciones = empty($observaciones) ? 'null' : toba::db()->quote($observaciones);

		$sql = "UPDATE	sga_readmisiones_solicitud
				SET		estado = $estado,
						fecha_procesamiento = $fecha_procesamiento,
						observaciones = $observaciones
				WHERE	$filtro
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de la solicitud de readmisión con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Eliminar pérdida de Regularidad -------------------------------------
	/**
	 * Elimina todos los registros de pérdida de regularidad y sus detalles asociados, que existan para el 
	 * alumno y año académico dado. Previamente elimina, si existen, las readmisiones asociadas a dichas
	 * pérdidas de regularidad.
	 * @param int $alumno Alumno para el que se eliminan los registros de pérdida de regularidad
	 * @param int $anio_academico Año Académico para el que se eliminan los registros de pérdida de regularidad del Alumno.
	 */
	function eliminar_perdida_regularidad_alumno($alumno, $anio_academico)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		$this->eliminar_solicitudes_readmision($alumno, $anio_academico);
		$this->eliminar_vencimiento_readmisiones_asociadas_a_perdida_regularidad($alumno, $anio_academico);
		$this->eliminar_readmisiones_asociadas_a_perdida_regularidad($alumno, $anio_academico);
		$this->eliminar_detalle_perdida_regularidad($alumno, $anio_academico);
		$this->eliminar_cabecera_perdida_regularidad($alumno, $anio_academico);
	}

	function eliminar_solicitudes_readmision($alumno, $anio_academico)
	{
		// Borro solicitud de readmision si existe para la pérdida de regularidad.
		$sql = "DELETE FROM sga_readmisiones_solicitud
				  WHERE perdida_regularidad IN (SELECT perdida_regularidad 
				                                  FROM sga_perdida_regularidad
				                                 WHERE alumno = $alumno
				                                   AND anio_academico = $anio_academico
												);
				";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de solicitud de readmisión de pérdida de regularidad del alumno para el año académico con éxito. ************');
	}

	function eliminar_cabecera_perdida_regularidad($alumno, $anio_academico)
	{
		// Borro la pérdida de regularidad								
		$sql = "DELETE
				FROM	sga_perdida_regularidad
				WHERE	alumno = $alumno
				AND		anio_academico = $anio_academico;
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de pérdida de regularidad del alumno para el año académico con éxito. ************');
	}

	function eliminar_detalle_perdida_regularidad($alumno, $anio_academico)
	{
		$sql = "DELETE
				FROM	sga_perdida_regularidad_det
				WHERE	sga_perdida_regularidad_det.perdida_regularidad IN
											(SELECT sga_perdida_regularidad.perdida_regularidad
											 FROM	sga_perdida_regularidad
											 WHERE	alumno = $alumno
											 AND	anio_academico = $anio_academico)
				
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros del detalle de pérdida de regularidad del alumno para el año académico con éxito. ************');
	}

	/**
	 * Elimina los registros de vencimiento de readmisiones asociados a perdidas de regularidad para el alumno y año académico dados.
	 * @param int $alumno
	 * @param int $anio_academico
	 */
	function eliminar_vencimiento_readmisiones_asociadas_a_perdida_regularidad($alumno, $anio_academico)
	{
		$sql = "DELETE
				FROM	sga_readmisiones_vencimiento
				WHERE	readmision IN (	SELECT	sga_readmisiones.readmision
										FROM	sga_readmisiones
												JOIN sga_perdida_regularidad ON sga_readmisiones.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad
										WHERE	sga_perdida_regularidad.alumno = $alumno AND
												sga_perdida_regularidad.anio_academico = $anio_academico)
				
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de vencimiento de readmisiones asociadas a pérdidas de regularidad del alumno para el año académico con éxito. ************');
	}

	/**
	 * Elimina los registros de readmisiones asociados a perdidas de regularidad para el alumno y año académico dados.
	 * @param int $alumno
	 * @param int $anio_academico
	 */
	function eliminar_readmisiones_asociadas_a_perdida_regularidad($alumno, $anio_academico)
	{
		$sql = "DELETE
				FROM	sga_readmisiones
				WHERE	sga_readmisiones.perdida_regularidad IN
											(SELECT sga_perdida_regularidad.perdida_regularidad
											 FROM	sga_perdida_regularidad
											 WHERE	alumno = $alumno
											 AND	anio_academico = $anio_academico)
				
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de readmisiones asociadas a pérdidas de regularidad del alumno para el año académico con éxito. ************');
	}

	function insert_solicitud_readmision($alumno, $perdida_regularidad, $anio_academico, $fecha_solicitud, $estado, $observaciones, $fecha_procesamiento)
	{
		$alumno = toba::db()->quote($alumno);
		$perdida_regularidad = toba::db()->quote($perdida_regularidad);
		$anio_academico = toba::db()->quote($anio_academico);
		$fecha_solicitud = toba::db()->quote($fecha_solicitud);
		$estado = toba::db()->quote($estado);
		$observaciones = toba::db()->quote($observaciones);
		$fecha_procesamiento_quote = toba::db()->quote($fecha_procesamiento);
		
		//si es pendiente no esta procesado 
		$campo_fecha_procesamiento = !empty($fecha_procesamiento ) ? ', fecha_procesamiento': '' ;
		$valor_fecha_procesamiento = !empty($fecha_procesamiento ) ? ','. $fecha_procesamiento_quote:'';

		$sql = "INSERT INTO sga_readmisiones_solicitud
				(   
					alumno,
					perdida_regularidad,
					anio_academico,
					fecha_solicitud,
					estado,
					observaciones
					$campo_fecha_procesamiento
					
				)
				VALUES 
				(
					$alumno,
					$perdida_regularidad,
					$anio_academico,
					$fecha_solicitud,
					$estado,
					$observaciones
					$valor_fecha_procesamiento
				)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron el registro de solicitud de readmision.************');
	}

}
?>