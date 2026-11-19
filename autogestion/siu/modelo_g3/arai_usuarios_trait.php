<?php

namespace siu\modelo_g3;

/**
 * Trait personalizada para consumir los WS de Araí Usuarios
 * https://www.php.net/manual/en/language.oop5.traits.php
 * @package Araí
 */
trait arai_usuarios_trait
{
	/**
	 * @return GuzzleHttp\Client El cliente de Guzzle.
	 */
	public function getClienteRest()
	{
		if (is_null($this->cliente_arai_usuarios)) {
			$cliente = \toba::servicio_web_rest('arai_usuarios');
			$this->cliente_arai_usuarios = $cliente;
		}
		return $this->cliente_arai_usuarios;
	}

}