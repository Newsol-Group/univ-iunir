<?php
/**
 * Ejecución de funciones o scripts que producen cambios en la base de negocio
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class act_planes_nucleo
{
	function crear_version_plan($plan_version,$nueva_version,$nuevo_nombre_version) 
	{
		$plan_version = quote($plan_version);
		$nueva_version = quote($nueva_version);
		$nuevo_nombre_version = quote($nuevo_nombre_version);
		$tipo_a_crear = toba::db()->quote(planes::copia_version);
		
		$ejecutar = "SELECT f_crear_version_plan(	$plan_version
													,$tipo_a_crear
													,$nueva_version
													,$nuevo_nombre_version
													,NULL
													,NULL
													,NULL
													,NULL
													,NULL
												);";

		$salida = guarani_db::consultar_fila($ejecutar);
		$resultado = explode(',',$salida['f_crear_version_plan']);
		
		$id_nueva_version = $resultado[0];
		
		if ($id_nueva_version < 0) {
			throw new guarani_error($resultado[1]);
		}
		
		return $id_nueva_version;
	}

	/**
	 * Crea un nuevo Plan a partir de uno de origen, con todos los datos replicados, y elementos que lo componen, 
	 * retornando el id del nuevo Plan Versión generado.
	 */
	function duplicar_plan_version($id_pv_orig, $cod_nueva_version, $nombre_nueva_version, $nombre_plan, $propuesta, $tipo_plan, $modalidad, $convenio=null)
	{
		$id_pv_orig = toba::db()->quote($id_pv_orig);
		$cod_nueva_version = toba::db()->quote($cod_nueva_version);
		$nombre_nueva_version = toba::db()->quote($nombre_nueva_version);
		$tipo_a_crear = toba::db()->quote(planes::copia_plan);
		$nombre_plan = toba::db()->quote($nombre_plan);
		$propuesta = toba::db()->quote($propuesta);
		$tipo_plan = toba::db()->quote($tipo_plan);
		$modalidad = toba::db()->quote($modalidad);
	
		if (!is_null($convenio)) {
			$convenio = toba::db()->quote($convenio);
		} else {
			$convenio = 'NULL';
		}
		
		$ejecutar = "SELECT f_crear_version_plan(	$id_pv_orig,
													$tipo_a_crear,
													$cod_nueva_version,
													$nombre_nueva_version,
													$nombre_plan,
													$tipo_plan,
													$propuesta,
													$modalidad,
													$convenio
												);";

		$salida = guarani_db::consultar_fila($ejecutar);
		$resultado = explode(',',$salida['f_crear_version_plan']);
		
		$id_nueva_version = $resultado[0];
		
		if ($id_nueva_version < 0) {
			throw new guarani_error($resultado[1]);
		}
		
		return $id_nueva_version;
	}
	
	/*
	 *  Elimina los elementos Huerfanos que quedaron en la tabla, producto de editar planes
	 *
	 */
	function purgar_elementos_huerfanos()
	{
		$ejecutar = "SELECT f_depurar_elementos()";
		try {
			$salida = guarani_db::consultar($ejecutar);
			$resultado = explode(',',$salida[0]['f_depurar_elementos']);
			if ($resultado[0] < 0) {
				throw new guarani_error($resultado[1]);
			}
			toba::logger()->info("########## Se purgaron los elementos con exito #############");
		} catch (toba_error $e) {
			toba::logger()->error("########## Purgador de elementos:  ".$e->get_mensaje() ." #############");
			/*
			 *  TO-DO se deberia manejar un log de errores para informar
			 *  a tecnicos que no se estan borrando los elementos
			 *  no se propaga este error ya que la eliminacion o la activacion
			 *  se realizaron con exito solo el purgado resulto con error
			 */
		}
	
	}
	
	/**
	 * Altera el estado de habilitación de inscripción.
	 *
	 */
	function actualizar_habilitacion_inscripcion($plan, $habilitado)
	{				
		if ($habilitado == 'S') {
			$habilitado = 'N';
		} else {
			$habilitado = 'S';
		}
		
		$plan = toba::db()->quote($plan);
		$habilitado = toba::db()->quote($habilitado);
		
		$sql = "UPDATE 		sga_planes  
				SET 		inscripcion_habilitada = $habilitado 
				WHERE 		plan = $plan
				";
		
		return guarani_db::ejecutar($sql);
	}
	
	function confirmar_optativas_de_generica($materia_generica)
	{
		$ejecutar = "	SELECT	*
						FROM	f_confirmar_modificacion_actividad_generica($materia_generica);
						";
		return guarani_db::ejecutar($ejecutar, true, true);
	}
	
	function borrar_matrices_plan_version_nuevo($plan_version, $desactivar_trans=false)
	{
		/**
		 * Ticket #1733: 
		 *	AGREGADO DE ANALISIS Lo que tiene que hacer es que cuando se borra una version 
		 *	en estado NUEVO, se deben eliminar tambien todas las matrices que tengan como 
		 *	destino esa version de ese plan de estudios.
		 */
		
		$plan_version = toba::db()->quote($plan_version);
	
		try {
			if (!$desactivar_trans) {
				toba::db()->abrir_transaccion();
			}
			
			$sql = "DELETE 
					FROM	sga_equiv_actividades 
					WHERE	grupo_equivalencia IN (
								SELECT	grupo_equivalencia 
								FROM	sga_equiv_grupos 
								WHERE	matriz IN (
											SELECT	matriz 
											FROM	sga_equiv_matrices 
											WHERE	plan_version_destino = $plan_version 
										)
							);
					";
			guarani_db::ejecutar($sql);

			$sql = "DELETE	
					FROM	sga_equiv_grupos 
					WHERE	matriz IN (
								SELECT	matriz 
								FROM	sga_equiv_matrices 
								WHERE	plan_version_destino = $plan_version 
							);
					";
			guarani_db::ejecutar($sql);

			$sql = "DELETE 
					FROM	sga_equiv_matrices 
					WHERE	plan_version_destino = $plan_version;
					";
			guarani_db::ejecutar($sql);
			
			if (!$desactivar_trans) {
				toba::db()->cerrar_transaccion();
			}
		} catch (toba_error $e) {
			if (!desactivar_trans) {
				toba::db()->abortar_transaccion();
			}
			throw $e;
		}		
	}
	
	/**
	 * Baja lógica de un Plan de Estudios. Se modifica el estado a 'B', con la fecha y documento dados.
	 * @param type $id_plan Id del Plan de Estudios a dar de Baja.
	 */
	function baja_de_plan($id_plan, $fecha='now()', $documento=null)
	{
		$id_plan = toba::db()->quote($id_plan);
		$fecha = toba::db()->quote($fecha);
		$estado_baja = toba::db()->quote(planes::estado_baja);
		$documento_baja = "";
		
		if(!is_null($documento)){
			$documento = toba::db()->quote($documento);
			$documento_baja = ", documento_baja = $documento";
		}
		
		$sql = "UPDATE 		sga_planes  
				SET 		estado = $estado_baja,
							fecha_baja = $fecha
							$documento_baja
				WHERE 		plan = $id_plan
				;";
		
		return guarani_db::ejecutar($sql);	
	}
	
	/**
	 * Baja lógica de una Versión de Plan de Estudios. Se modifica el estado a 'B', con la fecha y documento dados.
	 * @param type $id_plan_version Id del Plan Versión de Estudios a dar de Baja.
	 */
	function baja_de_version($id_plan_version, $fecha='now()', $documento=null)
	{
		$id_plan_version = toba::db()->quote($id_plan_version);
		$fecha = toba::db()->quote($fecha);
		$estado_baja = toba::db()->quote(planes::estado_baja);
		$documento_baja = "";
		
		if(!is_null($documento)){
			$documento = toba::db()->quote($documento);
			$documento_baja = ", documento_baja = $documento";
		}
		
		$sql = "UPDATE 		sga_planes_versiones  
				SET 		estado = $estado_baja,
							fecha_baja = $fecha
							$documento_baja
				WHERE 		plan_version = $id_plan_version
				;";
		
		return guarani_db::ejecutar($sql);
	}
	
	function cambiar_sincronizacion_plan_sq($plan_id, $sincronizado = true)
	{
		$plan_id				= toba::db()->quote($plan_id);
		$plan_sincronizado_sq	= ($sincronizado)? planes::sincronizado_sq : planes::no_sincronizado_sq;
		$plan_sincronizado_sq	= toba::db()->quote($plan_sincronizado_sq);
		
		$sql = "UPDATE 		sga_planes  
				SET 		sq_sincronizado = $plan_sincronizado_sq
				WHERE 		plan = $plan_id";
		
		return guarani_db::ejecutar($sql);	
	}

	function actualizar_modulo_raiz($plan_version, $modulo_raiz)
	{
		$plan_version = toba::db()->quote($plan_version);
		$modulo_raiz = toba::db()->quote($modulo_raiz);
		$sql = "UPDATE	sga_planes_versiones
				SET		elemento_revision = $modulo_raiz
				WHERE	plan_version = $plan_version
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el módulo raíz del plan-versión con éxito. ************'); 
	}

	/**
	 * Recibe un conjunto de elementos-revisión y los elimina del plan-versión dado.
	 * 
	 * @param array $elementos_revision
	 * @param int $plan_version
	 */
	function eliminar_elementos_plan($elementos_revision, $plan_version)
	{
		$in_elementos_revision = guarani_sql::armar_condicion_in($elementos_revision, 'sga_elementos_plan', 'elemento_revision');
		$plan_version = toba::db()->quote($plan_version);
		$sql = "DELETE
				FROM	sga_elementos_plan_modalidad
				WHERE	sga_elementos_plan_modalidad.elemento_plan IN (	SELECT	elemento_plan 
																		FROM	sga_elementos_plan 
																		WHERE	plan_version = $plan_version AND
																				$in_elementos_revision);
		";
		guarani_db::ejecutar($sql, false, true);
		$sql = "DELETE
				FROM	sga_elementos_plan 
				WHERE	sga_elementos_plan.plan_version = $plan_version AND
						$in_elementos_revision;
		";
		guarani_db::ejecutar($sql, false, true);
	}
}