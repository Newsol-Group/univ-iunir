<?php
/**
 *
 *
 * @package		Propuestas Formativas
 * @subpackage 	Escalas de Notas
 * 
 */
class cn_man_conceptos_notas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'concepto_nota';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_conceptos_notas')->get_listado($filtro);
	}
}
?>