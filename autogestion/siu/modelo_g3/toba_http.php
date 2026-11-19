<?php

namespace siu\modelo_g3;

/**
 * Maneja HEADERS de HTTP.
 * Hay que llamarla antes de devolver cualquier tipo de contenido o llamar a session_start
 *
 * @package SalidaGrafica
 */
class toba_http
{
    	static function headers_download($tipo_salida, $archivo, $longitud, $tipo_descarga = 'attachment')
	{
		header("Cache-Control: private");
		header("Content-type: $tipo_salida");
		header("Content-Length: $longitud");
		header("Content-Disposition: $tipo_descarga; filename=$archivo");
		header("Pragma: no-cache");
		header("Expires: 0");

	} 
}