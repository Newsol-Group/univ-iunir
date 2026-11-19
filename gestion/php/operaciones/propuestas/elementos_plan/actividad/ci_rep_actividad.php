<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class ci_rep_actividad extends ci_reporte
{
	protected $tipo = elemento::tipo_actividad;
	protected $subtipo = null;

	function get_tabla_principal()
	{
		return 'sga_elementos';
	}

	/**
	 * Recupera las actividades y las materias genericas
	 * 
	 * @param $filtro
	 * @return Array() Actividades y Materias Génericas
	 */
	function get_datos($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado($this->tipo, $this->subtipo, $filtro);
	}
	
}

?>