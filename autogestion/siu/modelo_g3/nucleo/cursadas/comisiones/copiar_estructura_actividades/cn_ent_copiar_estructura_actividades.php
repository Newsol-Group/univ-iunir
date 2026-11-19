<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_ent_copiar_estructura_actividades extends cn_procesos_bk
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_copiar_estructura_actividades::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

}
?>