<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Circuitos
 * 
 */
class cn_ent_estado_circuito_tramite extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('estados')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('estados')->get();
	}

	function validar_datos($datos)
	{
		$datos['estado'] = $this->tabla('estados')->get_columna('estado');
		$this->validar_nombre_estado($datos['nombre'], $datos['estado']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_nombre_estado($nombre, $estado)
	{
		if (! toba::consulta_php('co_circuitos_tramite')->validar_nombre_estado($nombre, $estado)) {
			$this->set_error('dato_duplicado', array('nombre', 'Estado de Circuito de Egreso'));
		}
	}
}
?>