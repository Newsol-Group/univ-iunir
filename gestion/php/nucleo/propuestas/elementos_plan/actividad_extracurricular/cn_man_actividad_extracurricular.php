<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_man_actividad_extracurricular extends cn_man_actividad
{
	protected $tipo = elemento::tipo_actividad;
	protected $subtipo = elemento::subtipo_actividad_extracurricular;
	
	function get_indice_instancia()
	{
		return 'extracurricular';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado($this->tipo, $this->subtipo, $filtro);
	}
}

?>