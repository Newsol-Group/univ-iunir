<?php
namespace siu\modelo\transacciones;

use siu\modelo\entidades\persona;
use siu\modelo\controles\controles;

use siu\guarani;

abstract class transaccion_alumno extends transaccion
{
	protected $propuesta;
	protected $alumno;
	protected $plan;
	protected $plan_version;
	protected $ubicacion;
	protected $modalidad;
	protected $division;
	
	function __construct($persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion, $modalidad=null, $division=null) 
	{
		$this->propuesta = $propuesta;
		$this->alumno = $alumno;
		$this->plan = $plan;
		$this->plan_version = $plan_version;
		$this->ubicacion = $ubicacion;
		if (isset($modalidad)) {
			$this->modalidad = $modalidad;
		}
		if (isset($division)) {
			$this->division = $division;
		}
		
		parent::__construct($persona);
	}
}

?>