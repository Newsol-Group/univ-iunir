<?php
class cn_ent_prueba_bk extends guarani_pers_cn
{

	protected $s__proceso;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_prueba::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function get_estado_proceso()
	{
		return $this->s__proceso->get_estado();
	}

	function get_resultado_proceso($reporte)
	{
		return $this->s__proceso->get_resultado($reporte);
	}



}

?>