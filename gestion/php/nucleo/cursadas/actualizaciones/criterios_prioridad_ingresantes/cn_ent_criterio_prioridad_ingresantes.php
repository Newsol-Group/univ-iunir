<?php
/**
 * Representa un Criterio de Prioridad de Ingresantes
 *
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_ent_criterio_prioridad_ingresantes extends cn_entidad
{
	function cargar_criterio_prioridad_ingresantes()
	{
		$this->dep('datos')->cargar();
		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	/**
	 * Se redefine la función sincronizar del cn_entidad ya que este DR tiene una único DT.
	 * No puede realizarse la sincronización de la relación por no tener un único registro en la tabla cabecera.
	 *
	 */
	function sincronizar()
	{
		$this->tabla('criterios_prioridad_ing')->sincronizar();
	}

	function resetear()
	{
		parent::resetear();
		$this->cargar_criterio_prioridad_ingresantes();
	}

	function get_criterios($activo=true)
	{
		$condicion = $activo ? array('activo' => 1) : array('activo' => 0);
		$criterios = $this->tabla('criterios_prioridad_ing')->get_filas($condicion);
		$orden = $activo ? 'orden' : 'nombre';
		return rs_ordenar_por_columna($criterios, $orden);
	}

	function procesar_criterios($datos)
	{
		$this->tabla('criterios_prioridad_ing')->procesar_filas($datos);
	}

	function eliminar_criterio($id_fila)
	{
		$this->tabla('criterios_prioridad_ing')->modificar_fila($id_fila, array('activo' => 0, 'orden' => null));
	}

	function agregar_criterio($criterio)
	{
		$id_fila = current($this->tabla('criterios_prioridad_ing')->get_id_fila_condicion($criterio));
		$this->tabla('criterios_prioridad_ing')->modificar_fila($id_fila, array('activo' => 1, 'orden' => $this->get_proximo_orden()));
	}

	function get_proximo_orden()
	{
		return guarani_arreglos::count($this->get_criterios(true)) + 1;
	}
}
?>