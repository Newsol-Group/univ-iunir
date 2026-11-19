<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 * @package 	Matrículas
 * @subpackage 	Inscripciones
 * 
 */
class act_preinscripciones_nucleo
{
	//--------------------------------------------------------------------------
	//---- Manejo de fuentes
	//--------------------------------------------------------------------------
	
	/**
	 * Agrega una nueva fuente a la base de datos de la instancia Toba.
	 *
	 * @param string $proyecto
	 * @param string $fuente_datos
	 * @param string $descripcion
	 */
	function insertar_fuente_instancia($proyecto, $fuente_datos, $descripcion)
	{
		$proyecto = toba::db()->quote($proyecto);
		$fuente_datos = toba::db()->quote($fuente_datos);
		$descripcion = toba::db()->quote($descripcion);

		$sql = "INSERT INTO	apex_fuente_datos
							(proyecto, fuente_datos, descripcion)
				VALUES		($proyecto, $fuente_datos, $descripcion)
		";

		toba::instancia()->get_db()->ejecutar($sql);
		toba::logger()->info("************ Se agregó la fuente de datos '$fuente_datos' a la base de la instancia Toba con éxito. ************");
	}
	
	//--------------------------------------------------------------------------
	//---- Actualizaciones de preinscripciones en Autogestión
	//--------------------------------------------------------------------------

	/**
	 * Marca una preinscripción efectuada desde AUTOGESTIÓN como concretada 
	 */
	function actualizar_estado_preinscripcion_autogestion($datos)
	{
		$preinscripcion = $datos['preinscripcion'];
		
		$sql = "UPDATE 	sga_preinscripcion_propuesta  
				SET 	estado = '" . preinscripcion::insc_procesada. "'
				WHERE 	preinscripcion = $preinscripcion;
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado de preinscripción con éxito ************');
	}

	//--------------------------------------------------------------------------
	//---- Actualizaciones de preinscripciones en Preinscripción
	//--------------------------------------------------------------------------
	
	/**
	 * Marca una preinscripción a propuesta como procesada
	 * 
	 * @param int $preinscripcion_propuesta
	 * @param string $fuente
	 */
	function marcar_inscripcion_propuesta($preinscripcion_propuesta, $fuente = null)
	{
		if ($fuente == preinscripcion::origen_preinscripcion) {
			$tabla = 'sga_preinscripcion_propuestas';
			$fuentebd = toba::memoria()->get_dato('fuente_preinscripcion');
			$preinscripcion_propuesta_where = "preinscripcion_propuesta = " . $preinscripcion_propuesta['preinscripcion_propuesta'];
		} else {
			$tabla = 'sga_preinscripcion_propuesta';

			$preinscripcion_propuesta_where = "preinscripcion = " . $preinscripcion_propuesta['preinscripcion'];
			$fuentebd = null;
		}

		$sql = "UPDATE 	  $tabla
				SET 	estado = '" . preinscripcion::insc_procesada . "'
				WHERE 	$preinscripcion_propuesta_where
				";

		guarani_db::ejecutar($sql, false, false, null, false, $fuentebd);
		toba::logger()->info("************ Se marcó la preinscripción a propuesta '$preinscripcion_propuesta_where' como procesada en la tabla '$tabla' ************");
		
		$this->registro_preinscripcion_log($preinscripcion_propuesta['preinscripcion_propuesta'], $preinscripcion_propuesta['preinscripcion'], 'cerrada', preinscripcion::estado_inscripto, $fuente);
		toba::logger()->info("************ Se registro log de preinscripcion************");
		

		}

	/**
	 * Copia al modelo de Guaraní las preinscripciones a propuestas que el aspirante 
	 * no hizo efectivas en ventanilla, para que las pueda seguir gestionando a través 
	 * de 3w
	 * 
	 * @param array $preinscripciones
	 * @param int $persona
	 * @param string $fuente
	 */
	function copiar_preinscripciones_no_procesadas($id_preinscripcion, $persona, $fuente = null)
	{
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}

		$preinscripciones = toba::consulta_php('co_preinscripciones')->get_preinscripciones_propuestas_pendientes($id_preinscripcion, $fuente);

		foreach ($preinscripciones as $datos_preinscripcion) {
			$propuesta = toba::db()->quote($datos_preinscripcion['propuesta']);
			$responsable_academica = toba::db()->quote($datos_preinscripcion['responsable_academica']);
			$ubicacion = toba::db()->quote($datos_preinscripcion['ubicacion']);
			$modalidad = toba::db()->quote($datos_preinscripcion['modalidad']);
			$periodo_insc = toba::db()->quote($datos_preinscripcion['periodo_insc']);
			$estado = toba::db()->quote($datos_preinscripcion['estado']);
			$atiende = (empty($datos_preinscripcion['atiende']))? 'NULL':toba::db()->quote($datos_preinscripcion['atiende']);
			$fecha_preinscripcion = toba::db()->quote($datos_preinscripcion['fecha_preinscripcion']);
			$fecha_fin_vigencia = toba::db()->quote($datos_preinscripcion['fecha_fin_vigencia']);
			$tipo_ingreso = toba::db()->quote($datos_preinscripcion['tipo_ingreso']);
			$id_de_preinscripcion = toba::db()->quote($datos_preinscripcion['preinscripcion_propuesta']);
			$sql = "INSERT INTO sga_preinscripcion_propuesta
					(
						persona,
						propuesta,
						responsable_academica,
						ubicacion,
						modalidad,
						periodo_insc,
						estado,
						atiende,
						fecha_preinscripcion,
						fecha_fin_vigencia,
						tipo_ingreso
					) 
					VALUES 
					(
						$persona,
						$propuesta,
						$responsable_academica,
						$ubicacion,
						$modalidad,
						$periodo_insc,
						$estado,
						$atiende,
						$fecha_preinscripcion,
						$fecha_fin_vigencia,
						$tipo_ingreso
					) RETURNING preinscripcion;";


			$nuevo_id_gestion = guarani_db::ejecutar($sql);

			toba::logger()->info("************ Se incorporó a la base de Guaraní la preinscripción no procesada de la persona '$persona' a la propuesta '$propuesta' con éxito ************");
			$actualizar = "
					UPDATE sga_preinscripcion_propuesta_log
					SET preinscripcion =" . $nuevo_id_gestion . ",
						origen = 'G3'
					WHERE preinscripcion = " . $id_de_preinscripcion . "
						AND origen = 'PRE';		";

			guarani_db::ejecutar($actualizar);
		}
	}

	/**
	 * Marca una preinscripción a propuesta como solicitud de intomacion S y observacion
	 * 
	 * @param int $preinscripcion_propuesta
	 * @param string $fuente
	 * @param string $mensaje
	 */
	function cambio_estado_inscripcion_propuesta($preinscripcion_propuesta,$id_preinscripcion, $mensaje, $fuente = null)
	{
		if ($fuente == preinscripcion::origen_preinscripcion) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
			$tabla = "sga_preinscripcion_propuestas";
			//cambio el estado de la preinscripcion tabla sga_preinscripcion
			$sql = "UPDATE 	sga_preinscripcion  
				SET 	estado = '" . preinscripcion::estado_activado . "'
				WHERE 	id_preinscripcion = $id_preinscripcion
				";

		guarani_db::ejecutar($sql, false, false, null, false, $fuente);

		} else {
			$fuente = null;
			$tabla = "sga_preinscripcion_propuesta";
		}
		
		$sql = "UPDATE 	$tabla  
				SET 	estado = '" . preinscripcion::insc_solicitud_informacion . "', observaciones = '" . $mensaje . "'
				WHERE 	preinscripcion_propuesta = $preinscripcion_propuesta
				";

		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		toba::logger()->info("************ Se marcó la preinscripción a propuesta '$preinscripcion_propuesta' se cambio el estado y se grabo el mensaje ************");
	}

	/**
	 * asigna a la persona que solicita(usuario de gestion) como atendedor
	 * 
	 * @param int $id_preinscripcion
	 * @param string $fuente
	 * @param int $usuario_gestion
	 */
	function cambio_atendedor($id_preinscripcion, $fuente = null, $usuario_gestion = null)
	{
		if ($fuente == preinscripcion::origen_autogestion) {
			$fuente = null;
			$tabla = "sga_preinscripcion_propuesta";
			$where = "	WHERE 	preinscripcion = ".$id_preinscripcion;
		} else {
			$fuente = (is_null(toba::memoria()->get_dato('fuente_preinscripcion')))? $fuente : toba::memoria()->get_dato('fuente_preinscripcion');
			$tabla = "sga_preinscripcion_propuestas";
			$where = "WHERE 	id_preinscripcion = ".$id_preinscripcion;
		}
		$sql = "UPDATE 	$tabla  
				SET 	atiende = '" . $usuario_gestion['persona'] . "'
				$where 
				";


		guarani_db::ejecutar($sql, false, false, null, false, $fuente);

		toba::logger()->info("************ Se asigno a la persona id ". $usuario_gestion['persona'] . " como atendedor la preinscripción a propuesta '$id_preinscripcion'  ************");
	}

	/**
	 * Actualiza registro_preinscripcion_log
	 *
	 * @param int $preinscripcion_propuesta
	 * @param int $id_preinscripcion
	 * @param int $mensaje
	 * @param string $estado
	 * @param string $fuente
	 */
	function registro_preinscripcion_log($preinscripcion_propuesta, $id_preinscripcion, $mensaje, $estado, $fuente)
	{
		$fecha = toba::db()->quote(guarani_fecha::get_ahora());
		$estado = toba::db()->quote($estado);
		$mensaje = toba::db()->quote($mensaje);
		if ($fuente == preinscripcion::origen_preinscripcion) {
			$preinscripcion = $preinscripcion_propuesta;
			$fuente = toba::db()->quote(preinscripcion::origen_preinscripcion_pre);
		} else {
			$preinscripcion = $id_preinscripcion;
			$fuente = toba::db()->quote(preinscripcion::origen_preinscripcion_g3);
		}
		$sql = "INSERT INTO sga_preinscripcion_propuesta_log
					(
						preinscripcion,
						fecha,
						estado,
						observaciones,
						origen
					) 
					VALUES 
					($preinscripcion , $fecha, $estado, $mensaje, $fuente

					);";

		guarani_db::ejecutar($sql);
		toba::logger()->info("************registro en el log************");
	}

	/**
	 * Actualiza el estado de una preinscripción para marcarla como cerrada
	 *
	 * @param int $id_preinscripcion
	 * @param int $persona
	 * @param string $fuente
	 */
	function cerrar_preinscripcion($id_preinscripcion, $persona, $fuente=null)
	{
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		
		$id_preinscripcion_q = toba::db()->quote($id_preinscripcion);
		$persona_q = toba::db()->quote($persona);
		
		try {
			toba::db($fuente)->abrir_transaccion();
			
			// Se marca la preinscripción como cerrada (Inscripto en Gestión), y se
			// asocia ese registro de Preinscripción con el ID de persona de Guaraní
			$sql = "UPDATE 	sga_preinscripcion  
					SET 	estado = '" . preinscripcion::estado_inscripto . "',
							persona = $persona_q
					WHERE 	id_preinscripcion = $id_preinscripcion_q
					";
			guarani_db::ejecutar($sql, false, false, null, false, $fuente);
			
			// Se copian las preinscripciones vigentes que no se procesaron al modelo de G3
			$this->copiar_preinscripciones_no_procesadas($id_preinscripcion, $persona, $fuente);
			
			// Se marcan las preinscripciones pendientes como Copiadas a Gestión
			$sql = "UPDATE 	sga_preinscripcion_propuestas  
					SET 	estado = '" . preinscripcion::insc_copiada_g3 . "'
					WHERE 	id_preinscripcion = $id_preinscripcion_q
							AND estado = '" . preinscripcion::insc_pendiente . "'
							AND fecha_fin_vigencia >= CURRENT_DATE
					";
			guarani_db::ejecutar($sql, false, false, null, false, $fuente);
			
			toba::db($fuente)->cerrar_transaccion();
		} catch (toba_error $ex) {
			toba::db($fuente)->abortar_transaccion();
			throw $ex;
		}
		
		toba::logger()->info("************ Se cerró la preinscripción $id_preinscripcion con éxito ************");
	}
	
	
	/**
	 * Marca una preinscripción a propuesta como procesada
	 * 
	 * @param int $preinscripcion_propuesta
	 * @param string $fuente
	 */
	function marcar_requisito_digital($requisito_digital, $estado, $fuente)
	{
		$requisito_digital = toba::db()->quote($requisito_digital);
		$estado = toba::db()->quote($estado);
		
		$sql = "UPDATE 	sga_requisitos_digitales  
				SET		estado = $estado
				WHERE	requisito_digital = $requisito_digital;
				";
		
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		toba::logger()->info("************ Se cambió el estado del requisito digital $requisito_digital a '$estado' ************");
	}

	function modificar_preinscripcion($id_preinscripcion, $fuente, $datos)
	{
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		$email = toba::db()->quote($datos['e_mail']);
		$pais_documento = toba::db()->quote($datos['pais_documento']);
		$tipo_documento = toba::db()->quote($datos['tipo_documento']);
		$nro_documento = toba::db()->quote($datos['nro_documento']);
		$sql = "UPDATE 	sga_preinscripcion  
				SET		e_mail = $email,
						pais_documento = $pais_documento,
						tipo_documento = $tipo_documento,
						nro_documento = $nro_documento
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		toba::logger()->info("************ Se modificaron los datos de la preinscripción con éxito ************");
	}

	function marcar_preinscripciones_no_duplicadas($id_preinscripcion, $fuente, $datos)
	{
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		$pais_documento = toba::db()->quote($datos['pais_documento']);
		$tipo_documento = toba::db()->quote($datos['tipo_documento']);
		$nro_documento = toba::db()->quote($datos['nro_documento']);
		// Se marca como no duplicada la cuenta modificada recientemente y su duplicada si fuera única.
		$sql = "UPDATE	sga_preinscripcion
				SET		duplicada = '".comunes::no."'
				WHERE	sga_preinscripcion.id_preinscripcion = $id_preinscripcion OR
						sga_preinscripcion.pais_documento = $pais_documento AND
						sga_preinscripcion.tipo_documento = $tipo_documento AND
						sga_preinscripcion.nro_documento = $nro_documento AND
						NOT EXISTS (SELECT	otra_preinscripcion.id_preinscripcion
									FROM	sga_preinscripcion as otra_preinscripcion
									WHERE	otra_preinscripcion.pais_documento = $pais_documento AND
											otra_preinscripcion.tipo_documento = $tipo_documento AND
											otra_preinscripcion.nro_documento = $nro_documento AND
											otra_preinscripcion.id_preinscripcion <> sga_preinscripcion.id_preinscripcion)";
		guarani_db::ejecutar($sql, false, true, null, false, $fuente);
		toba::logger()->info("************ Se marcaron las preinscripciones no duplicadas con éxitomodificaron los datos de la preinscripción con éxito ************");
	}

	function eliminar_preinscripcion($id_preinscripcion, $fuente)
	{
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		$sql = "DELETE
				FROM	sga_actuacion_doc
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		$sql = "DELETE
				FROM	sga_actuacion_prof
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		$sql = "DELETE
				FROM	sga_formacion_acad
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		$sql = "DELETE
				FROM	sga_requisitos_digitales
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		$sql = "DELETE
				FROM	sga_preinscripcion_propuestas
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		$sql = "DELETE
				FROM	sga_preinscripcion
				WHERE	id_preinscripcion = $id_preinscripcion;
		";
		guarani_db::ejecutar($sql, false, false, null, false, $fuente);
		toba::logger()->info("************ Se eliminó la cuenta de preinscripción con éxito ************");
	}
}