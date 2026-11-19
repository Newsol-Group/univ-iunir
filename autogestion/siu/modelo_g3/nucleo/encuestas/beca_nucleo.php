<?php
/**
 * 
 * 
 * @package Encuestas
 */
use GuzzleHttp\Psr7\Response;

class beca_nucleo
{
	// Becas Manuel Belgrano y Progresar.
	const becas_manuel_belgrano = 'B';
	const becas_manuel_belgrano_descr = 'Manuel Belgrano';
	const becas_manuel_belgrano_dir = 'manuel_belgrano';
	const becas_progresar = 'P';
	const becas_progresar_descr = 'Progresar';
	const becas_progresar_dir = 'progresar';

	// Períodos para becas Manuel Belgrano y Progresar.
	const periodo_anual = 'A';
	const periodo_anual_descr = 'Anual';
	const periodo_primer_semestre = 'S';
	const periodo_primer_semestre_descr = 'Primer Semestre';

	// Validez de las convocatorias.
	const validez_vigentes = 'V';
	const validez_vigentes_futuras = 'A';
	const validez_todas = 'T';

        // Estados de los requisitos para la convocatoria
        const REQUISITO_RECHAZADO = 'Rechazado';
	const REQUISITO_NO_POSEE = 'No posee';
	const REQUISITO_PRESENTADO = 'Presentado';
	const REQUISITO_NO_PRESENTADO = 'No presentado';

	protected static $cliente_tehuelche;
    protected static $ug;

	/**
	 * @return \GuzzleHttp\Client
	 */
	protected static function get_cliente_rest()
	{
		if(!isset(self::$cliente_tehuelche)){
			$cliente = toba::servicio_web_rest('tehuelche');
			self::$cliente_tehuelche = $cliente->guzzle();
		}
		return self::$cliente_tehuelche;
	}

	/**
	 * Verifica la conexión con SIU-Tehuelche.
	 * 
	 * @return boolean
	 */
	public static function conexion_tehuelche_ok()
	{
		try {
			// Se realiza un pedido dummy a Tehuelche para testear la conexión.
			$response = static::get_cliente_rest()->get('unidades-gestion', array());
			$exito = true;
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getRequest()->getBody());
			toba::logger()->error($e->getResponse()->getBody());
			toba::logger()->error($e->getMessage());
			$exito = false;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			$exito = false;
		}
		return $exito;
	}

	/**
	 * Determina si está parametrizado que se usan Unidades de Gestión de Guaraní en la vinculación con convocatorias Tehuelche.
	 * 
	 * @return boolean
	 */
	public static function tehuelche_utiliza_ug()
	{
		return guarani::parametros()->get_valor('bec_utiliza_ug_tehuelche', null, false) == comunes::si;
	}

	public static function get_ug_default_tehuelche()
	{
		return guarani::parametros()->get_valor('bec_tehuelche_unidad_gestion', null, false);
	}

	/**
	 * Obtiene las Unidades de Gestión definidas en Tehuelche.
	 * 
	 * @return type
	 */
	public static function get_unidades_gestion()
	{
		try {
			$response = static::get_cliente_rest()->get('unidades-gestion', array());
			static::validar_response($response, 200, __FUNCTION__);
			return rest_decode(guarani_rest::get_response_json($response));
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getMessage());
			return null;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			return null;
		}
	}

    public static function get_convocatorias($ug, $validez=beca::validez_vigentes)
	{
		$response = static::get_cliente_rest()->get('convocatorias', array('query' => array('unidad_de_gestion' => $ug, 'validez' => $validez)));
		static::validar_response($response, 200, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}
	
	public static function get_convocatoria($ug, $id)
	{
		$response = static::get_cliente_rest()->get('convocatorias', array(
			'query' => array(
				'unidad_de_gestion' => $ug,
				'id' => $id,
				'validez' => beca::validez_todas
			)
		));
		static::validar_response($response, 200, __FUNCTION__);
		return current(rest_decode(guarani_rest::get_response_json($response)));
	}

	public static function get_lista_convocatorias($ug, $lista_ids)
	{
		$response = static::get_cliente_rest()->get('convocatorias', array(
			'query' => array(
				'unidad_de_gestion' => $ug,
				'lista_ids' => $lista_ids,
				'validez' => beca::validez_todas
			)
		));
		static::validar_response($response, 200, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
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
		if($response->getStatusCode() != $status){
			toba::logger()->error('Error en '. $desc_error . ". Se esperaba $status y se obtuvo {$response->getStatusCode()}");
			toba::logger()->var_dump(rest_decode(guarani_rest::get_response_json($response)));
			throw guarani::error(static::get_mensaje_descripcion($response));
		}
	}

	public static function get_aspirante_solicitudes($ug, $cuil)
	{
		$response = static::get_cliente_rest()->get("aspirantes/{$cuil['nro_documento']}/solicitudes",
                    [
                        'query' => [
                            'unidad_de_gestion' => $ug,
                            'limit' => 'all',
                        ],
                        'http_errors' => false
                    ]
		);

                // Si en Tehuelche no se sincronizo los "Datos de aspirantes + respuestas del cuestionario complementario"
                // entonces muestro "Solicitud enviada a evaluación"
                if ($response->getStatusCode() == 404) {
                    return [];
                } else {
                    static::validar_response($response, 200, __FUNCTION__);
                    return rest_decode(guarani_rest::get_response_json($response));
                }
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
				$mje = 'Error en la comunicación con Tehuelche';
			}
		}
		return $mje;
	}
}