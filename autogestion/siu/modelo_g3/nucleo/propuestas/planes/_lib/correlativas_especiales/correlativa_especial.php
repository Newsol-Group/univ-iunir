<?php
abstract class correlativa_especial 
{
	protected $observaciones;
	
	abstract function evaluar_requisito($plan_version, $legajo_alumno);
	
	function get_observaciones()
	{
		return $this->observaciones;
	}
}
?>