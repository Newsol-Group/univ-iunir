<?php
/**
 * Ejecución de funciones o scripts que producen cambios en la base de negocio
 *
 *
 * @package Examenes
 * @subpackage Mesas de Examen
 */
class act_mesas_de_examen_nucleo
{
	/**
	 * Copia una Mesa de Exámen para una Actividad en un turno destino a partir de los parámetros de entrada dados.
	 * Retorna un arreglo conteniendo los datos de los Llamados que pudieron ser copiados con éxito para la Mesa Origen dada,
	 * con las observaciones correspondientes a la copia (si las hay).
	 */
	function copiar_mesa($mesa_origen, $turno_destino, $metodo_asign_fecha, $dia_no_habil)
	{
		$mesa_origen		= toba::db()->quote($mesa_origen);
		$turno_destino		= toba::db()->quote($turno_destino);
		$metodo_asign_fecha = toba::db()->quote($metodo_asign_fecha);
		$dia_no_habil 		= toba::db()->quote($dia_no_habil);
		
		$sql = "SELECT	*
				FROM	f_copiar_mesa_examen($mesa_origen, $turno_destino, $metodo_asign_fecha, null, $dia_no_habil) 
				";
		
		return guarani_db::ejecutar($sql, true, true);
	}
	
	/**
	 * Genera una nueva Mesa a partir de los parámetros de entrada
	 */
	function generar_mesa($turno_destino, $tipo_generacion_fecha, $tipo_generacion_catedra, $docentes, $nombre, $elemento, $catedra, $ubicacion, $instancias, $escalas_notas, $planes, $modalidades)
	{
		$tipo_generacion_fecha = quote($tipo_generacion_fecha);
		$docentes = quote($docentes);
		$nombre = quote($nombre);
		
		$ejecutar = "	SELECT	*
						FROM	f_generar_mesa($turno_destino, $tipo_generacion_fecha, $tipo_generacion_catedra, $docentes, $nombre, $elemento, $catedra, $ubicacion, $instancias, $escalas_notas, $planes, $modalidades)
						";
		return guarani_db::ejecutar($ejecutar, true, true);
	}
	
	/** 
	 * Suspende un llamado mesa, con el motivo dado, pasando todas las inscripciones de alumnos a la tabla 
	 * de log con la marca de suspendido.
	 * @param int $llamado_mesa llamado mesa a suspender.
	 * @param int $motivo motivo por el cual se suspende el llamado mesa.
	 */
	function suspender_llamado_mesa($llamado_mesa, $motivo)
	{
		$llamado_mesa = quote($llamado_mesa);
		$motivo = quote($motivo);
		
		$sql = "UPDATE 	sga_llamados_mesa
				SET 	estado = '" . mesa_de_examen::mesa_estado_suspendido . "',
						motivo = $motivo
				WHERE 	llamado_mesa = $llamado_mesa
				;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se suspendió el Llamado de la Mesa con éxito. ************');
	}
		
	/**
	 * Se modifica la fecha de un llamado_mesa, con el motivo dado.
	 * @param int $llamado_mesa llamado mesa a suspender.
	 * @param int $motivo motivo por el cual se modifica la fecha del llamado mesa
	 * @param date $fecha nueva fecha para el llamado mesa.
	 */
	function cambiar_fecha_llamado_mesa($llamado_mesa, $motivo, $fecha)
	{
		$llamado_mesa = quote($llamado_mesa);
		$motivo = quote($motivo);
		$fecha = quote($fecha);
		
		$sql = "UPDATE 	sga_llamados_mesa
				SET 	motivo = $motivo,
						fecha = $fecha
				WHERE 	llamado_mesa = $llamado_mesa
				;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se modificó la fecha del Llamado de la Mesa con éxito. ************');
	}
	
	/**
	 * Se modifica el horario de un llamado_mesa, con el motivo dado.
	 * @param int $llamado_mesa llamado mesa a suspender.
	 * @param int $motivo motivo por el cual se modifica la fecha del llamado mesa
	 * @param int $hora_inicio nuevo horario de inicio para el llamado mesa.
	 * @param int $hora_fin nuevo horario de finalizacion para el llamado mesa.
	 */
	function cambiar_horario_llamado_mesa($llamado_mesa, $motivo, $hora_inicio, $hora_fin=null)
	{
		$llamado_mesa = quote($llamado_mesa);
		$motivo = quote($motivo);
		$hora_inicio = quote($hora_inicio);
		$set_hora_fin = '';
		if (!is_null($hora_fin)) {
			$hora_fin = quote($hora_fin);
			$set_hora_fin = " , hora_finalizacion = $hora_fin ";
		}
		$sql = "UPDATE 	sga_llamados_mesa
				SET 	motivo = $motivo,
						hora_inicio = $hora_inicio
						$set_hora_fin
				WHERE 	llamado_mesa = $llamado_mesa
				;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se modificó el horario del Llamado de la Mesa con éxito. ************');
	}
	
	/**
	 * Genera una nueva Mesa a partir de los parámetros de entrada
	 */
	function insertar_instancia_mesa($mesa_examen, $instancia, $escala_nota)
	{
		$mesa_examen = quote($mesa_examen);
		$instancia = quote($instancia);
		$escala_nota = quote($escala_nota);
		
		$sql = "INSERT INTO sga_mesas_examen_instancias
				(
					mesa_examen,
					instancia,
					escala_nota
				)
				VALUES
				(
					$mesa_examen,
					$instancia,
					$escala_nota
				)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la instancia de la Mesa con éxito. ************');
	}

	function desvincular_curso_virtual_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$sql = "DELETE
				FROM	int_pv_cursos_llamados_mesa
				WHERE	llamado_mesa = $llamado_mesa
		";
		return guarani_db::ejecutar($sql);
	}
}