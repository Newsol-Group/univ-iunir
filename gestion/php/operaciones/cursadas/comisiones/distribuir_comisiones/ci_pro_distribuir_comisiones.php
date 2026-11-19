<?php
class ci_pro_distribuir_comisiones extends ci_nav_procesos_bk
{
	public function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_distribuir_comisiones::nombre;
	}

}
?>