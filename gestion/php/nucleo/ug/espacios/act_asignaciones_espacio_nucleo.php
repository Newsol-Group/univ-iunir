<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 * @package 	Unidades de Gestión
 * @subpackage 	Espacios
 * 
 */
class act_asignaciones_espacio_nucleo
{
	//-----------------------------------------------------------------------------------
	//---- sga_espacios_asignacion
	//-----------------------------------------------------------------------------------
	
	/**
	 * Inserta una asignacion de espacio.
	 *
	 * @return id interno de la asignacion de espacio insertada
	 */
	function insertar_asignacion_espacio($espacio, $tipo, $fecha, $hora_ini, $hora_fin, $clase, $llamado_mesa, $estado)
	{
		$espacio = toba::db()->quote($espacio);
		$tipo = toba::db()->quote($tipo);
		$fecha = toba::db()->quote($fecha);
		$hora_ini = toba::db()->quote($hora_ini);
		$hora_fin = toba::db()->quote($hora_fin);
		$clase = (is_null($clase)) ? 'null' : toba::db()->quote($clase);
		$llamado_mesa = (is_null($llamado_mesa)) ? 'null' : toba::db()->quote($llamado_mesa);
		$estado = toba::db()->quote($estado);
		
		$sql = "INSERT INTO sga_espacios_asignacion
				(
					espacio,
					asignacion_tipo,
					fecha,
					hora_inicio,
					hora_finalizacion,
					clase,
					llamado_mesa,
					estado
				) 
				VALUES 
				(
					$espacio,
					$tipo,
					$fecha,
					$hora_ini,
					$hora_fin,
					$clase,
					$llamado_mesa,
					$estado
				)";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó una asignacion de espacio con éxito. ************');
	}
	
	function insertar_asignaciones_multiples_clases($clases, $espacio, $hora_inicio, $hora_fin, $estado) 
	{
		try {
			toba::db()->abrir_transaccion();
			foreach ($clases as $clase) {
				$this->insertar_asignacion_espacio($espacio, 1, $clase['fecha'], $hora_inicio, $hora_fin, $clase['clase'], null, $estado);
			}
			toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se insertaron las asignaciones de espacio de las clases con éxito. ************');
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function actualizar_asignacion_espacio($asignacion_espacio, $espacio, $tipo, $fecha, $hora_ini, $hora_fin, $clase, $llamado_mesa, $estado)
	{
		$asignacion_espacio = toba::db()->quote($asignacion_espacio);
		$espacio = toba::db()->quote($espacio);
		$tipo = toba::db()->quote($tipo);
		$fecha = toba::db()->quote($fecha);
		$hora_ini = toba::db()->quote($hora_ini);
		$hora_fin = toba::db()->quote($hora_fin);
		$clase = (is_null($clase)) ? 'null' : toba::db()->quote($clase);
		$llamado_mesa = (is_null($llamado_mesa)) ? 'null' : toba::db()->quote($llamado_mesa);
		$estado = toba::db()->quote($estado);
		
		$sql = "UPDATE	sga_espacios_asignacion
				SET		espacio = $espacio,
						asignacion_tipo = $tipo,
						fecha = $fecha,
						hora_inicio = $hora_ini,
						hora_finalizacion = $hora_fin,
						clase = $clase,
						llamado_mesa = $llamado_mesa,
						estado = $estado
				WHERE	asignacion_espacio = $asignacion_espacio
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron datos de asignación de espacio con éxito. ************');
	}
	
	function actualizar_asignaciones_multiples_clases($asignaciones_a_actualizar, $asignaciones_a_eliminar, $espacio, $hora_inicio, $hora_fin, $estado) 
	{
		try {
			toba::db()->abrir_transaccion();
			foreach ($asignaciones_a_actualizar as $asignacion) {
				$this->actualizar_asignacion_espacio($asignacion['asignacion_espacio'], $espacio, 1, $asignacion['fecha'], $hora_inicio, $hora_fin, $asignacion['clase'], null, $estado);
			}
			$this->eliminar_asignaciones_multiples_clases($asignaciones_a_eliminar);
			toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se actualizaron las asignaciones de espacio de las clases con éxito. ************');
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	function eliminar_asignacion_espacio($asignacion_espacio)
	{
		$asignacion_espacio = toba::db()->quote($asignacion_espacio);
		
		$sql = "DELETE 
				FROM	sga_espacios_asignacion
				WHERE	asignacion_espacio = $asignacion_espacio
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros correspondientes a asignaciones de espacio con éxito. ************');
	}
	
	function eliminar_asignaciones_multiples_clases($asignaciones) 
	{
		try {
			toba::db()->abrir_transaccion();
			foreach ($asignaciones as $asignacion) {
				$this->eliminar_asignacion_espacio($asignacion['asignacion_espacio']);
			}
			toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se insertaron las asignaciones de espacio de las clases con éxito. ************');
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
}
?>