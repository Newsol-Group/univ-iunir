<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 * @package		Cursadas
 * @subpackage	Inscripción
 * 
 */
class act_inscripcion_cursadas_nucleo
{
	
	/**
	 * Inserta las inscripciones de las instancias de una comisión y a sus subcomisiones
	 * 
	 * function f_insc_cursada_inscribir
	 * 
	 * Recibe los parámetros:
	 *  + Comision
	 *  + Alumno
	 *  + Persona (puede ser nulo)
	 *  + Versión del plan del alumno (si es nula se calcula para la fecha actual)
	 *  + Prioridad de la Inscripción (si es nula le setea el valor 1)
	 *  + Estado de la Inscripción (A-Aceptada / P-Pendiente)
	 *  + Tipo de Inscripción (I-inscripción normal/ P-Preinscripción = inscripción con prioridades)
	 *  + Estado de la Preinscripción (P - Pendiente / A - Aceptada)
	 *  + Fuera de Término (S / N)
	 *  + Nro de Transacción (Solo cuando es una preinscripción - inscripción con prioridades-, en otro caso enviar NULL)
	 *  + Instancias en las que se inscribe (array)
	 *  + Subcomisiones (array)
	 *  + Interfaz (En Gestion debe ir 1)
	 *  + Autorizado por (sólo va el dato cuando es fuera de término, en otro caso va NULL)
	 *  + Período lectivo de la comisión (puede venir en nulo)
	 *  + Parámetro: Controlar cupo en la comisión (cur_controlar_cupo)
	 *  + Parámetro: Forma de controlar el cupo (cur_controlar_cupo_por)
	 *  + Parámetro: Estado Final Inscripción (cur_insc_siempre_pendiente)
	 * 
	 * Retorna el resultado = type_retorno_inscripcion
	 */
	function guardar_inscripcion_a_comision_func($nro_transaccion, $datos_inscripcion, $instancias = [], $subcomisiones = [], $forzar_pendiente = false, $interfaz = comunes::interfaz_gestion, $params_sistema = [])
	{
		// Resultado
		$ret = [];
		// Parámetros de la funcion
		$comision = $datos_inscripcion['comision'];
		$alumno = $datos_inscripcion['alumno'];
		$periodo_lectivo = $datos_inscripcion['periodo_lectivo'];
		$prioridad = $datos_inscripcion['prioridad'];
		$plan_version = $datos_inscripcion['plan_version'];
		$estado = toba::db()->quote($datos_inscripcion['estado']);
		$tipo = toba::db()->quote($datos_inscripcion['tipo']);
		$fuera_de_termino = toba::db()->quote($datos_inscripcion['fuera_de_termino']);
		$autorizacion = empty($datos_inscripcion['autorizacion']) ? 'null' : toba::db()->quote($datos_inscripcion['autorizacion']);
		$instancias = empty($instancias) ? 'NULL' : 'array[' . implode(',', $instancias) . ']';
		$subcomisiones = empty($subcomisiones) ? 'NULL' : 'array[' . implode(',', $subcomisiones) . ']';

		// Parámetros
		$controlar_cupo = $params_sistema['cur_controlar_cupo'];
		if (isset($controlar_cupo)) {
			$controlar_cupo = toba::db()->quote($controlar_cupo);
		}

		$controlar_cupo_por = $params_sistema['cur_controlar_cupo_por'];
		if (isset($controlar_cupo_por)) {
			$controlar_cupo_por = toba::db()->quote($controlar_cupo_por);
		}

		// ¿Dejar siempre pendiente?
		if ($forzar_pendiente) {
			$cur_insc_pend = toba::db()->quote(comunes::si);
		} else {
			$cur_insc_pend = $params_sistema['cur_insc_siempre_pendiente'];
			if (isset($cur_insc_pend)) {
				$cur_insc_pend = toba::db()->quote($cur_insc_pend);
			}
		}

		$sql = "select f_insc_cursada_inscribir(
						$comision,
						$alumno,
						null,
						$plan_version,
						$prioridad,
						$estado,
						$tipo,
						'P', 
						$fuera_de_termino,
						$nro_transaccion,
						$instancias,
						$subcomisiones,
						$interfaz,
						$autorizacion,
						$periodo_lectivo,
						$controlar_cupo,
						$controlar_cupo_por,
						$cur_insc_pend
				);";

		// Se ejecuto la función y se parsea el resultado
		$result = current(guarani_db::ejecutar($sql, true, true));
		$result = trim($result['f_insc_cursada_inscribir'], "(,)");
		$result = explode(',', $result);
		
		// Se ordenan los datos a retornar
		// Resultado de la función: 1 = OK | -1 = ERROR
		$ret['res'] = $result[0];
		// Índice del mensaje de retorno
		$ret['msg_indice'] = $result[1];
		// Parámetros del mensaje
		$ret['pars'] = array();
		if ((isset($result[2])) && (!is_null($result[2]))) {
			$ret['pars'] = explode('$$$', $result[2]);
		}
		
		toba::logger()->info('************ Inscripcion a Cursadas: ' . $ret['res'] . ' ************');

		return $ret;
	}

	//-----------------------------------------------------------------------------------
	//---- Tabla sga_insc_cursada -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function eliminar_insc_cursada_func($inscripcion)
	{
		$ret = array();
		try {
			$sql = "select f_insc_cursada_eliminar($inscripcion);";

			// Ejecución de la función y parseo del resultado.
			$result = current(guarani_db::ejecutar($sql, true, true));
			$result = trim($result['f_insc_cursada_eliminar'], "(,)");
			$result = explode(',', $result);

			// Resultado de la función: 1 = OK | -1 = ERROR.
			$ret['res'] = $result[0];

			// Índice del mensaje de retorno.
			$ret['msg_indice'] = $result[1];

			// Parámetros del mensaje de retorno.
			$ret['pars'] = null;
			if (isset($result[2]) && !is_null($result[2])) {
				$ret['pars'] = $result[2];
			}

			toba::logger()->info('************ Eliminación Inscripción a Cursadas: ' . $ret['res'] . ' ************');
		} catch (toba_error $e) {
			$mensaje = $e->getMessage();
			toba::logger()->error($mensaje);
			throw guarani::error($mensaje);
		}
		return $ret;
	}

	/**
	 * Actualiza el estado de una inscripción o un conjunto de inscripciones recibidas como parámetro a "Aceptada"
	 */
	function aceptar_inscripciones($inscripciones)
	{
		if (is_array($inscripciones)) {
			$where = guarani_sql::armar_condicion_in($inscripciones, 'sga_insc_cursada', 'inscripcion');
		} else {
			$inscripcion = toba::db()->quote($inscripciones);
			$where = "sga_insc_cursada.inscripcion = $inscripcion";
		}
		$sql = "UPDATE	sga_insc_cursada
				SET		estado = '" . inscripcion_cursada::estado_aceptada . "'
				WHERE	$where	
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de las inscripciones con éxito. ************');
	}

	function cambiar_estado_inscripcion($inscripcion, $estado)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$estado = toba::db()->quote($estado);
		$sql = "UPDATE	sga_insc_cursada
				SET		estado = $estado
				WHERE	inscripcion = $inscripcion
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de la inscripción con éxito. ************');
	}

	/**
	 * Actualiza el estado de una inscripción recibidas como parámetro a "Aceptada"
	 *
	 */
	function aceptar_inscripcion_prioridad($inscripcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);

		$sql = "UPDATE	sga_insc_cursada
				SET		estado = '" . inscripcion_cursada::estado_aceptada . "', 
						estado_preinscripcion = '" . inscripcion_cursada::estado_aceptada . "'
				WHERE	sga_insc_cursada.inscripcion = $inscripcion
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se aceptaron inscripción con prioridad con éxito. ************');
	}

	function exceptuar_inscripcion($inscripcion, $motivo_excepcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$motivo_excepcion = toba::db()->quote($motivo_excepcion);
		$sql = "UPDATE	sga_insc_cursada
				SET		estado = '" . inscripcion_cursada::estado_aceptada . "',
						exceptuado = '" . comunes::si . "',
						motivo_excepcion = $motivo_excepcion
				WHERE	sga_insc_cursada.inscripcion = $inscripcion
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se exceptuó la inscripción con éxito. ************');
	}

	function eliminar_insc_cursada_prioridad($inscripcion, $motivo = null, $requisitos = null)
	{
		if (!$motivo) {
			$motivo = 'null';
		}
		if (!$requisitos) {
			$requisitos = 'null';
		}
		$sql = "select f_insc_cursada_rechazar_preinscripcion($inscripcion, $motivo, $requisitos);";
		guarani_db::ejecutar($sql, true, true);
	}

	function revertir_rechazo_insc_cursada_func($inscripcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$sql = "SELECT f_anular_rechazo_insc_cursada($inscripcion);";

		// Se ejecuta la función y se parsea el resultado
		$resultado = current(guarani_db::ejecutar($sql, true, true));
		$resultado = trim($resultado['f_anular_rechazo_insc_cursada'], "(,)");
		$resultado = explode(',', $resultado);

		// Se ordenan los datos a retornar
		// Resultado de la funcion: 1 = OK | -1 = ERROR
		$ret['res'] = $resultado[0];

		// Índice del mensaje de retorno
		$ret['msg_indice'] = $resultado[1];

		// Parámetros del mensaje
		$ret['params'] = array();

		if ((isset($resultado[2])) && (!is_null($resultado[2]))) {
			$ret['params'] = $resultado[2];
		}

		/**
		 * El resultado completo retornado por la función se muestra por el log del editor para que, en caso de haber
		 * ocurrido un error de BD, se tenga el mensaje completo de la ejecución de la sql, y no sólo el mensaje que 
		 * se muestra por pantalla al usuario.
		 */
		toba::logger()->info('************ Eliminación Inscripción a Cursadas: ' . $ret['res'] . ' ************');
		return $ret;
	}

	/**
	 * Distribución de Comisiones. Se mueve la inscripción origen a la Comisión y Subcomisiones Destino indicadas, 
	 * con el estado y en las instancias dadas.
	 */
	function mover_insc_a_comision($insc_original, $com_destino, $estado, $instancias_destino, $subcomisiones_destino, $origen = null)
	{
		$insc_original = toba::db()->quote($insc_original);
		$com_destino = toba::db()->quote($com_destino);
		$estado = toba::db()->quote($estado);

		$instancias_destino = 'array[' . implode(',', $instancias_destino) . ']';
		if (!empty($subcomisiones_destino)) {
			if (is_null($origen)) {
				$subcomisiones_destino = 'array[' . implode(',', $subcomisiones_destino) . ']';
			}
		} else {
			$subcomisiones_destino = 'null';
		}

		$sql = "SELECT	f_insc_cursada_mover($insc_original, $com_destino, $estado, $instancias_destino, $subcomisiones_destino)";
		if (!is_null($origen)) {
			$sql = "SELECT f_insc_cursada_mover_subcomisiones ($insc_original, $origen, $subcomisiones_destino) as f_insc_cursada_mover";
		}

		// Se ejecuta la función y se parsea el resultado
		$resultado = current(guarani_db::ejecutar($sql, true, true));
		$resultado = trim($resultado['f_insc_cursada_mover'], "(,)");
		$resultado = explode(',', $resultado);

		// Se ordenan los datos a retornar
		// Resultado de la funcion: 1 = OK | -1 = ERROR
		$ret['res'] = $resultado[0];

		// Índice del mensaje de retorno
		$ret['msg_indice'] = $resultado[1];

		// Parámetros del mensaje
		$ret['params'] = array();

		if ((isset($resultado[2])) && (!is_null($resultado[2]))) {
			$ret['pars'] = explode('$$$', $resultado[2]);
		}

		/**
		 * El resultado completo retornado por la función se muestra por el log del editor para que, en caso de haber
		 * ocurrido un error de BD, se tenga el mensaje completo de la ejecución de la sql, y no sólo el mensaje que 
		 * se muestra por pantalla al usuario.
		 */
		toba::logger()->info('************ Distribución de Inscripciones a Cursadas: ' . $ret['res'] . ' ************');
		return $ret;
	}

	//-----------------------------------------------------------------------------------
	//---- Tabla sga_insc_cursada_log_requisitos ----------------------------------------
	//-----------------------------------------------------------------------------------

	function insertar_insc_cursada_log_requisitos($inscripcion, $requisitos)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		foreach ($requisitos as $key => $requisito) {
			$sql = "INSERT INTO	sga_insc_cursada_log_requisitos
								(inscripcion, mensaje_validacion, requisito)
					VALUES		(".$inscripcion ." , '". $requisito."' , '".$key."')";
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se insertó el registro en sga_insc_cursada_log_requisitos con éxito. ************');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Tabla sga_insc_cursada_instancia ---------------------------------------------
	//-----------------------------------------------------------------------------------

	function insertar_insc_cursada_instancia($inscripcion, $instancia)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$instancia = toba::db()->quote($instancia);
		$sql = "INSERT INTO sga_insc_cursada_instancias (inscripcion, instancia) VALUES ($inscripcion, $instancia)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro en sga_insc_cursada_instancias con éxito. ************');
	}

	function eliminar_insc_cursada_instancia($inscripcion, $instancia)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$instancia = toba::db()->quote($instancia);
		$sql = "DELETE
				FROM	sga_insc_cursada_instancias
				WHERE	inscripcion = $inscripcion AND
						instancia = $instancia
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de sga_insc_cursada_instancias con éxito. ************');
	}

	//-----------------------------------------------------------------------------------
	//---- Tabla sga_comision_cupo ------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Actualiza el cupo de una comisión
	 */
	function actualizar_cupo_comision($comision)
	{
		$comision = toba::db()->quote($comision);

		$sql = "UPDATE	sga_comisiones_cupo
				SET		cant_inscriptos = cant_inscriptos + 1
				WHERE	cant_inscriptos < cupo AND comision = $comision;
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se actualizó el cupo de la comision $comision ************");
	}

	/**
	 * Actualiza Token de SQ inscipción a curso
	 */
	function actualizar_SQ_token($nro_transaccion, $SQ_token)
	{
		$nro_transaccion = toba::db()->quote($nro_transaccion);
		$SQ_token = toba::db()->quote($SQ_token);

		$sql = "UPDATE	sga_insc_cursada
				SET		sq_token = $SQ_token
				WHERE	nro_transaccion = $nro_transaccion;
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se actualizó el token de SQ para la inscipción a curso con nro_transaccion $nro_transaccion ************");
	}

	/**
	 * Reemplazo Token de SQ inscipción a curso
	 */
	function reemplazar_SQ_token($SQ_token_actual, $SQ_token_nuevo)
	{
		$SQ_token_actual = toba::db()->quote($SQ_token_actual);
		$SQ_token_nuevo = toba::db()->quote($SQ_token_nuevo);

		$sql = "UPDATE	sga_insc_cursada
				SET		sq_token = $SQ_token_nuevo
				WHERE	sq_token = $SQ_token_actual;
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se remplazó el token de SQ para la inscipción a curso de $SQ_token_actual a $SQ_token_nuevo ************");
	}

}