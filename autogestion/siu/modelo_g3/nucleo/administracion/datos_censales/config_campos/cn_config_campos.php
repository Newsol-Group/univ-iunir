<?php
class cn_config_campos extends guarani_pers_cn
{	
	function get_datos($indice_inicial=null, $indice_final=null)
	{
		return toba::consulta_php('co_datos_censales')->get_datos_config($indice_inicial, $indice_final);
	}
	
	function set_datos($datos)
	{
		guarani::act('act_personas')->actualizar_datos_censales_config($datos);
	}
}
?>