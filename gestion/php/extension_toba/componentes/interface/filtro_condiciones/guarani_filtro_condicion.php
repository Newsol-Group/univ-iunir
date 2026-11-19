<?php

/**
 * @package Componentes
 * @subpackage Filtro
 */
class guarani_filtro_condicion extends toba_filtro_condicion
{
	protected $columna;

	function __construct($columna, $etiqueta='', $operador_sql='', $pre='', $post='', $casting_izq='', $casting_der='')
	{
		$this->columna = $columna;
		parent::__construct($etiqueta, $operador_sql, $pre, $post, $casting_izq, $casting_der);
	}

	function filtro()
	{
		return $this->columna->controlador();
	}

	function get_sql_sensitivo($expresion, $valor, $cs=false, $as=false)
	{
		$operador = $this->sensibilizar_operador($this->operador_sql,$cs);
		if ($this->filtro()->con_sensitividad_tildes() && ! $as) {
			$valor = guarani_texto::limpiar_acentos($valor);
			$expresion = 'f_limpiar_acentos('.$expresion.$this->casting_izq.')';
		}
		$valor = toba::db()->quote($this->pre.trim($valor).$this->post);
		return $expresion.' '.$operador.' '.
						$this->pre_evaluacion.$valor.$this->post_evaluacion.$this->casting_der;
	}
	
	function sensibilizar_operador($operador,$cs=false)
	{
		if ($cs) {//Case Sensitive
			switch ($operador) {
				case 'ILIKE':
					$operador = 'LIKE';
					break;
			 	case 'NOT ILIKE':
					$operador = 'NOT LIKE';
					break;
			}
		} else {
			switch ($operador) {
				case '=':
					$operador = 'ILIKE';
					break;
			 	case '!=':
					$operador = 'NOT ILIKE';
					break;
			}
		}
		
		return $operador;
	}

}
?>