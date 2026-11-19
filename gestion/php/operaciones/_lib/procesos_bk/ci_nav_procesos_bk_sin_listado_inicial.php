<?php
abstract class ci_nav_procesos_bk_sin_listado_inicial extends ci_nav_procesos_bk
{
	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}
}
?>