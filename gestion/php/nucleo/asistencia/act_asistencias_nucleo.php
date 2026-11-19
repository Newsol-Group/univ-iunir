<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Asistencias
 * @subpackage 
 */

class act_asistencias_nucleo
{
	static function insertar_registro_asistencia_clase($clase, $alumno, $cant_inasistencias=0)
	{
		$clase = toba::db()->quote($clase);
		$alumno = toba::db()->quote($alumno);
		$cant_inasistencias = toba::db()->quote($cant_inasistencias);

		$sql = "INSERT INTO sga_clases_asistencia
				(
					clase,
					alumno,
					cant_inasistencias
				)
				VALUES
				(
					$clase,
					$alumno, 
					$cant_inasistencias
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro de asistencia a clases con éxito. ************');
	}
	
	static function modificar_registro_asistencia_clase($clase, $alumno, $cant_inasistencias=0)
	{
		$clase = toba::db()->quote($clase);
		$alumno = toba::db()->quote($alumno);
		$cant_inasistencias = toba::db()->quote($cant_inasistencias);

		$sql = "UPDATE	sga_clases_asistencia
				SET		cant_inasistencias = $cant_inasistencias
				WHERE	clase = $clase AND alumno = $alumno
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se modificó el registro de asistencia a clases con éxito. ************');
	}
}
?>