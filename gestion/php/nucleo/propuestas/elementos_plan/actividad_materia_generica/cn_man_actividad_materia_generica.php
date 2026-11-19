<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_man_actividad_materia_generica extends cn_man_modulo
{
	protected $tipo = elemento::tipo_modulo;
	protected $subtipo = elemento::subtipo_materia_generica;
	
	function get_indice_instancia()
	{
		return 'materia_generica';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado($this->tipo, $this->subtipo, $filtro);
	}
}

?>