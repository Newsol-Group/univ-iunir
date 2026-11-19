<?php
/**
 * @package Componentes
 * @subpackage Filtro
 */
class guarani_filtro_condicion_negativa extends guarani_filtro_condicion
{
	function get_sql($campo, $valor)
	{
		$valor = toba::db()->quote($this->pre.trim($valor).$this->post);
		return '('.$campo.$this->casting_izq.' '.$this->operador_sql.' '.
			$this->pre_evaluacion.$valor.$this->post_evaluacion.$this->casting_der. ' OR '. $campo.' IS NULL)';
	}	
	
	function get_sql_sensitivo($expresion, $valor, $cs=false, $as=false)
	{
		$operador = $this->sensibilizar_operador($this->operador_sql,$cs);
		if ($this->filtro()->con_sensitividad_tildes() && ! $as) {
			$valor = guarani_texto::limpiar_acentos($valor);
			$expresion = 'f_limpiar_acentos('.$expresion.$this->casting_izq.')';
		}
		$valor = toba::db()->quote($this->pre.trim($valor).$this->post);
		return '('.$expresion.' '.$operador.' '.
				$this->pre_evaluacion.$valor.$this->post_evaluacion.$this->casting_der. ' OR '. $expresion.' IS NULL)';
	}
	
}
?>
