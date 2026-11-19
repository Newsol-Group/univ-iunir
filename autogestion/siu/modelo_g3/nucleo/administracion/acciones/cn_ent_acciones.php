<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Acciones
 */
class cn_ent_acciones extends cn_entidad
{
	
	function set_datos_acciones($datos)
	{
		$this->validar_datos_acciones($datos);
		$this->tabla('acciones')->set($datos);
	}
	
	function get_datos_acciones()
	{
		return $this->tabla('acciones')->get();
	}
	
	function eliminar()
	{
		$this->dep('datos')->eliminar();
	}
	
	//-------------------------------------------------------------------
	
	function get_datos_operaciones()
	{
		return $this->tabla('acc_op')->get_filas();
	}
	
	function agregar_operaciones($datos)
	{
		$this->tabla('acc_op')->nueva_fila($datos);
	}
	
	function eliminar_operacion($id_fila)
	{
		$this->tabla('acc_op')->eliminar_fila($id_fila);	
	}
	
	//-------------------------------------------------------------------
	
	function get_datos_requisitos()
	{
		return $this->tabla('req_val')->get_filas();
	}
	
	function agregar_requisitos($datos)
	{
		$this->tabla('req_val')->nueva_fila($datos);
	}
	
	function eliminar_requisito($id_fila)
	{
		$this->tabla('req_val')->eliminar_fila($id_fila);	
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos_acciones($datos)
	{
		$nombre = $datos['nombre'];
		$accion = $this->tabla('acciones')->get_columna('accion');
		if (!toba::consulta_php('co_acciones')->validar_nombre_accion($nombre, $accion)) {
			$this->set_error('dato_existente', array('nombre','acción'));
		}
	}
	
	function pre_sincronizar() 
	{
		//Si existen errores se los dispara antes de sincronizar
		if ($this->hay_errores()) {
			throw guarani::error($this->get_mensaje_error());
		}
	}	

}

?>