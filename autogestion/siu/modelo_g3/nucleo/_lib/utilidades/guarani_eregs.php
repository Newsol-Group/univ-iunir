<?php

/**
 * Expresiones regulares
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_eregs
{
	const entero = '-{0,1}[0-9]+';
	const decimal = '-{0,1}[0-9]*[.]{0,1}[0-9]+';
	const hora_minutos = '(2{1}[0-3]{1}|[0-1]{0,1}[0-9]{1}):[0-5]{0,1}[0-9]{1}';
	const fecha = '([1-2]{1}[0-9]{3})-([0-1]{0,1}[0-9]{1})-([0-3]{0,1}[0-9]{1})';
	const fecha_hora_minutos = '[1-2]{1}[0-9]{3})[-/]{1}([0-1]{0,1}[0-9]{1})[-/]{1}([0-3]{0,1}[0-9]{1} (2{1}[0-3]{1}|[0-1]{0,1}[0-9]{1}):[0-5]{0,1}[0-9]{1}';
	
}

?>