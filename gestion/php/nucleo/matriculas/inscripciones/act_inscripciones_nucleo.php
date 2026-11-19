<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 * @package 	Matrículas
 * @subpackage 	Inscripciones
 * 
 */
class act_inscripciones_nucleo
{
	/**
	 * Actualiza el legajo de un alumno dado
	 *
	 */
	function actualizar_legajo($persona, $propuesta, $inscripcion, $ubicacion, $unico_legajo, $generacion, $legajo='')
	{
		if ($generacion == legajos::generacion_automatica) {
			$legajo = legajos::get_nuevo_nro_legajo($unico_legajo, $inscripcion, $persona, $propuesta, $ubicacion);
		}
		$legajo = toba::db()->quote($legajo);
		$sql = "UPDATE	sga_alumnos  
				SET		legajo = $legajo 
				WHERE	persona = $persona AND
						propuesta = $propuesta
		";
		guarani_db::ejecutar($sql);		
		toba::logger()->info('************ Se actualizó el legajo con éxito. ************');
	}
	
	/**
	 * Actualiza la situación de un aspirante a propuesta.
	 * 
	 * @param type $propuesta_aspira
	 * @param type $situacion_asp
	 * @param type $fecha_rechazo
	 * @param type $observaciones
	 * @param type $situacion_ant
	 */
	function actualizar_situacion_propuesta_aspira($propuesta_aspira, $situacion_asp=null, $fecha_rechazo=null, $observaciones=null, $set_situacion_ant=false, $observaciones_rechazo=null)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		// Si no se recibe ninguna situación aspirante, se setea la situación anterior.
		$situacion_asp = empty($situacion_asp) ? 'situacion_asp_anterior' : toba::db()->quote($situacion_asp);
		// Si no se recibe ninguna fecha de rechazo, se pone en null.
		$fecha_rechazo = empty($fecha_rechazo) ? 'null' : toba::db()->quote($fecha_rechazo);
		$observaciones = empty($observaciones) ? '' : ', observaciones = ' . toba::db()->quote($observaciones);
		// Si se pide setear la situación anterior, se toma del valor de situacion_aspirante
		// En caso contrario la situación anterior será null (para limpiarla en caso de revertir el rechazo).
		$situacion_ant = $set_situacion_ant ? 'situacion_asp' : 'null';
		$observaciones_rechazo = is_null($observaciones_rechazo) ? '' : ', observaciones_rechazo = ' . toba::db()->quote($observaciones_rechazo);

		$sql = "UPDATE	sga_propuestas_aspira  
				SET		situacion_asp = $situacion_asp,
						fecha_rechazo = $fecha_rechazo,
						situacion_asp_anterior = $situacion_ant
						$observaciones
						$observaciones_rechazo
				WHERE	propuesta_aspira = $propuesta_aspira
		";
		guarani_db::ejecutar($sql);		
		toba::logger()->info('************ Se actualizó situacion_asp en sga_propuestas_aspira con éxito. ************');
	}
	
	function insertar_propuestas_aspira_rechazo($propuesta_aspira, $requisito)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$requisito = toba::db()->quote($requisito);
		
		$sql_existe = "
				SELECT	1 AS existe
				FROM	sga_propuestas_aspira_rechazo
				WHERE	propuesta_aspira = $propuesta_aspira
						AND requisito = $requisito
			";
		
		$res = guarani_db::consultar_fila($sql_existe);
		
		// Si se intenta insertar un requisito que conforme un registro duplicado, se
		// omite la inserción. Ej: se rechazó una inscripción, se intenta revertir y
		// se vuelve a rechazar por no cumplir el mismo requisito.
		if (empty($res['existe'])) {
			$sql = "INSERT INTO sga_propuestas_aspira_rechazo
					(
						propuesta_aspira,
						requisito
					) 
					VALUES 
					(
						$propuesta_aspira,
						$requisito
					);";

			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se insertó un registro en sga_propuestas_aspira_rechazo con éxito. ************');
		}
	}

	/**
	 * Se eliminan los registros de los Requisitos por los cuales se había Rechazado la Inscripción a Propuesta
	 * del Aspirante (sga_propuestas_aspira_rechazo) 
	 * @param integer $propuesta_aspira
	 */
	function eliminar_registros_rechazo_propuesta_aspira($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$sql = "DELETE
				FROM	sga_propuestas_aspira_rechazo
				WHERE	propuesta_aspira = $propuesta_aspira
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de sga_propuestas_aspira_rechazo para el id de propuesta_aspira dado con éxito. ************');
	}
	
	/**
	 * Elimina una inscripción a propuesta.
	 *
	 * @param integer $propuesta_aspira
	 */
	function eliminar_inscripcion_propuesta($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$sql = "DELETE
				FROM	sga_propuestas_aspira
				WHERE	propuesta_aspira = $propuesta_aspira
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó la inscripción a propuesta con éxito. ************');
	}
	
	/**
	 * Pasar inscripciones a examenes y actividades a tablas históricas.
	 *
	 * @param int $anio_academico
	 * @param int $cursadas
	 * @param int $examenes
	 */
	function pasar_inscripciones_a_historico($anio_academico, $cursadas, $examenes)
	{
		$sql = "SELECT f_pasar_inscripciones_a_historico($anio_academico, $cursadas, $examenes);";
		guarani_db::ejecutar($sql);
	}

	/**
	 * Registra la reinscripcion de un alumno en una propuesta para un año académico.
	 *
	 * @param integer $alumno
	 * @param integer $anio_academico
	 */
	function reinscribir_a_propuesta($alumno, $anio_academico, $nro_transaccion)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		$nro_transaccion = toba::db()->quote($nro_transaccion);
		$interfaz = comunes::interfaz_gestion;
		
		$sql = "INSERT INTO sga_reinscripciones
				(
					alumno,
					anio_academico,
					interfaz,
					nro_transaccion
				)
				VALUES
				(
					$alumno,
					$anio_academico,
					$interfaz,
					$nro_transaccion
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se reinscribió el alumno en el año académico con éxito. ************');
	}
	
	/**
	 * Inserta un registro de aspirante a propuesta.
	 *
	 * @param int $persona
	 * @param int $periodo_insc
	 * @param int $anio_academico
	 * @param int $propuesta
	 * @param int $plan_version
	 * @param int $modalidad
	 * @param int $ubicacion
	 * @param int $tipo_ingreso
	 * 
	 * @return int id automático de 'propuestas_aspira'
	 */
	function inscribir_a_propuesta($persona, $periodo_insc, $anio_academico, $propuesta, $plan_version,	$modalidad,	$ubicacion,	$fecha_inscripcion=null, $tipo_ingreso=null, $convenio=null, $insc_fuera_de_termino=comunes::no, $situacion_asp=propuesta::situacion_asp_insc_aspirante_a_prop, $observaciones=null)
	{
		$persona = toba::db()->quote($persona);
		$periodo_insc = toba::db()->quote($periodo_insc);
		$anio_academico = toba::db()->quote($anio_academico);
		$propuesta = toba::db()->quote($propuesta);
		$plan_version = toba::db()->quote($plan_version);
		$modalidad = toba::db()->quote($modalidad);
		$ubicacion = toba::db()->quote($ubicacion);
		$fecha_inscripcion = is_null($fecha_inscripcion) ? guarani_sql::fecha_actual : toba::db()->quote($fecha_inscripcion);
		$tipo_ingreso = (is_null($tipo_ingreso)) ? 'null' : toba::db()->quote($tipo_ingreso);
		$convenio = (is_null($convenio)) ? 'null' : toba::db()->quote($convenio);
		$insc_fuera_de_termino = toba::db()->quote($insc_fuera_de_termino);
		$situacion_asp = toba::db()->quote($situacion_asp);
		$observaciones = (is_null($observaciones)) ? 'null' : toba::db()->quote($observaciones);
		
		$sql = "INSERT INTO sga_propuestas_aspira
				(
					persona,
					periodo_insc,
					anio_academico,
					propuesta,
					plan_version,
					modalidad,
					ubicacion,
					fecha_inscripcion,
					tipo_ingreso,
					convenio,
					insc_fuera_de_termino,
					situacion_asp,
					observaciones
				) 
				VALUES (
					$persona,
					$periodo_insc,
					$anio_academico,
					$propuesta,
					$plan_version,
					$modalidad,
					$ubicacion,
					$fecha_inscripcion,
					$tipo_ingreso,
					$convenio,
					$insc_fuera_de_termino,
					$situacion_asp,
					$observaciones
				);";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se inscribió el alumno en el año académico con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_propuestas_aspira_seq');
	}
	
	/**
	 * Actualiza el token de SQ de un aspirante a propuesta.
	 *
	 * @param $nro_transaccion
	 * @param $sq_token
	 */
	function actualizar_sq_token_propuesta_aspira($nro_transaccion, $sq_token)
	{
		$nro_transaccion = toba::db()->quote($nro_transaccion);
		$sq_token = toba::db()->quote($sq_token);

		$sql = "UPDATE	sga_propuestas_aspira  
				SET		sq_token = $sq_token
				WHERE	nro_transaccion = $nro_transaccion
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó sq_token en sga_propuestas_aspira con éxito. ************');
	}
	
	/**
	 * Actualiza el estado de sincronización con SQ de una inscripción a propuesta.
	 *
	 * @param int $propuesta_aspira
	 * @param int $sq_sincronizado Del 0 al 6, ver la clase cobro_nucleo constantes SQ_INSCRIPCION_XXX 
	 */
	function actualizar_sq_sincronizado_propuesta_aspira($propuesta_aspira, $sq_sincronizado, $es_baja = false)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$sq_sincronizado = toba::db()->quote($sq_sincronizado);
		
		$tabla = ($es_baja)? "sga_propuestas_aspira_log" : "sga_propuestas_aspira";

		$sql = "UPDATE	$tabla  
				SET		sq_sincronizado = $sq_sincronizado
				WHERE	propuesta_aspira = $propuesta_aspira
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó sq_sincronizado en sga_propuestas_aspira o sga_propuestas_aspira_log con éxito. ************');
	}
	
}
?>