<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_man_actividad_materia extends cn_man_actividad
{
	protected $tipo = elemento::tipo_actividad;
	protected $subtipo = elemento::subtipo_materia;
	
	function get_indice_instancia()
	{
		return 'materia';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado($this->tipo, $this->subtipo, $filtro);
	}
	
}

?>