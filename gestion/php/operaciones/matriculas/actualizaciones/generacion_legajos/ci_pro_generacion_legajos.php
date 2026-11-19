<?php
class ci_pro_generacion_legajos extends ci_nav_procesos_bk
{
	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_generacion_legajos_nucleo::nombre;
	}
}
?>