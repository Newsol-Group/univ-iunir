<?php                  
/**
 * @package Propuestas
 * @subpackage Plan
 */                       
interface i_elemento_plan
{                      
	function get_id();
	function get_elemento_gui();

}

interface i_elemento_plan_modulo
{
	
}

interface i_elemento_plan_actividad
{
	function es_tipo_hijo_valido($tipo);
	
	
}



?>