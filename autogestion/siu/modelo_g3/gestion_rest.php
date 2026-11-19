<?php
namespace siu\modelo_g3;

use GuzzleHttp\Psr7\Response;
use SIU\Chulupi\kernel;

class gestion_rest {


	//Códigos de respuesta de los servicios
	const STATUS_CODE_OK = 200;
	const STATUS_CODE_BAD_REQUEST = 400;
	const STATUS_CODE_NOT_FOUND = 404;
	const STATUS_CODE_ERROR = 500;
	
	protected static $cliente_gestion;
	
    /**
     * @return \GuzzleHttp\Client
     */
    protected static function get_cliente_rest()
    {
        if (!isset(static::$cliente_gestion)) {
            $cliente = \toba::servicio_web_rest('gestion');
            static::$cliente_gestion = $cliente;
        }
        return static::$cliente_gestion;
    }

    /**
	 * Servicio REST a Gestión "GET /alumnos/{$alumno}/certificados/{$certificado}":
	 * Permite saber si un alumno cumple las condiciones para solicitar la certificación.
	 * @param int $alumno Id del alumno
	 * @param int $certificado Id del certificado
	 * @return array
	 * <code>
	 *  array(
	 *     "cumple_condiciones" => [true, false],
	 *  )
	 * </code>
	 */
	public static function cumple_condiciones_certificacion($alumno, $certificado)
	{
		$parametros = array();
		
		try {
			$response = static::get_cliente_rest()->get("alumnos/{$alumno}/certificados/{$certificado}", $parametros);
			
			static::validar_response($response, static::STATUS_CODE_OK, __FUNCTION__);
			return rest_decode(\guarani_rest::get_response_json($response));
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			throw new \guarani_error(kernel::traductor()->trans('solicitar_certificacion.fallo_conexion_rest'));
		}
		
	}

    /**
     * Valida si el response tiene el status requerido, en caso de error devuelve una descripción del mismo
     *
     * @param Response	$response
     * @param integer	$status	estado contra el que valida si el response tiene ese estado
     * @param string	$desc_error	descripción en caso de que haya un error.
     */
    protected static function validar_response(Response $response, $status, $desc_error)
    {
		// Información de DEBUG
		kernel::log()->add_debug('=== Body ===', $response->getBody());
		kernel::log()->add_debug('=== Headers ===', $response->getHeaders());
		kernel::log()->add_debug('=== ReasonPhrase ===', $response->getReasonPhrase());
		kernel::log()->add_debug('=== StatusCode ===', $response->getStatusCode());
		
        if ($response->getStatusCode() != $status) {
            kernel::log()->add_debug('Error REST:', 'Error en ' . $desc_error . ". Se esperaba $status y se obtuvo {$response->getStatusCode()}");
            kernel::log()->add_debug('Response REST:', rest_decode(\guarani_rest::get_response_json($response)));
            throw new \Exception(static::get_mensaje_descripcion($response));
        }
    }
	
	protected static function get_mensaje_descripcion($guzzle_res)
	{
		$response = rest_decode(\guarani_rest::get_response_json($guzzle_res));
		if (isset($response['descripcion'])) {
			$mje = $response['descripcion'];
		} else {
			if (isset($response['errores']) && isset($response['errores'][0]['error'])) {
				$mje = $response['errores'][0]['error']; //muestro el primer error
			} else {
				$mje = kernel::traductor()->trans('solicitar_certificacion.fallo_conexion_rest');
			}
		}
		return $mje;
	}
	
}