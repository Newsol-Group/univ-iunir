<?php
/**
 *
 * @package Encuestas
 */
class cn_man_relevamientos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'relevamiento';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_encuestas')->get_listado_grupos($filtro);
	}

	function sincronizar($seleccion)
	{
		$datos_habilitacion_g = toba::consulta_php('co_encuestas')->get_datos_habilitacion($seleccion);
		$ug = $datos_habilitacion_g['kolla_unidad_gestion'];

		if ($this->es_habilitacion_sincronizada($datos_habilitacion_g)) {
			toba::logger()->debug('Se quizo habilitar una habilitacion ya sincronizada (no deberia entrar aca)');
			throw guarani::error(guarani_mensajes::get('800ENC_error_sincronizacion'));
		}

		if ($this->es_habilitacion_nueva($datos_habilitacion_g)) {
			$mje = guarani_mensajes::get('800ENC_habilitacion_registrada');
			toba::logger()->debug('Registrando nueva habilitacion...');
		} else {
			$mje = guarani_mensajes::get('800ENC_habilitacion_modificada');
			toba::logger()->debug('Modificando habilitacion...');
		}

		$datos_habilitacion_kolla = guarani::co('co_encuestas')->get_datos_habilitacion_rest($datos_habilitacion_g['habilitacion']);

		try {
			//creo una habilitacion (o si ya estaba la actualizo)
			$datos_habilitacion_kolla['habilitacion']['descarga_pdf'] = ($datos_habilitacion_g['tipo_relevamiento'] == encuesta::tipo_relevamiento_encuestas) ? comunes::si : comunes::no;
			$habilitacion_kolla = $this->sincronizar_habilitacion($datos_habilitacion_kolla['habilitacion'], $ug);

			if (isset($habilitacion_kolla['habilitacion']) && isset($habilitacion_kolla['password'])) {
				guarani::act('act_encuestas')->actualizar_habilitacion_kolla($datos_habilitacion_g['habilitacion'], $habilitacion_kolla['habilitacion'], $habilitacion_kolla['password']);
			} else {
				throw guarani::error(guarani_mensajes::get('800ENC_error_sincronizacion'));
			}

			$this->sincronizar_masivo($datos_habilitacion_kolla['elementos'], 'elementos', $ug);

			$this->sincronizar_masivo($datos_habilitacion_kolla['conceptos'], 'conceptos', $ug);

			$this->sincronizar_masivo($datos_habilitacion_kolla['formularios'], 'formularios', $ug, $habilitacion_kolla['habilitacion']);

			guarani::act('act_encuestas')->actualizar_habilitacion_kolla_sincronizada($datos_habilitacion_g['habilitacion']);

			toba::notificacion()->agregar($mje, comunes::error_informacion);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getRequest());
			toba::logger()->error($e->getResponse());
			$response = rest_decode(guarani_rest::get_response_json($e->getResponse()));

			$mje = encuesta::get_mensaje_descripcion($e->getResponse());

			throw guarani::error($mje, print_r($response['detalle'], true));
		} catch (Exception $exc) {
			toba::notificacion()->agregar($exc->getMessage(), 'error');
		}
	}

	public function es_habilitacion_nueva($datos_habilitacion)
	{
		return is_null($datos_habilitacion['kolla_id_habilitacion']);
	}

	public function es_habilitacion_sincronizada($datos_habilitacion)
	{
		return $datos_habilitacion['kolla_sincronizado'] == comunes::si;
	}

	protected function sincronizar_habilitacion($params, $ug)
	{
		if (empty($params['habilitacion'])) {
			unset($params['habilitacion']);
			$resultado = encuesta::crear_habilitacion($params, $ug);
			$id = $resultado['habilitacion'];
		} else {
			encuesta::actualizar_habilitacion($params, $ug);
			$id = $params['habilitacion'];
		}

		//pido la habilitacion
		return encuesta::get_habilitacion($id, $ug);
	}

	protected function sincronizar_masivo($datos, $endpoint, $ug, $habilitacion = null)
	{
		$col_id = substr($endpoint, 0, strlen($endpoint) - 1);
		$ids_a_sincronizar = array();
		$total = 0;

		if (!is_null($habilitacion)) {
			$url = 'habilitaciones/' . $habilitacion . '/' . $endpoint;
		} else {
			$url = $endpoint;
		}

		//le saco la 's'. 'conceptos' tiene un campo id 'concepto'
		toba::logger()->debug('Iniciando pedido a ' . $endpoint);
		toba::logger()->var_dump($datos);


		foreach ($datos as $id => $dato) {
			$ids_a_sincronizar[] = $datos[$id][$col_id];
			$total++;
		}
		$errores = encuesta::actualizar_masivo($datos, $url, $ug);
		if (!empty($errores)) {
			foreach ($errores as $error) {
				unset($ids_a_sincronizar[(int) $error['indice']]); //tuvo error
			}
		}

		$metodo_actualizar = 'actualizar_' . $endpoint;
		//  ej: encuesta::actualizar_elementos($params);
		$cant = call_user_func(array(guarani::act('act_encuestas'), $metodo_actualizar), $ids_a_sincronizar);

		if ($cant != $total) {
			throw guarani::error("Se actualizaron parcialmente (" . (int) $cant . " de $total) los recursos de $endpoint - No se puede continuar");
		}
		toba::logger()->debug($cant . " $endpoint actualizados");
	}
    
    public function recalcular($habilitacion)
    {
        guarani::act('act_encuestas')->generar_items($habilitacion);
        toba::notificacion()->agregar('Encuesta Recalculada con éxito', comunes::error_informacion);
    }

	function copiar_habilitacion($habilitacion)
	{
		try {
			$resultado = guarani::act('act_encuestas')->copiar_habilitacion($habilitacion);
			if ($resultado['resultado'] < 1) {
				$mje = guarani_mensajes::get($resultado['mensaje_indice']);
				throw guarani::error($mje);
			} else {
				$mje = guarani_mensajes::get($resultado['mensaje_indice']);
				toba::notificacion()->agregar($mje, comunes::error_informacion);
			}
			$nueva_habilitacion = $resultado['resultado'];
		} catch (guarani_error $e) {
			throw guarani::error($e->getMessage());
		}
		return $nueva_habilitacion;
	}

	/**
	 * Determina si existe algún relevamiento vinculado a una convocatoria dada.
	 * 
	 * @param integer $convocatoria
	 * @return boolean
	 */
	function existe_relevamiento($convocatoria)
	{
		$filtro = "gde_habilitaciones.becas_convocatoria = ".toba::db()->quote($convocatoria);
		$habilitacion = $this->entidad()->get_columna_habilitacion('habilitacion');
		if (!empty($habilitacion)) {
			$filtro .= " AND gde_habilitaciones.habilitacion <> $habilitacion";
		}
		$relevamientos = toba::consulta_php('co_encuestas')->get_listado_grupos($filtro);
		return !empty($relevamientos);
	}
}
?>