<?php
/**
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class act_aprobacion_por_resolucion_nucleo
{
	/**
	 * Cierra el trámite de aprobación por resolución según el número de trámite dado
	 */
	function cerrar_tramite_aprobacion($nro_tramite)
	{
		$nro_tramite = toba::db()->quote($nro_tramite);
		$estado_cerrado = toba::db()->quote(aprobacion_por_resolucion::estado_cerrado);
		
		$sql = "UPDATE 	sga_reconocimiento 
				SET 	fecha_cierre = now(),
				        estado = $estado_cerrado
				WHERE 	nro_tramite = $nro_tramite";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se cerró el trámite de equivalencia con éxito. ************');
	}
	
	/**
	 * Anula el trámite de aprobación por resolución según el número de trámite dado
	 */
	function anular_tramite_aprobacion($nro_tramite, $observaciones)
	{
		$nro_tramite = toba::db()->quote($nro_tramite);
		$estado_anulado = toba::db()->quote(aprobacion_por_resolucion::estado_anulado);
		$observaciones = toba::db()->quote($observaciones);
		
		$sql = "UPDATE 	sga_reconocimiento 
				SET 	fecha_anulacion = now(),
				        estado = $estado_anulado,
				        observaciones = $observaciones
				WHERE 	nro_tramite = $nro_tramite";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se anuló el trámite de equivalencia con éxito. ************');
	}
	
	/**
	 * Anula el trámite de aprobación por resolución
	 */
	function invalidar_detalle($reconocimiento_act)
	{
		$estado_invalido = toba::db()->quote(aprobacion_por_resolucion::estado_actividad_invalidado);
		$sql = "UPDATE 	sga_reconocimiento_act 
				SET 	estado = $estado_invalido
				WHERE 	reconocimiento_act = $reconocimiento_act";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se inválido el trámite de aprobación por resolución. ************');
	}
	
	/**
	 * Convalida el trámite de aprobación por resolución
	 */
	function convalidar_detalle($reconocimiento_act)
	{
		$estado_activo = toba::db()->quote(aprobacion_por_resolucion::estado_actividad_activo);
		$sql = "UPDATE 	sga_reconocimiento_act 
				SET 	estado = $estado_activo
				WHERE 	reconocimiento_act = $reconocimiento_act";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se inválido el trámite de aprobación por resolución. ************');
	}
}
?>