<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class cn_man_escalas_notas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'escalas_notas';
	}

	function get_clase_consulta()
	{
		return 'co_escalas_notas';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_escalas_notas')->get_listado($filtro);
	}
	
	//--------------------------------------------------------------------------
	//---- Copia de escala de notas
	//--------------------------------------------------------------------------

	function copiar_escala($escala_nota)
	{
		$nueva_escala = guarani::act('act_escalas_notas')->copiar_escala($escala_nota);
		return current(current($nueva_escala));
	}
}
?>