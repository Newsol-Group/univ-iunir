<?php


namespace siu\modelo\entidades;


///////////////////////////////////////////////////////////////////
/////// FUNCIONES EXTRAIDAS DE KOLLA KOLLA::url_encuestas.php //////
///////////////////////////////////////////////////////////////////
class url_encuestas
{
	const param_habilitacion = 'h';
	const param_token = 't';
	const param_op_toba = 'ai=kolla||40000112&tm=1';

	////////////////////PARA LAS URLS/////////////////////////

	/**
	 * @param type $password el password provisto en la habilitacion
	 * @param type $id_hab el id de la habilitacion
	 * @param type $id_form el formulario a responder
	 * @param type $cod_externo identificación del encuestado
	 * @param type $accion 'ver_respuestas' para ver las respuestas de una encuesta ya respondida
	 * @param type $cod_recuperacion si la encuesta es anonima es requerido para recuperarla
	 * @param type $hash_validacion si la encuesta es anonima es requerido para recuperarla
	 * @return type el token para concatener a la direccion base provista en la habilitacion.
	 */
	static function get_token_seguridad($password, $id_hab, $id_form, $cod_externo, $accion=null, $cod_recuperacion=null, $hash_validacion=null)
	{
		$s = "$id_hab|$id_form|$cod_externo";
		if ( isset($accion) ) {
			$s .= "|$accion";
		}
		if ( isset($cod_recuperacion) ) {
			$s .= "|$cod_recuperacion";
		}
		if ( isset($hash_validacion) ) {
			$s .= "|$hash_validacion";
		}
		$t = static::encriptar_kolla($s, $password);
		return urlencode($t);
	}

	static function decodificar_token_seguridad($token, $password)	{
		$key = $password;
		$t = $token;
		$s = static::desencriptar_kolla($t, $key);

		$datos = explode('|', $s);
		$res = array();
		if (\guarani_arreglos::count($datos) != 3) {//hago que no falle nunca
			$res['habilitacion'] = -1;
			$res['formulario'] = -1;
			$res['codigo_externo'] = '';
		} else {
			$res['habilitacion'] = $datos[0];
			$res['formulario'] = $datos[1];
			$res['codigo_externo'] = $datos[2];
		}
		return $res;
	}
	////////////////////PARA LAS RESPUESTAS///////////////////////

	static function get_token_callback_js($id_hab, $cui, $item, $password){
		$s = "$id_hab|$item|$cui";
		$t = static::encriptar_kolla($s, $password);
		return utf8_encode($t);

	}

	static function decodificar_token_callback_js($token, $password){
		$token = utf8_decode($token);
		$string = static::desencriptar_kolla($token, $password);
		$datos = explode('|', $string);
		$res = array();
		if (\guarani_arreglos::count($datos) != 3) {//hago que no falle nunca
			$res['habilitacion'] = -1;
			$res['formulario'] = -1;
			$res['codigo_externo'] = '';
		} else {
			$res['habilitacion'] = $datos[0];
			$res['formulario'] = $datos[1];
			$res['codigo_externo'] = $datos[2];
		}
		return $res;
	}

	/////////////////////// AUXILIARES //////////////////////

	/**
	 *
	 * @param string $punto_acceso por ejemplo: http:localhost/kolla/3.3/aplicacion.php
	 * @param int $habilitacion id_habilitacion
	 * @param string $token_seguridad los parametros hasheados
	 */
	static function gen_url($punto_acceso, $habilitacion, $token_seguridad){

		$url = $punto_acceso;
		$url .= '?'.self::param_op_toba;
		$url .= '&'.self::param_habilitacion.'='.$habilitacion;
		$url .= '&'.self::param_token.'='.$token_seguridad;
		return $url;
	}
	
    /**
     * Desencripta datos para Kolla.
	 * 
     * @see https://odan.github.io/2017/07/14/replace-mcrypt-encrypt-with-openssl-encrypt.html Replace mcrypt_encrypt with openssl_encrypt.
	 * 
     * @param string $data		Dato encriptado.
     * @param string $key		Clave de encriptación.
     * @param string $method	Método de encriptación .
     *
     * @return string Dato desencriptado.
     */
	public static function desencriptar_kolla($data, $key, $method = 'AES-256-CBC')
	{
		$data = base64_decode($data);
		$ivSize = openssl_cipher_iv_length($method);
		$iv = substr($data, 0, $ivSize);
		$data = openssl_decrypt(substr($data, $ivSize), $method, $key, OPENSSL_RAW_DATA, $iv);

		return $data;
	}
	
	/**
     * Encripta datos para Kolla.
	 * 
     * @see https://odan.github.io/2017/07/14/replace-mcrypt-encrypt-with-openssl-encrypt.html Replace mcrypt_encrypt with openssl_encrypt.
	 * 
     * @param string $data		Dato a encriptar.
     * @param string $key		Clave de encriptación.
     * @param string $method	Método de encriptación .
     *
     * @return string Dato encriptado.
     */
	public static function encriptar_kolla($data, $key, $method = 'AES-256-CBC')
	{
		$ivSize = openssl_cipher_iv_length($method);
		$iv = openssl_random_pseudo_bytes($ivSize);

		$encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);

		// For storage/transmission, we simply concatenate the IV and cipher text
		$encrypted = base64_encode($iv . $encrypted);

		return $encrypted;
	}
	
}