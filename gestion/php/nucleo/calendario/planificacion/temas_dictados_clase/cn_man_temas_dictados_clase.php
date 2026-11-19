<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class cn_man_temas_dictados_clase extends cn_manejador_entidad
{
	protected $s__ev_info_datos;
	protected $s__ev_info_datos_clase;
	
	protected $s__comision_seleccionada;
	protected $s__subcomision_seleccionada;
	
	protected $s__banda_horaria;
	
	protected $s__subcomisiones;	
	
	function get_indice_instancia()
	{
		return 'temas_dictados_clase';
	}
	
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
	
	function get_banda_horaria()
	{
		return $this->s__banda_horaria;
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
		
		$max_chars = 100;
		foreach ($datos as $key => $dato) {
			if (!empty($dato['tema_dictado'])) {
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
		
		$this->s__banda_horaria = $seleccion['banda_horaria'];
		
		$clase_seleccionada = array('clase' => $seleccion['clase']);
		$this->cargar($clase_seleccionada);
	}
	
	function deseleccionar_clase()
	{
		unset($this->s__banda_horaria);
		unset($this->s__ev_info_datos_clase);
		parent::resetear();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Asignación de docentes
	//-----------------------------------------------------------------------------------

	function get_docentes($docentes, $es_not=false)
	{
		$where = '';
		
		if (isset($this->s__subcomision_seleccionada)) {
			// Se deben recuperar los docentes asociados a la subcomisión
			if (!empty($docentes)) {
				$where = guarani_sql::armar_condicion_in($docentes, 'sga_docentes_subcomision', 'docente', 'docente', $es_not);
			}
			$datos = toba::consulta_php('co_comisiones')->get_docentes_subcomision($this->s__subcomision_seleccionada, $where);
		} else {
			// Se recuperan directamente los docentes asociados a la comisión
			if (!empty($docentes)) {
				$where = guarani_sql::armar_condicion_in($docentes, 'sga_docentes_comision', 'docente', 'docente', $es_not);
			}
			$datos = toba::consulta_php('co_comisiones')->get_docentes_comision($this->s__comision_seleccionada, $where);
		}
		
		return $datos;
	}
	
	function get_docente($docente)
	{
		$docente = toba::db()->quote($docente);
		$where = '';
		
		if (isset($this->s__subcomision_seleccionada)) {
			// Se deben recuperar los datos del docente de la subcomisión
			$where = "sga_docentes_subcomision.docente = $docente";
			$datos = toba::consulta_php('co_comisiones')->get_docentes_subcomision($this->s__subcomision_seleccionada, $where);
		} else {
			// Se deben recuperar los datos del docente de la comisión
			$where = "sga_docentes_comision.docente = $docente";
			$datos = toba::consulta_php('co_comisiones')->get_docentes_comision($this->s__comision_seleccionada, $where);
		}
		
		return current($datos);
	}
	
}
?>