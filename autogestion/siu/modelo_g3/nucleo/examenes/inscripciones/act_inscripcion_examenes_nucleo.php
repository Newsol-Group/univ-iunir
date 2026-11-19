<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Examenes
 * @subpackage Inscripción
 */

class act_inscripcion_examenes_nucleo
{
	/**
	 * Inserta las inscripciones de las instancias de ua comision y a sus subcomisiones
	 */
	function guardar_inscripcion_a_examen($datos_inscripcion)
	{
		$alumno = toba::db()->quote($datos_inscripcion['alumno']);
		$plan_version = toba::db()->quote($datos_inscripcion['plan_version']);
		$llamado_mesa = toba::db()->quote($datos_inscripcion['llamado_mesa']);
		$instancia = toba::db()->quote($datos_inscripcion['instancia']);
		$fuera_de_termino = toba::db()->quote($datos_inscripcion['fuera_de_termino']);
		$estado = toba::db()->quote($datos_inscripcion['estado']);
		if (!$datos_inscripcion['autorizacion'] || $datos_inscripcion['autorizacion'] == '') {
			$autorizacion = 'null';
		} else {
			$autorizacion = toba::db()->quote($datos_inscripcion['autorizacion']);
		}

		$sql = "INSERT INTO sga_insc_examen(
							alumno,
							llamado_mesa,
							plan_version,
							instancia,
							fuera_de_termino,
							autorizado_por,
							estado)
				VALUES		(
							$alumno,
							$llamado_mesa,
							$plan_version,
							$instancia,
							$fuera_de_termino,
							$autorizacion,
							$estado
							);";

		guarani_db::ejecutar($sql);
		$inscripcion = toba::db()->recuperar_secuencia('sga_insc_examen_seq');
		
		toba::logger()->info('************ Se insertó la inscripción con éxito. ************');
	}

	function inscribir_a_examen($datos_inscripcion, $params_sistema)
	{
		$resultado = array();

		// Parámetros.
		$estado = toba::db()->quote($datos_inscripcion['estado']);
		$fuera_de_termino = toba::db()->quote($datos_inscripcion['fuera_de_termino']);
		$autorizacion = empty($datos_inscripcion['autorizacion']) ? 'null' : toba::db()->quote($datos_inscripcion['autorizacion']);

		$unico_llamado = $params_sistema['exa_insc_unico_llamado']; 
		if (isset($unico_llamado)) {
			$unico_llamado = toba::db()->quote($unico_llamado);
		}
		
		$max_insc_a_actividad_en_llamado = $params_sistema['exa_insc_cant_max_una_actividad_por_llamado'];
		if (isset($max_insc_a_actividad_en_llamado)) {
			$max_insc_a_actividad_en_llamado = toba::db()->quote($max_insc_a_actividad_en_llamado);
		}
		
		$max_insc_a_actividad_en_turno = $params_sistema['exa_insc_cant_max_una_actividad_por_turno'];
		if (isset($max_insc_a_actividad_en_turno)) {
			$max_insc_a_actividad_en_turno = toba::db()->quote($max_insc_a_actividad_en_turno);
		}
		
		$exa_insc_siempre_pendiente = $params_sistema['exa_insc_siempre_pendiente']; 

		$sql = "SELECT	f_insc_examen_inscribir(
							{$datos_inscripcion['llamado_mesa']},
							{$datos_inscripcion['alumno']},
							null,
							{$datos_inscripcion['plan_version']},
							{$datos_inscripcion['actividad']},
							$estado,
							$fuera_de_termino,
							$autorizacion,
							{$datos_inscripcion['instancia']},
							" . comunes::interfaz_gestion . ",
							$unico_llamado,
							$max_insc_a_actividad_en_llamado,
							$max_insc_a_actividad_en_turno,
							" . quote($exa_insc_siempre_pendiente) .
						");
		";

		// Se ejecuta la función y parsea el resultado.
		$rs = current(guarani_db::ejecutar($sql, true, true));
		$rs = trim($rs['f_insc_examen_inscribir'], "(,)");
		$rs = explode(',', $rs);

		$resultado['res'] = $rs[0];
		$resultado['msg_indice'] = $rs[1];
		if (isset($rs[6])){
			$resultado['estado'] = $rs[6];
		}
		$resultado['pars'] = array();
		if (isset($rs[2]) && !is_null($rs[2])) {
			$resultado['pars'] = explode('$$$', $rs[2]);
		}
		
		toba::logger()->info('************ Inscripcion a Examen: ' . $resultado['res'] . ' ************');
		return $resultado;
	}

	/**
	 * Inscribe al alumno dado, con sus datos asociados, al llamado de la mesa pasado.
	 * Utilizado para inscripciones automáticas.
	 *
	 * @param array		$alumno			Datos del alumno a inscribir en el llamado de mesa de examen.
	 * @param integer	$llamado_mesa	Id del llamado mesa para realizar la inscripción.
	 */
	function inscribir_alumno_automaticamente($alumno, $llamado_mesa)
	{
		$id_alumno = toba::db()->quote($alumno['alumno']);
		$instancia = toba::db()->quote($alumno['instancia']);
		$plan_version = toba::db()->quote($alumno['plan_version']);
		$estado = toba::db()->quote($alumno['estado']);
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		//Los datos restantes se completan con los valores default de la base.
	
		$sql = "INSERT INTO sga_insc_examen(
							alumno,
							llamado_mesa,
							plan_version,
							instancia,
							estado)
				VALUES		(
							$id_alumno,
							$llamado_mesa,
							$plan_version,
							$instancia,
							$estado
							)
				;";
	
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se inscribió al alumno con éxito en el llamado de la mesa de examen. ************');
		return toba::db()->recuperar_secuencia('sga_insc_examen_seq');	
	}

	/**
	 * Actualiza la cantidad, usuario y fecha la asociación de comisiones a mesas ante el proceso de inscripción automática.
	 * @param integer	$comision		Id de la comisión que se procesó para realizar las inscripciones automáticas.
	 * @param integer	$llamado_mesa	Id del llamado mesa asociado a la inscripción automática de la comisión.
	 * @param integer	$cant_actual	representa la cantidad de veces que la comisión ya ha sido procesada para inscripción automática a examen.
	 * @param integer	$usuario		Id de la persona correspondiente al usuario que realizó la operación.
	 */
	function actualizar_insc_automaticas_comision($comision, $llamado_mesa, $cant_actual, $usuario=null)
	{
		$comision = toba::db()->quote($comision);
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$cant_nueva = $cant_actual + 1;
		$cant_nueva = toba::db()->quote($cant_nueva);
		$set_usuario = '';
		if (!is_null($usuario)) {
			$usuario = toba::db()->quote($usuario);
			$set_usuario = " , operador = $usuario "; 
		}
		
		$sql = "UPDATE 	sga_insc_auto_examen
				SET 	fecha = now(),
						cantidad = $cant_nueva
						$set_usuario
				WHERE 	comision = $comision AND
						llamado_mesa = $llamado_mesa
				;";
	
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó con éxito la asociación de comisión a llamado mesa ************');
	}
	
	/**
	 * Inserta un nuevo registro en la tabla de sga_insc_examen_log.
	 * 
	 */
	function insertar_insc_examen_log($inscripcion, $operacion, $nro_transaccion_log, $motivo_rechazo=null)
	{
		$motivo_rechazo = empty($motivo_rechazo) ? 'null' : toba::db()->quote($motivo_rechazo);
		
		$sql = "INSERT INTO	sga_insc_examen_log (
							inscripcion,
							alumno,
							llamado_mesa,
							plan_version,
							instancia,
							fecha_inscripcion,
							nro_transaccion,
							motivo_rechazo,
							exceptuado,
							interfaz,
							estado,
							operacion,
							fecha_operacion,
							nro_transaccion_log)
							
				SELECT		inscripcion,
							alumno,
							llamado_mesa,
							plan_version,
							instancia,
							fecha_inscripcion,
							nro_transaccion,
							$motivo_rechazo,
							exceptuado,
							interfaz,
							estado,
							'$operacion',
							now(),
							$nro_transaccion_log
				FROM		sga_insc_examen
				
				WHERE		sga_insc_examen.inscripcion = $inscripcion
		";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro en sga_insc_examen_log con éxito. ************');
	}
	
	/**
	 * Pasa todos los registros de Inscripción a Examen para un Llamado Mesa dado, a insc_examen_log con la marca de
	 * suspendidos, por haberse suspendido el llamado mesa correspondiente.
	 * @param int $llamado_mesa llamado mesa suspendido para el cual se pasan al log las inscripciones.
	 * @param int $nro_transaccion_log transacción asociada a la suspensión, es la misma para todos los alumnos inscriptos
	 * al llamado mesa.
	 */
	function insertar_logs_inscripciones_llamado_mesa($llamado_mesa, $nro_transaccion_log)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$nro_transaccion_log = toba::db()->quote($nro_transaccion_log); 
		
		$sql = "INSERT INTO	sga_insc_examen_log (
							inscripcion,
							alumno,
							llamado_mesa,
							plan_version,
							instancia,
							fecha_inscripcion,
							fuera_de_termino,
							autorizado_por,
							nro_transaccion,
							motivo_excepcion,
							exceptuado,
							interfaz,
							estado,
							operacion,
							fecha_operacion,
							nro_transaccion_log)
							
				SELECT		inscripcion,
							alumno,
							llamado_mesa,
							plan_version,
							instancia,
							fecha_inscripcion,
							fuera_de_termino,
							autorizado_por,
							nro_transaccion,
							motivo_excepcion,
							exceptuado,
							interfaz,
							estado,
							'" . mesa_de_examen::cambio_suspension . "',
							now(),
							$nro_transaccion_log
				FROM		sga_insc_examen
				
				WHERE		sga_insc_examen.llamado_mesa = $llamado_mesa
		";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se suspendieron las inscripciones, insertando los registros en sga_insc_examen_log en con éxito. ************');
	}

	/**
	 * Elimina las inscripciones a examen de un llamado de mesa dado, por haberse suspendido el mismo.
	 * @param int $llamado llamado mesa que fue suspendido para el cual se borran las inscripciones a examen.
	 */
	function eliminar_inscripciones_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		
		$sql = "DELETE 
				FROM	sga_insc_examen
				WHERE	sga_insc_examen.llamado_mesa = $llamado_mesa
				;";				
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se suspendieron las inscripciones, eliminando los registros en sga_insc_examen en con éxito. ************');
	}

	
	function insertar_insc_examen_log_requisitos($inscripcion, $requisitos)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		foreach ($requisitos as $key => $requisito) {
			$mensaje_validacion = toba::db()->quote($mensaje_validacion);
			$sql = "INSERT INTO	sga_insc_examen_log_requisitos
								(inscripcion, mensaje_validacion, requisito )
					VALUES		(".$inscripcion ." , '". $requisito."' , '".$key."')";
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se insertó el registro en sga_insc_examen_log_requisitos con éxito. ************');
		}
	}

	/**
	 * @todo
	 *
	 */
	function insertar_insc_examen_requisitos($inscripcion, $requisitos)
	{
		/*
		$sql = "";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro en sga_insc_examen_log_requisitos con éxito. ************');
		*/
	}
	
	function eliminar_insc_examen($inscripcion)
	{
		$sql = "DELETE
				FROM	sga_insc_examen
				WHERE	inscripcion = $inscripcion
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de sga_insc_examen con éxito. ************');
	}

	/**
	 * Actualiza el estado de una inscripción o un conjunto de inscripciones recibidas como parámetro a "Aceptada".
	 *
	 */
	function aceptar_inscripciones($inscripciones)
	{
		if (is_array($inscripciones)) {
			$where = guarani_sql::armar_condicion_in($inscripciones, 'sga_insc_examen', 'inscripcion');
		} else {
			$inscripcion = toba::db()->quote($inscripciones);
			$where = "sga_insc_examen.inscripcion = $inscripcion";
		}
		
		$sql = "UPDATE	sga_insc_examen
				SET		estado = '".inscripcion_examen::estado_aceptada."'
				WHERE	$where
		";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de las inscripciones a examen con éxito. ************');
	}

	function exceptuar_inscripcion($inscripcion, $motivo_excepcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$motivo_excepcion = toba::db()->quote($motivo_excepcion);
		$sql = "UPDATE	sga_insc_examen
				SET		estado = '".inscripcion_cursada::estado_aceptada."',
						exceptuado = '".comunes::si."',
						motivo_excepcion = $motivo_excepcion
				WHERE	sga_insc_examen.inscripcion = $inscripcion
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se exceptuó la inscripción a examen con éxito. ************');
	}
}
?>