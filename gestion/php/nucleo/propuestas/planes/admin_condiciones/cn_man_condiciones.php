<?php 
/**
 * Representa las información de Condiciones en un Plan de Estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_man_condiciones extends cn_man_zona_planes
{
	protected $s__plan_version;
	protected $s__plan_version_editable;
	protected $s__contenido_plan;
	protected $s__datos_plan;

	function cargar($params, $usar_elementos=true)
	{
		$estado_anterior = isset($this->s__plan_version) ? $this->s__plan_version : null;
		if (! is_array($params)) {
			$this->s__plan_version = $params;
		} else {
			$this->s__plan_version = $params['plan_version'];
		}
		if ($usar_elementos) {
			$this->s__contenido_plan = toba::consulta_php('co_planes')->get_contenido_plan_version_ids( $this->s__plan_version );
		}
		//TODO VER SI ES NECESARIO ESTO ESTA EN LA ZONA
		if (! $estado_anterior || $estado_anterior != $this->s__plan_version) {
			$this->s__plan_version_editable = planes::es_editable($this->s__plan_version);
		}
		if (is_array($params) && array_key_exists('entidad',$params)) {
			parent::cargar($params);
		}
	}
	
	function get_id_plan_version()
	{
		return $this->s__plan_version;
	}
	
	function resetear()
	{
		unset($this->s__plan_version);
		unset($this->s__contenido_plan);
		$this->resetear_entidades();
	}

	function resetear_entidades()
	{
		parent::resetear();
	}
	
	//-------------------------------------------------------------------
	//--- Manejo de elementos
	//-------------------------------------------------------------------
	
	function seleccionar_elemento($datos)
	{
		$entidad = $datos['entidad'];
		$elemento = $datos['elemento'];
		toba::logger()->info("Se seleccionó el elemento: $elemento");
		if (!$this->existe_elemento($elemento)){
			throw new guarani_error("Seleccionar Elemento, la actividad no pertenece al plan version ");
		}
		$this->cargar(array('plan_version' => $this->s__plan_version, 'plan_version_editable' => $this->s__plan_version_editable, 'entidad' => $entidad));
	}
	
	function existe_elemento($elemento)
	{
		$datos = $this->s__contenido_plan;
		$existe = false;
		foreach ($datos as $dato) {
			if ($dato['elemento'] == $elemento && $dato['entidad_tipo'] == elemento::tipo_actividad) {
				$existe = true;
				break;
			}
		}
		return $existe;
	}
	
	
	function resetear_elemento()
	{
		parent::resetear();
	}

	function get_correlativas_plan_version($filtro='')
	{
		$elementos = toba::consulta_php('co_correlativas')->get_correlativas_plan_version($this->s__plan_version, $filtro);
		return $elementos;
	}
	
	
	
	//-------------------------------------------------------------------
	//-------------------------------------------------------------------

	/*
	function get_lista_actividades()
	{
		$actividades = array();
		foreach($this->s__contenido_plan as $e ) {
			if($e['entidad_tipo'] == elemento::tipo_actividad) {
				$actividades[] = $e['elemento'];
			}	
		}
		return $actividades;
	}
	*/
	
	//-----------------------------------------------
	//-- Interface con el CI
	//-----------------------------------------------

	/**
	*	1: Lista de requisitos de tipo materia que se pueden asociar al elemento
		ATENCION: no se debe listar la materia que se esta editando!!
	*/
	function get_correlativas_materias($filtro='')
	{
		$datos = toba::consulta_php('co_correlativas')->get_correlativas_actividades_plan_version($this->s__plan_version,$filtro);
        $elementos = Array();
		foreach ($datos as $key => $dato){
			if (($dato['entidad_tipo'] == elemento::tipo_actividad) || (($dato['entidad_tipo'] == elemento::tipo_modulo) && ($dato['entidad_subtipo'] == elemento::subtipo_materia_generica) )){
				$elementos[] = $dato;
			}
		}
		return $elementos;
	}

	/**
	*	Lista de requisitos de tipo algoritmo
		Una lista de los requisitos que no sean de tipo elemento.
	*/
	function get_requisitos_algoritmo($filtro='')
	{
		$datos = toba::consulta_php('co_restricciones')->get_requisitos_especiales_plan_version($this->s__plan_version,$filtro);
		return $datos;
	}
	
	

	//-------------------------------------------------------------------
	//--- Consultas
	//-------------------------------------------------------------------
		
	//** determina si el plan version seleccionado se puede editar

	function es_editable() 
	{
		return $this->s__plan_version_editable;
	}
	
	
	
}

?>