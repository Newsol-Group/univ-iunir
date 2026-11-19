<?php
/**
 *	Representa un Rol del Tribunal
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_ent_tribunal_rol extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tribunal_roles')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tribunal_roles')->get();
	}
	
	function validar_datos($datos)
	{
		$datos['rol'] = $this->tabla('tribunal_roles')->get_columna('rol');
		$this->validar_nombre_tribunal_rol($datos['nombre'], $datos['rol']);
		$this->validar_orden_en_acta($datos['orden_acta'], $datos['rol']);
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_tribunal_rol($nombre, $tribunal_rol)
	{
		if (!toba::consulta_php('co_tribunal_roles')->validar_nombre_tribunal_rol($nombre, $tribunal_rol)) {
			$this->set_error('dato_duplicado', array('nombre','rol de docentes en tribunales'));
		}
	}

	function validar_orden_en_acta($orden_acta, $tribunal_rol)
	{
		if (!is_null($orden_acta)) {
			$this->validar_rango_orden($orden_acta);
			$this->validar_existencia_en_rol($orden_acta, $tribunal_rol);
		}
	}
	
	function validar_rango_orden($orden_acta)
	{
		if (($orden_acta < 0) || ($orden_acta > 100)) {
			$this->set_error('800EXA_Error_Orden_Valor');
		}
	}
	
	function validar_existencia_en_rol($orden_acta, $tribunal_rol)
	{
		$where = $tribunal_rol ? "sga_tribunal_roles.rol <> $tribunal_rol" : '';
		$roles = toba::consulta_php('co_tribunal_roles')->get_listado($where);
		
		if (in_array($orden_acta, aplanar_matriz($roles, 'orden_acta'))) {
			$this->set_error('800EXA_Error_Orden_Repetido');
		}
	}
	
}
?>