<?php

/**
 * Métodos relacionados a los servicios web tipo REST
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_rest
{
	
	/**
	 * Obtiene el json de la Respuesta de Guzzle.
	 * 
	 * @version 6.3 Funciona con la versión 6.3 de Guzzle.
	 * 
	 * @param GuzzleHttp\Psr7\Response $response_object Respuesta de Guzzle.
	 *
	 * @return string Retorna el json de la Respuesta de Guzzle.
	 */
	public static function get_response_json($response_object)
	{
		$response_body = $response_object->getBody();
		return $response_body->getContents();
	}
	
}