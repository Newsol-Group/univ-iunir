<?php 
/**
 * Representa las información de Correlativas en un Plan de Estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_man_correlativas extends cn_man_condiciones
{
	function get_indice_instancia()
	{
		return 'correlativas';
	}

	function eliminar_correlativas($entidad)
	{
		$this->cargar(array('plan_version' => $this->s__plan_version,
							'plan_version_editable' => true,
							'entidad' => $entidad),false);
		$this->entidad()->eliminar_condiciones();
		$this->guardar();
		$this->resetear_entidades();
	}

	/**
	 * Retorna los datos de los elementos que tienen como correlativa a $elemento
	 * @param <type> $elemento
	 * @return <type>
	 */
	function tienen_como_correlativa_a($elemento)
	{
		return toba::consulta_php('co_correlativas')->get_elementos_tienen_correlativa_a($elemento, $this->s__plan_version);
	}
	
	function copiar_correlativas($elemento_origen, $elemento_destino)
	{
		return guarani::act('act_correlativas')->copiar_correlativas($elemento_origen, $this->s__plan_version, $elemento_destino, $this->s__plan_version);
	}
	
	//-------------------------------------------------------------------
	//-------------------------------------------------------------------
	//-------------------------------------------------------------------
		
	/**
	 * Determina si la Actividad dada es una Optativa de Genérica en el Plan Versión seleccionado, sin Mesas ni 
	 * Comisiones creadas para dicho Plan.
	 * @param integer $entidad entidad asociada al elemento para el cual se quieren verificar las condiciones.
	 * @return boolean true or false dependiendo si la Actividad cumple las condiciones para poder editar sus Correlativas.
	 */
	function es_actividad_editable($entidad)
	{
		return toba::consulta_php('co_elementos')->es_optativa_de_generica_sin_mesas_comisiones($entidad, $this->s__plan_version);
	}
	
	/*
	 * Se redefine la función cargar de la clase padre, cn_man_condiciones, para redefinir la condición de la función
	 * es_editable(), que se utiliza en la creación del árbol de condiciones y chequeos al guardar en todas las operaciones 
	 * del Módulo de Planes, dado que para el caso de Correlativas, si la Actividad seleccionada es una Optativa de Genérica, 
	 * es posible editar sus correlativas, aunque el plan esté activo, siempre que la misma no tenga mesas ni comisiones 
	 * creadas para ese Plan Versión. La condición de editabilidad no depende del estado del plan, sino de la Actividad 
	 * seleccionada para edición en esta operación.
	 */
	function cargar($params, $usar_elementos=true)
	{
		/**
		* Se verifica si ya se seleccionó la Actividad, además del Plan Versión, y si el plan versión no es editable 
		* en general (estado Nuevo para estructurados), para verificar si la Actividad en particular es editable para 
		* agregarle correlativas (en caso de que ya el plan versión sea editable, no tiene sentido pasar por este 
		* control ya que estará habilitada la asignación de correlativas para cualquier actividad).
		*/
		if (is_array($params) && array_key_exists('entidad', $params) && !$this->s__plan_version_editable) {
			// La condición de editable es o bien que el Plan Versión sea editable o que la Actividad en Particular lo sea.
			$this->s__plan_version_editable = $this->es_actividad_editable($params['entidad']);
			// Se realiza este control antes de llamar al cargar del CN Manejador padre, para pasarle el parámetro con el 
			// que se invoca al cargar del CN Entidad.
			$params['plan_version_editable'] = $this->s__plan_version_editable;
		}
		parent::cargar($params, $usar_elementos);
	}

	/**
	 * Se redefine la función resetear elemento (que se invoca en el Cancelar del ci edi plan condiciones), ya que
	 * para la operación de correlativas, debe resetearse la condición de editabilidad cada vez que se vuelve a seleccionar
	 * una nueva Actividad, ya que no sólo depende del estado del Plan, sino también de las propiedades del elemento.
	 * Al volver de la selección de actividad, la condición de editabilidad vuelve a depender sólo del estado del Plan.
	 */
	function resetear_elemento()
	{
		$this->s__plan_version_editable = planes::es_editable($this->s__plan_version);
		/* Se volverá a consultar el estado del plan, aunque el mismo no cambie, ya que sólo cambió la selección de 
		 * actividad pero para utilizar 2 variables separadas para plan y actividad o redefinir el chequeo que se haga 
		 * por la función es_editable() y no por la variable de sesión s__plan_version_editable, deberían modificarse 
		 * todas las funciones de carga y guardado del cn ent condiciones, reutilizado en todo el módulo de planes.
		 */
		parent::resetear_elemento();
	}
}
?>