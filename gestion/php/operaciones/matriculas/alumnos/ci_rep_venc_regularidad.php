<?php
/**
 *
 *
 * @package 	Matriculas
 * @subpackage 	Alumnos
 * 
 */
class ci_rep_venc_regularidad extends ci_rep_sin_listado_inicial
{
	
	function get_tabla_principal()
	{
		return 'sga_actas_detalle';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado_vencimiento_regularidades($filtro);
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