<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Planes
 */
class ev_plan_cabecera
{
	
	protected $plan_version = null;
	protected $datos_plan = null;
	protected $elemento = array();
	
	function __construct($plan_version, $datos_plan)
	{
		$this->plan_version = $plan_version;
		$this->datos_plan = $datos_plan;
	}
	
	function set_elemento($elmento)
	{
		$this->elemento = $elemento;
	}

	function generar_layout()
	{
		$this->datos_plan['propuesta_nombre'] = guarani_texto::acortar_texto($this->datos_plan['propuesta_nombre'], 39);
		$this->datos_plan['plan_nombre'] = guarani_texto::acortar_texto($this->datos_plan['plan_nombre'], 25);
		echo '<table class="tabla-1" align="center" width="60%" border="0">';
		echo '<tr>';
		echo '<td class="zona-barra-superior-info-top" colspan="2">';
		echo '<strong>('.$this->datos_plan['propuesta_codigo'].')&nbsp;'.$this->datos_plan['propuesta_nombre'].'</strong>';
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td class="zona-barra-superior-info-bottom" colspan="1" align="left">'.'Plan: '.' <strong>('.$this->datos_plan['plan_codigo'].") ".$this->datos_plan['plan_nombre'].'&nbsp;&nbsp;(&nbsp;<i>'.$this->datos_plan['plan_estado_descripcion'].'</i>&nbsp;)</strong>';
		echo '</td>';
		echo '<td class="zona-barra-superior-info-bottom" colspan="1" align="left">'.'Versi&oacute;n: <strong>'.$this->datos_plan['version'].'&nbsp;&nbsp;'.'(&nbsp;<i>'.$this->datos_plan['version_estado_descripcion'].'</i>&nbsp)</strong>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}
	
}

?>