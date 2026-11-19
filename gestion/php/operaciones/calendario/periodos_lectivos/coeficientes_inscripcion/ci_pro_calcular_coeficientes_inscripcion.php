<?php
/**
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */
class ci_pro_calcular_coeficientes_inscripcion extends ci_nav_procesos_bk
{
	public function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_calcular_coeficientes_inscripcion_nucleo::nombre;
	}
}
?>