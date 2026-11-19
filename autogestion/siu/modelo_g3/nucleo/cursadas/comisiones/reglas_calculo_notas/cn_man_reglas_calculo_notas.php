<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class cn_man_reglas_calculo_notas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'regla_calculo_notas';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_reglas_calculo')->get_listado($filtro);			
	}
}
?>