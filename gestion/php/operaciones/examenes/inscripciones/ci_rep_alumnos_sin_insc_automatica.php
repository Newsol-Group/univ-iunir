<?php
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 * 
 */
class ci_rep_alumnos_sin_insc_automatica extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_examenes')->get_listado_rep_alumnos_sin_insc_auto_examen($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
}
?>