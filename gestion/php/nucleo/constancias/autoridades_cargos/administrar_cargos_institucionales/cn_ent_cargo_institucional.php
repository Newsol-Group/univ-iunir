<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Cargos Institucionales
 * 
 */
class cn_ent_cargo_institucional extends cn_entidad
{
	function get_datos($tabla=null)
	{
		return $this->tabla('cargos')->get();
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('cargos')->set($datos);
	}
	
	function validar_datos($datos)
	{
		$datos['cargo'] = $this->tabla('cargos')->get_columna('cargo');
		$this->validar_nombre_cargo($datos['nombre'], $datos['cargo']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_nombre_cargo($nombre, $cargo) 
	{
		if (! toba::consulta_php('co_autoridades_cargos')->validar_nombre_cargo($nombre, $cargo)) {
			$this->set_error('dato_existente', array('nombre', 'Cargo Institucional'));
		}
	}
}
?>