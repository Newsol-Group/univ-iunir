<?php
/**
 *
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Aprobación por Resolución
 * 
 */
class ci_rep_tramite_aprobacion_por_resolucion extends ci_rep_sin_listado_inicial
{
	protected $s__seleccion;

	function get_tabla_principal()
	{
		return 'sga_reconocimiento';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_aprobacion_por_resolucion')->get_listado($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- SELECCION 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_tramite_aprobacion_seleccionado($seleccion);
		$this->set_pantalla('pant_impresion');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_tramite_aprobacion_seleccionado()
	{
		return current($this->s__seleccion);
	}

	function set_tramite_aprobacion_seleccionado($tramite)
	{
		$this->s__seleccion = $tramite;
	}

}
?>