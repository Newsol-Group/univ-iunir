<?php
/**
 * 
 *
 * @package Cursadas
 * @subpackage Evaluaciones
 */
class act_evaluaciones_nucleo
{
	function marcar_acta_promocion_cerrada_detalle_cursadas($evaluacion, $alumno)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno = toba::db()->quote($alumno);

		$sql = "UPDATE	sga_eval_detalle_cursadas
				SET		acta_promocion_cerrada = 1
				WHERE	sga_eval_detalle_cursadas.evaluacion = $evaluacion AND
						sga_eval_detalle_cursadas.alumno = $alumno
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se modifica el detalle indicando que el acta de promoción está cerrada. ************');
	}

	function eliminar_detalle_cursadas_by_evaluacion_alumno($evaluacion, $alumno)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno = toba::db()->quote($alumno);

		$sql = "DELETE FROM sga_eval_detalle_cursadas
				WHERE	sga_eval_detalle_cursadas.evaluacion = $evaluacion AND
						sga_eval_detalle_cursadas.alumno = $alumno
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se elimina un registro en la tabla sga_eval_detalle_cursadas con éxito. ************');
	}

	function cambio_estado_evaluacion($parametros)
	{
		$estado = toba::db()->quote($parametros['estado']);
		$evaluacion = toba::db()->quote($parametros['evaluacion']);
		if (!is_null($parametros['fecha_cierre'])) {
			$fecha_cierre = ",fecha_cierre = ".toba::db()->quote($parametros['fecha_cierre']);
		} else {
			$fecha_cierre = ",fecha_cierre = NULL";
		}

		$sql = "UPDATE  sga_evaluaciones
				SET		estado = $estado   $fecha_cierre
				WHERE	sga_evaluaciones.evaluacion = $evaluacion 
		";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se cambia el estado de la evaluacion con éxito. ************');
	}

}
?>