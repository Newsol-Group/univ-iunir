<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */
class cn_man_cambiar_masivamente_plan_alumnos extends cn_procesos_bk
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_cambiar_masivamente_plan_alumnos::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
}
?>