<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_pro_deteccion_egresados extends cn_procesos_bk 
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_deteccion_egresados::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
	
	function etiquetas($concepto)
	{
		if (! isset($this->etiquetas[$concepto])) {
			$this->etiquetas[$concepto] = guarani_etiquetas::get_concepto($concepto);
		}
		return $this->etiquetas[$concepto];
	}
}
?>