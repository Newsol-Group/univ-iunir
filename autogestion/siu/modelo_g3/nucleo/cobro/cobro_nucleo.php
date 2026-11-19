<?php
/**
 * Clase para tomar los ws de sanaviron quilmes
 * Tener en cuenta lo siguiente para la herencia de métodos estáticos:
 * @link http://php.net/manual/en/language.oop5.late-static-bindings.php
 * @package Cobro
 */

use GuzzleHttp\Psr7\Response;
use SIU\Guarani\RestHooks\RestHookSanaviron;

class cobro_nucleo
{
	//Orden
	const ORDER_ASC = 'asc';
	const ORDER_DESC = 'desc';
	
	//Tipo
	const TIPO_POSGRADO = 'POSGRADO';
	const TIPO_CURSO_EXTENSION = 'CUREXTEN';
	const TIPO_GRADO = 'GRADO';
	
	//Estado
	const ESTADO_SERVICIO_ACTIVO = 'A';
	const ESTADO_SERVICIO_INACTIVO = 'I';
	const ESTADO_SERVICIO_CONFIRMADO = 'C';
	
	//Moneda
	const MONEDA_PESOS = 1;
	const MONEDA_DOLAR = 2;
	const MONEDA_EURO = 3;
	
	const MONEDA_PESOS_SIMBOLO = '$';
	const MONEDA_DOLAR_SIMBOLO = 'U$S';
	const MONEDA_EURO_SIMBOLO = '?';
	
	//Códigos de respuesta de los servicios SQ
	const ESTADO_POST_PUT_OK = 201;
	const ESTADO_GET_OK = 200;
	const ESTADO_DELETE_OK = 200;
	const ESTADO_GET_VACIO = 404;
	const ERROR_POST_SERV_VERS_SIN_SERVICIO = 422;
	
	//Tipos de consumo
	const TIPO_CONSUMO_SUSCRIPCION = 'suscripcion';
	const TIPO_CONSUMO_ARTICULO = 'articulo';
	const TIPO_CONSUMO_PRESTACION = 'prestacion';

	const TIPO_CONSUMO_SUSCRIPCION_ABREV = 'S';
	const TIPO_CONSUMO_ARTICULO_ABREV = 'A';
	const TIPO_CONSUMO_PRESTACION_ABREV = 'P';
	
	// Estados de consumo
	const ESTADO_CONSUMO_PAGADO = 'cobrada';
	const ESTADO_CONSUMO_PENDIENTE = 'pendiente';
	const ESTADO_CONSUMO_VENCIDO = 'vencida';
	
	//Tipos de novedades
	const TIPO_NOVEDAD_BAJA = 'B';
	const TIPO_NOVEDAD_INACTIVA = 'I';
	const TIPO_NOVEDAD_ACTIVA = 'A';
	
	//Estados pago
	const ESTADO_PAGO_PAGO = 'P';
	const ESTADO_PAGO_VENCIO = 'V';
	
	//Conceptos de notificaciones-pagos
	const CONCEPTO_NP_COMISION = 'C';
	const CONCEPTO_NP_PROPUESTA = 'P';
	
	//Estados posibles del retorno de SQ luego del pago de una inscripción
	const ESTADO_TRAMITE_PAGO_OK_INSTANTANEO = 1;		// Proceso de pago completado exitosamente (Tarjeta de crédito).
	const ESTADO_TRAMITE_PAGO_OK_PENDIENTE = 2;		    // Proceso de pago completado exitosamente (Efectivo en puntos de pago).
	const ESTADO_TRAMITE_PAGO_CANCELO = 3;	            // Proceso de pago cancelado.
	const ESTADO_TRAMITE_PAGO_ERROR = 4;	            // Hubo un error en el proceso de pago.

    //Codigos de procesamiento de los consumos externos
    const CODIGO_PROCESAMIENTO_CONSUMO_EXTERNO_EXPIRADO = 'EX';
    const CODIGO_PROCESAMIENTO_CONSUMO_EXTERNO_NUEVO = 'NUEVO';
    const CODIGO_PROCESAMIENTO_CONSUMO_EXTERNO_OK = 'OK';

	// Formas de cobro consumos externos (devueltas por el servicio GET /consumos-externos/estado)
	const CONSUMOS_EXTERNOS_FORMA_COBRO_AC = 'agente_cobranza';
	const CONSUMOS_EXTERNOS_FORMA_COBRO_CREDITO = 'credito';
	const CONSUMOS_EXTERNOS_FORMA_COBRO_NC = 'no_corresponde';
	
	// Etiquetas formas de cobro consumos externos (devueltas por el servicio GET /consumos-externos/estado)
	const CONSUMOS_EXTERNOS_FORMA_COBRO_AC_ETIQUETA = 'Devolución por agente de cobranza';
	const CONSUMOS_EXTERNOS_FORMA_COBRO_CREDITO_ETIQUETA = 'Crédito en la universidad';
	const CONSUMOS_EXTERNOS_FORMA_COBRO_NC_ETIQUETA = 'No corresponde devolución';

	// Suscripciones novedades modo devolución pago (recibidas por el servicio POST /suscripciones-novedades)
	const SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_CREDITO = 'C';
	const SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_AGENTE_COBRANZA = 'A';
	const SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE = 'NC';

	const MSJ_ERROR_CONEXION = 'Falló la conexión al servidor SQ';

	// Estados de sincronización con SQ de las inscripciones a propuestas
	const SQ_INSCRIPCION_NO_SINCRONIZADA					= 0;
	const SQ_INSCRIPCION_FALLO_ALTA						= 1;
	const SQ_INSCRIPCION_FALLO_BAJA						= 2;
	const SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_ACTIVO	= 3;
	const SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_PASIVO	= 4;
	const SQ_INSCRIPCION_FALLO_RECHAZO					= 5;
	const SQ_INSCRIPCION_SINCRONIZADA					= 6;
	const SQ_INSCRIPCION_FALLO_REVERTIR_RECHAZO			= 7;
	const SQ_INSCRIPCION_ENCOLADA = 8;

        // Parámetro sq_notif_insc_pendientes
	const PARAM_SQ_NOTIF_INSC_PENDIENTES = 'sq_notif_insc_pendientes';
	const SQ_NOTIF_INSC_TODAS = 'T';
	const SQ_NOTIF_INSC_ACEPTADAS = 'A';
	const SQ_NOTIF_INSC_REQ_DOC_RESTRICTIVOS = 'R';

	const CALLBACK_URL_NO_APLICA = 'http://www.no-aplica.com.ar';
	
	protected static $cliente_sanaviron;
	protected static $sq_id_externo;

	/**
	 * @return \GuzzleHttp\Client
	 */
	protected static function get_cliente_rest()
	{
		if (!isset(static::$cliente_sanaviron)) {
			$cliente = toba::servicio_web_rest('sanaviron');
			static::$cliente_sanaviron = $cliente->guzzle();
		}
		return static::$cliente_sanaviron;
	}

	/**
	 * Retorna el valor del parámetro del sistema "sq_id_externo" el cual
	 * indica la forma en que se identificará la instalación de Guaraní en el Sanaviron-Quilmes
	 * @return string sq_id_externo
	 */
	public static function get_sq_id_externo()
	{
		if (!isset(static::$sq_id_externo)) {
			static::$sq_id_externo = guarani::parametros()->get_valor('sq_id_externo', null, false);
		}
		return static::$sq_id_externo;
	}

	public static function get_mensaje_descripcion($guzzle_res)
	{
		$response = rest_decode(guarani_rest::get_response_json($guzzle_res));
		if (isset($response['descripcion'])) {
			$mje = $response['descripcion'];
		} else {
			if (isset($response['errores']) && isset($response['errores'][0]['error'])) {
				$mje = $response['errores'][0]['error']; //muestro el primer error
			} else {
				$mje = 'Error en la comunicación con SQ';
			}
		}
		return $mje;
	}

	/**
	 * Valida si el response tiene el status requerido, en caso de error devuelve una descripción del mismo
	 *
	 * @param Response						$response 
	 * @param integer						$status	estado contra el que valida si el response tiene ese estado
	 * @param string						$desc_error	descripción en caso de que haya un error.
	 */
	//@TODO Modificar el validar response para retornar distintos errores de acuerdo
	//al codigo de respuesta
	protected static function validar_response(Response $response, $status, $desc_error)
	{
		if ($response->getStatusCode() != $status) {
			toba::logger()->error('Error en ' . $desc_error . ". Se esperaba $status y se obtuvo {$response->getStatusCode()}");
			toba::logger()->var_dump(rest_decode(guarani_rest::get_response_json($response)));
			throw guarani::error(static::get_mensaje_descripcion($response));
		}
	}

	/**
	 * Servicio REST a SQ "GET /status":
	 * Verifica el estado de la conexión con SQ
	 * 
	 * @return boolean true: conexión OK | false: no se puede conectar
	 */
	public static function conexion_ok()
	{
		$estado = false;
		
		try{
			// Se realiza un pedido dummy a SQ para testear la conexión
			$response = static::get_cliente_rest()->get('status');
			static::validar_response($response, static::ESTADO_GET_OK, __FUNCTION__);
			$estado = true;
		}
		catch (Exception $e) {
			$estado = false;
		}
		
		return $estado;
	}
	
	/**
	 * Servicio REST a SQ "GET /servicios-instancias-valorizaciones":
	 * Permite recuperar la valorización "base" de un servicio-instancia en todas monedas en las que se definió.
	 * @param string $id_externo formato "propuesta|actividad|comision" ej: "1045|20555|4589"
	 * @param string $order 'asc' o 'desc', default: 'asc'
	 * @param int $limit cantidad máxima de registros a devolver
	 * @return array Estructura JSON
	 */
	public static function get_servicios_instancias_valorizaciones($id_externo, $order = self::ORDER_ASC, $limit = null)
	{
		$parametros = array();
		$parametros['query']['sistema_externo'] = static::get_sq_id_externo();
		$parametros['query']['id_externo'] = $id_externo;

		$parametros['query']['order'] = ($order == static::ORDER_DESC) ? '-id' : '+id';
		if ($limit) {
			$parametros['query']['limit'] = $limit;
		}

		$response = static::get_cliente_rest()->get('servicios-instancias-valorizaciones', $parametros);
		static::validar_response($response, static::ESTADO_GET_OK, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}

	/**
	 * Servicio REST a SQ "GET /servicios":
	 * Permite obtener los datos de una Actividad cobrable ya informada a SQ, con su estado (activo o inactivo) de Posgrados y Cursos de Extensión.
	 * Si la Actividad aún no fue informada a SQ, se retorna el arreglo vacío.
	 * @param string $id formato "propuesta|actividad" ej: "1045|20555"
	 * @return array
	 * <code>
	 *  array(
	 *     "id" => "ID del servicio en SQ",
	 *     "codigo" => "Código de la Actividad de Posgrado/Curso de Extensión",
	 *     "descripcion" => "Descripción de la Actividad de Posgrado/Curso de Extensión",
	 *     "id_tipo_servicio" => "Tipo de servicio",
	 *     "sistema_externo" => "Identificación de G3",
	 *     "id_externo" => "propuesta|actividad",
	 *     "id_contexto" => "Id de contexto",
	 *     "id_tipo_vencimiento" => "Tipo de vencimiento",
	 *     "estado" => ["A"|"I"],
	 *     "estado_actualizado_en" => "2016-07-05 18:10:52.90698",
	 *     "tags"=> array(
	 *          array("descripcion" => "idiomas"),
	 *          array("descripcion" => "avanzado"),
	 *          ...
	 *      ),
	 *  )
	 * </code>
	 */
	public static function obtener_actividad_cobrable($id)
	{
		$parametros = array();
		$parametros['query']['sistema_externo'] = static::get_sq_id_externo();
		$parametros['query']['id_externo'] = $id;
		try {
			$response = static::get_cliente_rest()->get('servicios', $parametros);
			/** Se harcodea un caso aparte para el reply del GET cuando no esta el recurso
			 * para que quede como en la version previa de SQ donde retornaba array vacio.
			 * Luego se extendera el validar response para que considere los distintos codigos
			 * de error ademas del OK.
			 */
			static::validar_response($response, static::ESTADO_GET_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			if ($ex->getCode() == static::ESTADO_GET_VACIO) {
				return array();
			} else {
				toba::logger()->error($ex->getMessage());
				throw new guarani_error(static::MSJ_ERROR_CONEXION);
			}
		}
	}

	/**
	 * Servicio REST a SQ "POST /servicios":
	 * Permite informar a SQ la oferta "facturable" de Actividades de Posgrados y Cursos de Extensión.
	 * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "id" => "propuesta|actividad",
	 *      "codigo" => "Código de la Actividad de Posgrado/Curso de Extensión",
	 *      "descripcion" => "Descripción de la Actividad de Posgrado/Curso de Extensión",
	 *      "tags"=> array(
	 *          array("descripcion" => "idiomas"),
	 *          array("descripcion" => "avanzado"),
	 *          ...
	 *          ),
	 *      "tipo" => ["POSGRADO"|"GRADO"|"CUREXTEN"],
	 *      "estado" => ["A"|"I"],
	 *		"contextos"=> array(
	 *          array("codigo" => "1", "descripcion" => "RA1"),
	 *          array("codigo" => "2", "descripcion" => "RA2"),
	 *          ...
	 *          )
	 *  )
	 * </code>
	 * @return array
	 * <code>
	 * array(
	 * "id" => "ID del servicio en SQ"
	 * )
	 * </code>
	 */
	public static function crear_servicio($body)
	{
		$body['sistema'] = static::get_sq_id_externo();
		try {
			$response = static::get_cliente_rest()->post('servicios', array('body' => rest_encode($body)));
			static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			toba::logger()->error($ex->getMessage());
			throw new guarani_error(static::MSJ_ERROR_CONEXION);
		}
	}

	/**
	 * Servicio REST a SQ "PUT /servicios/{id_externo}/sistema/{sistema}":
	 * Permite actualizar el estado de la Actividad de Posgrados y Cursos de Extensión, informada como "facturable"a SQ.
	 * @param integer $id id del recurso que se va a actualizar. Formato "propuesta|actividad" ej: "1045|20555"
	 * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "codigo" => "Código de la Actividad del Posgrado/Curso de Extensión",
	 *      "descripcion" => "Descripción de la Actividad Posgrado/Curso de Extensión",
	 *      "tags"=> array(
	 *          array("descripcion" => "idiomas"),
	 *          array("descripcion" => "avanzado"),
	 *          ...
	 *          ),
	 *      "tipo" => ["POSGRADO"|"CUREXTEN"],
	 *      "estado" => ["A"|"I"]
	 *  )
	 * </code>
	 * @return array
	 * <code>
	 * array(
	 * "id" => "ID del servicio en SQ"
	 * )
	 * </code>
	 */
	public static function actualizar_actividad_cobrable($id, $body)
	{
		$sistema = static::get_sq_id_externo();
		try {
			$response = static::get_cliente_rest()->put("servicios/{$id}/sistema/{$sistema}", array('body' => rest_encode($body)));
			static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			toba::logger()->error($ex->getMessage());
			throw new guarani_error(static::MSJ_ERROR_CONEXION);
		}
	}

	/**
	 * Servicio REST a SQ "DELETE /servicios/{id_externo}/sistema/{sistema}":
	 * Permite eliminar un servicio (Carreras de Grado, Posgrados y Cursos de Extensión) de SQ.
	 * @param integer $id_externo Id externo del servicio que se va a eliminar.
	 * @return array
	 * <code>
	 * array(
	 * "id" => "ID interno del servicio en SQ"
	 * )
	 * </code>
	 */
	public static function eliminar_servicio($id_externo)
	{
		$sistema = static::get_sq_id_externo();
		try {
			$response = static::get_cliente_rest()->delete("servicios/{$id_externo}/sistema/{$sistema}");
			static::validar_response($response, static::ESTADO_DELETE_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			toba::logger()->error($ex->getMessage());
			throw new guarani_error(static::MSJ_ERROR_CONEXION);
		}
	}
	
	/**
	 * Servicio REST a SQ "GET /servicios-versiones":
	 * Permite obtener información de SQ sobre las comisiones ?facturables? de Posgrados y Cursos de Extensión.
	 * @param string $id_externo formato "comision" ej: "25019"
	 * @return array
	 * <code>
	 *  array(
	 *     "id" => "ID del servicio-version en SQ",
	 *     "codigo" => "Código de la comisión",
	 *     "descripcion" => "Descripción de la comisión",
	 *     "id_servicio" => "Id servicio",
	 *     "id_servicio_instancia" => "Id servicio instancia",
	 *     "sistema_externo" => "Identificación de G3",
	 *     "id_externo" => "comision",
	 *     "estado" => ["A"|"I"],
	 *     "estado_actualizado_en" => "2016-07-07 15:43:06.418385",
	 *  )
	 * </code>
	 */
	public static function obtener_comision_cobrable($id_externo)
	{
		$parametros = array();
		$parametros['query']['sistema_externo'] = static::get_sq_id_externo();
		$parametros['query']['id_externo'] = $id_externo;
		try {
			$response = static::get_cliente_rest()->get('servicios-versiones', $parametros);
			static::validar_response($response, static::ESTADO_GET_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			if ($ex->getCode() == static::ESTADO_GET_VACIO) {
				return array();
			} else {
				toba::logger()->error($ex->getMessage());
				throw new guarani_error(static::MSJ_ERROR_CONEXION);
			}
		}
	}

	/**
	 * Servicio REST a SQ "POST /servicios-versiones":
	 * Permite informar a SQ las comisiones "facturables" de Posgrados y Cursos de Extensión.
	 * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "id" => "comision",
	 *      "codigo" => "Código de la comisión",
	 *      "descripcion" => "Descripción de la comisión",
	 *      "servicio" => "propuesta|actividad",
	 *      "estado" => ["A"|"I"]
	 *  )
	 * </code>
	 * @return array
	 * <code>
	 * array(
	 * "id" => "ID del servicio-version en SQ"
	 * )
	 * </code>
	 */
	public static function informar_comision_cobrable($body)
	{
		$body['sistema'] = static::get_sq_id_externo();
		try {
			$response = static::get_cliente_rest()->post('servicios-versiones', array('body' => rest_encode($body)));
			static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			/**
			 * Valida si el codigo de error de la excepcion corresponde al caso en que se esta 
			 * intentando informar una Comision (version), y la actividad (servicio) asociada no fue
			 * previamente informada, se retorna el codigo 422 y debe emitirse el msj de guarani correspondiente.
			 */
			if ($ex->getCode() == static::ERROR_POST_SERV_VERS_SIN_SERVICIO) {
				$msj = guarani_mensajes::get('800CUR_comision_actividad_no_informada_sq');
				throw guarani::error($msj);
			} else {
				toba::logger()->error($ex->getMessage());
				throw new guarani_error(static::MSJ_ERROR_CONEXION);
			}
		}
	}

	/**
	 * Servicio REST a SQ "PUT /servicios-versiones/{id_externo}/sistema/{sistema}":
	 * Permite actualizar en SQ la comisión "facturable" de Posgrados y Cursos de Extensión.
	 * @param string $id_externo formato "comision" ej: "25019"
	 * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "estado" => ["A"|"I"]
	 *  )
	 * </code>
	 * @return array
	 * <code>
	 * array(
	 * "id" => "ID del servicio-version en SQ"
	 * )
	 * </code>
	 */
	public static function actualizar_comision_cobrable($id_externo, $body)
	{
		$sistema = static::get_sq_id_externo();
		try {
			$response = static::get_cliente_rest()->put("servicios-versiones/{$id_externo}/sistema/{$sistema}", array('body' => rest_encode($body)));
			static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			toba::logger()->error($ex->getMessage());
			throw new guarani_error(static::MSJ_ERROR_CONEXION);
		}
	}

	/**
	 * Servicio REST a SQ "DELETE /servicios-versiones/{id_externo}/sistema/{sistema}":
	 * Permite eliminar en SQ la comisión "facturable" de Posgrados y Cursos de Extensión.
	 * @param string $id_externo formato "comision" ej: "25019"
	 * @return array
	 * <code>
	 * array(
	 * "descripcion" => "string"
	 * )
	 * </code>
	 */
	public static function eliminar_comision_cobrable($id_externo)
	{
		$sistema = static::get_sq_id_externo();
		try {
			$response = static::get_cliente_rest()->delete("servicios-versiones/{$id_externo}/sistema/{$sistema}");
			static::validar_response($response, static::ESTADO_DELETE_OK, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (Exception $ex) {
			toba::logger()->error($ex->getMessage());
			throw new guarani_error(static::MSJ_ERROR_CONEXION);
		}
	}
	
	/**
	 * Servicio REST a SQ "POST /solicitudes-consumos-externos":
	 * Genera una solicitud de consumo externo.
	 * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "cliente" => array(
	 *         "id_externo" => "ID persona",
	 *         "tipo_documento" => "Tipo documento",
	 *         "numero_documento" => "Número documento",
	 *         "apellido" => "Apellido",
	 *         "nombres" => "Nombres"
	 *      ),
	 *      "tipo_consumo" => ["S"|"P"|"A"],
	 *      "id_consumo" => "propuesta|actividad|comision",
	 *      "id_tramite_ref" => "Nro. transacción inscripción",
     *      "callback_url" => "URL para redirigir a Guaraní"
	 *  )
	 * </code>
	 * @return array
	 * <code>
	 * array(
	 * "token" => "Token para G3"
	 * )
	 * </code>
	 */
	public static function generar_solicitud_consumo_externo($body)
	{
		$body['id_canal_venta'] = 1; //siempre en 1
		$body['aplicacion_externa'] = static::get_sq_id_externo();
		$response = static::get_cliente_rest()->post('solicitudes-consumos-externos', array('body' => rest_encode($body)));
		static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}

    /**
     * Servicio REST a SQ "GET /solicitudes-consumos-externos":
     * Permite conocer las solicitudes de consumos externos que no se procesó el pago o que se produjo un error
     * @param array $query arreglo asociativo con los datos de la "query string" recibidos por el servicio.
     * Consta del siguiente formato, todos los valores son opcionales:
     * <code>
     *  array(
     *      "id" => "ID de la solicitud de consumo externo",
     *      "token" => "Token de la solicitud de consumo externo",
     *      "cliente" => "Id del cliente",
     *      "codigo_procesamiento" => "Permite multiples valores separados por coma",
     *      "limit" => "Limitar a esta cantidad de registros (defecto: 20)",
     *      "page" => "página",
     *      "order" => "Ordena por el/los campos (defecto: +curso) +/-[curso,nombre]"
     *  )
     * </code>
     * @return array
     * <code>
     * array(
     *     array(
     *         "id" => "90",
     *         "token" => "e6b370cf-f709-43a9-a1fa-2c6ace145eee",
     *         "id_canal_venta" => "1",
     *         "aplicacion_externa" => "3",
     *         "id_cliente" => "35",
     *         "tipo_consumo" => "S",
     *         "id_consumo" => "propuesta|curso|comision",
     *         "id_consumo_sq" => "32",
     *         "contexto_tramite_ref" => "",
     *         "id_tramite_ref" => "15420",
     *         "id_derivada_de" => "",
     *         "generado_en" => "2016-07-26 15:37:10.015299",
     *         "callback_cobranza_url" => "URL callback Guaraní",
     *         "procesado_en" => "2016-07-26 15:37:18.33837",
     *         "codigo_procesamiento" => ["EX"|"OK"|"NUEVO"],
     *         "mensaje_procesamiento" => "",
     *     ),
     *     ..................
     * )
     * </code>
     */
    public static function get_solicitudes_consumos_externos($query)
    {
        $parametros = array();
        $parametros['query'] = $query;
        $parametros['query']['sistema_externo'] = static::get_sq_id_externo();

        $response = static::get_cliente_rest()->get('solicitudes-consumos-externos', $parametros);
        static::validar_response($response, static::ESTADO_GET_OK, __FUNCTION__);
        return rest_decode(guarani_rest::get_response_json($response));
    }


    /**
     * Servicio REST a SQ "PUT /solicitudes-consumos-externos/{token}":
     * Verifica si la solicitud sigue activa. Si sigue activa devuelve el mismo token, si expiró genera una nueva suscripción derivada de la original y retorna el nuevo token.
     * @param string $token token de la solicitud de consumo externo
     * @return array
     * <code>
     * array(
     * "token" => "el mismo token si sigue activo, si expiró devuelve un token nuevo"
     * )
     * </code>
     */
    public static function actualizar_solicitud_consumo_externo($token)
    {
        try {
            $response = static::get_cliente_rest()->put("solicitudes-consumos-externos/{$token}");
            static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
            return rest_decode(guarani_rest::get_response_json($response));
        } catch (Exception $ex) {
            toba::logger()->error($ex->getMessage());
            throw new guarani_error(static::MSJ_ERROR_CONEXION);
        }
    }

	/**
	 * Servicio REST a SQ "GET /consumos-externos/estado":
	 * Obtiene el estado de un consumo, retornando si esta pagado o no, a partir de su tipo y
	 * su id de tramite de referencia.
	 * @param string $tipo_consumo el tipo de consumo para el que se desea averiguar el estado (suscripcion|prestacion|articulo).
	 * @param integer $id_tramite_ref id que referencia al tramite asociado al consumo cobrable.
     * @return array
     * <code>
     * array(
     *     array(
     *         "estado" => "cobrada|pendiente|vencida",
     *         "modo_devolucion" => "agente_cobranza|credito|no_corresponde",
     *         "opciones_devolucion" => [['valor' => 'A', 'descr' => 'Devolución por agente de cobranza'], ['valor' => 'C', 'descr' => 'Crédito en la universidad'], ['valor' => 'NC', 'descr' => 'No corresponde devolución']],
     *         "status_code" => "200|404"
     *     )
     * )
     * </code>
	 */
	public static function get_estado_consumo_externo($tipo_consumo, $id_tramite_ref)
	{
		$parametros = [];
		$parametros['query']['tipo_consumo'] = $tipo_consumo;
		$parametros['query']['sistema_externo'] = static::get_sq_id_externo();
		$parametros['query']['id_tramite_ref'] = $id_tramite_ref;
		$parametros['exceptions'] = false;

		$response = static::get_cliente_rest()->get('consumos-externos/estado', $parametros);

		//Si no existe consumo externo lo considero como pendiente
		if($response->getStatusCode() == static::ESTADO_GET_VACIO){
			return rest_decode([
				'estado' => static::ESTADO_CONSUMO_PENDIENTE,
				'modo_devolucion' => static::CONSUMOS_EXTERNOS_FORMA_COBRO_NC,
				'opciones_devolucion' => static::get_opciones_devolucion_suscripcion_novedad(static::CONSUMOS_EXTERNOS_FORMA_COBRO_NC),
				'status_code' => $response->getStatusCode()
			]);
		}

		static::validar_response($response, static::ESTADO_GET_OK, __FUNCTION__);
		$response_json = rest_decode(guarani_rest::get_response_json($response));
		$response_json['opciones_devolucion'] = static::get_opciones_devolucion_suscripcion_novedad($response_json['modo_devolucion']);
		$response_json['status_code'] = $response->getStatusCode();
		return $response_json;
	}

	/**
	 * Servicio REST a SQ "POST /suscripciones-novedades":
	 * Permite informar a SQ si un alumno "abandona" la cursada de un Curso de Extensión. También permite en Posgrados, suspensiones y reactivaciones.
	 * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "id"				=> "Nro. transacción inscripción",
	 *		"tipo_novedad"		=> ["A"|"B"|"I"],
	 *      "observaciones"		=> "Observaciones de la novedad",
	 * 		"modo_devolucion"	=> ["A"|"C"|"NC"]
	 *  )
	 * </code>
	 * 
	 * @throws Exception Excepción cuyo código es el Status Code de la respuesta de SQ (404: si no existe suscripción en SQ, 422: Si es un error de negocio en SQ).
	 * @return array
	 * <code>
	 * array(
	 * "id" => "ID del servicio-version en SQ"
	 * )
	 * </code>
	 */
	public static function informar_suscripcion_novedad($body)
	{
		$body['sistema'] = static::get_sq_id_externo();
		
		toba::logger()->debug("=== Body POST /suscripciones-novedades ===");
		toba::logger()->var_dump($body);
		
		$response = static::get_cliente_rest()->post('suscripciones-novedades', [
			'body' => rest_encode($body),
			'http_errors' => false
		]);
		
		// Si sale todo bien (Status Code 201)
		if ($response->getStatusCode() == static::ESTADO_POST_PUT_OK) {
			
			return rest_decode(guarani_rest::get_response_json($response));
			
		} else { // sino lanzo una Excepción con el código correspondiente
			
			toba::logger()->error("Error en " . __FUNCTION__ . ". Se esperaba " . static::ESTADO_POST_PUT_OK . " y se obtuvo {$response->getStatusCode()}");
			toba::logger()->var_dump(rest_decode(guarani_rest::get_response_json($response)));
			throw new Exception(static::get_mensaje_descripcion($response), $response->getStatusCode());
			
		}
		
	}

	/**
	 * Invoca al tópico 'post_solicitudes_consumos_externos' de rest-hooks.
	 * @param string	$tipo			Tipo de entidad [POSGRADO|GRADO|CUREXTEN].
	 * @param int		$detalle		Datos sobre el consumo externo.
	 * 
	 * @return void
	 */
	public static function post_solicitudes_consumos_externos_rest_hooks($tipo, $detalle) {

		// Si se trata de una propuesta cobrable
		if ($tipo == static::TIPO_POSGRADO) {

			$ras = guarani::co('co_responsables_academicas')->get_propuestas_ras($detalle['propuesta']);
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras, false);
			$es_plan_cobrable = ($detalle['plan_cobrable'] == comunes::si);


			/**
			 * @see https://repositorio.siu.edu.ar/trac/guarani/ticket/3121 Ticket.
			 * Si se trata de un plan cobrable y que utiliza SQ
			 */
			if (($usa_sq == comunes::si) && $es_plan_cobrable) {
				
				$datos_persona = guarani::co('co_personas')->get_datos_persona($detalle['persona']);

				$payload = array(
					"cliente" => array(
						"id_externo" => $datos_persona['persona'],
						"tipo_documento" => $datos_persona['tipo_doc'],
						"numero_documento" => $datos_persona['nro_documento'],
						"apellido" => $datos_persona['apellido'],
						"nombres" => $datos_persona['nombres']
					),
					"tipo_consumo" => static::TIPO_CONSUMO_SUSCRIPCION_ABREV,
					"aplicacion_externa" => static::get_sq_id_externo(),
					"id_consumo" => $detalle['plan'],
					"id_tramite_ref" => $detalle['nro_transaccion'],
					"callback_url" => guarani_instalacion_conf::get_parametro_instalacion('url3w', false, static::CALLBACK_URL_NO_APLICA),
					"id_canal_venta" => 1,
				);

				$context = array(
					'tipo' => static::TIPO_POSGRADO,
					'propuesta_aspira' => $detalle['propuesta_aspira']
				);

				try {
					// Se invoca al tópico 'post_solicitudes_consumos_externos' de rest-hooks
					RestHookSanaviron::getRestMessenger()->send("post_solicitudes_consumos_externos", $payload, $context);
				} catch (Exception $e) {

					// Si Redis esta caído:
					toba::logger()->error("Error al encolar mensaje en Rest-Hooks, referencia: http://documentacion.siu.edu.ar/wiki/SIU-Guarani/Version3.15.0/interfaces/rest-hooks");
					toba::logger()->error("Mensaje: " . $e->getMessage());
					$msg_sq_rest_hooks_caido = guarani_mensajes::get('800_sq_rest_hooks_caido');
					throw new guarani_error($msg_sq_rest_hooks_caido);
				}
				
			}
			
		}
	}

	/**
	 * Invoca al tópico 'post_suscripciones_novedades' de rest-hooks.
	 * @param string	$tipo			Tipo de entidad [POSGRADO|GRADO|CUREXTEN].
	 * @param int		$id			Id de la entidad, ej: para propuestas es el id de 'propuesta_aspira'.
	 * @param string	$tipo_novedad           Tipo de novedad [B|I|A].
         * @param string	$operacion		Tipo de novedad [B|P|A|R|RR] (ver constantes de la clase 'inscripcion').
	 * 
	 * @return void
	 */
	public static function post_suscripciones_novedades_rest_hooks($tipo, $id, $tipo_novedad, $operacion) {

		// Si se trata de una propuesta cobrable
		if ($tipo == static::TIPO_POSGRADO) {

			$propuesta_aspira = $id;

                        // Si se trata de la operación "Dar de Baja Inscripción a Propuesta"
                        if ($operacion == inscripcion::operacion_baja) {
                            $detalle = guarani::co('co_inscripciones')->get_detalle_propuesta_aspira_log($propuesta_aspira);
                        } else {
                            $detalle = guarani::co('co_inscripciones')->get_detalle_propuesta_aspira($propuesta_aspira);
                        }
			
			static::post_suscripciones_novedades_propuestas_send($detalle, $tipo_novedad, $operacion);
		}
		
	}

	/**
	 * Invoca al tópico 'post_suscripciones_novedades' de rest-hooks pasando los datos de la inscripción de una propuesta.
	 * @param array		$detalle		Datos de la inscripción de una propuesta.
	 * Consta del siguiente formato:
	 * <code>
	 *  array(
	 *      "propuesta"			=> "Id de la propuesta",
	 *		"plan_cobrable"		=> "Si el plan en cobrable o no",
	 *      "nro_transaccion"	=> "Nro. de transacción de la inscripción"
	 *  )
	 * </code>
	 * @param string	$tipo_novedad	Tipo de novedad [B|I|A].
	 * @param string	$operacion		Tipo de novedad [B|P|A|R|RR] (ver constantes de la clase 'inscripcion').
	 * 
	 * @return void
	 */
	public static function post_suscripciones_novedades_propuestas_send($detalle, $tipo_novedad, $operacion) {

		$ras = guarani::co('co_responsables_academicas')->get_propuestas_ras($detalle['propuesta']);
		$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras, false);

		$es_plan_cobrable = ($detalle['plan_cobrable'] == comunes::si);

		/**
		 * @see https://repositorio.siu.edu.ar/trac/guarani/ticket/3126 Ticket.
		 * Si se trata de un plan cobrable y que utiliza SQ
		 */
		if (($usa_sq == comunes::si) && $es_plan_cobrable) {

			/*
			 * Si no existe token, es decir, nunca se creo el consumo externo...
			 */
			if (is_null($detalle['sq_token'])) {

				$sq_sincronizado = cobro::SQ_INSCRIPCION_NO_SINCRONIZADA;
				$es_baja = ($operacion == inscripcion::operacion_baja);
				
				// ... si se cambio de calidad a activo o se revirtió el rechazo dejo que resincronice el alta ...
				if (in_array($operacion, array(inscripcion::operacion_cambio_calidad_activo, inscripcion::operacion_revertir_rechazo))) {
					$sq_sincronizado = cobro::SQ_INSCRIPCION_FALLO_ALTA;
				} else { // ... sino marco como sincronizado ya que se anula la notificación a SQ.
					$sq_sincronizado = cobro::SQ_INSCRIPCION_NO_SINCRONIZADA;
				}
				
				/*
				* Actualizo el estado de sincronización con SQ en la tabla 'sga_propuestas_aspira'
				*/
				guarani::act('act_inscripciones')->actualizar_sq_sincronizado_propuesta_aspira($detalle['propuesta_aspira'], $sq_sincronizado, $es_baja);
				return true;
				
			}

			$operacion_descr = "";
			
			switch ($operacion) {
				case inscripcion::operacion_baja:
					$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_baja');
					break;
				case inscripcion::operacion_cambio_calidad_activo:
					$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_cambio_calidad_activo');
					break;
				case inscripcion::operacion_cambio_calidad_pasivo:
					$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_cambio_calidad_pasivo');
					break;
				case inscripcion::operacion_rechazo:
					$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_rechazo');
					break;
				case inscripcion::operacion_revertir_rechazo:
					$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_revertir_rechazo');
					break;
			}
			
			$payload = array(
				"sistema" => static::get_sq_id_externo(),
				"id" => $detalle['nro_transaccion'],
				"tipo_novedad" => $tipo_novedad,
				"observaciones" => guarani_mensajes::get('800_observacion_sq_suscripciones_novedades', array($detalle['nro_transaccion'], $operacion_descr))
			);

			// Solo cuando se trata de una baja (tipo_novedad = 'B') se informa el modo de devolución.
			if ($tipo_novedad == static::TIPO_NOVEDAD_BAJA) {
                            $payload["modo_devolucion"] = static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE;
                        }
			
			$context = array(
				'tipo' => static::TIPO_POSGRADO,
				'propuesta_aspira' => $detalle['propuesta_aspira'],
				'operacion' => $operacion
			);
			
			try {
				// Se invoca al tópico 'post_suscripciones_novedades' de rest-hooks
				RestHookSanaviron::getRestMessenger()->send("post_suscripciones_novedades", $payload, $context);
			} catch (Exception $e) {
				
				// Si Redis esta caído:
				toba::logger()->error("Error al encolar mensaje en Rest-Hooks, referencia: http://documentacion.siu.edu.ar/wiki/SIU-Guarani/Version3.15.0/interfaces/rest-hooks");
				toba::logger()->error("Mensaje: " . $e->getMessage());
				$msg_sq_rest_hooks_caido = guarani_mensajes::get('800_sq_rest_hooks_caido');
				throw new guarani_error($msg_sq_rest_hooks_caido);
				
			}
			
		}
	}

    /**
     * Servicio REST a SQ "/POST /gco-jwt/":
     * obtiene el token
     * @param   "tipo_documento": 0,"numero_documento": 0
     * @return array
     * <code>
     * array(
     * "token" => "devuelve un token nuevo"
     * )
     * </code>
     */
    public static function portal_pagos_gco_jwt($body)
    {
		try {
			$response = static::get_cliente_rest()->post("gco-jwt", array('json' => $body));
			$datos = guarani_rest::get_response_json($response);
			static::validar_response($response, static::ESTADO_POST_PUT_OK, __FUNCTION__);
			return rest_decode($datos);
		} catch (Exception $ex) {
			toba::logger()->error($ex->getMessage());
			throw new guarani_error(static::MSJ_ERROR_CONEXION);
		}
	}
	
	/**
     * Devuelve los modos de devolución validos para el servicio POST /suscripciones-novedades
     * @return array modos de devolución validos para el servicio POST /suscripciones-novedades
     */
	public static function get_modos_devolucion_suscripcion_novedad()
	{
		return [
			static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_AGENTE_COBRANZA, 
			static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_CREDITO, 
			static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE
		];
	}
			
	/**
     * Devuelve opciones de devolución para el servicio POST /suscripciones-novedades
     * @param string $modo_devolucion Modo de devolución devuelto por el servicio GET /consumos-externos/estado
     * @return array opciones de devolución para el servicio POST /suscripciones-novedades
	 * <code>
     * [
	 *	[
	 *		'valor' => 'A', 
			'descr' => 'Devolución por agente de cobranza'
	 *	],
     *	[
	 *		'valor' => 'C', 
			'descr' => 'Crédito en la universidad'
	 *	],
     * ]
     * </code>
     */
	public static function get_opciones_devolucion_suscripcion_novedad($modo_devolucion)
    {
		
		// Puede venir en el formato "agente_cobranza|credito|no_corresponde"
		$modos_devolucion = explode("|", $modo_devolucion);
		
		$opciones_devolucion = [];
		
		foreach ($modos_devolucion as $modo_devolucion) {
			
			$opcion_devolucion = null;
		
			switch ($modo_devolucion) {
				case static::CONSUMOS_EXTERNOS_FORMA_COBRO_AC:
					$opcion_devolucion = [
						'valor' => static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_AGENTE_COBRANZA, 
						'descr' => static::CONSUMOS_EXTERNOS_FORMA_COBRO_AC_ETIQUETA
					];
					break;
				case static::CONSUMOS_EXTERNOS_FORMA_COBRO_CREDITO:
					$opcion_devolucion = [
						'valor' => static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_CREDITO, 
						'descr' => static::CONSUMOS_EXTERNOS_FORMA_COBRO_CREDITO_ETIQUETA
					];
					break;
				case static::CONSUMOS_EXTERNOS_FORMA_COBRO_NC:
					$opcion_devolucion = [
						'valor' => static::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE, 
						'descr' => static::CONSUMOS_EXTERNOS_FORMA_COBRO_NC_ETIQUETA
					];
					break;
			}
			
			if (!is_null($opcion_devolucion)) {
				$opciones_devolucion[] = $opcion_devolucion;
			}
		}
		
		return $opciones_devolucion;
	}
	
	/**
     * Devuelve select con opciones de devolución para el servicio POST /suscripciones-novedades
     * @param array opciones de devolución para el servicio POST /suscripciones-novedades
	 * <code>
     * [
	 *	[
	 *		'valor' => 'A', 
			'descr' => 'Devolución por agente de cobranza'
	 *	],
     *	[
	 *		'valor' => 'C', 
			'descr' => 'Crédito en la universidad'
	 *	],
     * ]
     * </code>
     * @return string Select con opciones de devolución para el servicio POST /suscripciones-novedades

     */
	public static function get_select_devolucion_suscripcion_novedad($opciones)
	{
		$select = "";
		foreach ($opciones as $opcion) {
			$select .= '<option value="'.$opcion['valor'].'">'.$opcion['descr'].'</option>';
		}
		return $select;
	}

        /**
         * Encola en Redis para notificar a SQ las inscripciones a Propuesta vía Rest-Hooks
         * @param int $propuesta_aspira Columna 'sga_propuestas_aspira.propuesta_aspira'
         * @throws guarani_error Si falla encolar en Redis
         * @return void
         */
        static function notificar_inscripcion_propuesta_SQ($propuesta_aspira)
        {
            // Recupero detalle de la tabla 'sga_propuestas_aspira'
            $detalle_propuesta_aspira = guarani::co('co_inscripciones')->get_detalle_propuesta_aspira($propuesta_aspira);

            // Si tiene token salgo (ya fue notificado a SQ)
            if (!is_null($detalle_propuesta_aspira['sq_token'])) {
                return false;
            }

            // Verifico que la inscripción no haya sido ya encolada para que no notifique dos veces
            if ($detalle_propuesta_aspira['sq_sincronizado'] != static::SQ_INSCRIPCION_NO_SINCRONIZADA) {
                return false;
            }

            // Si el plan no es cobrable salgo
            if ($detalle_propuesta_aspira['plan_cobrable'] == comunes::no) {
                return false;
            }

            $propuesta = $detalle_propuesta_aspira['propuesta'];
            $ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);

            // Recupero el parámetro 'sq_usa_sanaviron'
            $usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras, false);
            // Si no usa SQ salgo
            if ($usa_sq == comunes::no) {
                return false;
            }

            // Recupero el parámetro 'sq_notif_insc_pendientes'
            $sq_notif_insc_pendientes = guarani::parametros()->get_valor(cobro::PARAM_SQ_NOTIF_INSC_PENDIENTES, $ras, false);
            
            $notificar_SQ = false;
            $situacion_aspirante = $detalle_propuesta_aspira['situacion_asp'];

            switch ($sq_notif_insc_pendientes) {
              // Si el parámetro 'sq_notif_insc_pendientes' es 'T'
              case cobro::SQ_NOTIF_INSC_TODAS:

                  // Si la inscripción quedo "Pendiente" o "Aceptada"
                  $notificar_SQ = in_array($situacion_aspirante, [propuesta::situacion_asp_insc_aspirante_a_prop, propuesta::situacion_asp_insc_prop_aceptada]);
                  
                break;
              // Si el parámetro 'sq_notif_insc_pendientes' es 'A'
              case cobro::SQ_NOTIF_INSC_ACEPTADAS:

                  // Si la inscripción quedo "Aceptada"
                  $notificar_SQ = ($situacion_aspirante == propuesta::situacion_asp_insc_prop_aceptada);

                break;
              // Si el parámetro 'sq_notif_insc_pendientes' es 'R'
              case cobro::SQ_NOTIF_INSC_REQ_DOC_RESTRICTIVOS:

                // Si cumple los requisitos de ingreso documentales marcados como restrictivos
                $filtro = "sga_requisitos_aspirante.restrictivo = '".comunes::si."'";
                $notificar_SQ = toba::consulta_php('co_inscripciones')->cumple_requisitos_ingreso_documentales($propuesta_aspira, $filtro);

                break;
            }

            // Si corresponde notificar a SQ
            if ($notificar_SQ) {

                try {

                    toba::db()->abrir_transaccion();

                    // Actualizo el estado de sincronización con SQ en la tabla 'sga_propuestas_aspira'
                    guarani::act('act_inscripciones')->actualizar_sq_sincronizado_propuesta_aspira($propuesta_aspira, static::SQ_INSCRIPCION_ENCOLADA);

                     // Se encola en Redis para notificar a SQ vía Rest-Hooks
                     static::post_solicitudes_consumos_externos_rest_hooks(static::TIPO_POSGRADO, $detalle_propuesta_aspira);
                     
                     toba::db()->cerrar_transaccion();
                } catch (\Exception $e) {
                    toba::db()->abortar_transaccion();
                    throw new guarani_error($e->getMessage());
                }

            }
        }

}