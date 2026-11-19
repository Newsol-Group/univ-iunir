<?php
/**
 *
 * @package Encuestas
 */
class ci_detalle_encuestas_comision extends guarani_pers_ci
{
	protected $s__comision;
	protected $ev_info;

	function ini()
	{
		$parametro_comision = toba::memoria()->get_parametro('comision');
		if (!empty($parametro_comision)) {
			$this->s__comision = $parametro_comision;
		}
		$this->ev_info()->set_datos(array('nombre' => toba::consulta_php('co_comisiones')->get_nombre_comision($this->s__comision)));
	}

	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('60px');
			$this->ev_info->set_columnas_a_mostrar(array('nombre'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
		}
		return $this->ev_info;
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__comision) && $this->s__comision != apex_ef_no_seteado) {
			$detalle_encuestas = toba::consulta_php('co_encuestas')->get_encuestas_x_comision($this->s__comision);
			$cuadro->set_datos($detalle_encuestas);
		}
	}

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