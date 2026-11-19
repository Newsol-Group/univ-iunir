<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Divisiones
 * 
 */
class cn_ent_division extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('division')->set($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('division')->get();
	}

	//--------------------------------------------------------------------------

	function validar_datos($datos)
	{
		$id = $this->tabla('division')->get_columna('division');

		$this->validar_nombre($datos['nombre'], $id);
		$this->validar_codigo($datos['codigo'], $id);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_nombre($nombre, $id)
	{
		if (!toba::consulta_php('co_divisiones')->validar_nombre($nombre, $id)) {
			$this->set_error('dato_existente', array('nombre', 'División'));
		}
	}

	function validar_codigo($codigo, $id)
	{
		if (!toba::consulta_php('co_divisiones')->validar_codigo($codigo, $id)) {
			$this->set_error('dato_existente', array('código', 'División'));
		}
	}

}
?>