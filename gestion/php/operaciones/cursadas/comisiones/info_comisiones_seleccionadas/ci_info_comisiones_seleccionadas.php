<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class ci_info_comisiones_seleccionadas extends guarani_pers_ci
{
	// Comisiones seleccionadas.
	protected $s__comisiones;

	function ini()
	{
		$this->s__comisiones = toba::memoria()->get_dato('comisiones');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$where = guarani_sql::armar_condicion_in($this->s__comisiones, 'sga_comisiones', 'comision');
		$datos = guarani::co('co_comisiones')->get_listado($where);
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Extensión Javascript ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}
}
?>