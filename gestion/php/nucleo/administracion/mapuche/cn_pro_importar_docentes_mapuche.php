<?php
/**
 *
 * @package		Administración
 * @subpackage 	Mapuche
 */
class cn_pro_importar_docentes_mapuche extends cn_procesos_bk
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_importar_docentes_mapuche::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
}
?>
