<?php

use Ramsey\Uuid\Uuid;

/**
 * Clase para consumir los WS de Araí Usuarios V2
 * @see https://documentacion.siu.edu.ar/apis/?spec=arai-usuarios_v2 API de Araí Usuarios V2.
 * @package Araí
 */
class arai_usuarios_v2_nucleo extends arai_usuarios_nucleo {
	
	/**
	 * Servicio REST a Araí Usuarios, GET /uuid/{identificador}:
	 * Devuelve uid de un usuario.
	 * Solo disponible a partir de API V2
	 * @param string $identificador Identificador del usuario.
	 * @throws guarani_error En caso de error
	 * @return string uid de un usuario.
	 */
	public function getUidUsuario($identificador)
	{
		// https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
		$options['http_errors'] = false;
		$response = $this->getClienteRest()->get("uuid/$identificador", $options);
		$this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
		$respuesta = rest_decode(guarani_rest::get_response_json($response));
		toba::logger()->debug("API Araí Usuarios - GET /uuid/{identificador}:");
		toba::logger()->debug(print_r($respuesta, true));
		return $respuesta['uid'];
	}
	
	/**
	 * Servicio REST a Araí Usuarios, GET /usuarios/{identificador}:
	 * Usuario de Araí.
	 * @param string $usuario Identificador del usuario.
	 * @throws guarani_error En caso de error
	 * @return array Retorna datos de un usuario.
	 */
	public function getUsuario($usuario)
	{
		// Si lo que se le pasa no es un UUID válido entonces seguramente se esta pasando el id_usuario_sso
		if (!Uuid::isValid($usuario)) {
			$usuario = $this->getUidUsuario($usuario);	
		}
		
		return parent::getUsuario($usuario);
	}
	
}