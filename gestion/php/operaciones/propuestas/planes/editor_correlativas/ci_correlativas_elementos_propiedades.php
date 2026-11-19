<?php 

class ci_correlativas_elementos_propiedades extends guarani_pers_ci
{
	protected $s__id_nodo;
	protected $s__datos_nodo;
	
	function set_edicion_nodo($id_nodo)
	{
		$this->s__id_nodo = $id_nodo;
		$this->s__datos_nodo = $this->elemento($id_nodo);
		if (isset($this->s__datos_nodo['regla'])) {
			$datos_regla = guarani::co('co_reglas')->get_info_regla($this->s__datos_nodo['regla']);
			$this->s__datos_nodo['regla_tipo'] = $datos_regla['regla_tipo'];
		} else {
			$this->s__datos_nodo['regla_tipo'] = regla::tipo_cumplimiento_modulos;
		}
		$this->dep('regla')->resetear();
		$this->dep('regla')->set_regla_solo_lectura(false);
		$this->dep('regla')->set_regla_tipo($this->s__datos_nodo['regla_tipo']);
		$this->dep('regla')->reducir_cabecera();
		if ($this->es_condicion_requisito()) {
			$this->dep('regla')->set_regla_solo_lectura();
		}
		$this->conf__form_regla();
	}

	function get_edicion_nodo()
	{
		return $this->s__id_nodo;
	}

	function get_dato_nodo($dato)
	{
		return $this->s__datos_nodo[$dato];
	}

	function es_condicion_requisito() {
		return ($this->s__datos_nodo['tipo'] == condicion::requisito_requisito);
	}

	//--------------------------------------------------------------------
	//	Modificacion de las propiedades de los nodos
	//--------------------------------------------------------------------
	
	function conf() 
	{
		if (guarani::get_id_operacion() != planes::cod_op_asignar_correlativas && isset($this->s__datos_nodo['entidad'])) {
			$subtipo = toba::consulta_php('co_g3entidades')->get_subtipo_entidad($this->s__datos_nodo['entidad']);
			if ($subtipo == elemento::subtipo_materia_generica) {
				$this->dep('regla')->set_solo_lectura();
			}
		}
		if (!$this->cn()->entidad()->es_plan_version_editable()) {
			$this->dep('regla')->dep('formulario')->set_solo_lectura();
			$this->dep('regla')->dep('formulario')->eliminar_evento('modificacion');
		}
	}

	//--  MODULOS Y MATERIAS GENÉRICAS -----------------------------------

	function conf__form_regla()
	{
		$this->dep('regla')->set_etiqueta_regla('Cond. de cumplimiento:');
    	$regla = $this->get_datos_regla();
    	$this->dep('regla')->set_regla($regla['regla'],$regla['parametros']);
	}
	
	function get_datos_regla()
	{
		$datos_nodo = $this->elemento($this->s__id_nodo);
		return (array('regla' => $datos_nodo['regla'], 'parametros' => $datos_nodo['parametros']));
	}

	function evt__form_regla__modificacion($datos)
	{
		if (!isset($datos['parametros'])) {
			$datos['parametros'] = null;
		}
		$this->cn()->entidad()->modificar_requisito($this->s__id_nodo, $datos);
	}

	//--------------------------------------------------------------------
		
	function elemento($nodo)
	{
		return $this->cn()->entidad()->get_requisito($nodo);
	}
}
?>