<?php
/**
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_ent_tribunal_examen extends cn_ent_elemento
{
	function cargar($id)
	{
		$this->tabla('tribunal_actividades')->persistidor()->activar_perfil_de_datos();
		$this->tabla('tribunal_docentes')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	//---- Datos Principales -------------------------------------
	
	function get_datos_tribunal_examen()
	{
		return $this->tabla('tribunal_examen')->get();
	}
	
	function set_datos_tribunal_examen($datos)
	{
		$this->validar_datos($datos);
		$this->tabla('tribunal_examen')->set($datos);
	}
	
	//---- Datos Docentes ---------------------------------------
	
	function get_datos_docentes_asignados()
	{
		return $this->tabla('tribunal_docentes')->get_filas();
	}
	
	function agregar_fila_docente($fila)
	{
		$this->tabla('tribunal_docentes')->nueva_fila($fila);
	}
	
	function eliminar_fila_docente_asignado($seleccion)
	{
		$this->tabla('tribunal_docentes')->eliminar_fila($seleccion);
	}
	
	function set_datos_docentes_asignados($datos)
	{
		$this->tabla('tribunal_docentes')->procesar_filas($datos);
	}
	
	//---- Datos Actividades ------------------------------------
	
	function get_datos_actividades_asignadas()
	{
		return $this->tabla('tribunal_actividades')->get_filas();
	}
	
	function agregar_fila_actividad($fila)
	{
		$this->tabla('tribunal_actividades')->nueva_fila($fila);
	}
	
	function eliminar_fila_actividad_asignada($seleccion)
	{
		$this->tabla('tribunal_actividades')->eliminar_fila($seleccion);
	}
	
	function set_datos_actividades_asignadas($datos)
	{
		$this->tabla('tribunal_actividades')->procesar_filas($datos);
	}	
	
	//---- Validaciones -----------------------------------------
	
	function validar_datos($datos)
	{
		$tribunal = $this->tabla('tribunal_examen')->get_columna('tribunal');		
		$this->validar_nombre_tribunal($datos['nombre'], $tribunal);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}	

	function validar_nombre_tribunal($nombre, $tribunal)
	{
		if (! toba::consulta_php('co_tribunales')->validar_nombre_tribunal($nombre, $tribunal)) {
			$this->set_error('dato_duplicado', array('nombre', 'Tribunal'));
		}
	}

}
?>