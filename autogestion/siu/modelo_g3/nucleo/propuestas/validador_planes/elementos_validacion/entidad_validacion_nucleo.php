<?php
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class entidad_validacion_nucleo
{
	protected $id;
	protected $nombre;
	protected $props = array();
	protected $observaciones = array();

	function __construct($id, $nombre)
	{
		$this->id = $id;
		$this->nombre = $nombre;
	}

	function set_props($props)
	{
		$this->props = $props;
	}

	function set_observaciones($texto)
	{
		$this->observaciones[] = $texto;
	}

	function get_prop($aprob_prop)
	{
		return isset($this->props[$aprob_prop]) ? $this->props[$aprob_prop] : null;
	}

	function get_nombre()
	{
		return $this->nombre;
	}

	function get_id()
	{
		return $this->id;
	}

	function get_texto_observaciones()
	{
		return implode(', ',$this->observaciones);
	}
}
?>