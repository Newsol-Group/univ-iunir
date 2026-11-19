<?php
/**
 * @package Centrales
 * Esta es la fábrica abstracta de Guaraní3 
 */
interface fabrica
{
	static function catalogo_comunes();
	
	static function catalogo_elementos_plan();
	
	static function catalogo_personas();

	static function proceso_bk($proceso);
	
	static function db();
	
	static function parametros();
	
	static function catalogo_g3entidades();
	
	static function complemento($id_complemento);
	
}

?>