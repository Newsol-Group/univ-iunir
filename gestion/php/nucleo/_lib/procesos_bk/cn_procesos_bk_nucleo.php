<?php
abstract class cn_procesos_bk_nucleo extends guarani_pers_cn 
{
	protected $s__proceso;

	function get_estado_proceso()
	{
		return $this->s__proceso->get_estado();
	}

	function get_resultado_proceso($reporte)
	{
		return $this->s__proceso->get_resultado($reporte);
	}
	
	function abortar_proceso()
	{
		$this->s__proceso->detener_procesamiento();
	}
	
	function get_mensaje_error($separador="\n")
	{
		return implode($separador, aplanar_matriz($this->errores, 'mensaje'));
	}
	
	abstract function ejecutar_proceso($datos);
}
?>