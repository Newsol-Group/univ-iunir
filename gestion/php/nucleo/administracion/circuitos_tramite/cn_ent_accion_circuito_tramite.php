<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Circuitos
 * 
 */
class cn_ent_accion_circuito_tramite extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('accion')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('accion')->get();
	}

	function validar_datos($datos)
	{
		$datos['accion'] = $this->tabla('accion')->get_columna('accion');
		$this->validar_nombre_accion($datos['nombre'], $datos['accion']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_nombre_accion($nombre, $accion)
	{
		if (! toba::consulta_php('co_circuitos_tramite')->validar_nombre_accion($nombre, $accion)) {
			$this->set_error('dato_duplicado', array('nombre', 'Acción del Circuito de Egreso'));
		}
	}
}
?>