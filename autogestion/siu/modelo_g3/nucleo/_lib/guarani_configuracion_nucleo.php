<?php
/**
 * Catálogo de parametros de configuración del sistema
 *
 * @package Núcleo
 * @subpackage Configuración
 */
class guarani_configuracion_nucleo {
	
	static private $instancia;
	private $parametros;
	private $co;

	private function __construct()
	{
		$this->parametros = [];
		$this->co = toba::consulta_php('co_configuracion');
	}
	
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new guarani_configuracion();	
		}
		return self::$instancia;	
	}	
	
	/**
	  Obtiene el valor de un parámetro dado
	 */
	function get_valor_config($parametro)
	{
		$parametro = strtoupper($parametro);
		if ($this->esta_cargado_valor($parametro)) return $this->get_valor_cargado($parametro);
		$valor = $this->co->get_valor_config($parametro);
		if (!empty($valor)) {
			$this->parametros[$parametro] = $valor;
		}
		return $valor;
	}
	
	/**
	  Determina si ya está cargado en memoria el valor de un parámetro dado
	 */
	private function esta_cargado_valor($parametro)
	{
		return isset($this->parametros[$parametro]);
	}
	
	/**
	  Obtiene el valor de un parámetro dado que ya está cargado en memoria
	 */
	private function get_valor_cargado($parametro)
	{
		return $this->parametros[$parametro];
	}
	
}