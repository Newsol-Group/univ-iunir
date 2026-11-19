<?php
/**
 *	Representa un Grupo de Propuestas
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class cn_ent_grupo_de_propuestas extends cn_entidad
{
	function cargar($id)
	{
		$this->tabla('propuestas_x_grupo')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos_grupo($datos);
		$this->tabla('propuestas_grupo')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('propuestas_grupo')->get();
	}
	
	function set_datos_prop_asoc($datos)
	{
		$this->tabla('propuestas_x_grupo')->nueva_fila($datos);
	}
	
	function get_datos_prop_asoc($clave = null)
	{
		if (is_null($clave)) {
			$datos = $this->tabla('propuestas_x_grupo')->get_filas();
		} else {
			$datos = $this->tabla('propuestas_x_grupo')->get_fila($clave);			
		}
		return $datos;
	}
	
	function eliminar_datos_prop_asoc($datos)
	{
		return $this->tabla('propuestas_x_grupo')->eliminar_fila($datos);
	}
	
	function validar_datos_grupo($datos)
	{
		$grupo = $this->tabla('propuestas_grupo')->get_columna('grupo_propuesta');
		$this->validar_nombre($datos['nombre'], $grupo);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre($nombre, $id_grupo=null)
	{
		if (!toba::consulta_php('co_propuestas')->validar_nombre_grupo_propuestas($nombre, $id_grupo)) {
			$this->set_error('dato_existente', array('nombre','grupo'));
		}
	}
}
?>