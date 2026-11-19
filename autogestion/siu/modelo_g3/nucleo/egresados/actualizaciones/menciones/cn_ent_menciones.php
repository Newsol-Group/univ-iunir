<?php
/**
 *	Representa una mencion
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_ent_menciones extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('menciones')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('menciones')->get();
	}
	
	function validar_datos($datos)
	{
		$datos['mencion'] = $this->tabla('menciones')->get_columna('mencion');
		$this->validar_nombre_mencion($datos['nombre'],$datos['mencion']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_mencion($nombre, $mencion)
	{
		if (! toba::consulta_php('co_menciones')->validar_nombre_mencion($nombre, $mencion)) {
			$this->set_error('dato_duplicado', array('nombre','mención'));
		}
	}

}
?>