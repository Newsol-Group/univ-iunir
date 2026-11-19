<?php
/**
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 */
class cn_pro_procesar_matrices_equivalencia extends cn_procesos_bk
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_procesar_matrices_equivalencia::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
}
?>