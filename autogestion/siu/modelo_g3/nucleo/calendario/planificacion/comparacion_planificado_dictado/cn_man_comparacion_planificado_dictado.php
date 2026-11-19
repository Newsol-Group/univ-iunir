<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class cn_man_comparacion_planificado_dictado extends cn_manejador_entidad
{
	protected $s__ev_info_datos;
	protected $s__ev_info_datos_clase;
	
	protected $s__comision_seleccionada;
	protected $s__subcomision_seleccionada;
	protected $s__clase_seleccionada;
	
	protected $s__subcomisiones;	
	
		
	function get_ev_info_datos()
	{
		return $this->s__ev_info_datos;
	}
	
	function get_ev_info_datos_clase()
	{
		return $this->s__ev_info_datos_clase;
	}
	
	function resetear()
	{
		unset($this->s__ev_info_datos);
		unset($this->s__ev_info_datos_clase);
		
		unset($this->s__comision_seleccionada);
		unset($this->s__subcomision_seleccionada);
		unset($this->s__clase_seleccionada);
		
		unset($this->s__subcomisiones);
		
		parent::resetear();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Selección de Comisión
	//-----------------------------------------------------------------------------------
	
	function get_listado_comisiones($filtro='')
	{
		return toba::consulta_php('co_comisiones')->get_listado($filtro);
	}
	
	function seleccionar_comision($seleccion)
	{
		$this->s__comision_seleccionada = $seleccion['comision'];
		$this->s__subcomisiones = toba::consulta_php('co_comisiones')->get_subcomisiones_comision($this->s__comision_seleccionada);
		
		$where = 'sga_comisiones.comision = '.toba::db()->quote($this->s__comision_seleccionada);
		$this->s__ev_info_datos = current(toba::consulta_php('co_comisiones')->get_listado($where));
	}
	
	function deseleccionar_comision()
	{
		unset($this->s__comision_seleccionada);
		unset($this->s__subcomisiones);
		unset($this->s__ev_info_datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Selección de Subcomisión
	//-----------------------------------------------------------------------------------
	
	function existen_subcomisiones()
	{
		return (!empty($this->s__subcomisiones));
	}
	
	function get_subcomisiones()
	{
		return $this->s__subcomisiones;
	}
	
	function seleccionar_subcomision($seleccion)
	{
		$this->s__subcomision_seleccionada = $seleccion['subcomision'];		
		
		$where = 'sga_subcomisiones.subcomision = '.toba::db()->quote($this->s__subcomision_seleccionada);
		$datos = current(toba::consulta_php('co_comisiones')->get_subcomisiones($where));
		
		$this->s__ev_info_datos['subcomision_nombre'] = $datos['subcomision_nombre'];
		$this->s__ev_info_datos['tipo_clase_nombre'] = $datos['tipo_clase_nombre'];
	}
	
	function deseleccionar_subcomision()
	{
		unset($this->s__subcomision_seleccionada);
		
		unset($this->s__ev_info_datos['subcomision_nombre']);
		unset($this->s__ev_info_datos['tipo_clase_nombre']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Selección de Clases
	//-----------------------------------------------------------------------------------
	
	function get_clases()
	{
		if (isset($this->s__subcomision_seleccionada)) {
			$datos = toba::consulta_php('co_clases')->get_clases_subcomision($this->s__subcomision_seleccionada);
		} else {
			$datos = toba::consulta_php('co_clases')->get_clases_comision($this->s__comision_seleccionada);
		}
		
		$max_chars = 50;
		
		foreach ($datos as $key => $dato) {
			if (!empty($dato['tema_planificado'])) {
				//Vamos a utilizar dos componentes del arreglo, para mantener el valor actual de la base sin cortarlo
				//tema_planificado_bk tendra el valor real, mientras tema_planificado se cortara al valor de max_chars
				$datos[$key]['tema_planificado_bk'] = $datos[$key]['tema_planificado'];
				if (strlen($dato['tema_planificado']) > $max_chars) {
					$datos[$key]['tema_planificado'] = substr($dato['tema_planificado'], 0, $max_chars).'...';
				}
			}
		}
		
		foreach ($datos as $key => $dato) {
			if (!empty($dato['tema_dictado'])) {
				//tema_dictado_bk_bk tendra el valor real, mientras tema_dictado_bk se cortara al valor de max_chars
				$datos[$key]['tema_dictado_bk'] = $datos[$key]['tema_dictado'];
				if (strlen($dato['tema_dictado']) > $max_chars) {
					$datos[$key]['tema_dictado'] = substr($dato['tema_dictado'], 0, $max_chars).'...';
				}
			}
		}
		return $datos;
	}
	
	function seleccionar_clase($seleccion)
	{
		$this->s__ev_info_datos_clase['fecha'] = $seleccion['fecha'].' ('.$seleccion['dia_semana_nombre'].')';
		$this->s__ev_info_datos_clase['hora_inicio'] = $seleccion['hora_inicio'];
		$this->s__ev_info_datos_clase['hora_finalizacion'] = $seleccion['hora_finalizacion'];
		
		$this->s__clase_seleccionada = $seleccion['clase'];
	}
	
	function deseleccionar_clase()
	{
		unset($this->s__ev_info_datos_clase);
		unset($this->s__clase_seleccionada);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Datos de la Clase
	//-----------------------------------------------------------------------------------

	function get_datos_clase()
	{
		return toba::consulta_php('co_clases')->get_datos_clase($this->s__clase_seleccionada);
	}
	
	function get_docentes_clase()
	{		
		$datos = toba::consulta_php('co_clases')->get_docentes_clase($this->s__clase_seleccionada);
		foreach ($datos as $key => $dato) {
			if ($dato['responsable']) {
				$datos[$key]['nombre_persona'] .= ' (Responsable de la clase)';
				break;
			}
		}
		return $datos;
	}
	
}
?>