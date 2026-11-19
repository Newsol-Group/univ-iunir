<?php 
/**
 * Representa las Correlativas sobre un elemento del plan
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_ent_correlativas extends cn_ent_condiciones
{
	function cargar($id) 
	{
		toba::logger()->info("################ Cargar condiciones ENTIDAD: {$id['entidad']} ##################");
		$this->resetear();
		$this->s__plan_version = $id['plan_version'];
		$this->s__entidad = $id['entidad'];
		$this->s__plan_version_editable = $id['plan_version_editable']; 
		$clave = array( 'plan_version'  => 	$this->s__plan_version,
						'entidad'		=> 	$this->s__entidad
						);
		parent::cargar($clave);
	}

	function modo_correlativas()
	{
		return true;
	}
}

?>