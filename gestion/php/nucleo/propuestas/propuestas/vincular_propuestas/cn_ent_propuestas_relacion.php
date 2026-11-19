<?php
/**
 * 
 * @package Propuestas Formativas
 */
class cn_ent_propuestas_relacion extends cn_entidad
{
	function cargar($id)
	{
		$where = "relacion.propuesta = {$id['propuesta']}";
		$where .= isset($id['plan']) ? " AND relacion.plan = {$id['plan']}" : ' AND relacion.plan IS NULL';
		$where .= " AND relacion.anio_academico = {$id['anio_academico']}";
		$this->dep('datos')->persistidor()->cargar_con_wheres(array('relacion' => $where));
		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	//------------------------------------------------------------------------
	//-- Relación
	//------------------------------------------------------------------------

	function set_datos_relacion($datos)
	{
		$this->tabla('relacion')->set($datos);
	}

	function get_columna_relacion($columna)
	{
		return $this->tabla('relacion')->get_columna($columna);
	}

	function eliminar_relacion()
	{
		$this->tabla('relacion')->eliminar_todo();
	}

	//------------------------------------------------------------------------
	//-- Grupos
	//------------------------------------------------------------------------

	function set_datos_grupo($datos)
	{
		if ($this->hay_grupo_seleccionado()) {
			$this->tabla('grupos')->set($datos);
		}
	}

	function get_id_grupo($condiciones)
	{
		return current($this->tabla('grupos')->get_id_fila_condicion($condiciones));
	}

	function seleccionar_grupo($id_fila_grupo)
	{
		$this->tabla('grupos')->set_cursor($id_fila_grupo);
	}

	function get_datos_grupo_seleccionado()
	{
		if ($this->hay_grupo_seleccionado()) {
			return $this->tabla('grupos')->get();
		}
	}

	function hay_grupo_seleccionado()
	{
		return $this->tabla('grupos')->hay_cursor();
	}

	function eliminar_grupo_seleccionado()
	{
		if ($this->hay_grupo_seleccionado()) {
			$id_fila = $this->tabla('grupos')->get_cursor();
			$this->tabla('grupos')->eliminar_fila($id_fila);
		}
	}

	function nuevo_grupo($datos)
	{
		$id = $this->tabla('grupos')->nueva_fila($datos);
		$this->seleccionar_grupo($id);
	}

	//------------------------------------------------------------------------
	//-- Planes
	//------------------------------------------------------------------------

	function get_datos_planes()
	{
		return $this->tabla('planes')->get_filas();
	}

	function get_id_plan($condiciones)
	{
		return current($this->tabla('planes')->get_id_fila_condicion($condiciones, false));
	}

	function agregar_plan($datos_plan)
	{
		$this->tabla('planes')->nueva_fila($datos_plan);
	}

	function eliminar_plan($id_fila_plan)
	{
		$this->tabla('planes')->eliminar_fila($id_fila_plan);
	}
}
?>