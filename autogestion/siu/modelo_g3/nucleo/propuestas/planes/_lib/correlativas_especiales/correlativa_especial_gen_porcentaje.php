<?php
class correlativa_especial_gen_porcentaje extends correlativa_especial
{
	private $cur_apr;
	private $año_desde;
	private $año_hasta;
	private $modulo_revision;
	private $porcentaje;
	
	function evaluar_requisito($plan_version, $legajo_alumno)
	{
		//Esto en realidad tiene que lanzar un error de toba.
		$msg = "Datos Insuficientes. No se pudo evaluar.";
		if ($plan_version && $legajo_alumno)
		{
			
			if ($this->año_desde && $this->año_hasta)
			{
				//Obtener el porcentaje de aprobadas/cursadas para un plan_version y legajo_alumno con un año desde y año hasta
			}
			elseif ($this->modulo_revision)
			{
				//Obtener el porcentaje de aprobadas/cursadas para un plan_version , legajo_alumno y un modulo_revision
			}
			else
			{
				//Obtener el porcentaje de aprobadas/cursadas para un plan_version y legajo_alumno
			}
			if ($porc_alcanzado >= $this->porcentaje) 
			{
				$this->observaciones = "Requisito cumplido. se obtuvo un $porc_alcanzado% de actividades ".(($this->cur_apr=='A')?'Aprobadas':'Cursadas').". El mínimo requerido era ".$this->porcentaje."%.";
				return 1;
			}
			$msg = "Requisito no cumplido. Se necesitaba como mínimo".$this->porcentaje."%. de activiades ".(($this->cur_apr=='A')?'Aprobadas':'Cursadas')." y sólo se obtuvo un $porc_alcanzado%.";
		}
		$this->observaciones = $msg;
		return -1;
	}
	
	function set_parametros($cur_apr='A', $año_desde=null, $año_hasta=null, $modulo_revision=null, $porcentaje = 100)
	{
		$this->cur_apr = $cur_apr;
		$this->año_desde = $año_desde;
		$this->año_hasta = $año_hasta;
		$this->modulo_revision = $modulo_revision;
		$this->porcentaje = $porcentaje;
	}
}
?>