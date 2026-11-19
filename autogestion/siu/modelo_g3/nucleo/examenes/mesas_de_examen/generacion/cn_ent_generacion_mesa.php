<?php 
/**
 *
 *
 * @package Examenes
 * @subpackage Generación Masiva de Mesas de Examen
 */

class cn_ent_generacion_mesa extends cn_procesos_bk
{
	//-----------------------------------------------------------------------------------
	//---- Funciones de manejo del proceso background -----------------------------------
	//-----------------------------------------------------------------------------------
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_generacion_mesas::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
}

?>