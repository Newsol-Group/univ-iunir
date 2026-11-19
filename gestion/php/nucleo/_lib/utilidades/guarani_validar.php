<?php

/**
 * Validación de datos
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_validar
{
	static function validar_ereg($ereg,$dato)
	{
		return (preg_match("/($ereg)/i", $dato, $regs) && (in_array($dato,$regs))) ;
	}
	
	static function es_entero($dato)
	{
		return static::validar_ereg(guarani_eregs::entero,$dato) ;
	}

	static function es_decimal($dato)
	{
		return static::validar_ereg(guarani_eregs::decimal,$dato) ;
	}

	static function es_fecha($dato)
	{
		return static::validar_ereg(guarani_eregs::fecha,$dato) ;
	}
	
	static function es_hora_minutos($dato)
	{
		return static::validar_ereg(guarani_eregs::hora_minutos,$dato) ;
	}
	
	static function es_fecha_hora_minutos($dato)
	{
		return static::validar_ereg(guarani_eregs::fecha_hora_minutos,$dato) ;
	}

	static function esta_entre($dato,$piso,$tope)
	{
		return ( $dato < $piso ? false: $dato <= $tope);
	}

	static function longitud($dato, $min = false, $max = false){
		$l = strlen($dato);
		return (false === $min || $l >= $min) && (false === $max || $l <= $max);
	}

}

?>