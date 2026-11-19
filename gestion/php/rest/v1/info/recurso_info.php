<?php

namespace SIU\Guarani\php\rest\v1\info;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;

class recurso_info implements modelable
{
	
    public static function _get_modelos(): array
	{
		return [
			'info' => [
				'nombre'		=> ['type' => 'string'],
				'version'		=> ['type' => 'string'],
				'api_version'	=> ['type' => 'string'],
				'api_major'		=> ['type' => 'string'],
				'api_minor'		=> ['type' => 'string']
			]
		];
	}

	/**
	 * GET /info
	 * 
	 * @notes
	 * Devuelve información acerca de la API.<br>
	 *
	 * @summary Devuelve información acerca de la API
	 * 
	 * @responses 200 {$ref:info} OK
	 * @responses 500 Error de configuración del server
	 */
	public function get_list()
	{
		$version = rest::app()->config('version');
		$api = rest::app()->config('api_version');
		$nombre = rest::app()->config('api_titulo');
		$api_major = rest::app()->config('api_major');
		$api_minor = rest::app()->config('api_minor');

		if (is_null($version) || is_null($nombre) || is_null($api) || is_null($api_major) || is_null($api_minor)) {
			rest::response()->error_negocio(['La información solicitada no esta disponible'], 500);
		} else {
			$datos = ['nombre' => $nombre, 'version' => $version, 'api_version' => $api, 'api_major' => $api_major, 'api_minor' => $api_minor];
			rest::response()->get_list($datos);
		}
	}

}