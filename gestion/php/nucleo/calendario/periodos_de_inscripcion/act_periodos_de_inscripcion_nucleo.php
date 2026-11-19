<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Calendario
 * @subpackage Períodos de Inscripción
 */

class act_periodos_de_inscripcion_nucleo
{
	/**
	 * Inserta un nuevo registro en la tabla de aplanado.
	 * 
	 */
	static function insertar_aplanado($periodo_insc, $plan_version=null, $propuesta=null)
	{
		$plan_version = empty($plan_version) ? 'null' : toba::db()->quote($plan_version);
		$propuesta = empty($propuesta) ? 'null' : toba::db()->quote($propuesta);
		$sql = "INSERT INTO	sga_periodos_inscripcion_aplanado (
							periodo_insc,
							plan_version,
							propuesta)
				VALUES (
							$periodo_insc,
							$plan_version,
							$propuesta)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro de aplanado con éxito. ************');
	}

	/**
	 * Elimina de la tabla de aplanado todos los registros correspondientes a un rango de fechas de período de inscripción dado.
	 * 
	 */
	static function eliminar_aplanado($periodo_insc)
	{
		$sql = "DELETE
				FROM	sga_periodos_inscripcion_aplanado
				WHERE	periodo_insc = $periodo_insc
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de aplanado con éxito. ************');
	}
}

?>