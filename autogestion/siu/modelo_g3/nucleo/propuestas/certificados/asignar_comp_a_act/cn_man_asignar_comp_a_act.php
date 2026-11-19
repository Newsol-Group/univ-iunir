<?php
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage	Certificados
 * 
 */
class cn_man_asignar_comp_a_act extends cn_manejador_entidad
{
	protected $s__ev_info_datos;
	protected $s__plan_version;
	protected $s__certificado;
	protected $s__elemento;
	
	
	function get_indice_instancia()
	{
		return 'asignacion_comp_a_act';
	}
	
	function get_ev_info_datos()
	{
		return $this->s__ev_info_datos;
	}
	
	function get_selecciones()
	{
		$selecciones = array();
		$selecciones['plan_version'] = $this->s__plan_version;
		$selecciones['certificado'] = $this->s__certificado;
		$selecciones['elemento'] = $this->s__elemento;
		return $selecciones;
	}
	
	function deseleccionar_todo()
	{
		$this->deseleccionar_plan();
		$this->deseleccionar_certificado();
		$this->deseleccionar_actividad();
	}

	//-----------------------------------------------------------------------------------
	//---- Selección de Plan
	//-----------------------------------------------------------------------------------
	
	function get_listado_planes($filtro='')
	{
		if (!empty($filtro)) {
			$filtro .= ' AND ';
		}
		
		$filtro .= "v_sga_planes_estados.estado IN ('".planes::estado_nuevo."', '".planes::estado_activo."', '".planes::estado_vigente."')";		
		
		$datos = toba::consulta_php('co_planes')->get_lista_planes($filtro);
		
		foreach ($datos as $key => $valor) {
			if (isset($valor['plan_nombre'])) {
				$plan_nombre = $valor['plan_nombre'];
			}
			$datos[$key]['propuesta_datos'] = '('.$datos[$key]['propuesta_codigo'] .') - ' . $datos[$key]['propuesta_nombre']; 
			$datos[$key]['plan_datos'] = '('.$datos[$key]['plan_codigo'] .') - ' . $plan_nombre . ' - ' .$datos[$key]['plan_estado_descripcion'];
		}
		
		return $datos;
	}
	
	function seleccionar_plan($seleccion)
	{
		$this->s__plan_version = $seleccion['plan_version'];
		
		$this->s__ev_info_datos['propuesta_datos'] = $seleccion['propuesta_datos'];
		$this->s__ev_info_datos['plan_datos'] = $seleccion['plan_datos'];
		$this->s__ev_info_datos['version_nombre'] = $seleccion['version_nombre'];
	}
	
	function deseleccionar_plan()
	{
		unset($this->s__plan_version);
		
		unset($this->s__ev_info_datos['propuesta_datos']);
		unset($this->s__ev_info_datos['plan_datos']);
		unset($this->s__ev_info_datos['version_nombre']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Selección de Certificado
	//-----------------------------------------------------------------------------------
	
	function get_listado_certificados($filtro='')
	{
		return toba::consulta_php('co_certificados')->get_certificados_plan_version($this->s__plan_version, $filtro);
		
	}
	
	function seleccionar_certificado($seleccion)
	{
		$this->s__certificado = $seleccion['certificado'];
		
		$this->s__ev_info_datos['certificado_nombre'] = $seleccion['nombre'];
	}
	
	function deseleccionar_certificado()
	{
		unset($this->s__certificado);
		
		unset($this->s__ev_info_datos['certificado_nombre']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Selección de Actividad
	//-----------------------------------------------------------------------------------
	
	function get_listado_actividades($filtro='')
	{
		return toba::consulta_php('co_certificados')->get_actividades_certificado_plan_version($this->s__certificado, $this->s__plan_version, $filtro);
	}
	
	function seleccionar_actividad($seleccion)
	{
		$this->s__elemento = $seleccion['elemento'];
		
		$this->s__ev_info_datos['elemento_nombre'] = $seleccion['nombre'];
		
		// Carga del CN
		$this->cargar($this->get_selecciones());
	}
	
	function deseleccionar_actividad()
	{
		unset($this->s__elemento);
		
		unset($this->s__ev_info_datos['elemento_nombre']);
	}

}
?>