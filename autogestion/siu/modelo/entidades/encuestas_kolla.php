<?php
namespace siu\modelo\entidades;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use GuzzleHttp\Psr7\Response;
use SIU\Chulupi\util\u;
use siu\guarani;
use siu\modelo\transacciones\becas;
use SIU\Chulupi\ErrorKernel;

class encuestas_kolla
{
	public static $url_kolla;

	protected static $respondidas;
	protected static $pendientes;
	protected static $cliente_kolla;

	// Por defecto el IFRAME va a tener 400px de alto en caso de que Kolla devuelva event.data.height 0
	const ALTO_IFRAME_DEFECTO = 400;

	// Cuando se pulsa "Terminar" Kolla no nos devuelve el alto del comprobante, por defecto le ponemos 800px
	const ALTO_IFRAME_COMPROBANTE = 800;
	
	static function get_encuestas_pendientes($persona)
	{
		if(!isset(self::$pendientes)){
			kernel::log()->add_debug('Encuestas Pendientes para', $persona);
			self::$url_kolla = kernel::proyecto()->get_url_kolla();
			$datos = static::cargar_datos($persona);
			kernel::log()->add_debug('Pendientes', $datos);
			self::$pendientes = $datos;
		}
		return self::$pendientes;
	}
	
	static function limpiar_encuestas_pendientes($persona)
	{
		$parametros = ['persona' => $persona];
		$parametros_encuestas_pendientes = [
			'persona'	=> $persona,
			'perfil'	=> kernel::persona()->get_id_perfil_activo()
		];
		catalogo::limpiar_cache('encuestas', 'encuestas_pendientes', $parametros_encuestas_pendientes);
		catalogo::limpiar_cache('becas', 'get_convocatorias_by_persona', ['persona'	=> $persona, 'vigentes'	=> \comunes::si]);
		catalogo::limpiar_cache('becas', 'get_convocatorias_by_persona', ['persona'	=> $persona, 'vigentes'	=> \comunes::no]);
		catalogo::limpiar_cache('becas', 'get_convocatorias_vigentes_by_persona', $parametros);
		catalogo::limpiar_cache('becas', 'get_convocatorias_aplicadas_by_persona', $parametros);
	}
	
	static function set_encuestas_respondidas($persona, $hab, $token, $fecha_respuesta){
		$parametros = ['habilitacion' => $hab];
		$datos = catalogo::consultar('encuestas', 'password_habilitacion', $parametros);
		
		if(\guarani_arreglos::count($datos) != 1) {
			kernel::log()->add_debug('Error habilitacion', "No se encontró la habilitación, o hay mas de una habilitación para esta encuesta, corrobore corriendo esta consulta: SELECT COUNT(*) AS cant_habilitaciones FROM gde_habilitaciones WHERE kolla_id_habilitacion = {$hab};");
			throw new ErrorKernel(kernel::traductor()->trans("encuestas_kolla.error_habilitacion"));
		}
		
		$password = $datos[0]['kolla_password'];
		$habilitacion = $datos[0]['habilitacion'];
		$tipo_relevamiento = $datos[0]['tipo_relevamiento'];
		$res = url_encuestas::decodificar_token_callback_js($token, $password);
		kernel::log()->add_debug('kolla callback', $res);
		
		//chequeo que se desencripto bien.
		if($res['habilitacion'] != $hab){
			kernel::log()->add_debug('Error habilitacion', $res);
			throw new ErrorKernel(kernel::traductor()->trans("encuestas_kolla.error_habilitacion"));
		}
		
                $guardada_ok = false;
                $msg_error = "";
                try {
                    kernel::db()->abrir_transaccion();
                    // Marco encuesta como respondida con la fecha de Kolla
                    $parametros = ['hash'=> $res['codigo_externo'], 'fecha_respuesta' => $fecha_respuesta];
                    catalogo::consultar('encuestas', 'marcar_encuesta_respondida', $parametros);

					// Si se trata de un Relevamiento para Becas
					if ($tipo_relevamiento == \encuesta::tipo_relevamiento_becas) {
						// Si se trata de una convocatoria a becas marco los requisitos como no rechazados
						guarani::becas()->marcar_requisitos_convocatoria_no_rechazados($habilitacion);

						// Actualizo los datos académicos del aspirante a la beca
						guarani::becas()->actualizar_datos_academicos_aspirante_becas($habilitacion);

						// Borro la convocatoria de la sesión
						becas::borrar_sesion_convocatoria();
					}
					
                    kernel::db()->cerrar_transaccion();
                    $guardada_ok = true;
                } catch (\Exception $e) {
                    kernel::db()->abortar_transaccion();
                    kernel::log()->add_error($e);
                    $guardada_ok = false;
                }

		if ($guardada_ok) {
			kernel::log()->add_debug("encuesta", "respuesta guardada con exito");
			// Limpio la cache de encuestas pendientes
			static::limpiar_encuestas_pendientes($persona);
		} else {
			kernel::log()->add_debug("encuesta", "respuesta guardada sin exito");
			throw new ErrorKernel(kernel::traductor()->trans("encuestas_kolla.error_habilitacion"));
		}
		
		return $res;
	}

	/**
	 * 
	 * @param type $persona  array('persona' => id_persona);
	 * @return type
	 */
	private static function cargar_datos($persona){
		$parametros = [
			'persona'	=> $persona,
			'perfil'	=> kernel::persona()->get_id_perfil_activo()
		];
		$datos = catalogo::consultar('encuestas', 'encuestas_pendientes', $parametros);
		$d_asosiactivo = array();
		//ya es asociativo. Solo para compatibilidad g3w2
		foreach ($datos as $encuesta_pendiente){
			$fila = array();
			$fila['id'] = $encuesta_pendiente[0]; //uso al "cui" como id.. el hash.
			$fila['formulario'] = $encuesta_pendiente[2];
			$fila['cui'] = $encuesta_pendiente[0];
			$fila['habilitacion'] = $encuesta_pendiente[3];
			$fila['password'] = $encuesta_pendiente[4];
			$fila['titulo'] = $encuesta_pendiente[5];
			$fila['tipo_relevamiento'] = $encuesta_pendiente[7];
			$d_asosiactivo[] = $fila; 
		}
		return $d_asosiactivo;
	}

	static function generar_url($id_hab, $id_form, $cod_externo, $password_hab, $accion=null, $cod_recuperacion=null, $hash_validacion=null)
	{
		kernel::log()->add_debug('kolla_url', "$id_hab, $id_form, $cod_externo, $password_hab");
		if(!is_null($accion)) kernel::log()->add_debug('accion', $accion);
		if(!is_null($cod_recuperacion)) kernel::log()->add_debug('cod_recuperacion', $cod_recuperacion);
		if(!is_null($hash_validacion)) kernel::log()->add_debug('hash_validacion', $hash_validacion);
		self::$url_kolla = kernel::proyecto()->get_url_kolla();
		$url_base = self::$url_kolla; //'http://localhost/kolla/3.2/aplicacion.php';
		$token_seguridad = url_encuestas::get_token_seguridad($password_hab, $id_hab, $id_form, $cod_externo, $accion, $cod_recuperacion, $hash_validacion);
		return url_encuestas::gen_url($url_base, $id_hab, $token_seguridad);
	}

	/**
	 * @return \GuzzleHttp\Client
	 */
	protected static function get_cliente_rest()
	{
		klog('get_cliente_rest');
		if (!isset(self::$cliente_kolla)) {
			self::$cliente_kolla = kernel::proyecto()->get_ws_cliente('kolla');
		}

		return self::$cliente_kolla;
	}

	/**
	 * Valida si el response tiene el status requerido, en caso de error devuelve una descripción del mismo
	 *
	 * @param Response						$response 
	 * @param integer						$status	estado contra el que valida si el response tiene ese estado
	 * @param string						$desc_error	descripción en caso de que haya un error.
	 */
	protected static function validar_response(Response $response, $status, $desc_error)
	{
		if ($response->getStatusCode() != $status) {
			klog('Error en ' . $desc_error . ". Se esperaba $status y se obtuvo {$response->getStatusCode()}");
			kernel::log()->add_debug('respuesta', rest_decode(\guarani_rest::get_response_json($response)));
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_comunicacion_con_kolla'));
		}
	}

	// Consulta la lista de bloques de la encuesta $id_encuesta
	public static function get_encuesta_bloques($id_encuesta, $unidad_gestion)
	{

		$response = static::get_cliente_rest()
			->get("encuestas/{$id_encuesta}/bloques",
				array(
					'query' => array('unidad_gestion' => $unidad_gestion),
					'exceptions' => false
				)
			);

		
		if ($response->getStatusCode() == "404") {//La Unidad de Gestion o la encuesta no existe
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.parametros_incorrectos'));
		} elseif ($response->getStatusCode() == "400") {//No se pudo validar la encuesta
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.no_existe_encuesta', array('%encuesta%' => $id_encuesta)));
		} elseif ($response->getStatusCode() == "500") {//Error interno de kolla
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_interno_kolla'));
		} else {
			static::validar_response($response, 200, __FUNCTION__);
			$bloques = rest_decode(\guarani_rest::get_response_json($response));
			foreach ($bloques['detalle'] as $key => $bloque) {
				$bloques['detalle'][$key][catalogo::id] = catalogo::generar_id($bloque['bloque']);
			}
			return ($bloques);
		}
	}

	//Consulta la lista de preguntas del bloque $id_bloque de la encuesta $id_encuesta
	public static function get_preguntas($id_encuesta, $id_bloque, $unidad_gestion)
	{

		$response = static::get_cliente_rest()
			->get("encuestas/{$id_encuesta}/bloques/{$id_bloque}/preguntas",
				  array('query' => array('unidad_gestion' => $unidad_gestion),
					    'exceptions' => false
				  		)
				 );

		
		if ($response->getStatusCode() == "404") {//La Unidad de Gestion o la encuesta o el bloque no existe
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.parametros_incorrectos'));
		} elseif ($response->getStatusCode() == "400") {//No se pudo validar la encuesta
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.no_existe_encuesta', array('%encuesta%' => $id_encuesta)));
		} elseif ($response->getStatusCode() == "500") {//Error interno de kolla
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_interno_kolla'));
		} else {
			static::validar_response($response, 200, __FUNCTION__);
			$preguntas = rest_decode(\guarani_rest::get_response_json($response));
			foreach ($preguntas['detalle']['detalle'] as $key => $pregunta) {
				$preguntas['detalle']['detalle'][$key][catalogo::id] = catalogo::generar_id($pregunta['pregunta']);
			}
			return ($preguntas);
		}
	}

	//Consulta la respuesta de la pregunta $id_pregunta para la encuesta $id_encuesta
	public static function get_respuesta($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion)
	{

		$response = static::get_cliente_rest()->get("habilitaciones/{$id_habilitacion}/formularios/{$id_formulario}/encuestas/{$id_encuesta}/elementos/{$id_elemento}/bloque/{$id_bloque}/pregunta/{$id_pregunta}/orden/{$pregunta_orden}/respuestas",
			array('query' => array('unidad_gestion' => $unidad_gestion,
				'limite' => kernel::proyecto()->get_encuestas_cantidad_maxima_respuestas_kolla()
			),
				'exceptions' => false)
		);
		
		if ($response->getStatusCode() == "404") {//Alguno de los parámetros no existe
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.no_existe_parametros'));
		} elseif ($response->getStatusCode() == "400") {//No se pudieron validar los datos ingresados
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.parametros_incorrectos'));
		} elseif ($response->getStatusCode() == "500") {//Error interno de kolla
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_interno_kolla'));
		} else {
			static::validar_response($response, 200, __FUNCTION__);
			$respuesta = rest_decode(\guarani_rest::get_response_json($response));

			return ($respuesta);
		}

	}

	public static function get_encuesta_completa ($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $unidad_gestion){

		$response = static::get_cliente_rest()->get("habilitaciones/{$id_habilitacion}/formularios/{$id_formulario}/encuestas/{$id_encuesta}/elementos/{$id_elemento}/respuestas",
												  array('query' => array('unidad_gestion' => $unidad_gestion,
				  														 'limite' => kernel::proyecto()->get_encuestas_cantidad_maxima_respuestas_kolla()
			    														),
														'exceptions' => false
												  		)
												  );
		
		if ($response->getStatusCode() == "404") {//Alguno de los parámetros no existe
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.no_existe_parametros'));
		} elseif ($response->getStatusCode() == "400") {//No se pudieron validar los datos ingresados
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.parametros_incorrectos'));
		} elseif ($response->getStatusCode() == "500") {//Error interno de kolla
			throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_interno_kolla'));
		} else {
			static::validar_response($response, 200, __FUNCTION__);
			$respuesta = rest_decode(\guarani_rest::get_response_json($response));

			return ($respuesta);
		}

	}

	static function get_encuestas_respondidas($persona, $fecha_desde, $fecha_hasta, $docente, $actividad)
	{
		if(!isset(self::$respondidas)){
			self::$url_kolla = kernel::proyecto()->get_url_kolla();
			$parametros = array(
				'persona'		=> $persona,
				'perfil'		=> kernel::persona()->get_id_perfil_activo(),
				'fecha_desde'	=> $fecha_desde,
				'fecha_hasta'	=> $fecha_hasta,
				'docente'		=> $docente,
				'actividad'		=> $actividad
			);
			$encuestas_r = catalogo::consultar('encuestas', 'encuestas_respondidas', $parametros);

			kernel::log()->add_debug('encuestas respondidas', $encuestas_r);
			self::$respondidas = $encuestas_r;
		}
		return self::$respondidas;

	}

	static function es_protocolo_compatible() {

		if (u::isHTTPS()) {
			$protocolo_guarani = 'https';
		} else {
			$protocolo_guarani = 'http';
		}
		kernel::log()->add_debug("=== protocolo_guarani ===", $protocolo_guarani);

		static::$url_kolla = kernel::proyecto()->get_url_kolla();
		$protocolo_kolla = strtolower(parse_url(static::$url_kolla, PHP_URL_SCHEME));
		kernel::log()->add_debug("=== protocolo_kolla ===", $protocolo_kolla);

		return ($protocolo_guarani == $protocolo_kolla);
	}

	static function get_dominio_kolla() {
		$url_kolla_parsed = parse_url(static::$url_kolla);
		$domain_kolla = $url_kolla_parsed['scheme']."://".$url_kolla_parsed['host'];
		if(isset($url_kolla_parsed['port']) && $url_kolla_parsed['port'] != '80'){
			$domain_kolla .= ':'.$url_kolla_parsed['port'];
		}
		return $domain_kolla;
	}
}
