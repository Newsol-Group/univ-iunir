<?php
/**
 * 
 * @package Encuestas
 */

use SIUToba\rest\lib\rest_error;

class act_encuestas_nucleo
{
	function actualizar_habilitacion_kolla($habilitacion, $kolla_id_habilitacion, $kolla_password)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$kolla_id_habilitacion = toba::db()->quote($kolla_id_habilitacion);
		$kolla_password = toba::db()->quote($kolla_password);

		$sql = "UPDATE	gde_habilitaciones
				SET		kolla_id_habilitacion = $kolla_id_habilitacion,
						kolla_password = $kolla_password
						--,kolla_sincronizado = '".comunes::si."'
				WHERE	habilitacion = $habilitacion
		";

		guarani_db::ejecutar($sql);
	}

	function actualizar_habilitacion_kolla_sincronizada($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);

		$sql = "UPDATE	gde_habilitaciones
				SET		kolla_sincronizado = '".comunes::si."'
				WHERE	habilitacion = $habilitacion
				--AND todos los formularios ya estan sinc //@Delu

				-- g2 es asi:
				--AND NOT EXISTS (SELECT 1 FROM gde_formulario as f
				--  WHERE f.alcance = alcance
				--  AND f.generado_en_kolla = 'N')


		";

		return guarani_db::ejecutar($sql);
	}

    /**
     * parametros: alcance, _ua, ids
     * no_quote: ids
     * cache: no
     */
    function actualizar_formularios($parametros)
    {
        if (!empty($parametros)) {
            $IN = implode(',', $parametros);
        } else {
            return 0;
        }


        $sql = "UPDATE gde_formularios SET generado_en_kolla = 'S'
					WHERE formulario IN ($IN)
					AND generado_en_kolla = 'N'";


        return guarani_db::ejecutar($sql);
    }

    /**
     * parametros: alcance, _ua, ids
     * no_quote: ids
     * cache: no
     */
    function actualizar_conceptos($parametros)
    {
        return guarani_arreglos::count($parametros); //nunca los actualizo porque en 3.10 son iguales a los formularios
//        if (!empty($parametros['ids'])) {
//            $IN = implode(',', $parametros['ids']);
//        } else {
//            return 0;
//        }
//
//        $sql = "UPDATE gde_concepto SET generado_en_kolla = 'S'
//					WHERE concepto IN ($IN)
//					AND generado_en_kolla = 'N'";
//
//        return guarani_db::ejecutar($sql);
    }

    /**
     * parametros: alcance, _ua, ids
     * no_quote: ids
     * cache: no
     */
    function actualizar_elementos($parametros)
    {
        if (!empty($parametros)) {
            $IN = implode(',', $parametros);
        } else {
            return 0;
        }

        $sql = "UPDATE gde_items SET generado_en_kolla = 'S'
					WHERE item IN ($IN)
					AND generado_en_kolla = 'N'";

        return guarani_db::ejecutar($sql);
    }

    function generar_items($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$ejecutar = "	SELECT	*
						FROM	f_encuestas_generar_items($habilitacion)
		";
		guarani_db::ejecutar($ejecutar);
		toba::logger()->info('************ Se generaron los ítems con éxito. ************');
	}

	function eliminar_items($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);

		$ejecutar = "	DELETE
						FROM	gde_formulario_items
						WHERE	item IN (	SELECT	gde_items.item
											FROM	gde_items
											WHERE	gde_items.habilitacion = $habilitacion)";
		guarani_db::ejecutar($ejecutar);

		$ejecutar = "	DELETE
						FROM	gde_items
						WHERE	habilitacion = $habilitacion";
		guarani_db::ejecutar($ejecutar);

		$ejecutar = "	DELETE
						FROM	gde_encuestas_pendientes
						WHERE	formulario IN (	SELECT	gde_formularios.formulario
												FROM	gde_formularios
												WHERE	gde_formularios.habilitacion = $habilitacion)";
		guarani_db::ejecutar($ejecutar);

		$ejecutar = "	DELETE
						FROM	gde_formularios
						WHERE	habilitacion = $habilitacion";
		guarani_db::ejecutar($ejecutar);

		toba::logger()->info('************ Se eliminaron los ítems con éxito. ************');
	}

	function copiar_habilitacion($habilitacion)
	{
		$sql = "SELECT f_copiar_habilitacion_encuesta($habilitacion);";
		$resultado = current(guarani_db::ejecutar($sql, true, true));
		$resultado = trim($resultado['f_copiar_habilitacion_encuesta'], "(,)");
		$resultado = explode(',', $resultado);
		$retorno['resultado'] = $resultado[0];
		$retorno['mensaje_indice'] = $resultado[1];
		toba::logger()->info('************ Copia de habilitación: ' . $retorno['resultado'] . ' ************');
		return $retorno;
	}

	////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////
	/////CONSULTAS REFERIDAS A LA SINCRONIZACION CON KOLLA
	////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Marca una encuesta respondida y devuelve si hizo un cambio o no.
	 * Si la encuesta pendiente existe y devuelve falso, es porque ya
	 * estaba marcada.
	 * Tambien puede devolver falso si no existe.
	 */
	function marcar_encuesta_respondida ($hash)
	{
		$respuesta = quote($hash);
		$sql = "UPDATE gde_encuestas_pendientes
			SET fecha_respuesta = CURRENT_TIMESTAMP,
			sync_status = 'PENDIENTE'
			WHERE hash = $respuesta
			AND fecha_respuesta IS NULL"; // no la marco 2 veces;"; //  -- es el pasado en la query anterior...
		$datos = guarani_db::ejecutar($sql);
		return $datos > 0;
	}
	
	
	/**
	 * Marca una encuesta respondida y devuelve si hizo un cambio o no.
	 * Si la encuesta pendiente existe y devuelve falso, es porque ya
	 * estaba marcada.
	 * Tambien puede devolver falso si no existe.
	 */
	function actualizar_estado_sinc_encuesta_respondida ($hash, $estado)
	{
		$status = quote($estado);
		$respuesta = quote($hash);
		$sql = "UPDATE gde_encuestas_pendientes
				SET 
				--	fecha_respuesta = CURRENT_TIMESTAMP,
					sync_status = $status
				WHERE hash = $respuesta
				--	AND fecha_respuesta IS NULL"; // no la marco 2 veces;";
		$datos = guarani_db::ejecutar($sql);
		return $datos > 0;
	}

        /**
	 * Reabre una encuesta respondida y devuelve si hizo un cambio o no.
	 * @param string $hash
         * @return bool TRUE si hubo cambio, FALSE en caso contrario.
	 */
	function reabrir_encuesta_respondida($hash)
	{
		$sql = "UPDATE gde_encuestas_pendientes
                        SET fecha_respuesta = NULL,
                        sync_status = NULL
                        WHERE hash = '{$hash}'";
		$datos = guarani_db::ejecutar($sql);
                return $datos > 0;
	}

	/**
	 * Inserta un nuevo registro en la tabla gde_log_sync_kolla retornando su id
	 * @param $estado enumerado de estados posibles
	 * @return el id del nuevo registro
	 */
	function insertar_log_sync_kolla($estado, $detalle){
		$est = quote($estado);
		$det = quote($detalle);
		$sql = "INSERT INTO gde_sync_kolla(status, detalle)
				VALUES ($est, $det) returning log";
		$res = guarani_db::consultar_fila($sql);
		return $res['log'];
	}
	
	/**
	 * Actualiza el registro del log
	 */
	function actualizar_log_sync_kolla($id, $estado, $detalle){
		$est = quote($estado);
		$det = quote($detalle);
		$id_ = quote($id);
		$sql = "UPDATE gde_sync_kolla
			   SET status=$est, detalle=$det
			 WHERE log=$id_";
		$res = guarani_db::ejecutar($sql);
		return $res;
	}
	
	
	function insertar_sync_resp_inex($habilitacion, $cui, $formulario){
		$habilitacion = (int)$habilitacion;
		$cui = quote($cui);
		$formulario = quote($formulario);
		$sql = "INSERT INTO gde_sync_resp_inex(
					habilitacion, codigo_externo, concepto_externo, reportado)
				VALUES ($habilitacion, $cui, $formulario, '0');";
		return guarani_db::ejecutar($sql);
	}
	
	/**
	 * Habilitacion, cui, formulario no es la clave de la fila, pero se actualizan todas
	 * juntas, ya que en kolla si es id. Se le indica 1 vez.
	 * @param boolean $reportado
	 * @return Ambigous <number, multitype:>
	 */
	function actualizar_sync_resp_inex($habilitacion, $cui, $formulario, $reportado){
		$r = $reportado? 1:0;
		$habilitacion = (int)$habilitacion;
		$cui = quote($cui);
		$formulario = quote($formulario);
		$sql = "UPDATE gde_sync_resp_inex
				SET reportado=$r
				WHERE habilitacion = $habilitacion
				AND codigo_externo = $cui
				AND concepto_externo = $formulario";
		return guarani_db::ejecutar($sql);
	}

	function eliminar_datos_censales_config($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "DELETE FROM gde_datos_censales_config WHERE habilitacion = $habilitacion";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron las configuraciones de datos censales por relevamiento con éxito. ************'); 
	}

	function insertar_datos_censales_config($habilitacion, $datos)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = array();
		foreach ($datos as $dato) {
			$tabla = toba::db()->quote($dato['tabla']);
			$campo = toba::db()->quote($dato['campo']);
			$obligatorio = toba::db()->quote($dato['obligatorio']);
			$sql[] = "	INSERT INTO	gde_datos_censales_config (habilitacion, tabla, campo, obligatorio)
						VALUES  ($habilitacion, $tabla, $campo, $obligatorio)";
		}
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron las configuraciones de datos censales por relevamiento con éxito. ************'); 
	}

	function modificar_convocatoria_apiv1($becas_convocatoria, $data)
	{
		if (! toba::consulta_php('co_encuestas')->existe_convocatoria($becas_convocatoria)) {
			throw new rest_error(404, "La convocatoria '$becas_convocatoria' no existe");
		}
		$set = '';
		if (isset($data['fecha_desde']) && toba::consulta_php('co_encuestas')->es_convocatoria_a_futuro($becas_convocatoria)) {
			$fecha_desde = toba::db()->quote($data['fecha_desde']);
			$set .= ", fecha_desde = $fecha_desde";
		}
		$fecha_hasta = toba::db()->quote($data['fecha_hasta']);
		if (isset($data['fecha_evaluacion_ha_desde'])) {
			$fecha_evaluacion_ha_desde = toba::db()->quote($data['fecha_evaluacion_ha_desde']);
			$set .= ", fecha_evaluacion_ha_desde = $fecha_evaluacion_ha_desde";
		}
		$fecha_evaluacion_ha = toba::db()->quote($data['fecha_evaluacion_ha']);
		$descripcion = toba::db()->quote($data['descripcion']);
		$sql = "UPDATE	gde_habilitaciones
				SET		fecha_hasta = $fecha_hasta,
						fecha_evaluacion_ha_hasta = $fecha_evaluacion_ha,
						descripcion = $descripcion
						$set
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		return (guarani::db()->ejecutar($sql) == 1);
	}

	function modificar_convocatoria($becas_convocatoria, $data)
	{
		if (! toba::consulta_php('co_encuestas')->existe_convocatoria($becas_convocatoria)) {
			throw new rest_error(404, "La convocatoria '$becas_convocatoria' no existe");
		}
		$set = '';
		if (isset($data['fecha_desde']) && toba::consulta_php('co_encuestas')->es_convocatoria_a_futuro($becas_convocatoria)) {
			$fecha_desde = toba::db()->quote($data['fecha_desde']);
			$set .= ", fecha_desde = $fecha_desde";
		}
		$fecha_hasta = toba::db()->quote($data['fecha_hasta']);
		if (isset($data['fecha_evaluacion_ha_desde'])) {
			$fecha_evaluacion_ha_desde = toba::db()->quote($data['fecha_evaluacion_ha_desde']);
			$set .= ", fecha_evaluacion_ha_desde = $fecha_evaluacion_ha_desde";
		}
		$fecha_evaluacion_ha_hasta = toba::db()->quote($data['fecha_evaluacion_ha_hasta']);
		$descripcion = toba::db()->quote($data['descripcion']);
		$sql = "UPDATE	gde_habilitaciones
				SET		fecha_hasta = $fecha_hasta,
						fecha_evaluacion_ha_hasta = $fecha_evaluacion_ha_hasta,
						descripcion = $descripcion
						$set
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		return (guarani::db()->ejecutar($sql) == 1);
	}
}